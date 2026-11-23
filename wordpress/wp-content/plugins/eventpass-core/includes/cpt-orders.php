<?php
/**
 * Event Orders & Direct Booking Engine
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register ep_order Custom Post Type
 */
function eventpass_register_order_cpt() {
    $labels = [
        'name'               => __('Orders', 'eventpass-core'),
        'singular_name'      => __('Order', 'eventpass-core'),
        'menu_name'          => __('Bookings & Orders', 'eventpass-core'),
        'name_admin_bar'     => __('Event Order', 'eventpass-core'),
        'add_new'            => __('New Order', 'eventpass-core'),
        'add_new_item'       => __('Add New Order', 'eventpass-core'),
        'new_item'           => __('New Order', 'eventpass-core'),
        'edit_item'          => __('Edit Order', 'eventpass-core'),
        'view_item'          => __('View Order', 'eventpass-core'),
        'all_items'          => __('All Orders', 'eventpass-core'),
        'search_items'       => __('Search Orders', 'eventpass-core'),
    ];

    register_post_type('ep_order', [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=ep_event',
        'supports'           => ['title', 'custom-fields'],
        'show_in_rest'       => false,
    ]);
}
add_action('init', 'eventpass_register_order_cpt');

/**
 * Custom Columns for ep_order in WordPress Admin
 */
function eventpass_ep_order_columns($columns) {
    $new_columns = [
        'cb'              => $columns['cb'],
        'order_number'    => __('Order #', 'eventpass-core'),
        'order_customer'  => __('Customer', 'eventpass-core'),
        'order_event'     => __('Event', 'eventpass-core'),
        'order_total'     => __('Total (NAD)', 'eventpass-core'),
        'order_payment'   => __('Payment Status', 'eventpass-core'),
        'order_tickets'   => __('Tickets Issued', 'eventpass-core'),
        'date'            => __('Date', 'eventpass-core'),
    ];
    return $new_columns;
}
add_filter('manage_ep_order_posts_columns', 'eventpass_ep_order_columns');

function eventpass_ep_order_custom_column($column, $post_id) {
    switch ($column) {
        case 'order_number':
            $num = get_post_meta($post_id, '_ep_order_number', true) ?: ('EP-' . $post_id);
            echo '<strong>' . esc_html($num) . '</strong>';
            break;

        case 'order_customer':
            $name  = get_post_meta($post_id, '_ep_order_customer_name', true) ?: 'Guest';
            $email = get_post_meta($post_id, '_ep_order_customer_email', true);
            echo '<strong>' . esc_html($name) . '</strong><br><small style="color:#64748B;">' . esc_html($email) . '</small>';
            break;

        case 'order_event':
            $event_id = get_post_meta($post_id, '_ep_order_event_id', true);
            if ($event_id && ($event = get_post($event_id))) {
                echo '<a href="' . esc_url(get_edit_post_link($event_id)) . '"><strong>' . esc_html($event->post_title) . '</strong></a>';
            } else {
                echo '<span style="color:#94A3B8;">Event #' . intval($event_id) . '</span>';
            }
            break;

        case 'order_total':
            $total = get_post_meta($post_id, '_ep_order_total', true);
            echo '<strong style="color:#0B1B3D;">N$ ' . number_format((float)$total, 2) . '</strong>';
            break;

        case 'order_payment':
            $status = get_post_meta($post_id, '_ep_order_payment_status', true) ?: 'pending';
            $method = get_post_meta($post_id, '_ep_order_payment_method', true) ?: 'finatic';
            if ($status === 'completed') {
                echo '<span style="background:#ECFDF5; color:#065F46; padding:2px 8px; border-radius:999px; font-weight:700; font-size:11px;">PAID (' . esc_html(strtoupper($method)) . ')</span>';
            } elseif ($status === 'cancelled') {
                echo '<span style="background:#FEF2F2; color:#991B1B; padding:2px 8px; border-radius:999px; font-weight:700; font-size:11px;">CANCELLED</span>';
            } else {
                echo '<span style="background:#FFFBEB; color:#92400E; padding:2px 8px; border-radius:999px; font-weight:700; font-size:11px;">PENDING</span>';
            }
            break;

        case 'order_tickets':
            $tickets = get_post_meta($post_id, '_ep_order_tickets', true);
            $count = is_array($tickets) ? count($tickets) : 0;
            echo '<strong>' . intval($count) . ' pass(es)</strong>';
            break;
    }
}
add_action('manage_ep_order_posts_custom_column', 'eventpass_ep_order_custom_column', 10, 2);

/**
 * Create a new booking order and reserve inventory atomically
 *
 * @param array $args
 * @return int|WP_Error Order post ID on success
 */
function eventpass_create_order($args) {
    $event_id       = intval($args['event_id'] ?? 0);
    $items          = $args['items'] ?? []; // Array of ['tier_id' => ..., 'qty' => ...]
    $customer_name  = sanitize_text_field(trim($args['customer_name'] ?? ''));
    $customer_email = sanitize_email(trim($args['customer_email'] ?? ''));
    $customer_phone = sanitize_text_field(trim($args['customer_phone'] ?? ''));
    $user_id        = intval($args['user_id'] ?? get_current_user_id());
    $payment_method = sanitize_key($args['payment_method'] ?? 'finatic');

    if (!$event_id || !get_post($event_id)) {
        return new WP_Error('invalid_event', __('Invalid event ID provided for booking.', 'eventpass-core'));
    }

    if (empty($customer_name) || empty($customer_email)) {
        return new WP_Error('missing_contact', __('Customer full name and email are required.', 'eventpass-core'));
    }

    if (empty($items) || !is_array($items)) {
        return new WP_Error('empty_cart', __('No ticket tiers selected for checkout.', 'eventpass-core'));
    }

    // Retrieve active tiers for pricing and validation
    $db_tiers = eventpass_get_event_tiers($event_id);
    $tiers_by_id = [];
    foreach ($db_tiers as $t) {
        $t_id = $t['id'] ?? '';
        if ($t_id) $tiers_by_id[$t_id] = $t;
    }

    $processed_items = [];
    $subtotal = 0.0;
    $reserved_tracking = [];

    // 1. Validate availability and reserve atomically
    foreach ($items as $req_tier_id => $req_qty) {
        $qty = intval($req_qty);
        if ($qty <= 0) continue;

        if (!isset($tiers_by_id[$req_tier_id])) {
            // Roll back any already reserved tiers
            foreach ($reserved_tracking as $r_id => $r_qty) {
                eventpass_release_tickets($event_id, $r_id, $r_qty);
            }
            return new WP_Error('invalid_tier', sprintf(__('Requested ticket tier "%s" does not exist.', 'eventpass-core'), esc_html($req_tier_id)));
        }

        $tier_info = $tiers_by_id[$req_tier_id];
        $res = eventpass_reserve_tickets($event_id, $req_tier_id, $qty);
        if (is_wp_error($res)) {
            // Roll back previous reservations
            foreach ($reserved_tracking as $r_id => $r_qty) {
                eventpass_release_tickets($event_id, $r_id, $r_qty);
            }
            return $res;
        }

        $reserved_tracking[$req_tier_id] = $qty;
        $unit_price = floatval($tier_info['price'] ?? 0);
        $line_total = $unit_price * $qty;
        $subtotal += $line_total;

        $processed_items[] = [
            'tier_id'    => $req_tier_id,
            'tier_name'  => $tier_info['name'] ?? 'General Admission',
            'unit_price' => $unit_price,
            'quantity'   => $qty,
            'total'      => $line_total,
        ];
    }

    if (empty($processed_items)) {
        return new WP_Error('zero_quantity', __('Total ticket quantity must be at least 1.', 'eventpass-core'));
    }

    // 2. Financial Calculations
    $vat_rate = 0.15; // 15% Namibian Statutory VAT (inclusive)
    $vat_amount = round($subtotal * ($vat_rate / (1 + $vat_rate)), 2);
    $total_amount = $subtotal;

    // Commission Engine
    $comm_rate = class_exists('EventPass_Commission_Engine') ? EventPass_Commission_Engine::get_platform_commission_rate($event_id) : 7.5;
    $comm_amount = round($subtotal * ($comm_rate / 100), 2);
    $net_payout = $subtotal - $comm_amount;

    // 3. Create Order Record
    $order_num = 'EP-2026-' . wp_rand(1000, 9999);
    $order_id = wp_insert_post([
        'post_title'   => sprintf('Order #%s - %s', $order_num, $customer_name),
        'post_type'    => 'ep_order',
        'post_status'  => 'pending',
    ]);

    if (!$order_id || is_wp_error($order_id)) {
        // Rollback reservations
        foreach ($reserved_tracking as $r_id => $r_qty) {
            eventpass_release_tickets($event_id, $r_id, $r_qty);
        }
        return new WP_Error('order_creation_failed', __('Failed to create order record in database.', 'eventpass-core'));
    }

    // 4. Save Order Metadata
    update_post_meta($order_id, '_ep_order_number', $order_num);
    update_post_meta($order_id, '_ep_order_event_id', $event_id);
    update_post_meta($order_id, '_ep_order_user_id', $user_id);
    update_post_meta($order_id, '_ep_order_customer_name', $customer_name);
    update_post_meta($order_id, '_ep_order_customer_email', $customer_email);
    update_post_meta($order_id, '_ep_order_customer_phone', $customer_phone);
    update_post_meta($order_id, '_ep_order_items', $processed_items);
    update_post_meta($order_id, '_ep_order_subtotal', $subtotal);
    update_post_meta($order_id, '_ep_order_vat', $vat_amount);
    update_post_meta($order_id, '_ep_order_total', $total_amount);
    update_post_meta($order_id, '_ep_order_payment_method', $payment_method);
    update_post_meta($order_id, '_ep_order_payment_status', 'pending');
    update_post_meta($order_id, '_ep_order_commission_rate', $comm_rate);
    update_post_meta($order_id, '_ep_order_commission_amount', $comm_amount);
    update_post_meta($order_id, '_ep_order_net_payout', $net_payout);
    update_post_meta($order_id, '_ep_order_created_at', current_time('mysql'));

    return $order_id;
}

/**
 * Process payment and issue digital tickets upon verification
 *
 * @param int    $order_id
 * @param string $payment_method
 * @param array  $gateway_payload
 * @return array|WP_Error Array with order data and issued ticket tokens
 */
function eventpass_complete_order($order_id, $payment_method = 'finatic', $gateway_payload = []) {
    $order_id = intval($order_id);
    $order = get_post($order_id);
    if (!$order || $order->post_type !== 'ep_order') {
        return new WP_Error('invalid_order', __('Order record not found.', 'eventpass-core'));
    }

    $current_status = get_post_meta($order_id, '_ep_order_payment_status', true);
    if ($current_status === 'completed') {
        return [
            'order_id'     => $order_id,
            'order_number' => get_post_meta($order_id, '_ep_order_number', true),
            'tickets'      => get_post_meta($order_id, '_ep_order_tickets', true),
            'status'       => 'already_completed'
        ];
    }

    $event_id       = intval(get_post_meta($order_id, '_ep_order_event_id', true));
    $user_id        = intval(get_post_meta($order_id, '_ep_order_user_id', true));
    $customer_name  = get_post_meta($order_id, '_ep_order_customer_name', true);
    $customer_email = get_post_meta($order_id, '_ep_order_customer_email', true);
    $customer_phone = get_post_meta($order_id, '_ep_order_customer_phone', true);
    $items          = get_post_meta($order_id, '_ep_order_items', true);

    // Generate cryptographic transaction reference
    $txn_hash = strtoupper(substr(md5(uniqid(wp_rand(), true)), 0, 8));
    $txn_id = "TXN-EP-NAM-{$txn_hash}";

    // Update order status
    wp_update_post([
        'ID'          => $order_id,
        'post_status' => 'publish',
    ]);
    update_post_meta($order_id, '_ep_order_payment_status', 'completed');
    update_post_meta($order_id, '_ep_order_payment_method', $payment_method);
    update_post_meta($order_id, '_ep_order_txn_id', $txn_id);
    update_post_meta($order_id, '_ep_order_paid_at', current_time('mysql'));

    // Issue authentic digital passes
    $issued_tickets = [];
    if (is_array($items)) {
        foreach ($items as $item) {
            $tier_id   = $item['tier_id'] ?? 'general';
            $tier_name = $item['tier_name'] ?? 'General Admission';
            $unit_price = floatval($item['unit_price'] ?? 0);
            $qty       = intval($item['quantity'] ?? 1);

            for ($i = 0; $i < $qty; $i++) {
                // Generate token
                $hex = strtoupper(substr(md5(uniqid(wp_rand(), true)), 0, 6));
                $prefix = strtoupper(preg_replace('/[^A-Z]/', '', substr($tier_name, 0, 3)));
                if (empty($prefix)) $prefix = 'GEN';
                $token = "EP-NAM-{$prefix}-{$hex}";

                $ticket_post_id = wp_insert_post([
                    'post_title'   => "{$token} - " . get_the_title($event_id),
                    'post_type'    => 'ep_ticket',
                    'post_status'  => 'publish',
                ]);

                if ($ticket_post_id && !is_wp_error($ticket_post_id)) {
                    update_post_meta($ticket_post_id, '_ep_ticket_order_id', $order_id);
                    update_post_meta($ticket_post_id, '_ep_order_id', $order_id);
                    update_post_meta($ticket_post_id, '_ep_ticket_event_id', $event_id);
                    update_post_meta($ticket_post_id, '_ep_event_id', $event_id);
                    update_post_meta($ticket_post_id, '_ep_ticket_tier', $tier_name);
                    update_post_meta($ticket_post_id, '_ep_tier', $tier_name);
                    update_post_meta($ticket_post_id, '_ep_ticket_tier_key', $tier_id);
                    update_post_meta($ticket_post_id, '_ep_ticket_price', $unit_price);
                    update_post_meta($ticket_post_id, '_ep_ticket_user_id', $user_id);
                    update_post_meta($ticket_post_id, '_ep_ticket_holder', $customer_name);
                    update_post_meta($ticket_post_id, '_ep_holder_name', $customer_name);
                    update_post_meta($ticket_post_id, '_ep_ticket_email', $customer_email);
                    update_post_meta($ticket_post_id, '_ep_ticket_phone', $customer_phone);
                    update_post_meta($ticket_post_id, '_ep_ticket_token', $token);
                    update_post_meta($ticket_post_id, '_ep_ticket_status', 'valid');
                    update_post_meta($ticket_post_id, '_ep_status', 'valid');
                    update_post_meta($ticket_post_id, '_ep_ticket_issued_at', current_time('mysql'));

                    $issued_tickets[] = [
                        'ticket_id' => $ticket_post_id,
                        'token'     => $token,
                        'tier'      => $tier_name,
                        'price'     => $unit_price,
                        'holder'    => $customer_name,
                    ];
                }
            }
        }
    }

    update_post_meta($order_id, '_ep_order_tickets', $issued_tickets);

    // Trigger action for email notifications and webhooks
    do_action('eventpass_order_completed', $order_id, $issued_tickets);

    return [
        'order_id'     => $order_id,
        'order_number' => get_post_meta($order_id, '_ep_order_number', true),
        'txn_id'       => $txn_id,
        'total'        => get_post_meta($order_id, '_ep_order_total', true),
        'tickets'      => $issued_tickets,
        'status'       => 'completed'
    ];
}

/**
 * Cancel order and release reserved tickets back to inventory
 */
function eventpass_cancel_order($order_id) {
    $order_id = intval($order_id);
    $order = get_post($order_id);
    if (!$order || $order->post_type !== 'ep_order') {
        return false;
    }

    $event_id = intval(get_post_meta($order_id, '_ep_order_event_id', true));
    $items = get_post_meta($order_id, '_ep_order_items', true);

    if (is_array($items)) {
        foreach ($items as $item) {
            $tier_id = $item['tier_id'] ?? '';
            $qty = intval($item['quantity'] ?? 0);
            if ($tier_id && $qty > 0) {
                eventpass_release_tickets($event_id, $tier_id, $qty);
            }
        }
    }

    wp_update_post([
        'ID'          => $order_id,
        'post_status' => 'draft',
    ]);
    update_post_meta($order_id, '_ep_order_payment_status', 'cancelled');

    return true;
}
