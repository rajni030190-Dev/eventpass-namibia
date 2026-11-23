<?php
/**
 * Platform Commission & Organiser Payout Engine
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register ep_payout Custom Post Type
 */
function eventpass_register_payout_cpt() {
    $labels = [
        'name'               => __('Payouts', 'eventpass-core'),
        'singular_name'      => __('Payout', 'eventpass-core'),
        'menu_name'          => __('Organiser Payouts', 'eventpass-core'),
        'name_admin_bar'     => __('Payout Request', 'eventpass-core'),
        'add_new'            => __('New Payout', 'eventpass-core'),
        'add_new_item'       => __('Add New Payout Request', 'eventpass-core'),
        'edit_item'          => __('Edit Payout Request', 'eventpass-core'),
        'view_item'          => __('View Payout', 'eventpass-core'),
        'all_items'          => __('All Payouts', 'eventpass-core'),
        'search_items'       => __('Search Payouts', 'eventpass-core'),
    ];

    register_post_type('ep_payout', [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=ep_event',
        'supports'           => ['title', 'custom-fields'],
        'show_in_rest'       => false,
    ]);
}
add_action('init', 'eventpass_register_payout_cpt');

/**
 * Custom Columns for ep_payout
 */
function eventpass_ep_payout_columns($columns) {
    return [
        'cb'             => $columns['cb'],
        'payout_ref'     => __('Payout Ref', 'eventpass-core'),
        'organiser'      => __('Organiser', 'eventpass-core'),
        'amount'         => __('Amount (NAD)', 'eventpass-core'),
        'bank_details'   => __('Namibian Bank Account', 'eventpass-core'),
        'payout_status'  => __('Status', 'eventpass-core'),
        'eft_ref'        => __('EFT Trace Ref', 'eventpass-core'),
        'date'           => __('Date Requested', 'eventpass-core'),
    ];
}
add_filter('manage_ep_payout_posts_columns', 'eventpass_ep_payout_columns');

function eventpass_ep_payout_custom_column($column, $post_id) {
    switch ($column) {
        case 'payout_ref':
            $ref = get_post_meta($post_id, '_ep_payout_reference', true) ?: ('PO-' . $post_id);
            echo '<strong>' . esc_html($ref) . '</strong>';
            break;

        case 'organiser':
            $name  = get_post_meta($post_id, '_ep_payout_organiser_name', true) ?: 'Organiser';
            $email = get_post_meta($post_id, '_ep_payout_organiser_email', true);
            echo '<strong>' . esc_html($name) . '</strong><br><small style="color:#64748B;">' . esc_html($email) . '</small>';
            break;

        case 'amount':
            $amt = floatval(get_post_meta($post_id, '_ep_payout_amount', true));
            echo '<strong style="color:#0B1B3D; font-size:1.05rem;">N$ ' . number_format($amt, 2) . '</strong>';
            break;

        case 'bank_details':
            $bank = get_post_meta($post_id, '_ep_payout_bank_name', true) ?: 'Bank Windhoek';
            $acc  = get_post_meta($post_id, '_ep_payout_account_number', true) ?: '••••';
            $code = get_post_meta($post_id, '_ep_payout_branch_code', true) ?: '';
            echo '<strong>' . esc_html($bank) . '</strong><br><small>Acc: ' . esc_html($acc) . ' (' . esc_html($code) . ')</small>';
            break;

        case 'payout_status':
            $status = get_post_meta($post_id, '_ep_payout_status', true) ?: 'pending';
            if ($status === 'processed') {
                echo '<span style="background:#ECFDF5; color:#065F46; padding:3px 9px; border-radius:999px; font-weight:700; font-size:11px;">PAID / REMITTED</span>';
            } elseif ($status === 'approved') {
                echo '<span style="background:#EFF6FF; color:#1D4ED8; padding:3px 9px; border-radius:999px; font-weight:700; font-size:11px;">APPROVED FOR EFT</span>';
            } elseif ($status === 'rejected') {
                echo '<span style="background:#FEF2F2; color:#991B1B; padding:3px 9px; border-radius:999px; font-weight:700; font-size:11px;">REJECTED</span>';
            } else {
                echo '<span style="background:#FFFBEB; color:#92400E; padding:3px 9px; border-radius:999px; font-weight:700; font-size:11px;">PENDING REVIEW</span>';
            }
            break;

        case 'eft_ref':
            $eft = get_post_meta($post_id, '_ep_payout_eft_reference', true);
            if ($eft) {
                echo '<code>' . esc_html($eft) . '</code>';
            } else {
                echo '<span style="color:#94A3B8;">Awaiting Transfer</span>';
            }
            break;
    }
}
add_action('manage_ep_payout_posts_custom_column', 'eventpass_ep_payout_custom_column', 10, 2);

/**
 * Platform Commission Engine
 */
class EventPass_Commission_Engine {

    /**
     * Get platform commission percentage (Default 7.5% per SOW)
     */
    public static function get_platform_commission_rate($event_id = null) {
        $custom_rate = $event_id ? get_post_meta($event_id, '_ep_custom_commission_rate', true) : null;
        if ($custom_rate !== '' && is_numeric($custom_rate)) {
            return floatval($custom_rate);
        }
        return floatval(get_option('ep_default_commission_rate', 7.5));
    }

    /**
     * Calculate financial breakdown for a gross revenue figure
     */
    public static function calculate_event_ledger($gross_revenue, $event_id = null) {
        $rate = self::get_platform_commission_rate($event_id);
        $commission_amount = round(($gross_revenue * ($rate / 100)), 2);
        $net_payout = $gross_revenue - $commission_amount;

        return [
            'gross_revenue'     => $gross_revenue,
            'commission_rate'   => $rate,
            'commission_amount' => $commission_amount,
            'net_payout'        => $net_payout,
            'currency'          => 'NAD',
        ];
    }
}

/**
 * Organiser Payout & Financial Ledger Engine
 */
class EventPass_Payout_Engine {

    /**
     * Supported Namibian Banking Institutions
     */
    public static function get_namibian_banks() {
        return [
            'bank_windhoek' => [
                'name'        => 'Bank Windhoek',
                'branch_code' => '483-872',
            ],
            'fnb_namibia' => [
                'name'        => 'First National Bank (FNB) Namibia',
                'branch_code' => '282-672',
            ],
            'standard_bank' => [
                'name'        => 'Standard Bank Namibia',
                'branch_code' => '082-772',
            ],
            'nedbank' => [
                'name'        => 'Nedbank Namibia',
                'branch_code' => '461-609',
            ],
        ];
    }

    /**
     * Calculate complete financial ledger and balances for an organiser (or platform-wide)
     *
     * @param int $organiser_id (0 for global platform figures)
     * @return array
     */
    public static function get_financial_ledger($organiser_id = 0) {
        $event_ids = [];

        if ($organiser_id > 0) {
            $user_events = get_posts([
                'post_type'      => 'ep_event',
                'post_status'    => ['publish', 'pending', 'draft'],
                'author'         => $organiser_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            $event_ids = $user_events;
        }

        // 1. Calculate Gross Ticket Sales and Orders from Completed ep_order posts
        $order_args = [
            'post_type'      => 'ep_order',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ];

        if ($organiser_id > 0 && !empty($event_ids)) {
            $order_args['meta_query'] = [
                [
                    'key'     => '_ep_order_event_id',
                    'value'   => $event_ids,
                    'compare' => 'IN',
                ]
            ];
        } elseif ($organiser_id > 0 && empty($event_ids)) {
            // Organiser has no events
            $order_args['post__in'] = [0];
        }

        $orders = get_posts($order_args);

        $gross_sales         = 0.0;
        $total_commission    = 0.0;
        $net_earnings        = 0.0;
        $total_tickets_sold  = 0;

        foreach ($orders as $ord) {
            $status = get_post_meta($ord->ID, '_ep_order_payment_status', true);
            if ($status !== 'completed') continue;

            $total = floatval(get_post_meta($ord->ID, '_ep_order_total', true));
            $comm  = floatval(get_post_meta($ord->ID, '_ep_order_commission_amount', true));
            $net   = floatval(get_post_meta($ord->ID, '_ep_order_net_payout', true));
            if ($comm <= 0 && $total > 0) {
                $comm = round($total * 0.075, 2);
                $net  = $total - $comm;
            }

            $items = get_post_meta($ord->ID, '_ep_order_items', true);
            if (is_array($items)) {
                foreach ($items as $it) {
                    $total_tickets_sold += intval($it['quantity'] ?? 0);
                }
            }

            $gross_sales      += $total;
            $total_commission += $comm;
            $net_earnings     += $net;
        }

        // Also add direct tier counts from events if no orders recorded yet
        if ($gross_sales === 0.0 && !empty($event_ids)) {
            foreach ($event_ids as $eid) {
                $tiers = get_post_meta($eid, '_ep_ticket_tiers', true);
                if (is_array($tiers)) {
                    foreach ($tiers as $tr) {
                        $sold = intval($tr['sold_quantity'] ?? 0);
                        $price = floatval($tr['price'] ?? 0);
                        if ($sold > 0) {
                            $line = $sold * $price;
                            $c = round($line * 0.075, 2);
                            $gross_sales += $line;
                            $total_commission += $c;
                            $net_earnings += ($line - $c);
                            $total_tickets_sold += $sold;
                        }
                    }
                }
            }
        }

        // 2. Query Organiser Payouts
        $payout_args = [
            'post_type'      => 'ep_payout',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        ];
        if ($organiser_id > 0) {
            $payout_args['meta_query'] = [
                [
                    'key'     => '_ep_payout_user_id',
                    'value'   => $organiser_id,
                    'compare' => '=',
                ]
            ];
        }

        $payout_posts = get_posts($payout_args);

        $paid_payouts    = 0.0;
        $pending_payouts = 0.0;
        $payout_history  = [];

        foreach ($payout_posts as $po) {
            $p_amt    = floatval(get_post_meta($po->ID, '_ep_payout_amount', true));
            $p_status = get_post_meta($po->ID, '_ep_payout_status', true) ?: 'pending';
            $p_ref    = get_post_meta($po->ID, '_ep_payout_reference', true) ?: ('PO-' . $po->ID);
            $p_eft    = get_post_meta($po->ID, '_ep_payout_eft_reference', true) ?: '';
            $p_bank   = get_post_meta($po->ID, '_ep_payout_bank_name', true) ?: 'Bank Windhoek';
            $p_acc    = get_post_meta($po->ID, '_ep_payout_account_number', true) ?: '';

            if ($p_status === 'processed') {
                $paid_payouts += $p_amt;
            } elseif ($p_status === 'pending' || $p_status === 'approved') {
                $pending_payouts += $p_amt;
            }

            $payout_history[] = [
                'id'           => $po->ID,
                'reference'    => $p_ref,
                'amount'       => $p_amt,
                'status'       => $p_status,
                'bank_name'    => $p_bank,
                'account_num'  => $p_acc,
                'eft_ref'      => $p_eft,
                'created_at'   => get_the_date('Y-m-d H:i', $po->ID),
            ];
        }

        $available_balance = max(0.0, $net_earnings - $paid_payouts - $pending_payouts);

        return [
            'gross_sales'        => round($gross_sales, 2),
            'total_commission'   => round($total_commission, 2),
            'net_earnings'       => round($net_earnings, 2),
            'paid_payouts'       => round($paid_payouts, 2),
            'pending_payouts'    => round($pending_payouts, 2),
            'available_balance'  => round($available_balance, 2),
            'total_tickets_sold' => $total_tickets_sold,
            'payout_history'     => $payout_history,
        ];
    }

    /**
     * Submit an organiser payout request
     */
    public static function request_payout($organiser_id, $amount, $bank_info = []) {
        $organiser_id = intval($organiser_id);
        $amount       = round(floatval($amount), 2);

        if ($organiser_id <= 0) {
            return new WP_Error('invalid_user', __('Invalid organiser account.', 'eventpass-core'));
        }

        if ($amount <= 0) {
            return new WP_Error('invalid_amount', __('Payout amount must be greater than zero.', 'eventpass-core'));
        }

        $ledger = self::get_financial_ledger($organiser_id);
        if ($amount > $ledger['available_balance']) {
            return new WP_Error('insufficient_funds', sprintf(
                __('Requested amount (N$ %s) exceeds your net available balance (N$ %s).', 'eventpass-core'),
                number_format($amount, 2),
                number_format($ledger['available_balance'], 2)
            ));
        }

        $user = get_userdata($organiser_id);
        $user_name = $user ? ($user->display_name ?: $user->user_login) : 'Organiser';
        $user_email = $user ? $user->user_email : '';

        // Extract banking details
        $bank_name   = sanitize_text_field($bank_info['bank_name'] ?? get_user_meta($organiser_id, '_ep_bank_name', true) ?: 'Bank Windhoek');
        $acc_name    = sanitize_text_field($bank_info['account_name'] ?? get_user_meta($organiser_id, '_ep_bank_acc_name', true) ?: $user_name);
        $acc_num     = sanitize_text_field($bank_info['account_number'] ?? get_user_meta($organiser_id, '_ep_bank_acc_num', true) ?: '');
        $acc_type    = sanitize_text_field($bank_info['account_type'] ?? get_user_meta($organiser_id, '_ep_bank_acc_type', true) ?: 'Cheque / Current');
        $branch_code = sanitize_text_field($bank_info['branch_code'] ?? get_user_meta($organiser_id, '_ep_bank_branch_code', true) ?: '483-872');

        if (empty($acc_num)) {
            return new WP_Error('missing_bank', __('Valid Namibian bank account number is required for EFT remittance.', 'eventpass-core'));
        }

        // Persist banking profile for future payouts
        update_user_meta($organiser_id, '_ep_bank_name', $bank_name);
        update_user_meta($organiser_id, '_ep_bank_acc_name', $acc_name);
        update_user_meta($organiser_id, '_ep_bank_acc_num', $acc_num);
        update_user_meta($organiser_id, '_ep_bank_acc_type', $acc_type);
        update_user_meta($organiser_id, '_ep_bank_branch_code', $branch_code);

        // Create ep_payout post
        $hex = strtoupper(substr(md5(uniqid(wp_rand(), true)), 0, 6));
        $ref = "PO-NAM-2026-{$hex}";

        $payout_id = wp_insert_post([
            'post_title'   => sprintf('%s - %s (N$ %s)', $ref, $user_name, number_format($amount, 2)),
            'post_type'    => 'ep_payout',
            'post_status'  => 'pending',
        ]);

        if (!$payout_id || is_wp_error($payout_id)) {
            return new WP_Error('db_error', __('Failed to create payout record in database.', 'eventpass-core'));
        }

        update_post_meta($payout_id, '_ep_payout_reference', $ref);
        update_post_meta($payout_id, '_ep_payout_user_id', $organiser_id);
        update_post_meta($payout_id, '_ep_payout_organiser_name', $user_name);
        update_post_meta($payout_id, '_ep_payout_organiser_email', $user_email);
        update_post_meta($payout_id, '_ep_payout_amount', $amount);
        update_post_meta($payout_id, '_ep_payout_bank_name', $bank_name);
        update_post_meta($payout_id, '_ep_payout_account_name', $acc_name);
        update_post_meta($payout_id, '_ep_payout_account_number', $acc_num);
        update_post_meta($payout_id, '_ep_payout_account_type', $acc_type);
        update_post_meta($payout_id, '_ep_payout_branch_code', $branch_code);
        update_post_meta($payout_id, '_ep_payout_status', 'pending');
        update_post_meta($payout_id, '_ep_payout_requested_at', current_time('mysql'));

        return [
            'payout_id' => $payout_id,
            'reference' => $ref,
            'amount'    => $amount,
            'bank'      => $bank_name,
            'status'    => 'pending'
        ];
    }

    /**
     * Process & mark payout as transferred via EFT
     */
    public static function process_payout($payout_id, $eft_reference = '', $admin_note = '') {
        $payout_id = intval($payout_id);
        $post = get_post($payout_id);
        if (!$post || $post->post_type !== 'ep_payout') {
            return new WP_Error('invalid_payout', __('Payout record not found.', 'eventpass-core'));
        }

        if (empty($eft_reference)) {
            $eft_reference = 'EFT-NAM-' . strtoupper(substr(md5(uniqid(wp_rand(), true)), 0, 8));
        }

        wp_update_post([
            'ID'          => $payout_id,
            'post_status' => 'publish',
        ]);

        update_post_meta($payout_id, '_ep_payout_status', 'processed');
        update_post_meta($payout_id, '_ep_payout_eft_reference', $eft_reference);
        update_post_meta($payout_id, '_ep_payout_admin_note', sanitize_text_field($admin_note));
        update_post_meta($payout_id, '_ep_payout_processed_at', current_time('mysql'));

        if (class_exists('EventPass_Notification_Engine')) {
            EventPass_Notification_Engine::notify_payout_status_changed($payout_id, 'processed');
        }

        return true;
    }

    /**
     * Reject payout request
     */
    public static function reject_payout($payout_id, $reason = '') {
        $payout_id = intval($payout_id);
        $post = get_post($payout_id);
        if (!$post || $post->post_type !== 'ep_payout') {
            return false;
        }

        wp_update_post([
            'ID'          => $payout_id,
            'post_status' => 'draft',
        ]);

        update_post_meta($payout_id, '_ep_payout_status', 'rejected');
        update_post_meta($payout_id, '_ep_payout_reject_reason', sanitize_text_field($reason));
        update_post_meta($payout_id, '_ep_payout_rejected_at', current_time('mysql'));

        return true;
    }

    /**
     * Stream CSV Sales Export
     */
    public static function stream_sales_csv($organiser_id = 0) {
        if (!is_user_logged_in()) {
            wp_die('Unauthorized: You must be logged in to export sales reports.', 'Access Denied', ['response' => 401]);
        }

        $current_user = wp_get_current_user();
        $is_admin = current_user_can('manage_options');
        $is_organiser = in_array('event_organiser', (array)$current_user->roles) || in_array('ep_organiser', (array)$current_user->roles) || current_user_can('view_event_sales');

        // RBAC: Only Platform Administrators and Accredited Organisers may export sales
        if (!$is_admin && !$is_organiser) {
            wp_die('Unauthorized: You do not have permission to export event sales records.', 'Access Denied', ['response' => 403]);
        }

        if (!$is_admin) {
            $organiser_id = $current_user->ID;
        }

        $event_ids = [];
        if ($organiser_id > 0) {
            $event_ids = get_posts([
                'post_type'      => 'ep_event',
                'post_status'    => ['publish', 'pending', 'draft'],
                'author'         => $organiser_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);

            // Strict Data Isolation: If an organiser has 0 events, immediately exit with empty CSV header
            if (empty($event_ids)) {
                $filename = 'EventPass_Namibia_Sales_Report_' . date('Y-m-d_His') . '.csv';
                if (!headers_sent()) {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename=' . $filename);
                }
                $output = fopen('php://output', 'w');
                fputcsv($output, [
                    'Order Number',
                    'Date',
                    'Event Title',
                    'Customer Name',
                    'Customer Email',
                    'Customer Phone',
                    'Tier Name',
                    'Quantity',
                    'Unit Price (NAD)',
                    'Gross Total (NAD)',
                    'Platform Fee (NAD)',
                    'Net Organiser (NAD)',
                    'Payment Gateway',
                    'Status'
                ]);
                fclose($output);
                exit;
            }
        }

        $order_args = [
            'post_type'      => 'ep_order',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];

        // Strict Data Isolation: Scoped to organiser's events only
        if ($organiser_id > 0 && !empty($event_ids)) {
            $order_args['meta_query'] = [
                [
                    'key'     => '_ep_order_event_id',
                    'value'   => $event_ids,
                    'compare' => 'IN'
                ]
            ];
        }

        $orders = get_posts($order_args);

        $filename = 'EventPass_Namibia_Sales_Report_' . date('Y-m-d_His') . '.csv';
        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
        }

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Order Number',
            'Date',
            'Event Title',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Tier Name',
            'Quantity',
            'Unit Price (NAD)',
            'Gross Total (NAD)',
            'Platform Fee (NAD)',
            'Net Organiser (NAD)',
            'Payment Gateway',
            'Status'
        ]);

        foreach ($orders as $ord) {
            $order_num = get_post_meta($ord->ID, '_ep_order_number', true);
            $date      = get_the_date('Y-m-d H:i', $ord->ID);
            $ev_id     = get_post_meta($ord->ID, '_ep_order_event_id', true);
            $ev_title  = get_the_title($ev_id);
            $c_name    = get_post_meta($ord->ID, '_ep_order_customer_name', true);
            $c_email   = get_post_meta($ord->ID, '_ep_order_customer_email', true);
            $c_phone   = get_post_meta($ord->ID, '_ep_order_customer_phone', true);
            $method    = get_post_meta($ord->ID, '_ep_order_payment_method', true);
            $status    = get_post_meta($ord->ID, '_ep_order_payment_status', true);
            $items     = get_post_meta($ord->ID, '_ep_order_items', true);

            if (is_array($items)) {
                foreach ($items as $it) {
                    $qty   = intval($it['quantity'] ?? 1);
                    $price = floatval($it['unit_price'] ?? 0);
                    $line  = $qty * $price;
                    $comm  = round($line * 0.075, 2);
                    $net   = $line - $comm;

                    fputcsv($output, [
                        $order_num,
                        $date,
                        $ev_title,
                        $c_name,
                        $c_email,
                        $c_phone,
                        $it['tier_name'] ?? 'General',
                        $qty,
                        number_format($price, 2),
                        number_format($line, 2),
                        number_format($comm, 2),
                        number_format($net, 2),
                        strtoupper($method),
                        strtoupper($status)
                    ]);
                }
            }
        }

        fclose($output);
        exit;
    }
}

// Hook CSV export action
add_action('admin_post_ep_export_sales_csv', function () {
    EventPass_Payout_Engine::stream_sales_csv();
});
add_action('admin_post_nopriv_ep_export_sales_csv', function () {
    wp_die('Unauthorized: You must be logged in as an accredited organiser or administrator to export sales reports.', 'Access Denied', ['response' => 401]);
});
add_action('wp_ajax_ep_export_sales_csv', function () {
    EventPass_Payout_Engine::stream_sales_csv();
});
