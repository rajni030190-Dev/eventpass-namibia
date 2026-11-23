<?php
/**
 * Template Name: Customer Login
 *
 * @package EventPass_Namibia
 */

// Capture contextual redirect target
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

// If already logged in, redirect directly to requested target or role-based dashboard
if (is_user_logged_in()) {
    if (!empty($redirect_to) && wp_validate_redirect($redirect_to)) {
        wp_safe_redirect($redirect_to);
    } else {
        wp_redirect(ep_get_role_destination_url());
    }
    exit;
}

$login_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_login_nonce'])) {
    if (!wp_verify_nonce($_POST['ep_login_nonce'], 'ep_customer_login_action')) {
        $login_error = 'Security session expired. Please refresh and try again.';
    } else {
        $user_input = sanitize_text_field($_POST['user_login'] ?? '');
        $password   = $_POST['user_password'] ?? '';
        $remember   = !empty($_POST['rememberme']);

        if (empty($user_input) || empty($password)) {
            $login_error = 'Please enter both your email address and password.';
        } else {
            // Check if user entered an email address
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
                $login_error = 'Invalid email or password. Please verify your credentials and try again.';
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

        <?php if (isset($_GET['loggedout'])) : ?>
            <div style="background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                ✓ You have successfully signed out of EventPass Namibia.
            </div>
        <?php endif; ?>

        <form action="<?php echo esc_url(add_query_arg([], home_url('/customer-login'))); ?>" method="POST">
            <?php wp_nonce_field('ep_customer_login_action', 'ep_login_nonce'); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address or Username</label>
                <input type="text" name="user_login" required placeholder="your.name@example.com" value="<?php echo esc_attr($_POST['user_login'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800);">Password</label>
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" style="font-size: 0.8rem; color: var(--ep-teal-dark);">Forgot password?</a>
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
        <?php else : ?>
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--ep-slate-500);">
                <span>Default Admin: <strong>admin</strong> | <strong>admin@eventpass.com.na</strong></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
get_footer();
