<?php
/**
 * Template Name: Customer Registration
 *
 * @package EventPass_Namibia
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url('/customer-dashboard') );
    exit;
}

$reg_error = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_reg_nonce']) ) {
    if ( ! wp_verify_nonce( $_POST['ep_reg_nonce'], 'ep_customer_reg_action' ) ) {
        $reg_error = 'Security session expired. Please refresh and try again.';
    } else {
        $full_name = sanitize_text_field( $_POST['full_name'] ?? '' );
        $email     = sanitize_email( $_POST['user_email'] ?? '' );
        $phone     = sanitize_text_field( $_POST['phone'] ?? '' );
        $password  = $_POST['password'] ?? '';

        if ( empty( $full_name ) || empty( $email ) || empty( $password ) ) {
            $reg_error = 'Please complete all required fields.';
        } elseif ( ! is_email( $email ) ) {
            $reg_error = 'Please enter a valid email address.';
        } elseif ( email_exists( $email ) ) {
            $reg_error = 'An account with this email address already exists. Please sign in instead.';
        } elseif ( strlen( $password ) < 8 ) {
            $reg_error = 'Password must be at least 8 characters long.';
        } else {
            // Generate clean username from email
            $base_username = sanitize_user( current( explode( '@', $email ) ) );
            $username = $base_username;
            $i = 1;
            while ( username_exists( $username ) ) {
                $username = $base_username . $i;
                $i++;
            }

            // Split first and last name
            $parts = explode( ' ', $full_name, 2 );
            $first_name = $parts[0] ?? '';
            $last_name  = $parts[1] ?? '';

            $user_id = wp_insert_user( array(
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'display_name' => $full_name,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'role'         => 'event_customer',
            ) );

            if ( is_wp_error( $user_id ) ) {
                $reg_error = $user_id->get_error_message();
            } else {
                // Save phone number meta
                if ( ! empty( $phone ) ) {
                    update_user_meta( $user_id, 'phone_number', $phone );
                }

                // Auto sign-in new customer
                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id, true );

                wp_redirect( home_url('/customer-dashboard/?welcome=1') );
                exit;
            }
        }
    }
}

get_header();
?>

<div class="container" style="padding: 5rem 1.5rem 6rem;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2.5rem; box-shadow: var(--ep-shadow-lg);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700;">Customer Registration</span>
            <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin: 0.25rem 0 0.5rem;">Join EventPass Namibia</h1>
            <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin: 0;">Discover, book, store and manage your digital event passes in one place.</p>
        </div>

        <?php if ( ! empty( $reg_error ) ) : ?>
            <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⚠️</span>
                <span><?php echo esc_html( $reg_error ); ?></span>
            </div>
        <?php endif; ?>

        <form action="<?php echo esc_url( home_url('/customer-registration') ); ?>" method="POST">
            <?php wp_nonce_field( 'ep_customer_reg_action', 'ep_reg_nonce' ); ?>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Full Name <span style="color: var(--ep-danger);">*</span></label>
                <input type="text" name="full_name" required placeholder="e.g. Johannes Shilongo" value="<?php echo esc_attr( $_POST['full_name'] ?? '' ); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Email Address <span style="color: var(--ep-danger);">*</span></label>
                <input type="email" name="user_email" required placeholder="name@domain.com.na" value="<?php echo esc_attr( $_POST['user_email'] ?? '' ); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Mobile Phone Number <span style="color: var(--ep-danger);">*</span></label>
                <input type="tel" name="phone" required placeholder="+264 81 123 4567" value="<?php echo esc_attr( $_POST['phone'] ?? '' ); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem;">Create Password <span style="color: var(--ep-danger);">*</span></label>
                <input type="password" name="password" required placeholder="Minimum 8 characters" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 1.75rem;">
                <label style="display: flex; gap: 0.6rem; align-items: flex-start; font-size: 0.85rem; color: var(--ep-slate-600); cursor: pointer;">
                    <input type="checkbox" required style="margin-top: 0.25rem;">
                    <span>I agree to the <a href="<?php echo esc_url(home_url('/terms-and-conditions')); ?>" target="_blank" style="color: var(--ep-teal-dark);">Terms & Conditions</a> and <a href="<?php echo esc_url(home_url('/privacy-policy')); ?>" target="_blank" style="color: var(--ep-teal-dark);">Privacy Policy</a>.</span>
                </label>
            </div>

            <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%;">
                <span>Create Free Account</span>
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--ep-slate-100); font-size: 0.9rem; color: var(--ep-slate-600);">
            Already have an account? 
            <a href="<?php echo esc_url(home_url('/customer-login')); ?>" style="color: var(--ep-teal-dark); font-weight: 700;">Sign In</a>
        </div>
    </div>
</div>

<?php
get_footer();
