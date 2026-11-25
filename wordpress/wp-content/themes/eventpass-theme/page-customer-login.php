<?php
/**
 * Template Name: Customer Login
 *
 * @package EventPass_Namibia
 */

$action      = sanitize_text_field($_REQUEST['action'] ?? 'login');
$redirect_to = !empty($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : '';

// Helper: Role-aware destination router
if (!function_exists('ep_get_role_destination_url')) {
    function ep_get_role_destination_url($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) {
            return home_url('/');
        }
        if (user_can($user, 'manage_options')) {
            return home_url('/admin-dashboard');
        }
        $roles = (array) $user->roles;
        if (in_array('ep_organiser', $roles) || in_array('event_organiser', $roles)) {
            return home_url('/organiser-dashboard');
        }
        return home_url('/customer-dashboard');
    }
}

// -----------------------------------------------------------------------------
// 1. Action: Verify Customer Email Address (SOW 4.2)
// -----------------------------------------------------------------------------
if ($action === 'verify_email') {
    $token = sanitize_text_field($_GET['token'] ?? '');
    $uid   = intval($_GET['uid'] ?? 0);
    $stored_token = get_user_meta($uid, '_ep_email_verify_token', true);

    if ($uid > 0 && !empty($token) && hash_equals((string)$stored_token, (string)$token)) {
        update_user_meta($uid, '_ep_email_verified', 1);
        delete_user_meta($uid, '_ep_email_verify_token');

        // Auto sign-in if not logged in
        if (!is_user_logged_in()) {
            wp_set_current_user($uid);
            wp_set_auth_cookie($uid, true);
        }

        wp_redirect(home_url('/customer-dashboard/?email_verified=1'));
        exit;
    } else {
        $login_error = 'This email verification link is invalid or has already been used.';
        $action = 'login';
    }
}

// -----------------------------------------------------------------------------
// 1b. Action: Verify Organiser Business Email (Independent of Admin Vetting)
// -----------------------------------------------------------------------------
if ($action === 'verify_org_email') {
    $token  = sanitize_text_field($_GET['token'] ?? '');
    $app_id = intval($_GET['app_id'] ?? 0);
    $stored_token = get_post_meta($app_id, '_ep_org_email_verify_token', true);

    if ($app_id > 0 && !empty($token) && hash_equals((string)$stored_token, (string)$token)) {
        update_post_meta($app_id, '_ep_org_email_verified', 1);
        delete_post_meta($app_id, '_ep_org_email_verify_token');

        $login_success = '✓ Organiser business email verified successfully! Your application has been flagged as email-verified for administrative vetting.';
        $action = 'login';
    } else {
        $login_error = 'This organiser email verification link is invalid or has already been used.';
        $action = 'login';
    }
}

// If already logged in and visiting normal login/recovery, redirect to destination
if (is_user_logged_in() && $action === 'login') {
    $current_logged_user = wp_get_current_user();
    // If target requires admin rights and current user lacks them, allow staying on login to switch accounts
    if (!empty($redirect_to) && strpos($redirect_to, 'admin-dashboard') !== false && !user_can($current_logged_user, 'manage_options')) {
        $login_error = 'You are currently logged in as ' . esc_html($current_logged_user->display_name ?: $current_logged_user->user_login) . ' (Customer Account). Please sign in with an Administrator account to access the Admin Hub, or <a href="' . esc_url(wp_logout_url(add_query_arg(['redirect_to' => urlencode($redirect_to)], home_url('/customer-login/')))) . '" style="text-decoration:underline; font-weight:bold;">Sign Out</a>.';
    } else {
        if (!empty($redirect_to) && wp_validate_redirect($redirect_to)) {
            wp_safe_redirect($redirect_to);
        } else {
            wp_redirect(ep_get_role_destination_url());
        }
        exit;
    }
}

$login_error        = '';
$login_success      = '';
$dev_otp_hint       = '';
$target_user_login  = sanitize_text_field($_REQUEST['user_login'] ?? '');
$target_user_email  = '';
$reset_session_token = sanitize_text_field($_REQUEST['reset_token'] ?? '');

// -----------------------------------------------------------------------------
// 2. Action: Request Password Reset OTP (Step 1 - SOW 4.2)
// -----------------------------------------------------------------------------
if ($action === 'lostpassword' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_lostpass_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_lostpass_nonce'], 'ep_lostpass_action')) {
        $login_error = 'Security session expired. Please refresh and try again.';
    } else {
        $user_input = sanitize_text_field($_POST['user_login'] ?? '');
        if (empty($user_input)) {
            $login_error = 'Please enter your registered email address or username.';
        } else {
            $user = null;
            if (is_email($user_input)) {
                $user = get_user_by('email', $user_input);
            }
            if (!$user) {
                $user = get_user_by('login', $user_input);
            }
            if (!$user && strtolower($user_input) === 'admin@eventpass.com.na') {
                $user = get_user_by('login', 'admin');
            }

            if (!$user) {
                $login_error = 'No account found matching that email address or username.';
            } else {
                // Generate genuine 6-digit numeric OTP
                $otp = sprintf('%06d', wp_rand(100000, 999999));
                update_user_meta($user->ID, '_ep_pwd_reset_otp', wp_hash_password($otp));
                update_user_meta($user->ID, '_ep_pwd_reset_otp_exp', time() + 900); // 15 mins
                update_user_meta($user->ID, '_ep_pwd_reset_otp_attempts', 0);

                // Dispatch genuine OTP email via Brevo / Central Notification Engine
                $dispatched = false;
                if (class_exists('EventPass_Notification_Engine')) {
                    $dispatched = EventPass_Notification_Engine::notify_password_reset_otp($user->ID, $otp);
                }

                $target_user_login = $user->user_login;
                $em = $user->user_email;
                $parts = explode('@', $em);
                $target_user_email = (strlen($parts[0]) > 2 ? substr($parts[0], 0, 2) . '***' : $parts[0]) . '@' . ($parts[1] ?? 'domain.com');

                $action = 'verify_otp';
                $login_success = 'A 6-digit verification code has been dispatched to your email address (' . esc_html($target_user_email) . ').';


            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2b. Action: Verify 6-Digit OTP Code (Step 2 - SOW 4.2)
// -----------------------------------------------------------------------------
if ($action === 'verify_otp' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_verify_otp_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_verify_otp_nonce'], 'ep_verify_otp_action')) {
        $login_error = 'Security session expired. Please try again.';
    } else {
        $u_login  = sanitize_text_field($_POST['user_login'] ?? '');
        $entered_otp = preg_replace('/\D/', '', sanitize_text_field($_POST['otp_code'] ?? ''));

        $user = get_user_by('login', $u_login);
        if (!$user) {
            $user = get_user_by('email', $u_login);
        }

        if (!$user) {
            $login_error = 'Invalid verification session. Please restart password recovery.';
            $action = 'lostpassword';
        } else {
            $target_user_login = $user->user_login;
            $attempts = intval(get_user_meta($user->ID, '_ep_pwd_reset_otp_attempts', true));
            $exp      = intval(get_user_meta($user->ID, '_ep_pwd_reset_otp_exp', true));
            $hash     = get_user_meta($user->ID, '_ep_pwd_reset_otp', true);

            if ($attempts >= 5) {
                delete_user_meta($user->ID, '_ep_pwd_reset_otp');
                $login_error = 'Too many failed verification attempts. Please request a new code.';
                $action = 'lostpassword';
            } elseif (empty($hash) || time() > $exp) {
                $login_error = 'Your verification code has expired. Please request a new one.';
                $action = 'lostpassword';
            } elseif (!wp_check_password($entered_otp, $hash)) {
                update_user_meta($user->ID, '_ep_pwd_reset_otp_attempts', $attempts + 1);
                $remaining = 5 - ($attempts + 1);
                $login_error = "Invalid 6-digit verification code. Please check your email and try again ({$remaining} attempts remaining).";
                $action = 'verify_otp';
            } else {
                // Genuine OTP verified! Clear OTP and issue temporary single-use reset token
                delete_user_meta($user->ID, '_ep_pwd_reset_otp');
                delete_user_meta($user->ID, '_ep_pwd_reset_otp_exp');
                delete_user_meta($user->ID, '_ep_pwd_reset_otp_attempts');

                $session_token = wp_generate_password(32, false);
                update_user_meta($user->ID, '_ep_pwd_reset_token', wp_hash_password($session_token));
                update_user_meta($user->ID, '_ep_pwd_reset_token_exp', time() + 600); // 10 minutes

                $reset_session_token = $session_token;
                $action = 'new_password';
                $login_success = '✓ Code verified! Please set your new secure password.';
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Action: Set New Password (Step 3 - SOW 4.2)
// -----------------------------------------------------------------------------
if ($action === 'new_password' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_new_pass_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_new_pass_nonce'], 'ep_new_pass_action')) {
        $login_error = 'Security session expired. Please try again.';
    } else {
        $u_login = sanitize_text_field($_POST['user_login'] ?? '');
        $token   = sanitize_text_field($_POST['reset_token'] ?? '');
        $pass1   = $_POST['new_pass'] ?? '';
        $pass2   = $_POST['confirm_pass'] ?? '';

        $user = get_user_by('login', $u_login);
        if (!$user) {
            $user = get_user_by('email', $u_login);
        }

        if (!$user) {
            $login_error = 'User account could not be resolved.';
            $action = 'lostpassword';
        } else {
            $stored_token_hash = get_user_meta($user->ID, '_ep_pwd_reset_token', true);
            $token_exp         = intval(get_user_meta($user->ID, '_ep_pwd_reset_token_exp', true));

            if (empty($stored_token_hash) || time() > $token_exp || !wp_check_password($token, $stored_token_hash)) {
                $login_error = 'Password reset session has expired or is invalid. Please restart.';
                $action = 'lostpassword';
            } elseif (empty($pass1) || empty($pass2)) {
                $login_error = 'Please fill in both password fields.';
                $target_user_login = $user->user_login;
                $reset_session_token = $token;
            } elseif ($pass1 !== $pass2) {
                $login_error = 'Passwords do not match. Please re-enter.';
                $target_user_login = $user->user_login;
                $reset_session_token = $token;
            } elseif (strlen($pass1) < 8) {
                $login_error = 'Password must be at least 8 characters long.';
                $target_user_login = $user->user_login;
                $reset_session_token = $token;
            } else {
                // Update password securely
                wp_set_password($pass1, $user->ID);
                delete_user_meta($user->ID, '_ep_pwd_reset_token');
                delete_user_meta($user->ID, '_ep_pwd_reset_token_exp');

                wp_redirect(home_url('/customer-login/?password_reset=1'));
                exit;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 4. Action: Handle Standard Sign In (SOW 4.2)
// -----------------------------------------------------------------------------
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_login_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_login_nonce'], 'ep_customer_login_action')) {
        $login_error = 'Security session expired. Please refresh and try again.';
    } else {
        $user_input = sanitize_text_field($_POST['user_login'] ?? '');
        $password   = $_POST['user_password'] ?? '';
        $remember   = !empty($_POST['rememberme']);

        if (empty($user_input) || empty($password)) {
            $login_error = 'Please enter both your email address and password.';
        } else {
            if (is_email($user_input)) {
                $user_obj = get_user_by('email', $user_input);
                if (!$user_obj && strtolower($user_input) === 'admin@eventpass.com.na') {
                    $user_obj = get_user_by('login', 'admin');
                }
                $username = $user_obj ? $user_obj->user_login : $user_input;
            } else {
                $username = $user_input;
            }

            $creds = [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ];

            $signon = wp_signon($creds, is_ssl());

            if (is_wp_error($signon)) {
                $login_error = 'Invalid credentials. Please verify your email/username and password.';
            } else {
                wp_set_current_user($signon->ID);

                // Smart Redirect: target URL > role default
                if (!empty($redirect_to) && wp_validate_redirect($redirect_to)) {
                    wp_safe_redirect($redirect_to);
                } else {
                    wp_redirect(ep_get_role_destination_url($signon));
                }
                exit;
            }
        }
    }
}

// Contextual portal styling & messaging
$is_admin_target = strpos($redirect_to, 'admin-dashboard') !== false;
$is_org_target   = strpos($redirect_to, 'organiser-dashboard') !== false;

if ($is_admin_target) {
    $badge_text = 'Platform Administration';
    $title_text = 'Sign In to Platform Admin';
    $desc_text  = 'Enter your administrator credentials to access the Platform Admin Hub.';
    $btn_text   = 'Sign In to Admin Hub';
} elseif ($is_org_target) {
    $badge_text = 'Organiser Portal';
    $title_text = 'Sign In to Organiser Hub';
    $desc_text  = 'Enter your organiser credentials to manage your events, ticket sales, and payouts.';
    $btn_text   = 'Sign In to Organiser Hub';
} else {
    $badge_text = 'Account Access';
    $title_text = 'Sign In to EventPass';
    $desc_text  = 'Access your digital tickets, booking history and receipts.';
    $btn_text   = 'Sign In to Dashboard';
}

add_filter('document_title_parts', function($parts) use ($is_admin_target, $is_org_target) {
    if ($is_admin_target) {
        $parts['title'] = 'Platform Admin Sign In';
    } elseif ($is_org_target) {
        $parts['title'] = 'Organiser Hub Sign In';
    } else {
        $parts['title'] = 'Sign In';
    }
    return $parts;
}, 99);

get_header();
?>

<div class="container" style="padding: 5rem 1.5rem 6rem;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-lg);">

        <?php // =========================================================================
              // VIEW A: FORGOT PASSWORD / REQUEST OTP FORM (SOW 4.2)
              // =========================================================================
        if ($action === 'lostpassword') : ?>

            <div style="text-align: center; margin-bottom: 2rem;">
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Account Recovery</span>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Reset Your Password</h1>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Enter your registered email address or username to receive a secure 6-digit OTP code.</p>
            </div>

            <?php if (!empty($login_error)) : ?>
                <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span>
                    <span><?php echo esc_html($login_error); ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo esc_url(add_query_arg(['action' => 'lostpassword'], home_url('/customer-login/'))); ?>" method="POST">
                <?php wp_nonce_field('ep_lostpass_action', 'ep_lostpass_nonce'); ?>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Registered Email or Username</label>
                    <input type="text" name="user_login" required placeholder="your.name@example.com" value="<?php echo esc_attr($_POST['user_login'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                    <span>Send Verification Code (OTP) &rarr;</span>
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem;">
                <a href="<?php echo esc_url(home_url('/customer-login/')); ?>" style="color: var(--ep-navy); font-weight: 700; text-decoration: none;">&larr; Back to Sign In</a>
            </div>

        <?php // =========================================================================
              // VIEW B: ENTER 6-DIGIT OTP VERIFICATION CODE (SOW 4.2)
              // =========================================================================
        elseif ($action === 'verify_otp') : ?>

            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 56px; height: 56px; background: rgba(0,180,216,0.12); color: #00B4D8; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 1rem;">
                    ✉️
                </div>
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Two-Step Verification</span>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Enter 6-Digit Code</h1>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">We sent a genuine 6-digit verification code to your registered email address.</p>
            </div>

            <?php if (!empty($login_error)) : ?>
                <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span>
                    <span><?php echo esc_html($login_error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($login_success)) : ?>
                <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <?php echo esc_html($login_success); ?>
                </div>
            <?php endif; ?>



            <form action="<?php echo esc_url(add_query_arg(['action' => 'verify_otp'], home_url('/customer-login/'))); ?>" method="POST">
                <?php wp_nonce_field('ep_verify_otp_action', 'ep_verify_otp_nonce'); ?>
                <input type="hidden" name="user_login" value="<?php echo esc_attr($target_user_login); ?>">

                <div style="margin-bottom: 1.75rem; text-align: center;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.6rem;">Enter 6-Digit OTP</label>
                    <input type="text" name="otp_code" required maxlength="6" pattern="[0-9]{6}" autofocus placeholder="000000" style="width: 220px; margin: 0 auto; padding: 0.85rem 1rem; border: 2px solid var(--ep-teal-dark); border-radius: var(--ep-radius-md); font-size: 1.8rem; font-weight: 800; text-align: center; letter-spacing: 8px; font-family: monospace;">
                </div>

                <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                    <span>Verify Code & Continue &rarr;</span>
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #64748B;">
                Didn't receive the email? 
                <a href="<?php echo esc_url(home_url('/customer-login/?action=lostpassword')); ?>" style="color: var(--ep-teal-dark); font-weight: 700;">Resend OTP</a>
            </div>

        <?php // =========================================================================
              // VIEW C: SET NEW PASSWORD FORM (SOW 4.2)
              // =========================================================================
        elseif ($action === 'new_password') : ?>

            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 56px; height: 56px; background: rgba(16,185,129,0.12); color: #10B981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 1rem;">
                    🔒
                </div>
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Security</span>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Choose New Password</h1>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Create a strong, new password (minimum 8 characters) for your EventPass account.</p>
            </div>

            <?php if (!empty($login_error)) : ?>
                <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span>
                    <span><?php echo esc_html($login_error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($login_success)) : ?>
                <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <?php echo esc_html($login_success); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo esc_url(add_query_arg(['action' => 'new_password'], home_url('/customer-login/'))); ?>" method="POST">
                <?php wp_nonce_field('ep_new_pass_action', 'ep_new_pass_nonce'); ?>
                <input type="hidden" name="user_login" value="<?php echo esc_attr($target_user_login); ?>">
                <input type="hidden" name="reset_token" value="<?php echo esc_attr($reset_session_token); ?>">

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">New Password (Min 8 Characters)</label>
                    <input type="password" name="new_pass" required minlength="8" placeholder="••••••••••••" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Confirm New Password</label>
                    <input type="password" name="confirm_pass" required minlength="8" placeholder="••••••••••••" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                    <span>Save New Password & Sign In</span>
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem;">
                <a href="<?php echo esc_url(home_url('/customer-login/')); ?>" style="color: var(--ep-navy); font-weight: 700; text-decoration: none;">&larr; Back to Sign In</a>
            </div>

        <?php // =========================================================================
              // VIEW C: STANDARD SIGN IN FORM (SOW 4.2)
              // =========================================================================
        else : ?>

            <div style="text-align: center; margin-bottom: 2rem;">
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;"><?php echo esc_html($badge_text); ?></span>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;"><?php echo esc_html($title_text); ?></h1>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;"><?php echo esc_html($desc_text); ?></p>
            </div>

            <?php if (!empty($login_error)) : ?>
                <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚠️</span>
                    <span><?php echo esc_html($login_error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($login_success)) : ?>
                <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <?php echo esc_html($login_success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['password_reset'])) : ?>
                <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    ✓ Your password has been successfully reset! Please sign in with your new password below.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['loggedout'])) : ?>
                <div style="background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    ✓ You have successfully signed out of EventPass Namibia.
                </div>
            <?php endif; ?>

            <form action="<?php echo esc_url(add_query_arg([], home_url('/customer-login/'))); ?>" method="POST">
                <?php wp_nonce_field('ep_customer_login_action', 'ep_login_nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address or Username</label>
                    <input type="text" name="user_login" required placeholder="your.name@example.com" value="<?php echo esc_attr($_POST['user_login'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800);">Password</label>
                        <a href="<?php echo esc_url(home_url('/customer-login/?action=lostpassword')); ?>" style="font-size: 0.8rem; color: var(--ep-teal-dark);">Forgot password?</a>
                    </div>
                    <input type="password" name="user_password" required placeholder="••••••••••••" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--ep-slate-600); cursor: pointer;">
                        <input type="checkbox" name="rememberme" checked>
                        <span>Remember me on this device</span>
                    </label>
                </div>

                <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                    <span><?php echo esc_html($btn_text); ?></span>
                </button>
            </form>

            <?php if (!$is_admin_target) : ?>
                <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem; color: var(--ep-slate-600);">
                    Don't have an account yet? 
                    <a href="<?php echo esc_url(home_url('/customer-registration')); ?>" style="color: var(--ep-teal-dark); font-weight: 700;">Create One Free</a>
                </div>

                <div style="text-align: center; margin-top: 1rem; font-size: 0.85rem;">
                    <a href="<?php echo esc_url(home_url('/organiser-registration')); ?>" style="color: var(--ep-navy); text-decoration: underline;">Are you an Event Organiser? Apply here &rarr;</a>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php
get_footer();
