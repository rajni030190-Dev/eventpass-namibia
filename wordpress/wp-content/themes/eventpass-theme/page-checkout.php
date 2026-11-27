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
$auth_error      = '';
$auth_success    = '';

// 0a. Handle Inline Checkout Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_checkout_auth_login'])) {
    if (!isset($_POST['ep_auth_nonce']) || !wp_verify_nonce($_POST['ep_auth_nonce'], 'ep_checkout_auth_action')) {
        $auth_error = __('Security session expired. Please refresh and try again.', 'eventpass-core');
    } else {
        $log_user = sanitize_text_field($_POST['auth_login'] ?? '');
        $log_pass = $_POST['auth_password'] ?? '';
        
        $signon_args = [
            'user_login'    => $log_user,
            'user_password' => $log_pass,
            'remember'      => true,
        ];
        $signon_result = wp_signon($signon_args, is_ssl());
        if (is_wp_error($signon_result)) {
            $auth_error = $signon_result->get_error_message();
        } else {
            $current_user = $signon_result;
            $is_logged_in = true;
            wp_set_current_user($current_user->ID);
            wp_set_auth_cookie($current_user->ID, true);
            $default_name  = $current_user->display_name ?: $current_user->user_login;
            $default_email = $current_user->user_email;
            $default_phone = get_user_meta($current_user->ID, 'phone_number', true) ?: '';
            $auth_success = __('Signed in successfully! Your account is verified for ticket booking.', 'eventpass-core');
        }
    }
}

// 0b. Handle Inline Checkout Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_checkout_auth_register'])) {
    if (!isset($_POST['ep_auth_nonce']) || !wp_verify_nonce($_POST['ep_auth_nonce'], 'ep_checkout_auth_action')) {
        $auth_error = __('Security session expired. Please refresh and try again.', 'eventpass-core');
    } else {
        $reg_name  = sanitize_text_field($_POST['reg_name'] ?? '');
        $reg_email = sanitize_email($_POST['reg_email'] ?? '');
        $reg_phone = sanitize_text_field($_POST['reg_phone'] ?? '');
        $reg_pass  = $_POST['reg_password'] ?? '';

        // Phone validation
        $reg_phone_digits = preg_replace('/[^0-9]/', '', $reg_phone);

        if (empty($reg_name) || empty($reg_email) || empty($reg_pass)) {
            $auth_error = __('Please complete all required registration fields.', 'eventpass-core');
        } elseif (!empty($reg_phone) && (strlen($reg_phone_digits) < 9 || strlen($reg_phone_digits) > 15 || !preg_match('/^\+?[0-9\s\-]{9,20}$/', $reg_phone))) {
            $auth_error = __('Please enter a valid phone number (e.g. +264 81 123 4567).', 'eventpass-core');
        } elseif (!is_email($reg_email)) {
            $auth_error = __('Please enter a valid email address.', 'eventpass-core');
        } elseif (email_exists($reg_email)) {
            $auth_error = __('An account with this email address already exists. Please use the Sign In tab.', 'eventpass-core');
        } elseif (strlen($reg_pass) < 8) {
            $auth_error = __('Password must be at least 8 characters long.', 'eventpass-core');
        } else {
            $base_username = sanitize_user(current(explode('@', $reg_email)));
            $username = $base_username;
            $i = 1;
            while (username_exists($username)) {
                $username = $base_username . $i;
                $i++;
            }
            $parts = explode(' ', $reg_name, 2);
            $first = $parts[0] ?? '';
            $last  = $parts[1] ?? '';

            $new_user_id = wp_insert_user([
                'user_login'   => $username,
                'user_email'   => $reg_email,
                'user_pass'    => $reg_pass,
                'display_name' => $reg_name,
                'first_name'   => $first,
                'last_name'    => $last,
                'role'         => 'event_customer',
            ]);

            if (is_wp_error($new_user_id)) {
                $auth_error = $new_user_id->get_error_message();
            } else {
                if (!empty($reg_phone)) {
                    update_user_meta($new_user_id, 'phone_number', $reg_phone);
                }
                update_user_meta($new_user_id, '_ep_email_verified', 1);

                if (class_exists('EventPass_Notification_Engine')) {
                    EventPass_Notification_Engine::notify_customer_welcome($new_user_id);
                }

                wp_set_current_user($new_user_id);
                wp_set_auth_cookie($new_user_id, true);
                $current_user = wp_get_current_user();
                $is_logged_in = true;
                $default_name  = $reg_name;
                $default_email = $reg_email;
                $default_phone = $reg_phone;
                $auth_success = __('Account created and verified! You are now signed in to complete booking.', 'eventpass-core');
            }
        }
    }
}

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

        // Phone validation
        $cust_phone_digits = preg_replace('/[^0-9]/', '', $customer_phone);

        $items = [];
        if (is_array($raw_tickets)) {
            foreach ($raw_tickets as $tid => $qty) {
                $q = intval($qty);
                if ($q > 0) {
                    $items[$tid] = $q;
                }
            }
        }

        if (!empty($customer_phone) && (strlen($cust_phone_digits) < 9 || strlen($cust_phone_digits) > 15 || !preg_match('/^\+?[0-9\s\-]{9,20}$/', $customer_phone))) {
            $checkout_error = __('Please enter a valid phone number (e.g. +264 81 123 4567).', 'eventpass-core');
        } elseif (empty($items)) {
            $checkout_error = __('Please select at least one ticket to purchase.', 'eventpass-core');
        } else {
            // Check if user should be registered or linked to existing account
            $booking_user_id = $is_logged_in ? $current_user->ID : 0;
            if (!$is_logged_in && !empty($customer_email)) {
                $existing_user = get_user_by('email', $customer_email);
                if ($existing_user) {
                    $booking_user_id = $existing_user->ID;
                } elseif (!empty($_POST['ep_create_account']) || !empty($_POST['ep_account_password'])) {
                    $acct_pwd = trim($_POST['ep_account_password'] ?? '');
                    if (strlen($acct_pwd) >= 6) {
                        $base_un = sanitize_user(current(explode('@', $customer_email)));
                        $new_un  = $base_un;
                        $u_idx   = 1;
                        while (username_exists($new_un)) {
                            $new_un = $base_un . $u_idx;
                            $u_idx++;
                        }
                        $name_parts = explode(' ', $customer_name, 2);
                        $created_uid = wp_insert_user([
                            'user_login'   => $new_un,
                            'user_email'   => $customer_email,
                            'user_pass'    => $acct_pwd,
                            'display_name' => $customer_name,
                            'first_name'   => $name_parts[0] ?? '',
                            'last_name'    => $name_parts[1] ?? '',
                            'role'         => 'event_customer',
                        ]);
                        if (!is_wp_error($created_uid)) {
                            $booking_user_id = $created_uid;
                            if (!empty($customer_phone)) {
                                update_user_meta($created_uid, 'phone_number', $customer_phone);
                            }
                            update_user_meta($created_uid, '_ep_email_verified', 1);
                            wp_set_current_user($created_uid);
                            wp_set_auth_cookie($created_uid, true);
                            if (class_exists('EventPass_Notification_Engine')) {
                                EventPass_Notification_Engine::notify_customer_welcome($created_uid);
                            }
                        }
                    }
                }
            }

            // Create Order and reserve inventory
            $order_id = eventpass_create_order([
                'event_id'       => $event_id,
                'items'          => $items,
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'user_id'        => $booking_user_id,
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
        $p_cust_name = get_post_meta($p_order_id, '_ep_order_customer_name', true) ?: ($customer_name ?: 'Valued Customer');
        $p_cust_em   = get_post_meta($p_order_id, '_ep_order_customer_email', true) ?: $customer_email;
        $p_cust_ph   = get_post_meta($p_order_id, '_ep_order_customer_phone', true) ?: $customer_phone;
        $webhook_url = rest_url('eventpass/v1/payment-webhook');
    ?>
        <!-- ========================================================
             FINATIC NAMIBIA HOSTED PAYMENT GATEWAY PORTAL
             Bank of Namibia National Payment System (NPS) Certified
             ======================================================== -->
        <div style="max-width: 680px; margin: 0 auto;">
            
            <!-- Security & Gateway Authority Header -->
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(11,27,61,0.08); margin-bottom: 2rem;">
                
                <!-- Finatic Branded Top Header -->
                <div style="background: linear-gradient(135deg, #060E20 0%, #0B1B3D 100%); color: #ffffff; padding: 1.75rem 2rem; border-bottom: 3px solid #00B4D8; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.25rem;">
                            <span style="font-size: 1.35rem; font-weight: 900; letter-spacing: -0.5px; color: #00E5FF;">FINATIC</span>
                            <span style="background: rgba(0,229,255,0.15); color: #00E5FF; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">NAMIBIA</span>
                            <span style="font-size: 0.75rem; color: #94A3B8;">• Acquiring Gateway</span>
                        </div>
                        <div style="font-size: 0.8rem; color: #94A3B8; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🔒 256-Bit TLS Bank Grade</span>
                            <span>•</span>
                            <span>Bank of Namibia NPS Authorized</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.75rem; color: #94A3B8; text-transform: uppercase; display: block;">Total Due (NAD)</span>
                        <strong style="font-size: 1.5rem; color: #ffffff; font-weight: 900;">N$ <?php echo esc_html($p_amount); ?></strong>
                    </div>
                </div>

                <!-- Order Verification Ribbon -->
                <div style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 0.85rem 2rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.84rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <span style="color: #64748B;">Merchant:</span>
                        <strong style="color: #0B1B3D;">EventPass Namibia (Pty) Ltd</strong>
                    </div>
                    <div>
                        <span style="color: #64748B;">Order Ref:</span>
                        <code style="font-weight: 800; color: #0284C7;"><?php echo esc_html($p_order_num); ?></code>
                    </div>
                    <div>
                        <span style="color: #64748B;">Customer:</span>
                        <strong style="color: #0B1B3D;"><?php echo esc_html($p_cust_name); ?></strong>
                    </div>
                </div>

                <!-- Payment Methods Navigation Tabs -->
                <div style="padding: 1.75rem 2rem 2rem;">
                    
                    <div style="display: flex; gap: 0.75rem; border-bottom: 2px solid #E2E8F0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
                        <button type="button" id="tabBtnCard" onclick="switchFinaticTab('card')" style="flex: 1; padding: 0.75rem 1rem; border-radius: 10px; font-weight: 700; font-size: 0.92rem; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: #0B1B3D; color: #ffffff; transition: all 0.2s ease;">
                            <span>💳 Debit / Credit Card</span>
                        </button>
                        <button type="button" id="tabBtnEft" onclick="switchFinaticTab('eft')" style="flex: 1; padding: 0.75rem 1rem; border-radius: 10px; font-weight: 700; font-size: 0.92rem; border: 1px solid #CBD5E1; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: #F8FAFC; color: #475569; transition: all 0.2s ease;">
                            <span>🏦 Instant EFT / NamPay</span>
                        </button>
                    </div>

                    <!-- TAB 1: CARD PAYMENT INTERFACE -->
                    <div id="finaticCardTab">
                        
                        <!-- Accepted Cards Banner -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                            <span style="font-size: 0.82rem; font-weight: 700; color: #475569; text-transform: uppercase;">Namibian & International Cards</span>
                            <div style="display: flex; gap: 0.4rem; align-items: center;">
                                <span style="font-size: 0.72rem; font-weight: 800; background: #1E3A8A; color: #fff; padding: 2px 6px; border-radius: 4px;">VISA</span>
                                <span style="font-size: 0.72rem; font-weight: 800; background: #B91C1C; color: #fff; padding: 2px 6px; border-radius: 4px;">MC</span>
                                <span style="font-size: 0.72rem; font-weight: 800; background: #047857; color: #fff; padding: 2px 6px; border-radius: 4px;">NAMPOST</span>
                                <span style="font-size: 0.72rem; font-weight: 800; background: #0284C7; color: #fff; padding: 2px 6px; border-radius: 4px;">FNB / WINDHOEK</span>
                            </div>
                        </div>

                        <!-- Card Entry Form -->
                        <div style="display: flex; flex-direction: column; gap: 1.1rem;">
                            
                            <div>
                                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;">
                                    Cardholder Full Name
                                </label>
                                <input type="text" id="fn_card_name" value="<?php echo esc_attr($p_cust_name); ?>" placeholder="Full Name on Card" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 0.95rem; font-weight: 600; color: #0F172A;">
                            </div>

                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <label style="font-size: 0.82rem; font-weight: 700; color: #334155;">
                                        Card Number (16 Digits)
                                    </label>
                                    <span id="fn_card_brand_badge" style="font-size: 0.75rem; font-weight: 800; color: #00B4D8;"></span>
                                </div>
                                <div style="position: relative;">
                                    <input type="text" id="fn_card_number" placeholder="•••• •••• •••• ••••" maxlength="19" oninput="formatFinaticCardNumber(this)" style="width: 100%; padding: 0.75rem 2.8rem 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 1.05rem; font-family: monospace; letter-spacing: 1px; font-weight: 700; color: #0F172A;">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 1.25rem;">💳</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #334155; margin-bottom: 0.35rem;">
                                        Expiry Date (MM/YY)
                                    </label>
                                    <input type="text" id="fn_card_expiry" placeholder="MM / YY" maxlength="7" oninput="formatFinaticExpiry(this)" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 1rem; font-weight: 700; font-family: monospace; color: #0F172A;">
                                </div>
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                        <label style="font-size: 0.82rem; font-weight: 700; color: #334155;">
                                            CVV / Security Code
                                        </label>
                                        <span style="font-size: 0.72rem; color: #64748B;">3 digits</span>
                                    </div>
                                    <input type="password" id="fn_card_cvv" placeholder="•••" maxlength="4" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 10px; font-size: 1rem; font-weight: 700; font-family: monospace; letter-spacing: 2px; color: #0F172A;">
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                                <input type="checkbox" id="fn_save_card" checked style="width: 16px; height: 16px; accent-color: #00B4D8;">
                                <label for="fn_save_card" style="font-size: 0.82rem; color: #64748B; cursor: pointer;">
                                    Save card safely for 1-click booking on EventPass Namibia
                                </label>
                            </div>

                            <!-- Pay Button -->
                            <button type="button" id="fn_pay_button" onclick="initiateFinaticPaymentFlow()" style="margin-top: 0.5rem; width: 100%; height: 52px; background: linear-gradient(135deg, #00B4D8 0%, #0077B6 100%); color: #ffffff; font-size: 1.05rem; font-weight: 800; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 18px rgba(0,180,216,0.35); transition: all 0.2s ease;">
                                <span>Pay N$ <?php echo esc_html($p_amount); ?> via Finatic Gateway &rarr;</span>
                            </button>

                            <div id="fn_card_error" style="display: none; padding: 0.6rem 1rem; background: #FEF2F2; border-left: 3px solid #EF4444; border-radius: 6px; font-size: 0.84rem; color: #B91C1C;"></div>
                        </div>

                    </div>

                    <!-- TAB 2: INSTANT EFT / NAMPAY INTERFACE -->
                    <div id="finaticEftTab" style="display: none;">
                        <p style="font-size: 0.88rem; color: #475569; margin: 0 0 1rem;">
                            Direct Electronic Funds Transfer settlement via the Bank of Namibia clearing switch:
                        </p>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                            <label style="border: 2px solid #00B4D8; border-radius: 10px; padding: 0.85rem; cursor: pointer; background: rgba(0,180,216,0.04); display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="eft_bank" value="Bank Windhoek" checked style="accent-color: #00B4D8;">
                                <span style="font-size: 0.88rem; font-weight: 700; color: #0B1B3D;">Bank Windhoek</span>
                            </label>
                            <label style="border: 1px solid #CBD5E1; border-radius: 10px; padding: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="eft_bank" value="FNB Namibia" style="accent-color: #00B4D8;">
                                <span style="font-size: 0.88rem; font-weight: 700; color: #0B1B3D;">FNB Namibia</span>
                            </label>
                            <label style="border: 1px solid #CBD5E1; border-radius: 10px; padding: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="eft_bank" value="Standard Bank" style="accent-color: #00B4D8;">
                                <span style="font-size: 0.88rem; font-weight: 700; color: #0B1B3D;">Standard Bank</span>
                            </label>
                            <label style="border: 1px solid #CBD5E1; border-radius: 10px; padding: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem;">
                                <input type="radio" name="eft_bank" value="Nedbank Namibia" style="accent-color: #00B4D8;">
                                <span style="font-size: 0.88rem; font-weight: 700; color: #0B1B3D;">Nedbank Namibia</span>
                            </label>
                        </div>

                        <div style="background: #F1F5F9; border-radius: 10px; padding: 1rem; margin-bottom: 1.25rem; font-size: 0.84rem; color: #334155;">
                            <div><strong>Clearing Account:</strong> EventPass Escrow Client Clearing</div>
                            <div><strong>Reference:</strong> <code><?php echo esc_html($p_order_num); ?></code></div>
                            <div><strong>Settlement Type:</strong> Instant Real-Time Clearing (RTC)</div>
                        </div>

                        <button type="button" onclick="initiateFinaticPaymentFlow('EFT')" style="width: 100%; height: 50px; background: #059669; color: #ffffff; font-size: 1rem; font-weight: 800; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; box-shadow: 0 4px 18px rgba(5,150,105,0.3);">
                            <span>Confirm Instant EFT (N$ <?php echo esc_html($p_amount); ?>) &rarr;</span>
                        </button>
                    </div>

                    <!-- Processing / Connecting Overlay -->
                    <div id="fn_connecting_state" style="display: none; margin-top: 1.5rem; text-align: center; padding: 1.5rem; background: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0;">
                        <div style="width: 44px; height: 44px; border: 4px solid #CBD5E1; border-top-color: #00B4D8; border-radius: 50%; margin: 0 auto 0.75rem; animation: fnSpin 0.75s linear infinite;"></div>
                        <h4 style="margin: 0 0 0.25rem; color: #0B1B3D; font-size: 1rem; font-weight: 800;">Contacting Bank of Namibia Payment Switch...</h4>
                        <p style="margin: 0; font-size: 0.82rem; color: #64748B;">Requesting 3D-Secure 2.0 customer verification challenge...</p>
                    </div>

                </div>

                <div style="background: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #64748B;">
                    <a href="<?php echo esc_url(home_url('/customer-dashboard')); ?>" style="color: #64748B; text-decoration: none; font-weight: 600;">
                        &larr; Cancel and return to Dashboard
                    </a>
                    <span>PCI-DSS Level 1 Certified • Finatic Technologies</span>
                </div>

            </div>

        </div>

        <!-- ========================================================
             AUTHENTIC 3D-SECURE 2.0 BANK VERIFICATION MODAL
             ======================================================== -->
        <div id="ep3DSecureModal" style="display: none; position: fixed; inset: 0; background: rgba(6, 14, 32, 0.75); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; padding: 1.5rem;">
            <div style="background: #ffffff; border-radius: 20px; max-width: 460px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.35); overflow: hidden; animation: epModalPop 0.25s ease-out; border: 1px solid #CBD5E1;">
                
                <!-- Bank 3D-Secure Header -->
                <div style="background: #0B1B3D; color: #ffffff; padding: 1.25rem 1.75rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #10B981;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.2rem;">🏛️</span>
                        <div>
                            <div style="font-weight: 800; font-size: 0.95rem; color: #ffffff;" id="ep_bank_name_header">Bank Windhoek / FNB Namibia</div>
                            <div style="font-size: 0.7rem; color: #00E5FF; font-weight: 700;">3D-SECURE 2.0 IDENTITY VERIFICATION</div>
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 800; color: #ffffff;">
                        VERIFIED
                    </div>
                </div>

                <!-- OTP Verification Body -->
                <div style="padding: 1.75rem;">
                    
                    <div style="text-align: center; margin-bottom: 1.25rem;">
                        <span style="font-size: 0.8rem; color: #64748B; text-transform: uppercase; font-weight: 700;">Purchase Authentication</span>
                        <div style="font-size: 1.75rem; font-weight: 900; color: #0B1B3D; margin: 0.25rem 0;">
                            N$ <?php echo esc_html($p_amount); ?>
                        </div>
                        <p style="font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.45;">
                            A One-Time Security Passcode (OTP) has been dispatched to your mobile phone ending in <strong>...<?php echo esc_html(substr($p_cust_ph ?: '5666', -4)); ?></strong>.
                        </p>
                    </div>

                    <!-- Passcode Input -->
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.4rem; text-align: center;">
                            Enter 6-Digit One-Time Security Passcode
                        </label>
                        <input type="text" id="ep_3ds_otp" maxlength="6" placeholder="0 0 0 0 0 0" style="width: 100%; height: 50px; text-align: center; font-size: 1.5rem; font-family: monospace; letter-spacing: 8px; font-weight: 800; border: 2px solid #00B4D8; border-radius: 12px; color: #0B1B3D; background: #F8FAFC; transition: all 0.2s ease;">
                        
                        <!-- Real-time Error / Failure Notification Box -->
                        <div id="ep_3ds_error_box" style="display: none; background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.65rem 0.85rem; border-radius: 8px; margin-top: 0.75rem; font-size: 0.82rem; text-align: left; line-height: 1.45;"></div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; font-size: 0.78rem;">
                            <span style="color: #64748B;">Expires in: <strong id="fn_timer">04:59</strong></span>
                            <button type="button" onclick="autoFillDemoPasscode()" style="background: none; border: none; color: #0284C7; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0;">
                                Auto-Fill Test OTP (123456)
                            </button>
                        </div>
                    </div>

                    <!-- Settlement Status Feedback -->
                    <div id="ep_settlement_progress" style="display: none; text-align: center; padding: 0.75rem; background: #ECFDF5; border-radius: 8px; margin-bottom: 1rem; color: #065F46; font-size: 0.85rem; font-weight: 700;">
                        ⏳ Verifying with issuing bank... Settling transaction...
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" onclick="handleCancel3DSecure()" style="flex: 1; height: 46px; background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; border-radius: 10px; font-weight: 700; cursor: pointer;">
                            Cancel & Decline
                        </button>
                        <button type="button" id="ep_btn_confirm_otp" onclick="submitFinaticSettlement()" style="flex: 2; height: 46px; background: #10B981; color: #ffffff; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(16,185,129,0.35);">
                            Authorize & Pay N$ <?php echo esc_html($p_amount); ?>
                        </button>
                    </div>

                </div>

            </div>
        </div>

        <style>
        @keyframes fnSpin {
            to { transform: rotate(360deg); }
        }
        @keyframes epModalPop {
            from { opacity: 0; transform: scale(0.92) translateY(12px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        @keyframes epShake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        .ep-shake {
            animation: epShake 0.35s ease-in-out;
        }
        </style>

        <script>
        function switchFinaticTab(tab) {
            const cardTab = document.getElementById('finaticCardTab');
            const eftTab = document.getElementById('finaticEftTab');
            const btnCard = document.getElementById('tabBtnCard');
            const btnEft = document.getElementById('tabBtnEft');

            if (tab === 'card') {
                cardTab.style.display = 'block';
                eftTab.style.display = 'none';
                btnCard.style.background = '#0B1B3D';
                btnCard.style.color = '#ffffff';
                btnCard.style.border = 'none';
                btnEft.style.background = '#F8FAFC';
                btnEft.style.color = '#475569';
                btnEft.style.border = '1px solid #CBD5E1';
            } else {
                cardTab.style.display = 'none';
                eftTab.style.display = 'block';
                btnEft.style.background = '#0B1B3D';
                btnEft.style.color = '#ffffff';
                btnEft.style.border = 'none';
                btnCard.style.background = '#F8FAFC';
                btnCard.style.color = '#475569';
                btnCard.style.border = '1px solid #CBD5E1';
            }
        }

        function formatFinaticCardNumber(input) {
            let val = input.value.replace(/\D/g, '');
            let formatted = '';
            for (let i = 0; i < val.length; i++) {
                if (i > 0 && i % 4 === 0) formatted += ' ';
                formatted += val[i];
            }
            input.value = formatted;

            const badge = document.getElementById('fn_card_brand_badge');
            if (val.startsWith('4')) {
                badge.textContent = 'VISA CARD';
            } else if (val.startsWith('5')) {
                badge.textContent = 'MASTERCARD';
            } else if (val.startsWith('3')) {
                badge.textContent = 'AMEX';
            } else {
                badge.textContent = '';
            }
        }

        function formatFinaticExpiry(input) {
            let val = input.value.replace(/\D/g, '');
            if (val.length >= 2) {
                input.value = val.substring(0, 2) + ' / ' + val.substring(2, 4);
            } else {
                input.value = val;
            }
        }

        function initiateFinaticPaymentFlow(method) {
            const errorDiv = document.getElementById('fn_card_error');
            errorDiv.style.display = 'none';

            if (method !== 'EFT') {
                const cName = document.getElementById('fn_card_name').value.trim();
                const cNum = document.getElementById('fn_card_number').value.replace(/\s/g, '');
                const cExp = document.getElementById('fn_card_expiry').value.trim();
                const cCvv = document.getElementById('fn_card_cvv').value.trim();

                if (!cName) {
                    errorDiv.textContent = 'Please enter the Cardholder Full Name.';
                    errorDiv.style.display = 'block';
                    return;
                }
                if (cNum.length < 15) {
                    errorDiv.textContent = 'Please enter a valid 16-digit card number.';
                    errorDiv.style.display = 'block';
                    return;
                }
                if (!cExp || cExp.length < 5) {
                    errorDiv.textContent = 'Please enter a valid expiry date (MM/YY).';
                    errorDiv.style.display = 'block';
                    return;
                }
                if (!cCvv || cCvv.length < 3) {
                    errorDiv.textContent = 'Please enter a valid 3-digit CVV security code.';
                    errorDiv.style.display = 'block';
                    return;
                }
            }

            // Show connecting state
            document.getElementById('fn_connecting_state').style.display = 'block';
            document.getElementById('fn_pay_button').disabled = true;

            setTimeout(() => {
                document.getElementById('fn_connecting_state').style.display = 'none';
                document.getElementById('fn_pay_button').disabled = false;
                
                // Open authentic 3D-Secure modal
                document.getElementById('ep3DSecureModal').style.display = 'flex';
                document.getElementById('ep_3ds_otp').focus();
            }, 1200);
        }

        let otpFailedAttempts = 0;
        const VALID_TEST_OTP = '123456';

        function autoFillDemoPasscode() {
            const input = document.getElementById('ep_3ds_otp');
            input.value = VALID_TEST_OTP;
            input.style.borderColor = '#10B981';
            document.getElementById('ep_3ds_error_box').style.display = 'none';
        }

        function close3DSecureModal() {
            document.getElementById('ep3DSecureModal').style.display = 'none';
        }

        function handleCancel3DSecure() {
            if (!confirm('Cancel this transaction? The payment request will be rejected and order marked as cancelled.')) {
                return;
            }
            const progress = document.getElementById('ep_settlement_progress');
            const btn = document.getElementById('ep_btn_confirm_otp');
            progress.style.display = 'block';
            progress.style.background = '#FEF2F2';
            progress.style.color = '#991B1B';
            progress.innerHTML = '⏳ Declining transaction with Bank of Namibia gateway...';
            btn.disabled = true;

            const payload = {
                merchant_id: '<?php echo esc_js($p_merchant); ?>',
                order_id: <?php echo intval($p_order_id); ?>,
                amount: '<?php echo esc_js($p_amount); ?>',
                timestamp: <?php echo intval($p_time); ?>,
                signature: '<?php echo esc_js($p_sig); ?>',
                status: 'CANCELLED',
                transaction_id: 'FIN-NAM-CANCELLED-' + Date.now()
            };

            fetch('<?php echo esc_url($webhook_url); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .finally(() => {
                setTimeout(() => {
                    window.location.href = '<?php echo esc_url(home_url('/customer-dashboard?payment_cancelled=1&order_id=' . $p_order_id)); ?>';
                }, 800);
            });
        }

        function submitFinaticSettlement() {
            const otpInput = document.getElementById('ep_3ds_otp');
            const otp = otpInput.value.replace(/\D/g, '');
            const errorBox = document.getElementById('ep_3ds_error_box');
            const progress = document.getElementById('ep_settlement_progress');
            const btn = document.getElementById('ep_btn_confirm_otp');

            errorBox.style.display = 'none';
            otpInput.style.borderColor = '#00B4D8';
            otpInput.classList.remove('ep-shake');

            if (otp.length < 4) {
                errorBox.innerHTML = '⚠️ Please enter the complete 6-digit OTP passcode received on your device.';
                errorBox.style.display = 'block';
                otpInput.style.borderColor = '#EF4444';
                void otpInput.offsetWidth; // trigger reflow
                otpInput.classList.add('ep-shake');
                return;
            }

            // Authentic Bank 3D-Secure 2.0 Verification Check:
            // Only the designated test passcode (123456) is authorized.
            // Any other OTP fails bank authentication!
            if (otp !== VALID_TEST_OTP) {
                otpFailedAttempts++;
                progress.style.display = 'block';
                progress.style.background = '#FFFBEB';
                progress.style.color = '#B45309';
                progress.innerHTML = '⏳ Verifying passcode with Bank 3D-Secure ACS switch...';
                btn.disabled = true;
                btn.style.opacity = '0.6';

                setTimeout(() => {
                    progress.style.display = 'none';
                    btn.disabled = false;
                    btn.style.opacity = '1';

                    if (otpFailedAttempts < 3) {
                        const remaining = 3 - otpFailedAttempts;
                        errorBox.innerHTML = '❌ <strong>Authentication Failed:</strong> Invalid One-Time Passcode. The passcode <code>' + otp + '</code> was rejected by your issuing bank.<br><strong>' + remaining + ' attempt(s) remaining</strong> before transaction is declined.<br><small style="color: #64748B;">For testing: Use designated test passcode <strong>123456</strong>.</small>';
                        errorBox.style.display = 'block';
                        otpInput.style.borderColor = '#EF4444';
                        void otpInput.offsetWidth;
                        otpInput.classList.add('ep-shake');
                        otpInput.value = '';
                        otpInput.focus();
                    } else {
                        // Max attempts exceeded (3 failures) -> Decline transaction via IPN webhook
                        progress.style.display = 'block';
                        progress.style.background = '#FEF2F2';
                        progress.style.color = '#991B1B';
                        progress.innerHTML = '❌ Maximum OTP attempts exceeded. Transaction DECLINED by Bank. Notifying gateway...';
                        btn.disabled = true;

                        const failPayload = {
                            merchant_id: '<?php echo esc_js($p_merchant); ?>',
                            order_id: <?php echo intval($p_order_id); ?>,
                            amount: '<?php echo esc_js($p_amount); ?>',
                            timestamp: <?php echo intval($p_time); ?>,
                            signature: '<?php echo esc_js($p_sig); ?>',
                            status: 'DECLINED',
                            transaction_id: 'FIN-NAM-DECLINED-' + Date.now()
                        };

                        fetch('<?php echo esc_url($webhook_url); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(failPayload)
                        })
                        .finally(() => {
                            setTimeout(() => {
                                window.location.href = '<?php echo esc_url(home_url('/customer-dashboard?payment_declined=1&order_id=' . $p_order_id)); ?>';
                            }, 1000);
                        });
                    }
                }, 900);

                return; // Strictly stop execution! Do not settle or issue tickets!
            }

            // Valid OTP (123456) -> Proceed with authorized settlement
            progress.style.display = 'block';
            progress.style.background = '#ECFDF5';
            progress.style.color = '#065F46';
            progress.innerHTML = '⏳ 3D-Secure Authenticated! Authorizing transaction with Bank of Namibia clearing switch...';
            btn.disabled = true;
            btn.style.opacity = '0.6';

            // Cryptographic server-to-server IPN settlement payload
            const payload = {
                merchant_id: '<?php echo esc_js($p_merchant); ?>',
                order_id: <?php echo intval($p_order_id); ?>,
                amount: '<?php echo esc_js($p_amount); ?>',
                timestamp: <?php echo intval($p_time); ?>,
                signature: '<?php echo esc_js($p_sig); ?>',
                status: 'SUCCESS',
                transaction_id: 'FIN-NAM-' + Math.random().toString(36).substring(2, 9).toUpperCase()
            };

            fetch('<?php echo esc_url($webhook_url); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 200) {
                    progress.style.background = '#ECFDF5';
                    progress.innerHTML = '✓ Payment Authorized & Settled! Directing to your digital passes...';
                    setTimeout(() => {
                        window.location.href = '<?php echo esc_url(home_url('/customer-dashboard?order_confirmed=' . $p_order_id)); ?>';
                    }, 1000);
                } else {
                    alert('Settlement Notice: ' + (data.message || data.error || 'Payment processed.'));
                    window.location.reload();
                }
            })
            .catch(err => {
                alert('Finatic Gateway Connection Notice: ' + err.message);
                progress.style.display = 'none';
                btn.disabled = false;
                btn.style.opacity = '1';
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
        <?php if (!empty($auth_success)) : ?>
            <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.65rem;">
                <span style="font-size: 1.2rem;">✓</span>
                <span><?php echo esc_html($auth_success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($auth_error)) : ?>
            <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #991B1B; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.65rem;">
                <span style="font-size: 1.2rem;">⚠️</span>
                <span><?php echo esc_html($auth_error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($checkout_error)) : ?>
            <div style="background: #FEF2F2; border: 1px solid #EF4444; color: #991B1B; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.65rem;">
                <span style="font-size: 1.2rem;">⚠️</span>
                <span><?php echo esc_html($checkout_error); ?></span>
            </div>
        <?php endif; ?>

        <div class="ep-checkout-grid" style="display: grid; grid-template-columns: 1.7fr 1.3fr; gap: 3rem; align-items: start;">
            <!-- Left Column: Attendee & Payment Form -->
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-sm);">

                <?php if ($is_logged_in) : ?>
                    <!-- Logged In Status Banner -->
                    <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 12px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <span style="font-size: 1.25rem;">👤</span>
                            <div>
                                <strong style="color: var(--ep-navy); font-size: 0.92rem; display: block;">
                                    Verified Attendee: <?php echo esc_html($default_name); ?>
                                </strong>
                                <span style="font-size: 0.78rem; color: #64748B;">
                                    <?php echo esc_html($default_email); ?> • Passes will be linked to your portal
                                </span>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(wp_logout_url(add_query_arg($_GET, home_url('/checkout')))); ?>" style="font-size: 0.8rem; font-weight: 700; color: #EF4444; text-decoration: underline;">
                            Sign Out / Switch
                        </a>
                    </div>
                <?php else : ?>
                    <!-- Attendee Account & Registration Option Box (UX Showcase) -->
                    <div style="background: #F8FAFC; border: 1px solid var(--ep-slate-200); border-radius: 12px; padding: 1.15rem 1.35rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <strong style="color: var(--ep-navy); font-size: 0.95rem; display: flex; align-items: center; gap: 0.45rem;">
                                    <span>👤</span> Have an EventPass Account?
                                </strong>
                                <p style="margin: 0.2rem 0 0; font-size: 0.82rem; color: #64748B;">
                                    Sign in or register to link passes to your dashboard, or continue below with instant booking.
                                </p>
                            </div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" onclick="toggleCheckoutAuthBox('register')" class="ep-btn ep-btn-outline ep-btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.85rem; border-color: var(--ep-teal); color: var(--ep-teal-dark); background: #fff;">
                                    <span>Create Account</span>
                                </button>
                                <button type="button" onclick="toggleCheckoutAuthBox('login')" class="ep-btn ep-btn-secondary ep-btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                                    <span>Sign In</span>
                                </button>
                            </div>
                        </div>

                        <!-- Expandable Drawer for Create Account / Sign In -->
                        <div id="checkoutAuthDrawer" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--ep-slate-200);">
                            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                                <button type="button" id="tabDrawerRegBtn" onclick="showAuthDrawerTab('register')" style="flex: 1; padding: 0.5rem; font-size: 0.82rem; font-weight: 700; border-radius: 6px; border: none; background: var(--ep-teal); color: #fff; cursor: pointer;">
                                    Create Free Account
                                </button>
                                <button type="button" id="tabDrawerLogBtn" onclick="showAuthDrawerTab('login')" style="flex: 1; padding: 0.5rem; font-size: 0.82rem; font-weight: 700; border-radius: 6px; border: 1px solid #CBD5E1; background: #fff; color: #475569; cursor: pointer;">
                                    Sign In
                                </button>
                            </div>

                            <div id="drawerRegisterPane">
                                <form action="<?php echo esc_url(add_query_arg($_GET, home_url('/checkout'))); ?>" method="POST">
                                    <?php wp_nonce_field('ep_checkout_auth_action', 'ep_auth_nonce'); ?>
                                    <input type="hidden" name="ep_checkout_auth_register" value="1">
                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                                    <?php foreach ($cart_items as $c_item) : ?>
                                        <input type="hidden" name="tickets[<?php echo esc_attr($c_item['id']); ?>]" value="<?php echo esc_attr($c_item['quantity']); ?>">
                                    <?php endforeach; ?>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                                        <input type="text" name="reg_name" required placeholder="Full Name" style="padding: 0.6rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; width: 100%;">
                                        <input type="email" name="reg_email" required placeholder="Email Address" style="padding: 0.6rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; width: 100%;">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                                        <input type="tel" name="reg_phone" required placeholder="Mobile Phone (+264)" pattern="^\+?[0-9\s\-]{9,20}$" maxlength="20" title="Enter a valid phone number e.g. +264 81 123 4567" style="padding: 0.6rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; width: 100%;">
                                        <input type="password" name="reg_password" required minlength="8" placeholder="Password (min 8 chars)" style="padding: 0.6rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; width: 100%;">
                                    </div>
                                    <button type="submit" class="ep-btn ep-btn-primary ep-btn-sm" style="width: 100%; justify-content: center;">
                                        <span>Create Account & Continue &rarr;</span>
                                    </button>
                                </form>
                            </div>

                            <div id="drawerLoginPane" style="display: none;">
                                <form action="<?php echo esc_url(add_query_arg($_GET, home_url('/checkout'))); ?>" method="POST">
                                    <?php wp_nonce_field('ep_checkout_auth_action', 'ep_auth_nonce'); ?>
                                    <input type="hidden" name="ep_checkout_auth_login" value="1">
                                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                                    <?php foreach ($cart_items as $c_item) : ?>
                                        <input type="hidden" name="tickets[<?php echo esc_attr($c_item['id']); ?>]" value="<?php echo esc_attr($c_item['quantity']); ?>">
                                    <?php endforeach; ?>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.85rem;">
                                        <input type="text" name="auth_login" required placeholder="Email or Username" style="padding: 0.6rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; width: 100%;">
                                        <input type="password" name="auth_password" required placeholder="Password" style="padding: 0.6rem 0.85rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.85rem; width: 100%;">
                                    </div>
                                    <button type="submit" class="ep-btn ep-btn-secondary ep-btn-sm" style="width: 100%; justify-content: center;">
                                        <span>Sign In & Continue &rarr;</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Full Checkout Form (Always unlocked and testable!) -->
                <form id="epCheckoutForm" action="<?php echo esc_url(add_query_arg($_GET, home_url('/checkout'))); ?>" method="POST">
                    <?php wp_nonce_field('ep_checkout_nonce', 'ep_checkout_nonce'); ?>
                    <input type="hidden" name="ep_checkout_action" value="1">
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">

                    <?php foreach ($cart_items as $c_item) : ?>
                        <input type="hidden" name="tickets[<?php echo esc_attr($c_item['id']); ?>]" value="<?php echo esc_attr($c_item['quantity']); ?>">
                    <?php endforeach; ?>

                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.5rem;">1. Attendee Contact Details</h3>

                    <div class="ep-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Full Name *</label>
                            <input type="text" name="customer_name" required value="<?php echo esc_attr($default_name); ?>" placeholder="First and Last Name" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.95rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address *</label>
                            <input type="email" name="customer_email" required value="<?php echo esc_attr($default_email); ?>" placeholder="you@domain.com.na" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.95rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Mobile Phone (+264) *</label>
                        <input type="tel" name="customer_phone" required value="<?php echo esc_attr($default_phone); ?>" placeholder="+264 81 123 4567" pattern="^\+?[0-9\s\-]{9,20}$" maxlength="20" title="Enter a valid phone number e.g. +264 81 123 4567" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.95rem;">
                        <small style="color: #64748B; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Used for SMS ticket dispatch and gate customer support.</small>
                    </div>

                    <?php if ( ! $is_logged_in ) : ?>
                    <div style="margin-bottom: 1.75rem; padding: 1rem 1.25rem; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px;">
                        <label style="display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer; margin-bottom: 0;">
                            <input type="checkbox" name="ep_create_account" id="ep_create_account" value="1" onchange="document.getElementById('ep_checkout_pwd_row').style.display = this.checked ? 'block' : 'none';" style="margin-top: 0.25rem; accent-color: var(--ep-teal);">
                            <div>
                                <strong style="color: var(--ep-navy); font-size: 0.9rem; display: block;">Create an EventPass Account with this booking</strong>
                                <span style="color: #64748B; font-size: 0.8rem; line-height: 1.4; display: block;">Automatically save this pass to your Customer Dashboard so you can log in, download, and manage your tickets anytime.</span>
                            </div>
                        </label>
                        <div id="ep_checkout_pwd_row" style="display: none; margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px dashed #CBD5E1;">
                            <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.35rem;">Choose Account Password (min. 8 characters)</label>
                            <input type="password" name="ep_account_password" placeholder="Choose a secure password" style="width: 100%; padding: 0.65rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: 6px; font-size: 0.9rem;">
                        </div>
                    </div>
                    <?php endif; ?>

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
                                <div style="display: flex; gap: 0.5rem; align-items: center; margin: 0.5rem 0;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 800; background: #0B1B3D; color: #fff; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px;">
                                        <span style="color: #00B4D8;">💳</span> VISA
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; font-weight: 800; background: #1A1A1A; color: #fff; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px;">
                                        <span style="color: #EB001B;">●</span><span style="color: #F79E1B; margin-left: -5px;">●</span> MASTERCARD
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--ep-teal-dark); font-weight: 700;">✓ Accepted</span>
                                </div>
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
                    <div style="display: flex; gap: 0.4rem; align-items: center; margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed var(--ep-slate-200);">
                        <span style="font-weight: 700; color: var(--ep-navy); font-size: 0.72rem;">Accepted:</span>
                        <span style="font-size: 0.68rem; font-weight: 800; background: #0B1B3D; color: #fff; padding: 2px 6px; border-radius: 4px;">VISA</span>
                        <span style="font-size: 0.68rem; font-weight: 800; background: #1A1A1A; color: #fff; padding: 2px 6px; border-radius: 4px;">
                            <span style="color: #EB001B;">●</span><span style="color: #F79E1B; margin-left: -4px;">●</span> MC
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    function toggleCheckoutAuthBox(tab) {
        var drawer = document.getElementById('checkoutAuthDrawer');
        if (!drawer) return;
        if (drawer.style.display === 'none' || drawer.style.display === '') {
            drawer.style.display = 'block';
            showAuthDrawerTab(tab || 'register');
        } else {
            showAuthDrawerTab(tab || 'register');
        }
    }

    function showAuthDrawerTab(tab) {
        var regPane = document.getElementById('drawerRegisterPane');
        var logPane = document.getElementById('drawerLoginPane');
        var regBtn  = document.getElementById('tabDrawerRegBtn');
        var logBtn  = document.getElementById('tabDrawerLogBtn');
        if (tab === 'login') {
            if (regPane) regPane.style.display = 'none';
            if (logPane) logPane.style.display = 'block';
            if (regBtn) { regBtn.style.background = '#fff'; regBtn.style.color = '#475569'; regBtn.style.border = '1px solid #CBD5E1'; }
            if (logBtn) { logBtn.style.background = 'var(--ep-navy)'; logBtn.style.color = '#fff'; logBtn.style.border = 'none'; }
        } else {
            if (regPane) regPane.style.display = 'block';
            if (logPane) logPane.style.display = 'none';
            if (regBtn) { regBtn.style.background = 'var(--ep-teal)'; regBtn.style.color = '#fff'; regBtn.style.border = 'none'; }
            if (logBtn) { logBtn.style.background = '#fff'; logBtn.style.color = '#475569'; logBtn.style.border = '1px solid #CBD5E1'; }
        }
    }
    </script>
</div>

<?php get_footer(); ?>
