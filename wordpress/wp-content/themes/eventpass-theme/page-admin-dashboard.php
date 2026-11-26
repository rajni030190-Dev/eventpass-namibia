<?php
/**
 * Template Name: Admin Dashboard
 *
 * SOW Module 12: Centralized Administrator Command Hub
 *
 * @package EventPass_Namibia
 */

// 1. Strict Administrator RBAC Guard
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(home_url('/admin-dashboard'))));
    exit;
}

if (!current_user_can('manage_options')) {
    $current_u   = wp_get_current_user();
    $logout_url  = wp_logout_url(home_url('/login?redirect_to=' . urlencode(home_url('/admin-dashboard'))));
    $user_label  = esc_html($current_u->display_name ?: $current_u->user_login);
    $user_email  = esc_html($current_u->user_email);

    wp_die(
        '<div style="max-width:600px; margin:80px auto; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; text-align:center; padding:40px; background:#fff; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); border:1px solid #E2E8F0;">
            <div style="font-size:3rem; margin-bottom:1rem;">🔒</div>
            <h1 style="color:#0B1B3D; font-size:1.6rem; font-weight:800; margin-bottom:0.5rem;">Administrator Access Required</h1>
            <p style="color:#64748B; font-size:1rem; line-height:1.6; margin-bottom:0.75rem;">
                You are currently signed in as <strong>' . $user_label . '</strong> (' . $user_email . ').
            </p>
            <p style="color:#64748B; font-size:0.95rem; line-height:1.6; margin-bottom:1.75rem;">
                This portal is strictly reserved for EventPass compliance and platform administrators. To access this dashboard, please sign in with an Administrator account (e.g. username: <code>admin</code>).
            </p>
            <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
                <a href="' . esc_url($logout_url) . '" style="background:#0B1B3D; color:#fff; padding:10px 22px; border-radius:6px; text-decoration:none; font-weight:700; font-size:0.95rem;">Sign Out &amp; Log In as Admin</a>
                <a href="' . esc_url(home_url('/customer-dashboard')) . '" style="background:#00B4D8; color:#fff; padding:10px 22px; border-radius:6px; text-decoration:none; font-weight:700; font-size:0.95rem;">Return to Customer Dashboard</a>
            </div>
        </div>',
        'Administrator Access Required',
        ['response' => 403]
    );
}

$current_admin = wp_get_current_user();
$action_msg  = '';
$action_type = 'success';

// 2. Process Moderation and Administrative Actions (POST / GET with Nonce)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_admin_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_admin_nonce'], 'ep_admin_action')) {
        $action_msg  = 'Security verification failed. Session expired.';
        $action_type = 'error';
    } else {
        $admin_action = sanitize_text_field($_POST['ep_admin_action'] ?? '');

        // A. Event Approval
        if ($admin_action === 'approve_event') {
            $ev_id = intval($_POST['event_id'] ?? 0);
            if ($ev_id > 0 && get_post_type($ev_id) === 'ep_event') {
                wp_update_post([
                    'ID'          => $ev_id,
                    'post_status' => 'publish',
                ]);
                $ev_title = get_the_title($ev_id);
                $action_msg = "Event \"{$ev_title}\" has been approved and published live!";
            }
        }

        // B. Event Rejection
        elseif ($admin_action === 'reject_event') {
            $ev_id = intval($_POST['event_id'] ?? 0);
            if ($ev_id > 0 && get_post_type($ev_id) === 'ep_event') {
                wp_update_post([
                    'ID'          => $ev_id,
                    'post_status' => 'draft',
                ]);
                $ev_title = get_the_title($ev_id);
                $action_msg = "Event \"{$ev_title}\" has been returned to draft status.";
                $action_type = 'warning';
            }
        }

        // C. Organiser Application Approval
        elseif ($admin_action === 'approve_organiser') {
            $app_id = intval($_POST['app_id'] ?? 0);
            if ($app_id > 0 && get_post_type($app_id) === 'ep_organizer_app') {
                update_post_meta($app_id, '_ep_app_status', 'approved');
                wp_update_post(['ID' => $app_id, 'post_status' => 'publish']);
                
                $org_email = sanitize_email(get_post_meta($app_id, '_ep_org_email', true));
                $org_name  = sanitize_text_field(get_post_meta($app_id, '_ep_org_name', true));
                $contact   = sanitize_text_field(get_post_meta($app_id, '_ep_org_contact', true));
                $phone     = sanitize_text_field(get_post_meta($app_id, '_ep_org_phone', true));
                $town      = sanitize_text_field(get_post_meta($app_id, '_ep_org_town', true));
                $bank      = sanitize_text_field(get_post_meta($app_id, '_ep_org_bank', true));

                $assigned_user_id = 0;
                $credentials = [];

                if (!empty($org_email)) {
                    $user = get_user_by('email', $org_email);
                    if ($user) {
                        // User already exists: promote with official event_organiser role
                        $user->add_role('event_organiser');
                        $user->add_role('ep_organiser'); // legacy fallback
                        update_user_meta($user->ID, '_ep_is_organiser', 1);
                        update_user_meta($user->ID, '_ep_organiser_status', 'approved');
                        if (!empty($bank)) {
                            update_user_meta($user->ID, '_ep_bank_name', $bank);
                        }
                        $assigned_user_id = $user->ID;
                    } else {
                        // Auto-provision a new WordPress organiser user account
                        $base_user = sanitize_user(current(explode('@', $org_email)));
                        if (empty($base_user)) {
                            $base_user = sanitize_user(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $org_name)));
                        }
                        if (empty($base_user)) {
                            $base_user = 'organiser';
                        }
                        $username = $base_user;
                        $i = 1;
                        while (username_exists($username)) {
                            $username = $base_user . $i;
                            $i++;
                        }

                        $generated_pass = wp_generate_password(12, true, false);
                        $parts = explode(' ', $contact, 2);
                        $first_name = $parts[0] ?? $org_name;
                        $last_name  = $parts[1] ?? '';

                        $new_user_id = wp_insert_user([
                            'user_login'   => $username,
                            'user_email'   => $org_email,
                            'user_pass'    => $generated_pass,
                            'display_name' => $org_name ?: $contact,
                            'first_name'   => $first_name,
                            'last_name'    => $last_name,
                            'role'         => 'event_organiser',
                        ]);

                        if (!is_wp_error($new_user_id)) {
                            update_user_meta($new_user_id, 'phone_number', $phone);
                            update_user_meta($new_user_id, '_ep_is_organiser', 1);
                            update_user_meta($new_user_id, '_ep_organiser_status', 'approved');
                            update_user_meta($new_user_id, '_ep_org_name', $org_name);
                            update_user_meta($new_user_id, '_ep_org_town', $town);
                            update_user_meta($new_user_id, '_ep_bank_name', $bank);
                            update_post_meta($app_id, '_ep_provisioned_user_id', $new_user_id);
                            
                            $assigned_user_id = $new_user_id;
                            $credentials = [
                                'username'      => $username,
                                'temp_password' => $generated_pass
                            ];
                        }
                    }
                }

                if (class_exists('EventPass_Notification_Engine')) {
                    EventPass_Notification_Engine::notify_organiser_app_status_changed($app_id, 'approved', $credentials);
                }

                $action_msg = "Organiser application approved! Account provisioned with 'event_organiser' role and accreditation email dispatched.";
            }
        }

        // D. Organiser Application Rejection
        elseif ($admin_action === 'reject_organiser') {
            $app_id = intval($_POST['app_id'] ?? 0);
            if ($app_id > 0 && get_post_type($app_id) === 'ep_organizer_app') {
                update_post_meta($app_id, '_ep_app_status', 'rejected');
                wp_update_post(['ID' => $app_id, 'post_status' => 'draft']);

                if (class_exists('EventPass_Notification_Engine')) {
                    EventPass_Notification_Engine::notify_organiser_app_status_changed($app_id, 'rejected');
                }

                $action_msg = "Organiser application rejected.";
                $action_type = 'warning';
            }
        }

        // E. Payout Settlement & Remittance
        elseif ($admin_action === 'process_payout') {
            $payout_id = intval($_POST['payout_id'] ?? 0);
            $eft_ref   = sanitize_text_field($_POST['eft_reference'] ?? '');
            $note      = sanitize_text_field($_POST['admin_note'] ?? 'Disbursed via Namibian EFT');

            if (empty($eft_ref)) {
                $eft_ref = 'EFT-NA-' . strtoupper(wp_generate_password(8, false));
            }

            if (class_exists('EventPass_Payout_Engine')) {
                $res = EventPass_Payout_Engine::process_payout($payout_id, $eft_ref, $note);
                if (!is_wp_error($res)) {
                    $action_msg = "Payout marked as processed! EFT Trace Reference: {$eft_ref}. Organiser has been notified.";
                } else {
                    $action_msg = $res->get_error_message();
                    $action_type = 'error';
                }
            }
        }

        // F. Payout Rejection
        elseif ($admin_action === 'reject_payout') {
            $payout_id = intval($_POST['payout_id'] ?? 0);
            $reason    = sanitize_text_field($_POST['reject_reason'] ?? 'Banking verification mismatch');

            if (class_exists('EventPass_Payout_Engine')) {
                EventPass_Payout_Engine::reject_payout($payout_id, $reason);
                $action_msg = "Payout request rejected. Available funds restored to organiser balance.";
                $action_type = 'warning';
            }
        }

        // F. Brevo Production SMTP Diagnostic Test
        elseif ($admin_action === 'test_brevo_smtp') {
            $test_target = sanitize_email($_POST['test_email'] ?? '');
            if (!empty($test_target) && is_email($test_target)) {
                if (class_exists('EventPass_Brevo_Mailer')) {
                    $test_res = EventPass_Brevo_Mailer::send_test_email($test_target);
                    $action_msg  = $test_res['message'];
                    $action_type = $test_res['success'] ? 'success' : 'error';
                } else {
                    $action_msg  = 'Brevo Mailer class is not loaded.';
                    $action_type = 'error';
                }
            } else {
                $action_msg  = 'Please provide a valid destination email address for Brevo testing.';
                $action_type = 'error';
            }
        }

        // G. Update Brevo Production Credentials
        elseif ($admin_action === 'update_brevo_credentials') {
            $brevo_user = sanitize_text_field($_POST['brevo_user'] ?? '');
            $brevo_key  = sanitize_text_field($_POST['brevo_key'] ?? '');
            $brevo_from = sanitize_email($_POST['brevo_from'] ?? '');
            $brevo_name = sanitize_text_field($_POST['brevo_name'] ?? '');

            if (class_exists('EventPass_Brevo_Mailer')) {
                EventPass_Brevo_Mailer::save_admin_credentials($brevo_user, $brevo_key, $brevo_from, $brevo_name);
                $action_msg  = 'Brevo SMTP credentials saved successfully! Outbound emails are now configured for Brevo production delivery.';
                $action_type = 'success';
            }
        }

        // H. Update Platform Commission Rate (SOW Section 10)
        elseif ($admin_action === 'update_commission_rate') {
            $new_rate = floatval($_POST['default_commission_rate'] ?? 7.5);
            if ($new_rate < 0 || $new_rate > 50) {
                $action_msg  = 'Commission rate must be between 0% and 50%.';
                $action_type = 'error';
            } else {
                update_option('ep_default_commission_rate', round($new_rate, 2));
                $action_msg  = sprintf('Platform commission rate updated to %.2f%%.', $new_rate);
                $action_type = 'success';
            }
        }

        // I. Suspend or Reactivate Organiser Account (SOW Section 4.3 & 10)
        elseif ($admin_action === 'toggle_organiser_status') {
            $target_uid = intval($_POST['organiser_user_id'] ?? 0);
            $new_status = sanitize_key($_POST['new_org_status'] ?? 'suspended');
            if ($target_uid > 0 && in_array($new_status, ['approved', 'suspended'], true)) {
                update_user_meta($target_uid, '_ep_organiser_status', $new_status);
                $u_obj = get_userdata($target_uid);
                $u_label = $u_obj ? ($u_obj->display_name ?: $u_obj->user_login) : ('User #' . $target_uid);
                $action_msg  = ($new_status === 'suspended')
                    ? "Organiser account \"{$u_label}\" has been suspended."
                    : "Organiser account \"{$u_label}\" has been reactivated.";
                $action_type = ($new_status === 'suspended') ? 'warning' : 'success';
            }
        }

        // J. Refund / Void Order & Invalidate Tickets (SOW Section 10)
        elseif ($admin_action === 'refund_order') {
            $order_id = intval($_POST['order_id'] ?? 0);
            if ($order_id > 0 && get_post_type($order_id) === 'ep_order') {
                $gw_res = class_exists('EventPass_Finatic_Gateway')
                    ? EventPass_Finatic_Gateway::process_refund($order_id, null, 'Admin initiated refund')
                    : new WP_Error('missing_gateway', 'Payment gateway module not loaded.');

                if (is_wp_error($gw_res)) {
                    $action_msg  = 'Refund failed at payment provider: ' . $gw_res->get_error_message();
                    $action_type = 'error';
                } else {
                    update_post_meta($order_id, '_ep_order_payment_status', 'refunded');
                    update_post_meta($order_id, '_ep_order_refunded_at', current_time('mysql'));

                    // Void all issued passes for this order so gate scanner rejects them
                    $order_tickets = get_posts([
                        'post_type'      => 'ep_ticket',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'meta_query'     => [
                            'relation' => 'OR',
                            ['key' => '_ep_ticket_order_id', 'value' => $order_id],
                            ['key' => '_ep_order_id', 'value' => $order_id],
                        ],
                    ]);
                    foreach ($order_tickets as $tk) {
                        update_post_meta($tk->ID, '_ep_ticket_status', 'refunded');
                        update_post_meta($tk->ID, '_ep_status', 'refunded');
                    }
                    $ord_num = get_post_meta($order_id, '_ep_order_number', true) ?: ('ORD-' . $order_id);
                    $gw_note = ($gw_res['mode'] === 'live')
                        ? 'Live Finatic Ref: ' . $gw_res['refund_ref']
                        : 'Sandbox Ref: ' . $gw_res['refund_ref'] . ' (Test Mode)';
                    $action_msg  = sprintf('Order %s refunded (%s) and %d digital pass(es) invalidated.', $ord_num, $gw_note, count($order_tickets));
                    $action_type = 'warning';
                }
            }
        }
    }
}

// 3. Platform Metric Aggregation
$total_gross_sales = 0.00;
$total_commission  = 0.00;
$total_tickets_sold = 0;

$all_orders = get_posts([
    'post_type'      => 'ep_order',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
]);

foreach ($all_orders as $ord) {
    $ord_total = floatval(get_post_meta($ord->ID, '_ep_order_total', true));
    
    // Accurate commission aggregation (checks _ep_order_commission_amount first)
    $comm_amount = get_post_meta($ord->ID, '_ep_order_commission_amount', true);
    if ($comm_amount !== '' && is_numeric($comm_amount)) {
        $comm = floatval($comm_amount);
    } else {
        $legacy_comm = get_post_meta($ord->ID, '_ep_order_commission', true);
        if ($legacy_comm !== '' && is_numeric($legacy_comm)) {
            $comm = floatval($legacy_comm);
        } else {
            $comm = round($ord_total * 0.075, 2);
        }
    }

    // Accurate tickets sold count from issued tickets array or cart items
    $tickets_data = get_post_meta($ord->ID, '_ep_order_tickets', true);
    if (is_array($tickets_data) && !empty($tickets_data)) {
        $ticket_count = count($tickets_data);
    } else {
        $items = get_post_meta($ord->ID, '_ep_order_items', true);
        $ticket_count = 0;
        if (is_array($items)) {
            foreach ($items as $it) {
                $ticket_count += intval($it['quantity'] ?? 1);
            }
        }
        if ($ticket_count === 0) {
            $ticket_count = intval(get_post_meta($ord->ID, '_ep_order_ticket_count', true) ?: 1);
        }
    }

    $total_gross_sales  += $ord_total;
    $total_commission   += $comm;
    $total_tickets_sold += $ticket_count;
}

$published_events_count = wp_count_posts('ep_event')->publish ?? 0;
$pending_events_count   = wp_count_posts('ep_event')->pending ?? 0;

$pending_events = get_posts([
    'post_type'      => 'ep_event',
    'post_status'    => 'pending',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

$pending_organisers = get_posts([
    'post_type'      => 'ep_organizer_app',
    'post_status'    => ['pending', 'draft', 'publish'],
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => '_ep_app_status',
            'value'   => 'pending',
            'compare' => '=',
        ],
    ],
]);

$pending_payouts = get_posts([
    'post_type'      => 'ep_payout',
    'post_status'    => ['pending', 'publish', 'draft'],
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => '_ep_payout_status',
            'value'   => 'pending',
            'compare' => '=',
        ],
    ],
]);

$recent_orders = get_posts([
    'post_type'      => 'ep_order',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'orderby'        => 'date',
    'order'          => 'DESC',
]);

$notification_logs = class_exists('EventPass_Notification_Engine') ? EventPass_Notification_Engine::get_recent_logs(20) : [];
$brevo_diag        = class_exists('EventPass_Brevo_Mailer') ? EventPass_Brevo_Mailer::validate_config() : null;

get_header();
?>

<!-- Admin Dashboard Banner -->
<section style="background: linear-gradient(135deg, #060E20 0%, #0B1B3D 100%); color: #fff; padding: 3.5rem 0 2.5rem; border-bottom: 3px solid var(--ep-teal);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <div class="ep-page-pill" style="background: rgba(245,158,11,0.15); border-color: rgba(245,158,11,0.4); color: #F59E0B; margin-bottom: 0.75rem;">
                    👑 ADMINISTRATOR COMMAND CENTRE • SOW MODULE 12
                </div>
                <h1 style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 900; line-height: 1.15; margin: 0 0 0.5rem 0;">
                    Platform Governance & Administration
                </h1>
                <p style="color: #94A3B8; font-size: 1.05rem; margin: 0;">
                    Logged in as <strong><?php echo esc_html($current_admin->display_name ?: $current_admin->user_login); ?></strong> (Super Administrator) • <?php echo esc_html(date_i18n('l, j F Y')); ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="<?php echo esc_url(admin_url()); ?>" class="ep-btn ep-btn-sm" style="background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.15);" target="_blank">
                    ⚙️ WP Backend &rarr;
                </a>
                <a href="<?php echo esc_url(home_url('/gate-scanner')); ?>" class="ep-btn ep-btn-secondary ep-btn-sm" target="_blank">
                    📲 Gate Scanner &rarr;
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Admin Layout -->
<div class="container" style="padding: 3rem 1.5rem 5rem;">

    <!-- Flash Alert Notice -->
    <?php if (!empty($action_msg)) : ?>
        <div style="border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; <?php
            echo ($action_type === 'success') ? 'background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #065F46;' :
                (($action_type === 'warning') ? 'background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #92400E;' :
                'background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #991B1B;');
        ?>">
            <div style="font-weight: 700; font-size: 1rem;">
                <?php echo ($action_type === 'success') ? '✅' : (($action_type === 'warning') ? '⚠️' : '❌'); ?> <?php echo esc_html($action_msg); ?>
            </div>
            <button onclick="this.parentElement.remove()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:inherit;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Platform Metric KPI Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 3rem;">
        
        <!-- Gross Ticket Sales -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 14px; padding: 1.5rem; box-shadow: var(--ep-shadow-sm); border-top: 4px solid var(--ep-teal);">
            <div style="font-size: 0.8rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                Gross Marketplace Volume
            </div>
            <div style="font-size: 1.85rem; font-weight: 900; color: var(--ep-navy);">
                N$ <?php echo number_format($total_gross_sales, 2); ?>
            </div>
            <div style="font-size: 0.82rem; color: #10B981; margin-top: 0.4rem; font-weight: 700;">
                All platform orders settled via Finatic
            </div>
        </div>

        <!-- Platform Commissions (7.5%) -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 14px; padding: 1.5rem; box-shadow: var(--ep-shadow-sm); border-top: 4px solid #10B981;">
            <div style="font-size: 0.8rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                Platform Commission Earned
            </div>
            <div style="font-size: 1.85rem; font-weight: 900; color: #047857;">
                N$ <?php echo number_format($total_commission, 2); ?>
            </div>
            <div style="font-size: 0.82rem; color: #64748B; margin-top: 0.4rem; font-weight: 600;">
                7.5% SOW Marketplace Revenue
            </div>
        </div>

        <!-- Total Tickets Issued -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 14px; padding: 1.5rem; box-shadow: var(--ep-shadow-sm); border-top: 4px solid #6366F1;">
            <div style="font-size: 0.8rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                Total Passes Issued
            </div>
            <div style="font-size: 1.85rem; font-weight: 900; color: #4338CA;">
                <?php echo number_format($total_tickets_sold); ?>
            </div>
            <div style="font-size: 0.82rem; color: #64748B; margin-top: 0.4rem; font-weight: 600;">
                Verified QR pass barcodes created
            </div>
        </div>

        <!-- Events & Pending Moderation -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 14px; padding: 1.5rem; box-shadow: var(--ep-shadow-sm); border-top: 4px solid #F59E0B;">
            <div style="font-size: 0.8rem; font-weight: 800; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem;">
                Events (Live / Pending)
            </div>
            <div style="font-size: 1.85rem; font-weight: 900; color: var(--ep-navy);">
                <?php echo intval($published_events_count); ?> <span style="font-size: 1.1rem; color: #F59E0B; font-weight: 700;">(<?php echo intval($pending_events_count); ?> pending)</span>
            </div>
            <div style="font-size: 0.82rem; color: #64748B; margin-top: 0.4rem; font-weight: 600;">
                <?php echo count($pending_organisers); ?> organisers &bull; <?php echo count($pending_payouts); ?> payouts pending
            </div>
        </div>

    </div>

    <!-- Administrative Moderation Queues -->
    <div style="display: flex; flex-direction: column; gap: 3rem;">

        <!-- 1. Event Approval Queue -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: var(--ep-shadow-sm);">
            <div style="padding: 1.5rem 2rem; background: var(--ep-navy-deep); color: #fff; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; margin: 0 0 0.25rem 0;">Pending Event Approvals (<?php echo count($pending_events); ?>)</h2>
                    <p style="color: #94A3B8; font-size: 0.88rem; margin: 0;">Review promoter submissions before publishing live on the explore page.</p>
                </div>
                <span style="background: rgba(245,158,11,0.2); color: #F59E0B; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 800;">
                    <?php echo count($pending_events); ?> Awaiting Review
                </span>
            </div>

            <div style="padding: 1.5rem 2rem;">
                <?php if (empty($pending_events)) : ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: #64748B;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 0.75rem;">🎉</span>
                        <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">Event Review Queue Clear</h4>
                        <p style="margin: 0; font-size: 0.92rem;">All event submissions have been reviewed and published.</p>
                    </div>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--ep-slate-200); color: #64748B; font-size: 0.8rem; text-transform: uppercase;">
                                    <th style="padding: 0.75rem 1rem;">Event & Category</th>
                                    <th style="padding: 0.75rem 1rem;">Organiser</th>
                                    <th style="padding: 0.75rem 1rem;">Date & Venue</th>
                                    <th style="padding: 0.75rem 1rem;">Tiers & Pricing</th>
                                    <th style="padding: 0.75rem 1rem; text-align: right;">Moderation Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_events as $pev) :
                                    $ev_date   = get_post_meta($pev->ID, '_ep_event_date', true) ?: 'Upcoming';
                                    $ev_venue  = get_post_meta($pev->ID, '_ep_event_venue', true) ?: 'Independence Stadium';
                                    $ev_town   = get_post_meta($pev->ID, '_ep_event_town', true) ?: 'Windhoek';
                                    $ev_author = get_userdata($pev->post_author);
                                    $author_name = $ev_author ? ($ev_author->display_name ?: $ev_author->user_login) : 'Organiser #' . $pev->post_author;
                                    $ev_terms  = get_the_terms($pev->ID, 'ep_event_category');
                                    $ev_cat    = (!empty($ev_terms) && !is_wp_error($ev_terms)) ? $ev_terms[0]->name : 'General';
                                    $tiers     = function_exists('eventpass_get_event_tiers') ? eventpass_get_event_tiers($pev->ID) : [];
                                ?>
                                    <tr style="border-bottom: 1px solid var(--ep-slate-200);">
                                        <td style="padding: 1rem;">
                                            <a href="<?php echo esc_url(get_permalink($pev->ID)); ?>" target="_blank" style="font-weight: 800; color: var(--ep-navy); text-decoration: none;">
                                                <?php echo esc_html($pev->post_title); ?> &rarr;
                                            </a>
                                            <div style="font-size: 0.8rem; color: var(--ep-teal-dark); font-weight: 700; margin-top: 2px;">
                                                🏷️ <?php echo esc_html($ev_cat); ?>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <strong><?php echo esc_html($author_name); ?></strong>
                                            <div style="font-size: 0.8rem; color: #64748B;"><?php echo esc_html($ev_author ? $ev_author->user_email : ''); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <div>📅 <?php echo esc_html($ev_date); ?></div>
                                            <div style="font-size: 0.82rem; color: #64748B;">📍 <?php echo esc_html($ev_venue . ', ' . ucfirst($ev_town)); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <?php if (!empty($tiers)) : ?>
                                                <?php foreach ($tiers as $tk => $tv) : ?>
                                                    <span style="display:inline-block; font-size:0.75rem; background:#F1F5F9; padding:2px 8px; border-radius:4px; margin:2px;">
                                                        <strong><?php echo esc_html($tv['name']); ?>:</strong> N$ <?php echo floatval($tv['price']); ?> (<?php echo intval($tv['available_quantity']); ?> qty)
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <span style="color:#94A3B8; font-size:0.8rem;">Standard tiers</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: right;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                                <form method="POST" action="" style="display: inline;">
                                                    <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                    <input type="hidden" name="ep_admin_action" value="approve_event">
                                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($pev->ID); ?>">
                                                    <button type="button" class="ep-btn ep-btn-sm" style="background: #10B981; color: #fff; font-weight: 700;" onclick="openAdminConfirmModal(this.form, {
                                                        title: 'Approve Event Live',
                                                        message: 'Are you sure you want to approve <strong><?php echo esc_js($pev->post_title); ?></strong> and publish it live on the EventPass marketplace?',
                                                        confirmText: '✓ Yes, Publish Live',
                                                        theme: 'success',
                                                        icon: '🎉'
                                                    });">
                                                        ✓ Approve Live
                                                    </button>
                                                </form>
                                                <form method="POST" action="" style="display: inline;">
                                                    <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                    <input type="hidden" name="ep_admin_action" value="reject_event">
                                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($pev->ID); ?>">
                                                    <button type="button" class="ep-btn ep-btn-sm" style="background: rgba(239,68,68,0.1); color: #DC2626; border: 1px solid rgba(239,68,68,0.3); font-weight: 700;" onclick="openAdminConfirmModal(this.form, {
                                                        title: 'Decline Event Submission',
                                                        message: 'Are you sure you want to decline <strong><?php echo esc_js($pev->post_title); ?></strong> and return it to Draft status?',
                                                        confirmText: 'Yes, Return to Draft',
                                                        theme: 'danger',
                                                        icon: '⚠️'
                                                    });">
                                                        Decline
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Organiser Application Vetting Queue -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: var(--ep-shadow-sm);">
            <div style="padding: 1.5rem 2rem; background: var(--ep-navy); color: #fff; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; margin: 0 0 0.25rem 0;">Organiser Accreditation Queue (<?php echo count($pending_organisers); ?>)</h2>
                    <p style="color: #94A3B8; font-size: 0.88rem; margin: 0;">Review event promoter onboarding submissions and grant publishing rights.</p>
                </div>
                <span style="background: rgba(0,229,255,0.15); color: var(--ep-cyan); padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 800;">
                    <?php echo count($pending_organisers); ?> Pending Accreditation
                </span>
            </div>

            <div style="padding: 1.5rem 2rem;">
                <?php if (empty($pending_organisers)) : ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: #64748B;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 0.75rem;">💼</span>
                        <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">All Organiser Applications Reviewed</h4>
                        <p style="margin: 0; font-size: 0.92rem;">No new promoter applications currently require compliance verification.</p>
                    </div>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--ep-slate-200); color: #64748B; font-size: 0.8rem; text-transform: uppercase;">
                                    <th style="padding: 0.75rem 1rem;">Company / Brand</th>
                                    <th style="padding: 0.75rem 1rem;">Contact Person</th>
                                    <th style="padding: 0.75rem 1rem;">Region & Category</th>
                                    <th style="padding: 0.75rem 1rem;">Banking Entity</th>
                                    <th style="padding: 0.75rem 1rem; text-align: right;">Accreditation Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_organisers as $porg) :
                                    $org_name  = get_post_meta($porg->ID, '_ep_org_name', true) ?: $porg->post_title;
                                    $contact   = get_post_meta($porg->ID, '_ep_org_contact', true);
                                    $email     = get_post_meta($porg->ID, '_ep_org_email', true);
                                    $phone     = get_post_meta($porg->ID, '_ep_org_phone', true);
                                    $town      = get_post_meta($porg->ID, '_ep_org_town', true) ?: 'Windhoek';
                                    $cat       = get_post_meta($porg->ID, '_ep_org_category', true) ?: 'Entertainment';
                                    $bank      = get_post_meta($porg->ID, '_ep_org_bank', true) ?: 'Bank Windhoek';
                                ?>
                                    <tr style="border-bottom: 1px solid var(--ep-slate-200);">
                                        <td style="padding: 1rem;">
                                            <strong style="color: var(--ep-navy); font-size: 1rem;"><?php echo esc_html($org_name); ?></strong>
                                            <div style="font-size: 0.8rem; color: #64748B;">Registered: <?php echo esc_html(get_the_date('j M Y, H:i', $porg->ID)); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <div><?php echo esc_html($contact); ?></div>
                                            <div style="font-size: 0.8rem; color: #64748B;"><?php echo esc_html($email); ?> &bull; <?php echo esc_html($phone); ?></div>
                                            <?php 
                                            $is_org_email_v = get_post_meta($porg->ID, '_ep_org_email_verified', true) == '1';
                                            if ($is_org_email_v) : ?>
                                                <span style="display:inline-block; margin-top:4px; background:rgba(16,185,129,0.1); color:#059669; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:4px;">Email Verified ✓</span>
                                            <?php else : ?>
                                                <span style="display:inline-block; margin-top:4px; background:rgba(245,158,11,0.12); color:#B45309; font-size:0.75rem; font-weight:700; padding:1px 6px; border-radius:4px;">Email Verification Pending ⏳</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <div>📍 <?php echo esc_html(ucfirst($town)); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--ep-teal-dark); font-weight: 700;"><?php echo esc_html($cat); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            🏦 <?php echo esc_html($bank); ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: right;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                                <form method="POST" action="" style="display: inline;">
                                                    <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                    <input type="hidden" name="ep_admin_action" value="approve_organiser">
                                                    <input type="hidden" name="app_id" value="<?php echo esc_attr($porg->ID); ?>">
                                                    <button type="button" class="ep-btn ep-btn-sm" style="background: #10B981; color: #fff; font-weight: 700;" onclick="openAdminConfirmModal(this.form, {
                                                        title: 'Accredit Organiser',
                                                        message: 'Are you sure you want to accredit <strong><?php echo esc_js($org_name); ?></strong> and grant live event publishing privileges on EventPass Namibia?',
                                                        confirmText: '✓ Yes, Accredit Organiser',
                                                        theme: 'success',
                                                        icon: '✓'
                                                    });">
                                                        ✓ Accredit Organiser
                                                    </button>
                                                </form>
                                                <form method="POST" action="" style="display: inline;">
                                                    <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                    <input type="hidden" name="ep_admin_action" value="reject_organiser">
                                                    <input type="hidden" name="app_id" value="<?php echo esc_attr($porg->ID); ?>">
                                                    <button type="button" class="ep-btn ep-btn-sm" style="background: rgba(239,68,68,0.1); color: #DC2626; border: 1px solid rgba(239,68,68,0.3); font-weight: 700;" onclick="openAdminConfirmModal(this.form, {
                                                        title: 'Decline Organiser Application',
                                                        message: 'Are you sure you want to decline the accreditation application for <strong><?php echo esc_js($org_name); ?></strong>?',
                                                        confirmText: 'Yes, Decline Application',
                                                        theme: 'danger',
                                                        icon: '⚠️'
                                                    });">
                                                        Decline
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Payout Settlement & Banking Remittance -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: var(--ep-shadow-sm);">
            <div style="padding: 1.5rem 2rem; background: #064E3B; color: #fff; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; margin: 0 0 0.25rem 0;">Payout Settlement & Remittance Desk (<?php echo count($pending_payouts); ?>)</h2>
                    <p style="color: #A7F3D0; font-size: 0.88rem; margin: 0;">Disburse net ticket proceeds to Namibian commercial bank accounts via Electronic Funds Transfer.</p>
                </div>
                <span style="background: rgba(16,185,129,0.25); color: #34D399; padding: 4px 12px; border-radius: 20px; font-size: 0.82rem; font-weight: 800;">
                    <?php echo count($pending_payouts); ?> Pending Remittance
                </span>
            </div>

            <div style="padding: 1.5rem 2rem;">
                <?php if (empty($pending_payouts)) : ?>
                    <div style="text-align: center; padding: 3rem 1rem; color: #64748B;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 0.75rem;">💰</span>
                        <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">All Payout Settlements Up to Date</h4>
                        <p style="margin: 0; font-size: 0.92rem;">No outstanding organiser withdrawal requests awaiting commercial bank remittance.</p>
                    </div>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--ep-slate-200); color: #64748B; font-size: 0.8rem; text-transform: uppercase;">
                                    <th style="padding: 0.75rem 1rem;">Payout Reference</th>
                                    <th style="padding: 0.75rem 1rem;">Beneficiary Organiser</th>
                                    <th style="padding: 0.75rem 1rem;">Banking Destination</th>
                                    <th style="padding: 0.75rem 1rem;">Settlement Amount</th>
                                    <th style="padding: 0.75rem 1rem; text-align: right;">EFT Disbursement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_payouts as $pay) :
                                    $p_ref     = get_post_meta($pay->ID, '_ep_payout_reference', true) ?: 'PAY-' . $pay->ID;
                                    $p_name    = get_post_meta($pay->ID, '_ep_payout_organiser_name', true) ?: 'Promoter';
                                    $p_email   = get_post_meta($pay->ID, '_ep_payout_organiser_email', true);
                                    $p_bank    = get_post_meta($pay->ID, '_ep_payout_bank_name', true) ?: 'Bank Windhoek';
                                    $p_acc     = get_post_meta($pay->ID, '_ep_payout_account_number', true) ?: '0000000000';
                                    $p_branch  = get_post_meta($pay->ID, '_ep_payout_branch_code', true) ?: '481972';
                                    $p_amount  = floatval(get_post_meta($pay->ID, '_ep_payout_amount', true));
                                ?>
                                    <tr style="border-bottom: 1px solid var(--ep-slate-200);">
                                        <td style="padding: 1rem;">
                                            <code style="font-weight: 800; color: #00B4D8; font-size: 0.95rem;"><?php echo esc_html($p_ref); ?></code>
                                            <div style="font-size: 0.8rem; color: #64748B;">Requested: <?php echo esc_html(get_the_date('j M Y, H:i', $pay->ID)); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <strong><?php echo esc_html($p_name); ?></strong>
                                            <div style="font-size: 0.8rem; color: #64748B;"><?php echo esc_html($p_email); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <div style="font-weight: 700; color: var(--ep-navy);">🏦 <?php echo esc_html($p_bank); ?></div>
                                            <div style="font-size: 0.82rem; color: #64748B;">Acc: <code><?php echo esc_html($p_acc); ?></code> &bull; Branch: <?php echo esc_html($p_branch); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <span style="font-size: 1.15rem; font-weight: 900; color: #047857;">
                                                N$ <?php echo number_format($p_amount, 2); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem; text-align: right;">
                                            <form method="POST" action="" style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                                <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                <input type="hidden" name="ep_admin_action" value="process_payout">
                                                <input type="hidden" name="payout_id" value="<?php echo esc_attr($pay->ID); ?>">
                                                <input type="text" name="eft_reference" placeholder="EFT Trace Ref" required style="padding: 0.4rem 0.6rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.82rem; width: 140px;">
                                                <button type="button" class="ep-btn ep-btn-sm" style="background: #10B981; color: #fff; font-weight: 700; white-space: nowrap;" onclick="const refInput = this.form.querySelector('input[name=eft_reference]'); const ref = refInput ? refInput.value.trim() : ''; if (!ref) { if (refInput) refInput.focus(); alert('Please enter an EFT Trace Reference first.'); return; } openAdminConfirmModal(this.form, {
                                                    title: 'Confirm EFT Remittance',
                                                    message: 'Remit net proceeds of <strong>N$ <?php echo number_format($p_amount, 2); ?></strong> to <strong><?php echo esc_js($p_name); ?></strong> (<?php echo esc_js($p_bank); ?>)?<br><div style=\'margin-top:0.75rem; padding: 0.5rem 0.75rem; background:#F1F5F9; border-radius:6px; font-size:0.85rem; color:#334155;\'><strong>EFT Trace Reference:</strong> ' + ref + '</div>',
                                                    confirmText: '✓ Disburse Funds',
                                                    theme: 'success',
                                                    icon: '💰'
                                                });">
                                                    Disburse EFT &rarr;
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. Recent Platform Orders & Transactions -->
        <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: var(--ep-shadow-sm);">
            <div style="padding: 1.5rem 2rem; background: var(--ep-navy); color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="font-size: 1.3rem; font-weight: 800; margin: 0 0 0.25rem 0;">Real-Time Financial Transactions</h2>
                    <p style="color: #94A3B8; font-size: 0.88rem; margin: 0;">Audited customer bookings settled across Namibian payment rails.</p>
                </div>
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="adminOrderSearch" onkeyup="filterAdminOrders()" placeholder="Search ref, customer, event..." style="padding: 0.45rem 0.85rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.25); background: rgba(255,255,255,0.08); color: #fff; font-size: 0.84rem; min-width: 210px;">
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=ep_export_sales_csv&nonce=' . wp_create_nonce('ep_export_sales'))); ?>" class="ep-btn ep-btn-sm" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2);">
                        📥 Download CSV Audit
                    </a>
                </div>
            </div>

            <div style="padding: 1.5rem 2rem;">
                <?php if (empty($recent_orders)) : ?>
                    <p style="color: #64748B; text-align: center; padding: 2rem;">No orders recorded yet.</p>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table id="adminOrdersTable" style="width: 100%; border-collapse: collapse; font-size: 0.92rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--ep-slate-200); color: #64748B; font-size: 0.8rem; text-transform: uppercase;">
                                    <th style="padding: 0.75rem 1rem;">Order Reference</th>
                                    <th style="padding: 0.75rem 1rem;">Customer</th>
                                    <th style="padding: 0.75rem 1rem;">Event</th>
                                    <th style="padding: 0.75rem 1rem;">Total Paid</th>
                                    <th style="padding: 0.75rem 1rem;">Commission</th>
                                    <th style="padding: 0.75rem 1rem;">Gateway Status</th>
                                    <th style="padding: 0.75rem 1rem; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $ord) :
                                    $o_num   = get_post_meta($ord->ID, '_ep_order_number', true) ?: 'ORD-' . $ord->ID;
                                    $c_name  = get_post_meta($ord->ID, '_ep_order_customer_name', true) ?: 'Customer';
                                    $c_mail  = get_post_meta($ord->ID, '_ep_order_customer_email', true);
                                    $ev_id   = get_post_meta($ord->ID, '_ep_order_event_id', true);
                                    $ev_name = get_the_title($ev_id) ?: 'Premier Event';
                                    $o_tot   = floatval(get_post_meta($ord->ID, '_ep_order_total', true));
                                    $o_stat  = get_post_meta($ord->ID, '_ep_order_payment_status', true) ?: 'completed';
                                    $o_com   = floatval(get_post_meta($ord->ID, '_ep_order_commission_amount', true) ?: get_post_meta($ord->ID, '_ep_order_commission', true));
                                    if ($o_com <= 0 && $o_tot > 0) {
                                        $o_com = round($o_tot * (floatval(get_option('ep_default_commission_rate', 7.5)) / 100), 2);
                                    }
                                ?>
                                    <tr class="admin-order-row" style="border-bottom: 1px solid var(--ep-slate-200);">
                                        <td style="padding: 1rem;">
                                            <code style="font-weight: 800; color: #00B4D8;"><?php echo esc_html($o_num); ?></code>
                                            <div style="font-size: 0.8rem; color: #64748B;"><?php echo esc_html(get_the_date('j M Y, H:i', $ord->ID)); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <strong><?php echo esc_html($c_name); ?></strong>
                                            <div style="font-size: 0.8rem; color: #64748B;"><?php echo esc_html($c_mail); ?></div>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <strong style="color: var(--ep-navy);"><?php echo esc_html($ev_name); ?></strong>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <strong>N$ <?php echo number_format($o_tot, 2); ?></strong>
                                        </td>
                                        <td style="padding: 1rem; color: #047857; font-weight: 700;">
                                            +N$ <?php echo number_format($o_com, 2); ?>
                                        </td>
                                        <td style="padding: 1rem;">
                                            <?php if ($o_stat === 'refunded') : ?>
                                                <span style="background: #FEF2F2; color: #991B1B; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 800;">
                                                    ✕ Refunded
                                                </span>
                                            <?php else : ?>
                                                <span style="background: rgba(16,185,129,0.1); color: #065F46; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 800;">
                                                    ✓ Settled (Finatic)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem; text-align: right;">
                                            <?php if ($o_stat !== 'refunded') : ?>
                                                <form method="POST" action="" style="display: inline-block;">
                                                    <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                    <input type="hidden" name="ep_admin_action" value="refund_order">
                                                    <input type="hidden" name="order_id" value="<?php echo esc_attr($ord->ID); ?>">
                                                    <button type="button" class="ep-btn ep-btn-sm" style="background: #FEE2E2; color: #DC2626; font-weight: 700; font-size: 0.78rem;" onclick="openAdminConfirmModal(this.form, {
                                                        title: 'Confirm Refund &amp; Pass Voiding',
                                                        message: 'Mark order <strong><?php echo esc_js($o_num); ?></strong> (N$ <?php echo number_format($o_tot, 2); ?>) as <strong>Refunded</strong> and void its digital QR passes?',
                                                        confirmText: '✕ Void &amp; Refund',
                                                        theme: 'danger',
                                                        icon: '↩️'
                                                    });">
                                                        Refund
                                                    </button>
                                                </form>
                                            <?php else : ?>
                                                <span style="font-size: 0.78rem; color: #94A3B8;">Voided</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. Platform Commission Configuration & Registered Organiser Control (SOW Section 10) -->
        <div class="ep-form-grid-2" style="display: grid; grid-template-columns: 1fr 1.6fr; gap: 2rem; align-items: start;">
            <!-- Commission Rate Card -->
            <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: var(--ep-shadow-sm);">
                <div style="padding: 1.25rem 1.75rem; background: var(--ep-navy); color: #fff;">
                    <h2 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 0.2rem 0;">Commission Configuration</h2>
                    <p style="color: #94A3B8; font-size: 0.82rem; margin: 0;">Configure default platform fee percentage (SOW Section 10).</p>
                </div>
                <div style="padding: 1.5rem 1.75rem;">
                    <?php $curr_comm_rate = floatval(get_option('ep_default_commission_rate', 7.5)); ?>
                    <form method="POST" action="">
                        <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                        <input type="hidden" name="ep_admin_action" value="update_commission_rate">
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.4rem;">Default Platform Commission (%)</label>
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <input type="number" step="0.1" min="0" max="50" name="default_commission_rate" value="<?php echo esc_attr($curr_comm_rate); ?>" required style="flex: 1; padding: 0.65rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 1rem; font-weight: 700;">
                            <button type="submit" class="ep-btn ep-btn-primary ep-btn-sm">Update Rate</button>
                        </div>
                        <small style="display: block; margin-top: 0.6rem; color: #64748B; font-size: 0.78rem;">Applied automatically to all new ticket orders and organiser payout ledgers.</small>
                    </form>
                </div>
            </div>

            <!-- Registered Organisers Management -->
            <div style="background: #fff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: var(--ep-shadow-sm);">
                <div style="padding: 1.25rem 1.75rem; background: var(--ep-navy); color: #fff; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 style="font-size: 1.15rem; font-weight: 800; margin: 0 0 0.2rem 0;">Registered Organisers</h2>
                        <p style="color: #94A3B8; font-size: 0.82rem; margin: 0;">Suspend or reactivate accredited promoter accounts.</p>
                    </div>
                </div>
                <div style="padding: 1.25rem 1.75rem; max-height: 280px; overflow-y: auto;">
                    <?php
                    $reg_organisers = get_users(['role__in' => ['event_organiser', 'ep_organiser'], 'number' => 50]);
                    if (empty($reg_organisers)) : ?>
                        <p style="color: #64748B; text-align: center; margin: 1rem 0;">No registered organisers yet.</p>
                    <?php else : ?>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.86rem; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--ep-slate-200); color: #64748B; font-size: 0.75rem; text-transform: uppercase;">
                                    <th style="padding: 0.5rem;">Organiser</th>
                                    <th style="padding: 0.5rem;">Status</th>
                                    <th style="padding: 0.5rem; text-align: right;">Access</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reg_organisers as $ro) :
                                    $ro_st = get_user_meta($ro->ID, '_ep_organiser_status', true) ?: 'approved';
                                    $is_susp = ($ro_st === 'suspended');
                                ?>
                                    <tr style="border-bottom: 1px solid var(--ep-slate-100);">
                                        <td style="padding: 0.65rem 0.5rem;">
                                            <strong style="color: var(--ep-navy);"><?php echo esc_html($ro->display_name ?: $ro->user_login); ?></strong>
                                            <div style="font-size: 0.76rem; color: #64748B;"><?php echo esc_html($ro->user_email); ?></div>
                                        </td>
                                        <td style="padding: 0.65rem 0.5rem;">
                                            <span style="padding: 2px 8px; border-radius: 999px; font-size: 0.74rem; font-weight: 700; background: <?php echo $is_susp ? '#FEF2F2' : '#ECFDF5'; ?>; color: <?php echo $is_susp ? '#991B1B' : '#065F46'; ?>;">
                                                <?php echo $is_susp ? 'Suspended' : 'Active'; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.65rem 0.5rem; text-align: right;">
                                            <form method="POST" action="" style="display: inline;">
                                                <?php wp_nonce_field('ep_admin_action', 'ep_admin_nonce'); ?>
                                                <input type="hidden" name="ep_admin_action" value="toggle_organiser_status">
                                                <input type="hidden" name="organiser_user_id" value="<?php echo esc_attr($ro->ID); ?>">
                                                <input type="hidden" name="new_org_status" value="<?php echo $is_susp ? 'approved' : 'suspended'; ?>">
                                                <button type="submit" class="ep-btn ep-btn-sm" style="font-size: 0.74rem; padding: 0.25rem 0.65rem; background: <?php echo $is_susp ? '#10B981' : '#FEE2E2'; ?>; color: <?php echo $is_susp ? '#fff' : '#DC2626'; ?>;">
                                                    <?php echo $is_susp ? 'Reactivate' : 'Suspend'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        function filterAdminOrders() {
            const q = (document.getElementById('adminOrderSearch').value || '').toLowerCase().trim();
            document.querySelectorAll('#adminOrdersTable .admin-order-row').forEach(row => {
                row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
            });
        }
        </script>

    </div>

</div>

<!-- Admin Action Confirmation Modal (Custom Branded Dialog) -->
<div id="epAdminConfirmModal" style="display: none; position: fixed; inset: 0; background: rgba(6, 14, 32, 0.72); backdrop-filter: blur(6px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem; transition: all 0.25s ease;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 480px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 1px solid var(--ep-slate-200); overflow: hidden; animation: epModalPop 0.22s cubic-bezier(0.16, 1, 0.3, 1);">
        <div style="padding: 2rem 2rem 1.25rem; text-align: center;">
            <div id="epModalIconBadge" style="width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.85rem; margin-bottom: 1rem; background: rgba(16,185,129,0.12); color: #059669;">
                ✓
            </div>
            <h3 id="epModalTitle" style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin: 0 0 0.5rem 0;">
                Confirm Action
            </h3>
            <div id="epModalMessage" style="font-size: 0.95rem; color: #475569; margin: 0; line-height: 1.55;">
                Are you sure you want to proceed with this administrative action?
            </div>
        </div>
        <div style="padding: 1.25rem 2rem 1.75rem; background: #F8FAFC; border-top: 1px solid #E2E8F0; display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" onclick="closeAdminConfirmModal()" class="ep-btn ep-btn-sm" style="background: #ffffff; color: #475569; border: 1px solid #CBD5E1; font-weight: 600; padding: 0.6rem 1.35rem; border-radius: 8px; cursor: pointer; transition: all 0.15s ease;">
                Cancel
            </button>
            <button type="button" id="epModalConfirmBtn" onclick="executeAdminConfirmedAction()" class="ep-btn ep-btn-sm" style="background: #10B981; color: #ffffff; font-weight: 700; padding: 0.6rem 1.6rem; border-radius: 8px; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(16,185,129,0.3); transition: all 0.15s ease;">
                Confirm
            </button>
        </div>
    </div>
</div>

<style>
@keyframes epModalPop {
    from { opacity: 0; transform: scale(0.92) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
let epPendingActionForm = null;

function openAdminConfirmModal(formElement, config) {
    epPendingActionForm = formElement;
    
    const modal = document.getElementById('epAdminConfirmModal');
    const iconBadge = document.getElementById('epModalIconBadge');
    const title = document.getElementById('epModalTitle');
    const msg = document.getElementById('epModalMessage');
    const btn = document.getElementById('epModalConfirmBtn');
    
    title.textContent = config.title || 'Confirm Action';
    msg.innerHTML = config.message || 'Are you sure you want to proceed?';
    btn.textContent = config.confirmText || 'Confirm';
    
    if (config.theme === 'danger') {
        iconBadge.innerHTML = config.icon || '⚠️';
        iconBadge.style.background = 'rgba(239, 68, 68, 0.12)';
        iconBadge.style.color = '#DC2626';
        btn.style.background = '#DC2626';
        btn.style.boxShadow = '0 4px 14px rgba(220, 38, 38, 0.3)';
    } else {
        iconBadge.innerHTML = config.icon || '✓';
        iconBadge.style.background = 'rgba(16, 185, 129, 0.12)';
        iconBadge.style.color = '#059669';
        btn.style.background = '#10B981';
        btn.style.boxShadow = '0 4px 14px rgba(16, 185, 129, 0.3)';
    }
    
    modal.style.display = 'flex';
}

function closeAdminConfirmModal() {
    const modal = document.getElementById('epAdminConfirmModal');
    if (modal) modal.style.display = 'none';
    epPendingActionForm = null;
}

function executeAdminConfirmedAction() {
    if (epPendingActionForm) {
        const formToSubmit = epPendingActionForm;
        closeAdminConfirmModal();
        formToSubmit.submit();
    }
}

// Close on backdrop click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('epAdminConfirmModal');
    if (e.target === modal) {
        closeAdminConfirmModal();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAdminConfirmModal();
    }
});
</script>

<?php
get_footer();
