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
    if (!empty($redirect_to) && wp_validate_redirect($redirect_to)) {
        wp_safe_redirect($redirect_to);
    } else {
        wp_redirect(ep_get_role_destination_url());
    }
    exit;
}

$login_error   = '';
$login_success = '';
$dev_reset_url = '';

// -----------------------------------------------------------------------------
// 2. Action: Handle Password Reset Request (Forgot Password - SOW 4.2)
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

            if (!$user) {
                // Friendly error
                $login_error = 'No account found matching that email address or username.';
            } else {
                // Generate WordPress standard password reset key
                $key = get_password_reset_key($user);
                if (is_wp_error($key)) {
                    $login_error = $key->get_error_message();
                } else {
                    $reset_url = home_url('/login?action=rp&key=' . urlencode($key) . '&login=' . rawurlencode($user->user_login));

                    // Dispatch branded notification
                    if (class_exists('EventPass_Notification_Engine')) {
                        EventPass_Notification_Engine::notify_password_reset_request($user->ID, $reset_url);
                    }

                    $login_success = 'Password reset instructions have been dispatched to your email address (' . esc_html($user->user_email) . ').';
                    $dev_reset_url = $reset_url;
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Action: Handle New Password Submission (Reset Password - SOW 4.2)
// -----------------------------------------------------------------------------
if ($action === 'rp') {
    $rp_key   = sanitize_text_field($_REQUEST['key'] ?? '');
    $rp_login = sanitize_text_field($_REQUEST['login'] ?? '');

    $user_check = check_password_reset_key($rp_key, $rp_login);
    $rp_invalid = is_wp_error($user_check);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_rp_nonce'])) {
        if (!wp_verify_nonce($_POST['ep_rp_nonce'], 'ep_rp_action')) {
            $login_error = 'Security session expired. Please try again.';
        } elseif ($rp_invalid) {
            $login_error = 'Your password reset link is invalid or has expired.';
        } else {
            $pass1 = $_POST['new_pass'] ?? '';
            $pass2 = $_POST['confirm_pass'] ?? '';

            if (empty($pass1) || empty($pass2)) {
                $login_error = 'Please fill in both password fields.';
            } elseif ($pass1 !== $pass2) {
                $login_error = 'Passwords do not match. Please re-enter.';
            } elseif (strlen($pass1) < 8) {
                $login_error = 'Password must be at least 8 characters long.';
            } else {
                reset_password($user_check, $pass1);
                wp_redirect(home_url('/login?password_reset=1'));
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

get_header();
?>

<div class="container" style="padding: 5rem 1.5rem 6rem;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-lg);">

        <?php // =========================================================================
              // VIEW A: FORGOT PASSWORD / PASSWORD RECOVERY FORM (SOW 4.2)
              // =========================================================================
        if ($action === 'lostpassword') : ?>

            <div style="text-align: center; margin-bottom: 2rem;">
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Account Recovery</span>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Reset Your Password</h1>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Enter your registered email address or username to receive password recovery instructions.</p>
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

            <?php if (!empty($login_success)) : ?>
                <div style="background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                    <strong>✓ Request Sent</strong>
                    <p style="margin: 0.35rem 0 0;"><?php echo esc_html($login_success); ?></p>
                    <?php if (!empty($dev_reset_url)) : ?>
                        <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(22,101,52,0.2);">
                            <span style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #15803D;">Local Environment Direct Action:</span>
                            <div style="margin-top: 0.25rem;">
                                <a href="<?php echo esc_url($dev_reset_url); ?>" style="background: #15803D; color: #ffffff; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-block;">
                                    Click Here to Set New Password &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo esc_url(add_query_arg(['action' => 'lostpassword'], home_url('/login'))); ?>" method="POST">
                <?php wp_nonce_field('ep_lostpass_action', 'ep_lostpass_nonce'); ?>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Registered Email or Username</label>
                    <input type="text" name="user_login" required placeholder="your.name@example.com" value="<?php echo esc_attr($_POST['user_login'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                    <span>Send Password Reset Link</span>
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem;">
                <a href="<?php echo esc_url(home_url('/login')); ?>" style="color: var(--ep-navy); font-weight: 700; text-decoration: none;">&larr; Back to Sign In</a>
            </div>

        <?php // =========================================================================
              // VIEW B: SET NEW PASSWORD FORM (SOW 4.2)
              // =========================================================================
        elseif ($action === 'rp') : ?>

            <div style="text-align: center; margin-bottom: 2rem;">
                <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Security</span>
                <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Choose New Password</h1>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Create a strong, new password for your EventPass account.</p>
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

            <?php if ($rp_invalid) : ?>
                <div style="background: #FFFBEB; border: 1px solid #FCD34D; color: #92400E; padding: 1.25rem; border-radius: 8px; font-size: 0.92rem; text-align: center;">
                    <p style="margin: 0 0 1rem;">⚠️ This password reset link has expired or has already been used.</p>
                    <a href="<?php echo esc_url(home_url('/login?action=lostpassword')); ?>" class="ep-btn ep-btn-primary ep-btn-sm" style="display: inline-block;">
                        Request New Password Reset Link
                    </a>
                </div>
            <?php else : ?>
                <form action="<?php echo esc_url(add_query_arg(['action' => 'rp', 'key' => $rp_key, 'login' => $rp_login], home_url('/login'))); ?>" method="POST">
                    <?php wp_nonce_field('ep_rp_action', 'ep_rp_nonce'); ?>
                    <input type="hidden" name="key" value="<?php echo esc_attr($rp_key); ?>">
                    <input type="hidden" name="login" value="<?php echo esc_attr($rp_login); ?>">

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">New Password (Min 8 Characters)</label>
                        <input type="password" name="new_pass" required minlength="8" placeholder="••••••••••••" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Confirm New Password</label>
                        <input type="password" name="confirm_pass" required minlength="8" placeholder="••••••••••••" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                    </div>

                    <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                        <span>Update Password & Save</span>
                    </button>
                </form>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem;">
                <a href="<?php echo esc_url(home_url('/login')); ?>" style="color: var(--ep-navy); font-weight: 700; text-decoration: none;">&larr; Back to Sign In</a>
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

            <form action="<?php echo esc_url(add_query_arg([], home_url('/login'))); ?>" method="POST">
                <?php wp_nonce_field('ep_customer_login_action', 'ep_login_nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address or Username</label>
                    <input type="text" name="user_login" required placeholder="your.name@example.com" value="<?php echo esc_attr($_POST['user_login'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800);">Password</label>
                        <a href="<?php echo esc_url(home_url('/login?action=lostpassword')); ?>" style="font-size: 0.8rem; color: var(--ep-teal-dark);">Forgot password?</a>
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
