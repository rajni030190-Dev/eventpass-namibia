<?php
/**
 * Template Name: Customer Login
 *
 * @package EventPass_Namibia
 */

// If already logged in, redirect directly to dashboard
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/customer-dashboard') );
    exit;
}

$login_error = '';

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_login_nonce']) ) {
    if ( ! wp_verify_nonce( $_POST['ep_login_nonce'], 'ep_customer_login_action' ) ) {
        $login_error = 'Security session expired. Please refresh and try again.';
    } else {
        $user_input = sanitize_text_field( $_POST['user_login'] ?? '' );
        $password   = $_POST['user_password'] ?? '';
        $remember   = !empty( $_POST['rememberme'] );

        if ( empty( $user_input ) || empty( $password ) ) {
            $login_error = 'Please enter both your email address and password.';
        } else {
            // Check if user entered an email address
            if ( is_email( $user_input ) ) {
                $user_obj = get_user_by( 'email', $user_input );
                $username = $user_obj ? $user_obj->user_login : $user_input;
            } else {
                $username = $user_input;
            }

            $creds = array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            );

            $signon = wp_signon( $creds, is_ssl() );

            if ( is_wp_error( $signon ) ) {
                $login_error = 'Invalid email or password. Please verify your credentials and try again.';
            } else {
                wp_set_current_user( $signon->ID );
                wp_redirect( home_url('/customer-dashboard') );
                exit;
            }
        }
    }
}

get_header();
?>

<div class="container" style="padding: 5rem 1.5rem 6rem;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-lg);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Account Access</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Sign In to EventPass</h1>
            <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Access your digital tickets, booking history and receipts</p>
        </div>

        <?php if ( ! empty( $login_error ) ) : ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⚠️</span>
                <span><?php echo esc_html( $login_error ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( isset($_GET['loggedout']) ) : ?>
            <div style="background: #F0FDF4; border: 1px solid #86EFAC; color: #166534; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem;">
                ✓ You have successfully signed out of EventPass Namibia.
            </div>
        <?php endif; ?>

        <form action="<?php echo esc_url( home_url('/customer-login') ); ?>" method="POST">
            <?php wp_nonce_field( 'ep_customer_login_action', 'ep_login_nonce' ); ?>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address</label>
                <input type="email" name="user_login" required placeholder="your.name@example.com" value="<?php echo esc_attr( $_POST['user_login'] ?? '' ); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800);">Password</label>
                    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size: 0.8rem; color: var(--ep-teal-dark);">Forgot password?</a>
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
                <span>Sign In to Dashboard</span>
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem; color: var(--ep-slate-600);">
            Don't have an account yet? 
            <a href="<?php echo esc_url(home_url('/customer-registration')); ?>" style="color: var(--ep-teal-dark); font-weight: 700;">Create One Free</a>
        </div>

        <div style="text-align: center; margin-top: 1rem; font-size: 0.85rem;">
            <a href="<?php echo esc_url(home_url('/organiser-registration')); ?>" style="color: var(--ep-navy); text-decoration: underline;">Are you an Event Organiser? Apply here &rarr;</a>
        </div>
    </div>
</div>

<?php
get_footer();
