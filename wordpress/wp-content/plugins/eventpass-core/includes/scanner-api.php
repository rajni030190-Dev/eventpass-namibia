<?php
/**
 * Event Gate Check-In & Scanner API Engine
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RBAC permission callback for Gate Scanner & Attendee Roster endpoints (SOW Section 9).
 */
function eventpass_scanner_permission_check(WP_REST_Request $request) {
    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', __('Authentication required for Gate Scanner access.', 'eventpass-core'), ['status' => 401]);
    }
    if (current_user_can('manage_options')) {
        return true;
    }
    $user = wp_get_current_user();
    $is_organiser = in_array('event_organiser', (array)$user->roles, true)
        || in_array('ep_organiser', (array)$user->roles, true)
        || current_user_can('edit_ep_events');
    $org_status = get_user_meta($user->ID, '_ep_organiser_status', true);

    if (!$is_organiser || in_array($org_status, ['pending', 'rejected', 'suspended'], true)) {
        return new WP_Error('rest_forbidden', __('Approved Organiser or Admin permissions required.', 'eventpass-core'), ['status' => 403]);
    }

    $event_id = intval($request->get_param('event_id'));
    if ($event_id > 0) {
        $author_id = intval(get_post_field('post_author', $event_id));
        if ($author_id !== $user->ID) {
            return new WP_Error('rest_forbidden', __('You can only manage gate check-in for your own events.', 'eventpass-core'), ['status' => 403]);
        }
    }
    return true;
}

add_action('rest_api_init', function () {
    // Verification Endpoint
    register_rest_route('eventpass/v1', '/scan', [
        'methods'             => ['POST', 'GET'],
        'callback'            => 'eventpass_rest_handle_scan',
        'permission_callback' => 'eventpass_scanner_permission_check',
    ]);

    // Live Event Gate Statistics Endpoint
    register_rest_route('eventpass/v1', '/gate-stats', [
        'methods'             => ['GET', 'POST'],
        'callback'            => 'eventpass_rest_handle_gate_stats',
        'permission_callback' => 'eventpass_scanner_permission_check',
    ]);

    // Live Event Attendee Roster Endpoint (Sprint 3)
    register_rest_route('eventpass/v1', '/event-attendees', [
        'methods'             => ['GET'],
        'callback'            => 'eventpass_rest_handle_event_attendees',
        'permission_callback' => 'eventpass_scanner_permission_check',
    ]);
});

/**
 * Handle Live Attendee Roster Request
 */
function eventpass_rest_handle_event_attendees(WP_REST_Request $request) {
    $event_id = intval($request->get_param('event_id'));
    if ($event_id <= 0) {
        return new WP_REST_Response(['error' => 'Missing event_id', 'attendees' => []], 400);
    }

    $tickets = get_posts([
        'post_type'      => 'ep_ticket',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_ep_ticket_event_id',
                'value'   => $event_id,
                'compare' => '='
            ],
            [
                'key'     => '_ep_event_id',
                'value'   => $event_id,
                'compare' => '='
            ]
        ],
        'orderby'        => 'date',
        'order'          => 'DESC'
    ]);

    $attendees = [];
    foreach ($tickets as $t) {
        $token      = get_post_meta($t->ID, '_ep_ticket_token', true);
        $holder     = get_post_meta($t->ID, '_ep_ticket_holder', true) ?: get_post_meta($t->ID, '_ep_holder_name', true) ?: 'Attendee';
        $email      = get_post_meta($t->ID, '_ep_ticket_email', true);
        $phone      = get_post_meta($t->ID, '_ep_ticket_phone', true);
        $tier       = get_post_meta($t->ID, '_ep_ticket_tier', true) ?: get_post_meta($t->ID, '_ep_tier', true) ?: 'General';
        $status     = get_post_meta($t->ID, '_ep_ticket_status', true) ?: get_post_meta($t->ID, '_ep_status', true) ?: 'valid';
        $scanned_at = get_post_meta($t->ID, '_ep_ticket_scanned_at', true) ?: get_post_meta($t->ID, '_ep_scanned_at', true);
        $order_id   = get_post_meta($t->ID, '_ep_ticket_order_id', true) ?: get_post_meta($t->ID, '_ep_order_id', true);

        $attendees[] = [
            'ticket_id'  => $t->ID,
            'token'      => $token,
            'holder'     => $holder,
            'email'      => $email,
            'phone'      => $phone,
            'tier'       => $tier,
            'status'     => $status,
            'scanned_at' => $scanned_at,
            'order_id'   => $order_id,
            'issued_at'  => get_the_date('Y-m-d H:i', $t->ID)
        ];
    }

    return new WP_REST_Response([
        'event_id'  => $event_id,
        'count'     => count($attendees),
        'attendees' => $attendees
    ], 200);
}

/**
 * Handle Live Ticket Scan Request
 */
function eventpass_rest_handle_scan(WP_REST_Request $request) {
    $code     = strtoupper(sanitize_text_field(trim($request->get_param('code'))));
    $event_id = intval($request->get_param('event_id'));

    return new WP_REST_Response(eventpass_validate_ticket_scan($code, $event_id), 200);
}

/**
 * Core Ticket Verification Logic
 */
function eventpass_validate_ticket_scan($code, $event_id = 0) {
    if (!$code) {
        return [
            'status'   => 'error',
            'result'   => 'invalid',
            'title'    => 'EMPTY CODE',
            'message'  => 'Missing ticket reference code.',
            'severity' => 'danger'
        ];
    }

    // Query ticket post by token
    $tickets = get_posts([
        'post_type'   => 'ep_ticket',
        'meta_query'  => [
            'relation' => 'OR',
            [
                'key'     => '_ep_ticket_token',
                'value'   => $code,
                'compare' => '=',
            ],
            [
                'key'     => '_ep_token',
                'value'   => $code,
                'compare' => '=',
            ]
        ],
        'post_status' => 'publish',
        'numberposts' => 1,
    ]);

    if (empty($tickets)) {
        return [
            'result'   => 'invalid',
            'title'    => 'INVALID TICKET',
            'message'  => sprintf('Code %s was not found in EventPass database.', esc_html($code)),
            'severity' => 'danger'
        ];
    }

    $ticket = $tickets[0];
    $ticket_id = $ticket->ID;

    // Check Event Association
    $ticket_event_id = intval(get_post_meta($ticket_id, '_ep_ticket_event_id', true) ?: get_post_meta($ticket_id, '_ep_event_id', true));
    if ($event_id > 0 && $ticket_event_id > 0 && $ticket_event_id !== $event_id) {
        $actual_event_title = get_the_title($ticket_event_id) ?: "Event #{$ticket_event_id}";
        return [
            'result'   => 'wrong_event',
            'title'    => 'WRONG EVENT ACCESS',
            'message'  => sprintf('This pass is registered for "%s", not the selected gate event.', esc_html($actual_event_title)),
            'severity' => 'warning'
        ];
    }

    $status = get_post_meta($ticket_id, '_ep_ticket_status', true) ?: (get_post_meta($ticket_id, '_ep_status', true) ?: 'valid');
    $holder = get_post_meta($ticket_id, '_ep_ticket_holder', true) ?: (get_post_meta($ticket_id, '_ep_holder_name', true) ?: 'Guest');
    $tier   = get_post_meta($ticket_id, '_ep_ticket_tier', true) ?: (get_post_meta($ticket_id, '_ep_tier', true) ?: 'Standard');

    if ($status === 'already_used') {
        $scanned_at = get_post_meta($ticket_id, '_ep_ticket_scanned_at', true) ?: get_post_meta($ticket_id, '_ep_scanned_at', true);
        return [
            'result'     => 'already_used',
            'title'      => 'ALREADY USED (DUPLICATE)',
            'holder'     => $holder,
            'tier'       => $tier,
            'message'    => sprintf('Ticket for %s (%s) was already checked in at %s. DENY ENTRY.', $holder, $tier, $scanned_at ?: 'earlier today'),
            'severity'   => 'warning',
            'scanned_at' => $scanned_at
        ];
    }

    if ($status === 'cancelled' || $status === 'refunded') {
        return [
            'result'   => 'cancelled',
            'title'    => 'TICKET CANCELLED / REFUNDED',
            'holder'   => $holder,
            'tier'     => $tier,
            'message'  => sprintf('This pass for %s was voided or refunded. Not valid for admission.', $holder),
            'severity' => 'danger'
        ];
    }

    if ($status === 'valid') {
        // Approve admission & transition status
        $time_str = current_time('mysql');
        update_post_meta($ticket_id, '_ep_ticket_status', 'already_used');
        update_post_meta($ticket_id, '_ep_status', 'already_used');
        update_post_meta($ticket_id, '_ep_ticket_scanned_at', $time_str);
        update_post_meta($ticket_id, '_ep_scanned_at', $time_str);

        return [
            'result'      => 'valid',
            'title'       => 'ENTRY APPROVED',
            'holder'      => $holder,
            'tier'        => $tier,
            'message'     => sprintf('Welcome %s! Ticket validated successfully.', $holder),
            'severity'    => 'success',
            'approved_at' => $time_str
        ];
    }

    return [
        'result'  => 'unknown',
        'message' => 'Unrecognized ticket state.'
    ];
}

/**
 * Handle Live Gate Attendance Statistics
 */
function eventpass_rest_handle_gate_stats(WP_REST_Request $request) {
    $event_id = intval($request->get_param('event_id'));
    return new WP_REST_Response(eventpass_get_gate_attendance_stats($event_id), 200);
}

/**
 * Calculate gate statistics for an event
 */
function eventpass_get_gate_attendance_stats($event_id = 0) {
    $meta_query = [];
    if ($event_id > 0) {
        $meta_query = [
            'relation' => 'OR',
            [
                'key'     => '_ep_ticket_event_id',
                'value'   => $event_id,
                'compare' => '=',
            ],
            [
                'key'     => '_ep_event_id',
                'value'   => $event_id,
                'compare' => '=',
            ]
        ];
    }

    $all_tickets = get_posts([
        'post_type'      => 'ep_ticket',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => $meta_query
    ]);

    $total_sold = count($all_tickets);
    $checked_in = 0;

    foreach ($all_tickets as $t) {
        $st = get_post_meta($t->ID, '_ep_ticket_status', true) ?: get_post_meta($t->ID, '_ep_status', true);
        if ($st === 'already_used') {
            $checked_in++;
        }
    }

    $remaining = max(0, $total_sold - $checked_in);

    return [
        'event_id'   => $event_id,
        'total_sold' => $total_sold,
        'checked_in' => $checked_in,
        'remaining'  => $remaining,
    ];
}

// Authenticated AJAX Fallback Hook
add_action('wp_ajax_eventpass_scan_ticket', function () {
    if (!current_user_can('manage_options') && !current_user_can('edit_ep_events')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }
    $code     = sanitize_text_field($_REQUEST['code'] ?? '');
    $event_id = intval($_REQUEST['event_id'] ?? 0);
    wp_send_json(eventpass_validate_ticket_scan($code, $event_id));
});
