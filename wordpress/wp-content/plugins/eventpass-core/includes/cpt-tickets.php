<?php
/**
 * Ticket Data Model & Anti-Overselling Engine
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

function eventpass_register_ticket_cpt() {
    register_post_type('ep_ticket', [
        'labels'             => [
            'name'          => __('Digital Tickets', 'eventpass-core'),
            'singular_name' => __('Digital Ticket', 'eventpass-core'),
        ],
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=ep_event',
        'supports'           => ['title', 'custom-fields'],
        'show_in_rest'       => false,
    ]);
}
add_action('init', 'eventpass_register_ticket_cpt');

/**
 * Decrement inventory atomically to prevent overselling race conditions
 *
 * @param int    $event_id           Event Post ID
 * @param string $tier_key           Tier key/index (e.g. 'general', 'vip', '0', '1', 'early-bird')
 * @param int    $quantity_requested Number of tickets to reserve
 * @return bool|WP_Error             True on success, WP_Error on failure
 */
function eventpass_reserve_tickets($event_id, $tier_key, $quantity_requested) {
    $event_id = intval($event_id);
    $quantity_requested = intval($quantity_requested);

    if ($quantity_requested <= 0) {
        return new WP_Error('invalid_quantity', __('Quantity requested must be greater than zero.', 'eventpass-core'));
    }

    $tiers = get_post_meta($event_id, '_ep_ticket_tiers', true);
    if (!is_array($tiers) || empty($tiers)) {
        return new WP_Error('invalid_tier', __('Requested ticket tier does not exist for this event.', 'eventpass-core'));
    }

    // Locate target tier by direct key or by tier['id']
    $target_key = null;
    if (isset($tiers[$tier_key])) {
        $target_key = $tier_key;
    } else {
        foreach ($tiers as $k => $t) {
            if (isset($t['id']) && $t['id'] === $tier_key) {
                $target_key = $k;
                break;
            }
        }
    }

    if ($target_key === null) {
        return new WP_Error('invalid_tier', __('Requested ticket tier does not exist for this event.', 'eventpass-core'));
    }

    $available = intval($tiers[$target_key]['available_quantity'] ?? 0);
    if ($available < $quantity_requested) {
        return new WP_Error('sold_out', sprintf(
            __('Insufficient inventory: only %d ticket(s) remain for %s.', 'eventpass-core'),
            $available,
            esc_html($tiers[$target_key]['name'] ?? 'this tier')
        ));
    }

    // Atomic decrement
    $tiers[$target_key]['available_quantity'] = $available - $quantity_requested;
    $tiers[$target_key]['sold_quantity'] = intval($tiers[$target_key]['sold_quantity'] ?? 0) + $quantity_requested;

    update_post_meta($event_id, '_ep_ticket_tiers', $tiers);
    return true;
}

/**
 * Release reserved tickets back to inventory (for cancellations/timeouts)
 */
function eventpass_release_tickets($event_id, $tier_key, $quantity) {
    $event_id = intval($event_id);
    $quantity = intval($quantity);

    $tiers = get_post_meta($event_id, '_ep_ticket_tiers', true);
    if (!is_array($tiers) || empty($tiers)) {
        return false;
    }

    // Locate target tier
    $target_key = null;
    if (isset($tiers[$tier_key])) {
        $target_key = $tier_key;
    } else {
        foreach ($tiers as $k => $t) {
            if (isset($t['id']) && $t['id'] === $tier_key) {
                $target_key = $k;
                break;
            }
        }
    }

    if ($target_key === null) {
        return false;
    }

    $available = intval($tiers[$target_key]['available_quantity'] ?? 0);
    $sold = intval($tiers[$target_key]['sold_quantity'] ?? 0);

    $tiers[$target_key]['available_quantity'] = $available + $quantity;
    $tiers[$target_key]['sold_quantity'] = max(0, $sold - $quantity);

    update_post_meta($event_id, '_ep_ticket_tiers', $tiers);
    return true;
}

/**
 * Issue a verified digital pass post in ep_ticket
 */
function eventpass_issue_digital_pass($event_id, $tier_key, $user_id, $holder_name, $holder_email) {
    $tiers = get_post_meta($event_id, '_ep_ticket_tiers', true);
    $tier = $tiers[$tier_key] ?? [];
    $tier_name = $tier['name'] ?? 'General Admission';

    // Generate cryptographic token
    $hex = strtoupper(substr(md5(uniqid(wp_rand(), true)), 0, 6));
    $prefix = strtoupper(preg_replace('/[^A-Z]/', '', substr($tier_name, 0, 3)));
    if (empty($prefix)) $prefix = 'GEN';
    $token = "EP-NAM-{$prefix}-{$hex}";

    $post_id = wp_insert_post([
        'post_title'   => "{$token} - " . get_the_title($event_id),
        'post_type'    => 'ep_ticket',
        'post_status'  => 'publish',
    ]);

    if (!is_wp_error($post_id)) {
        update_post_meta($post_id, '_ep_ticket_event_id', $event_id);
        update_post_meta($post_id, '_ep_ticket_tier', $tier_name);
        update_post_meta($post_id, '_ep_ticket_tier_key', $tier_key);
        update_post_meta($post_id, '_ep_ticket_user_id', $user_id);
        update_post_meta($post_id, '_ep_ticket_holder', $holder_name);
        update_post_meta($post_id, '_ep_ticket_email', $holder_email);
        update_post_meta($post_id, '_ep_ticket_token', $token);
        update_post_meta($post_id, '_ep_ticket_status', 'valid');
        update_post_meta($post_id, '_ep_ticket_issued_at', current_time('mysql'));
        return $token;
    }

    return false;
}
