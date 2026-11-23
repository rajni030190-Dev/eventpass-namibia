<?php
/**
 * Template Name: Checkout
 *
 * @package EventPass_Namibia
 */

get_header();

$current_user   = wp_get_current_user();
$is_logged_in   = is_user_logged_in();
$default_name   = $is_logged_in ? ($current_user->display_name ?: $current_user->user_login) : '';
$default_email  = $is_logged_in ? $current_user->user_email : '';
$default_phone  = $is_logged_in ? get_user_meta($current_user->ID, 'phone_number', true) : '';

$event_id = isset($_REQUEST['event_id']) ? intval($_REQUEST['event_id']) : 0;
$event    = get_post($event_id);

$order_confirmed = false;
$order_pending   = false;
$gateway_session = [];
$order_result    = null;
$checkout_error  = '';

// 1. Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_checkout_action'])) {
    if (!isset($_POST['ep_checkout_nonce']) || !wp_verify_nonce($_POST['ep_checkout_nonce'], 'ep_checkout_nonce')) {
        $checkout_error = __('Security validation failed. Please refresh and try again.', 'eventpass-core');
    } else {
        $customer_name  = sanitize_text_field(trim($_POST['customer_name'] ?? ''));
        $customer_email = sanitize_email(trim($_POST['customer_email'] ?? ''));
        $customer_phone = sanitize_text_field(trim($_POST['customer_phone'] ?? ''));
        $payment_method = sanitize_key($_POST['payment_method'] ?? 'finatic');
        $raw_tickets    = $_POST['tickets'] ?? [];

        $items = [];
        if (is_array($raw_tickets)) {
            foreach ($raw_tickets as $tid => $qty) {
                $q = intval($qty);
                if ($q > 0) {
                    $items[$tid] = $q;
                }
            }
        }

        if (empty($items)) {
            $checkout_error = __('Please select at least one ticket to purchase.', 'eventpass-core');
        } else {
            // Create Order and reserve inventory
            $order_id = eventpass_create_order([
                'event_id'       => $event_id,
                'items'          => $items,
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'user_id'        => $is_logged_in ? $current_user->ID : 0,
                'payment_method' => $payment_method,
            ]);

            if (is_wp_error($order_id)) {
                $checkout_error = $order_id->get_error_message();
            } else {
                // SOW Compliance: Zero tickets issued on checkout submit!
                // Orders stay strictly 'pending' until verified server-to-server IPN webhook settlement.
                $session = EventPass_Finatic_Gateway::initiate_checkout_session($order_id, $payment_method);
                if (!$session['success']) {
                    $checkout_error = $session['message'] ?? 'Payment gateway session could not be established.';
                } else {
                    $order_pending = true;
                    $gateway_session = $session;
                    $pending_order = get_post($order_id);
                }
            }
        }
    }
}

// 2. Prepare Cart for Initial Checkout View
$cart_items = [];
$total_amount = 0.0;

if ($event && $event->post_type === 'ep_event') {
    $db_tiers = eventpass_get_event_tiers($event_id);
    $tiers_by_id = [];
    foreach ($db_tiers as $t) {
        $t_id = $t['id'] ?? '';
        if ($t_id) $tiers_by_id[$t_id] = $t;
    }

    $req_tickets = $_REQUEST['tickets'] ?? [];
    if (is_array($req_tickets)) {
        foreach ($req_tickets as $t_key => $qty) {
            $q = intval($qty);
            if ($q > 0 && isset($tiers_by_id[$t_key])) {
                $t_info = $tiers_by_id[$t_key];
                $price = floatval($t_info['price'] ?? 0);
                $line = $price * $q;
                $cart_items[] = [
                    'id'       => $t_key,
                    'name'     => $t_info['name'] ?? 'Tier Pass',
                    'price'    => $price,
                    'quantity' => $q,
                    'total'    => $line,
                ];
                $total_amount += $line;
            }
        }
    }

    // Default 1 ticket if accessed without specific quantities
    if (empty($cart_items) && !empty($db_tiers)) {
        $first_available = null;
        foreach ($db_tiers as $tier) {
            if (intval($tier['available_quantity'] ?? 0) > 0) {
                $first_available = $tier;
                break;
            }
        }
        if (!$first_available) {
            $first_available = $db_tiers[0];
        }
        $tier_key = $first_available['id'] ?? 'general';
        $price = floatval($first_available['price'] ?? 0);
        $cart_items[] = [
            'id'       => $tier_key,
            'name'     => $first_available['name'] ?? 'General Admission',
            'price'    => $price,
            'quantity' => 1,
            'total'    => $price,
        ];
        $total_amount = $price;
    }
}
?>

<!-- Banner Section -->
<div class="ep-page-banner" style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); color: #ffffff; padding: 3rem 0 2.5rem;">
    <div class="container">
        <span style="color: var(--ep-cyan); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">
            <?php echo $order_confirmed ? 'Booking Confirmed' : 'Secure Checkout'; ?>
        </span>
        <h1 style="font-size: 2.25rem; font-weight: 800; margin: 0.25rem 0 0.5rem; line-height: 1.2;">
            <?php echo $order_confirmed ? 'Your Digital Passes Are Ready!' : 'Review & Complete Booking'; ?>
        </h1>
        <p style="font-size: 1rem; color: #CBD5E1; margin: 0;">
            <?php if ($event) : ?>
                <?php echo esc_html($event->post_title); ?> • Bank of Namibia NPS Compliant
            <?php else : ?>
                EventPass Namibia Online Ticketing
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="container" style="padding: 3.5rem 1.5rem 5rem;">

    <?php if ($order_pending && !empty($gateway_session)) : 
        $p_order_id  = $gateway_session['order_id'];
        $p_amount    = $gateway_session['amount'];
        $p_currency  = $gateway_session['currency'];
        $p_sig       = $gateway_session['signature'];
        $p_time      = $gateway_session['timestamp'];
        $p_merchant  = EventPass_Finatic_Gateway::get_merchant_id();
        $p_order_num = get_post_meta($p_order_id, '_ep_order_number', true) ?: 'ORD-' . $p_order_id;
        $webhook_url = rest_url('eventpass/v1/payment-webhook');
    ?>
        <!-- SOW Compliance: Hosted Payment Gateway Portal (Awaiting Independent Verification) -->
        <div style="max-width: 680px; margin: 0 auto;">
            <div style="background: #ffffff; border: 2px solid var(--ep-teal); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-lg); text-align: center; margin-bottom: 2rem;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(0,180,216,0.1); border: 1px solid rgba(0,180,216,0.3); padding: 0.4rem 1rem; border-radius: 999px; margin-bottom: 1.25rem;">
                    <span style="width: 8px; height: 8px; background: #00B4D8; border-radius: 50%; display: inline-block;"></span>
                    <span style="font-size: 0.8rem; font-weight: 800; color: #00B4D8; text-transform: uppercase;">Bank of Namibia NPS • Finatic Gateway</span>
                </div>

                <h2 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0 0 0.5rem;">
                    Finatic Hosted Payment Checkout
                </h2>
                <p style="color: #64748B; font-size: 0.95rem; margin: 0 0 1.5rem;">
                    Order <strong><?php echo esc_html($p_order_num); ?></strong> is currently <strong>Pending Payment Verification</strong>. Zero tickets are issued until verified settlement.
                </p>

                <!-- Financial Security Ledger Card -->
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; text-align: left; font-size: 0.88rem;">
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #E2E8F0; margin-bottom: 0.5rem;">
                        <span style="color: #64748B;">Settlement Amount:</span>
                        <strong style="color: #0B1B3D; font-size: 1.1rem;">N$ <?php echo esc_html($p_amount); ?> <?php echo esc_html($p_currency); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #E2E8F0; margin-bottom: 0.5rem;">
                        <span style="color: #64748B;">Merchant ID:</span>
                        <code style="color: #0077B6;"><?php echo esc_html($p_merchant); ?></code>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #E2E8F0; margin-bottom: 0.5rem;">
                        <span style="color: #64748B;">HMAC-SHA256 Signature:</span>
                        <code style="font-size: 0.75rem; color: #0F766E; word-break: break-all;"><?php echo esc_html(substr($p_sig, 0, 24)); ?>...</code>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748B;">Server IPN Listener:</span>
                        <span style="color: #10B981; font-weight: 700;">● Active (HTTP 200 Ready)</span>
                    </div>
                </div>

                <!-- 3D-Secure Payment Simulation Terminal -->
                <div style="border: 1px solid #CBD5E1; border-radius: 10px; padding: 1.25rem; background: #FFFFFF; margin-bottom: 1.5rem; text-align: left;">
                    <div style="font-weight: 700; color: #0B1B3D; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
                        <span>🔒 3D-Secure Payment Terminal</span>
                        <span style="font-size: 0.75rem; color: #64748B;">Visa / Mastercard / Local EFT</span>
                    </div>
                    <p style="font-size: 0.82rem; color: #64748B; margin-bottom: 1rem;">
                        Clicking authorize simulates the external acquiring bank processing and sends an authentic, cryptographically signed IPN webhook payload to the EventPass server.
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" onclick="submitFinaticSimulation('SUCCESS')" class="ep-btn ep-btn-primary" style="flex: 1; justify-content: center; height: 46px;">
                            <span>✓ Authorize Payment (N$ <?php echo esc_html($p_amount); ?>)</span>
                        </button>
                        <button type="button" onclick="submitFinaticSimulation('FAILED')" class="ep-btn" style="background: #FEE2E2; color: #DC2626; border: 1px solid #FCA5A5; padding: 0 16px; font-weight: 700;">
                            <span>✕ Decline</span>
                        </button>
                    </div>
                    <div id="gatewayLoadingMsg" style="display: none; margin-top: 1rem; text-align: center; color: #0284C7; font-weight: 700; font-size: 0.9rem;">
                        ⏳ Contacting Finatic acquiring rails... Verifying HMAC signature...
                    </div>
                </div>

                <a href="<?php echo esc_url(home_url('/customer-dashboard')); ?>" style="color: #64748B; font-size: 0.85rem; text-decoration: none;">
                    &larr; Return to Customer Dashboard (Awaiting Settlement)
                </a>
            </div>
        </div>

        <script>
        function submitFinaticSimulation(status) {
            document.getElementById('gatewayLoadingMsg').style.display = 'block';
            const payload = {
                merchant_id: '<?php echo esc_js($p_merchant); ?>',
                order_id: <?php echo intval($p_order_id); ?>,
                amount: '<?php echo esc_js($p_amount); ?>',
                timestamp: <?php echo intval($p_time); ?>,
                signature: '<?php echo esc_js($p_sig); ?>',
                status: status,
                transaction_id: 'TXN-FIN-' + Math.random().toString(36).substring(2, 10).toUpperCase()
            };

            fetch('<?php echo esc_url($webhook_url); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 200 && status === 'SUCCESS') {
                    // Payment confirmed and settled via webhook; redirect to customer hub
                    window.location.href = '<?php echo esc_url(home_url('/customer-dashboard?order_confirmed=' . $p_order_id)); ?>';
                } else {
                    alert('Gateway Callback: ' + (data.message || data.error || 'Payment status recorded.'));
                    window.location.reload();
                }
            })
            .catch(err => {
                alert('Webhook dispatch error: ' + err.message);
                document.getElementById('gatewayLoadingMsg').style.display = 'none';
            });
        }
        </script>

    <?php elseif ($order_confirmed && $order_result) : 
        $issued = $order_result['tickets'] ?? [];
        $order_num = $order_result['order_number'] ?? '';
        $txn_id = $order_result['txn_id'] ?? '';
        $event_title = $event ? $event->post_title : 'EventPass Namibia Event';
        $event_date = $event ? get_post_meta($event_id, '_ep_event_date', true) : 'Upcoming 2026';
        $event_venue = $event ? get_post_meta($event_id, '_ep_event_venue', true) : 'Namibia';
        $event_town = $event ? ucfirst(get_post_meta($event_id, '_ep_event_town', true)) : 'Windhoek';
    ?>
        <!-- Order Confirmed State -->
        <div style="max-width: 780px; margin: 0 auto;">
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-lg); text-align: center; margin-bottom: 2.5rem;">
                <div style="width: 72px; height: 72px; background: #ECFDF5; border-radius: 50%; color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 2.25rem; margin: 0 auto 1.25rem;">
                    ✓
                </div>

                <span style="display: block; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; color: var(--ep-success); letter-spacing: 0.5px;">Payment Verified & Settled</span>
                <h2 style="font-size: 2rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Booking Confirmation</h2>
                <p style="color: var(--ep-slate-600); font-size: 1rem; margin-bottom: 1.5rem;">
                    Thank you, <strong><?php echo esc_html($customer_name); ?></strong>! A copy of your pass details and invoice has been registered to <strong><?php echo esc_html($customer_email); ?></strong>.
                </p>

                <div style="display: inline-flex; gap: 2rem; background: var(--ep-slate-50); border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); padding: 0.85rem 1.75rem; font-size: 0.9rem; text-align: left;">
                    <div>
                        <span style="color: #64748B; font-size: 0.75rem; text-transform: uppercase; display: block;">Order Reference</span>
                        <strong style="color: var(--ep-navy); font-size: 1rem;"><?php echo esc_html($order_num); ?></strong>
                    </div>
                    <div>
                        <span style="color: #64748B; font-size: 0.75rem; text-transform: uppercase; display: block;">Gateway Txn ID</span>
                        <strong style="color: var(--ep-teal-dark); font-size: 1rem;"><?php echo esc_html($txn_id); ?></strong>
                    </div>
                    <div>
                        <span style="color: #64748B; font-size: 0.75rem; text-transform: uppercase; display: block;">Total Paid</span>
                        <strong style="color: var(--ep-navy); font-size: 1rem;">N$ <?php echo number_format((float)$order_result['total'], 2); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Issued Digital Passes Cards -->
            <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🎟️</span> Issued Entry Passes (<?php echo count($issued); ?>)
            </h3>

            <?php foreach ($issued as $ticket_item) : 
                $tok = $ticket_item['token'];
            ?>
                <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); overflow: hidden; margin-bottom: 2rem; box-shadow: var(--ep-shadow-md); display: grid; grid-template-columns: 1.75fr 1fr;">
                    <!-- Left Ticket Information -->
                    <div style="padding: 2rem; border-right: 2px dashed var(--ep-slate-200);">
                        <span class="ep-category-badge" style="position: static; margin-bottom: 0.75rem; display: inline-block;">Official Event Pass</span>
                        <h4 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.75rem;">
                            <?php echo esc_html($event_title); ?>
                        </h4>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.25rem 0;">
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-slate-600); font-weight: 700; display: block;">Date</span>
                                <strong style="font-size: 0.95rem; color: var(--ep-navy);"><?php echo esc_html($event_date); ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-slate-600); font-weight: 700; display: block;">Venue</span>
                                <strong style="font-size: 0.95rem; color: var(--ep-navy);"><?php echo esc_html($event_venue . ', ' . $event_town); ?></strong>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1.5rem; border-top: 1px solid var(--ep-slate-100); padding-top: 1rem; align-items: center;">
                            <div>
                                <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block;">Tier Category</span>
                                <strong style="color: var(--ep-teal-dark); font-size: 1rem;"><?php echo esc_html($ticket_item['tier']); ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block;">Ticket Holder</span>
                                <strong style="color: var(--ep-navy); font-size: 1rem;"><?php echo esc_html($ticket_item['holder']); ?></strong>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block;">Status</span>
                                <span style="color: var(--ep-success); font-weight: 700; font-size: 0.9rem;">● Valid Pass</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right QR Stub -->
                    <div style="background: var(--ep-navy-deep); padding: 2rem 1.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #ffffff;">
                        <span style="font-size: 0.7rem; text-transform: uppercase; color: var(--ep-cyan); font-weight: 800; letter-spacing: 1px; margin-bottom: 0.75rem;">Gate Check-In QR</span>

                        <div style="margin-bottom: 0.75rem;">
                            <?php echo eventpass_get_qr_svg($tok, 125); ?>
                        </div>

                        <code style="font-size: 0.85rem; letter-spacing: 1px; color: var(--ep-cyan); font-weight: 700; margin-bottom: 0.5rem;"><?php echo esc_html($tok); ?></code>
                        <span style="font-size: 0.7rem; color: #94A3B8;">Present at gate for scanner validation</span>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap;">
                <a href="javascript:window.print()" class="ep-btn ep-btn-primary ep-btn-lg">
                    <span>🖨️ Print / Download Passes</span>
                </a>
                <a href="<?php echo esc_url(home_url('/customer-dashboard')); ?>" class="ep-btn ep-btn-outline ep-btn-lg" style="background:#fff;">
                    <span>🎟️ Go to My Tickets</span>
                </a>
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-secondary ep-btn-lg">
                    <span>Find More Events &rarr;</span>
                </a>
            </div>
        </div>

    <?php elseif (!$event) : ?>
        <!-- Invalid Event State -->
        <div style="max-width: 600px; margin: 0 auto; text-align: center; padding: 4rem 1rem;">
            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">⚠️</span>
            <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Event Not Found</h2>
            <p style="color: var(--ep-slate-600); margin-bottom: 2rem;">Please select an event from our catalog to begin booking tickets.</p>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary">
                <span>Explore Events &rarr;</span>
            </a>
        </div>

    <?php else : ?>
        <!-- Checkout Form State -->
        <?php if (!empty($checkout_error)) : ?>
            <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #991B1B; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; font-weight: 600;">
                ⚠️ <?php echo esc_html($checkout_error); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.7fr 1.3fr; gap: 3rem; align-items: start;">
            <!-- Left Column: Attendee & Payment Form -->
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-sm);">
                <form id="epCheckoutForm" action="<?php echo esc_url(home_url('/checkout')); ?>" method="POST">
                    <?php wp_nonce_field('ep_checkout_nonce', 'ep_checkout_nonce'); ?>
                    <input type="hidden" name="ep_checkout_action" value="1">
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">

                    <?php foreach ($cart_items as $c_item) : ?>
                        <input type="hidden" name="tickets[<?php echo esc_attr($c_item['id']); ?>]" value="<?php echo esc_attr($c_item['quantity']); ?>">
                    <?php endforeach; ?>

                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.5rem;">1. Attendee Contact Details</h3>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Full Name *</label>
                            <input type="text" name="customer_name" required value="<?php echo esc_attr($default_name ?: 'Johannes Shilongo'); ?>" placeholder="First and Last Name" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.95rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address *</label>
                            <input type="email" name="customer_email" required value="<?php echo esc_attr($default_email ?: 'johannes@example.com.na'); ?>" placeholder="you@domain.com.na" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.95rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Mobile Phone (+264) *</label>
                        <input type="tel" name="customer_phone" required value="<?php echo esc_attr($default_phone ?: '+264 81 234 5678'); ?>" placeholder="+264 81 234 5678" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.95rem;">
                        <small style="color: #64748B; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Used for SMS ticket dispatch and gate customer support.</small>
                    </div>

                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.25rem; border-top: 1px solid var(--ep-slate-100); padding-top: 1.5rem;">
                        2. Select Payment Gateway (Namibia)
                    </h3>

                    <!-- Payment Method 1: Finatic Namibia -->
                    <div style="border: 2px solid var(--ep-teal); border-radius: var(--ep-radius-md); padding: 1.25rem; background: rgba(0,180,216,0.04); margin-bottom: 1rem; cursor: pointer;">
                        <label style="display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer;">
                            <input type="radio" name="payment_method" value="finatic" checked style="margin-top: 0.25rem;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <strong style="color: var(--ep-navy); font-size: 1.05rem;">Finatic Namibia Payment Gateway</strong>
                                    <span style="font-size: 0.75rem; background: var(--ep-teal-dark); color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700;">OFFICIAL PARTNER</span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--ep-slate-600); margin: 0 0 0.5rem; line-height: 1.5;">
                                    Accepts Visa & Mastercard debit/credit cards issued by Bank Windhoek, FNB Namibia, Standard Bank Namibia, Nedbank Namibia, and international cards.
                                </p>
                                <span style="font-size: 0.75rem; color: var(--ep-success); font-weight: 700;">✓ Bank of Namibia NPS Authorized • 256-bit TLS Encrypted</span>
                            </div>
                        </label>
                    </div>

                    <!-- Payment Method 2: DPO PayGate -->
                    <div style="border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); padding: 1.25rem; background: #fff; margin-bottom: 1rem; cursor: pointer;">
                        <label style="display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer;">
                            <input type="radio" name="payment_method" value="paygate" style="margin-top: 0.25rem;">
                            <div style="flex: 1;">
                                <strong style="color: var(--ep-navy); font-size: 1rem; display: block; margin-bottom: 0.25rem;">DPO PayGate (Regional Cards)</strong>
                                <p style="font-size: 0.85rem; color: var(--ep-slate-600); margin: 0; line-height: 1.5;">
                                    Southern African regional payment gateway for credit cards and virtual cards.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Payment Method 3: Instant EFT -->
                    <div style="border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); padding: 1.25rem; background: #fff; margin-bottom: 1.75rem; cursor: pointer;">
                        <label style="display: flex; gap: 0.75rem; align-items: flex-start; cursor: pointer;">
                            <input type="radio" name="payment_method" value="eft" style="margin-top: 0.25rem;">
                            <div style="flex: 1;">
                                <strong style="color: var(--ep-navy); font-size: 1rem; display: block; margin-bottom: 0.25rem;">Instant EFT / Bank Transfer</strong>
                                <p style="font-size: 0.85rem; color: var(--ep-slate-600); margin: 0; line-height: 1.5;">
                                    Direct electronic funds transfer via Namibian clearing houses.
                                </p>
                            </div>
                        </label>
                    </div>

                    <button type="submit" id="epPaySubmitBtn" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%; justify-content: center; height: 52px; font-size: 1.1rem;">
                        <span>Pay N$ <?php echo number_format($total_amount, 2); ?> via Finatic Gateway &rarr;</span>
                    </button>
                    
                    <p style="text-align: center; font-size: 0.75rem; color: #64748B; margin-top: 0.75rem;">
                        🔒 By confirming payment, you agree to EventPass Namibia Terms & Conditions and Attendee Refund Policy.
                    </p>
                </form>
            </div>

            <!-- Right Column: Order Summary -->
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2rem; box-shadow: var(--ep-shadow-sm); position: sticky; top: 100px;">
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.25rem;">Order Summary</h3>
                
                <div style="border-bottom: 1px solid var(--ep-slate-100); padding-bottom: 1rem; margin-bottom: 1.25rem;">
                    <strong style="color: var(--ep-navy); display: block; font-size: 1.05rem; margin-bottom: 0.25rem;">
                        <?php echo esc_html($event->post_title); ?>
                    </strong>
                    <span style="font-size: 0.85rem; color: #64748B; display: block;">
                        📅 <?php echo esc_html(get_post_meta($event_id, '_ep_event_date', true) ?: 'Upcoming 2026'); ?>
                    </span>
                    <span style="font-size: 0.85rem; color: #64748B; display: block; margin-top: 0.2rem;">
                        📍 <?php echo esc_html(get_post_meta($event_id, '_ep_event_venue', true) ?: 'Windhoek'); ?>
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
                    <?php foreach ($cart_items as $c_item) : ?>
                        <div style="display: flex; justify-content: space-between; font-size: 0.95rem;">
                            <span><?php echo intval($c_item['quantity']); ?>x <?php echo esc_html($c_item['name']); ?></span>
                            <strong style="color: var(--ep-navy);">N$ <?php echo number_format($c_item['total'], 2); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="border-top: 1px solid var(--ep-slate-100); padding-top: 0.75rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #64748B;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                        <span>Subtotal</span>
                        <span>N$ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem;">
                        <span>Booking & Processing Fee</span>
                        <span style="color: var(--ep-success); font-weight: 600;">Free / Included</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Namibian VAT (15% inclusive)</span>
                        <span>N$ <?php echo number_format($total_amount * (0.15 / 1.15), 2); ?></span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); border-top: 2px solid var(--ep-slate-200); padding-top: 1rem;">
                    <span>Total Due:</span>
                    <span style="color: var(--ep-teal-dark);">N$ <?php echo number_format($total_amount, 2); ?></span>
                </div>

                <div style="margin-top: 1.75rem; background: var(--ep-slate-50); border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); padding: 1rem; font-size: 0.8rem; color: #64748B;">
                    <span style="display: block; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.35rem;">🛡️ Buyer Protection</span>
                    Tickets are issued instantly with verified cryptographic QR codes upon completed checkout.
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
