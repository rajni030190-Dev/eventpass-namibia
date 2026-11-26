<?php
/**
 * Template Name: Organiser Registration
 *
 * @package EventPass_Namibia
 */

$app_submitted = false;
$app_error = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_org_app_nonce']) ) {
    if ( ! wp_verify_nonce( $_POST['ep_org_app_nonce'], 'ep_org_app_action' ) ) {
        $app_error = 'Security session expired. Please refresh and try again.';
    } else {
        $org_name = sanitize_text_field( $_POST['org_name'] ?? '' );
        $contact  = sanitize_text_field( $_POST['contact_name'] ?? '' );
        $phone    = sanitize_text_field( $_POST['phone'] ?? '' );
        $email    = sanitize_email( $_POST['email'] ?? '' );
        $town     = sanitize_text_field( $_POST['town'] ?? '' );
        $category = sanitize_text_field( $_POST['category'] ?? '' );
        $bank     = sanitize_text_field( $_POST['bank'] ?? '' );

        // Phone validation
        $org_phone_digits = preg_replace( '/[^0-9]/', '', $phone );

        if ( empty($org_name) || empty($contact) || empty($phone) || empty($email) ) {
            $app_error = 'Please fill in all mandatory fields.';
        } elseif ( strlen( $org_phone_digits ) < 9 || strlen( $org_phone_digits ) > 15 || ! preg_match( '/^\+?[0-9\s\-]{9,20}$/', $phone ) ) {
            $app_error = 'Please enter a valid phone number (e.g. +264 81 123 4567).';
        } elseif ( ! is_email( $email ) ) {
            $app_error = 'Please enter a valid business email address.';
        } else {
            $app_post_id = wp_insert_post( array(
                'post_title'   => $org_name . ' (' . $contact . ')',
                'post_type'    => 'ep_organizer_app',
                'post_status'  => 'pending',
                'post_content' => "Business Name: $org_name\nContact Person: $contact\nEmail: $email\nPhone: $phone\nTown: $town\nCategory: $category\nBank: $bank",
            ) );

            if ( ! is_wp_error( $app_post_id ) ) {
                update_post_meta( $app_post_id, '_ep_org_name', $org_name );
                update_post_meta( $app_post_id, '_ep_org_contact', $contact );
                update_post_meta( $app_post_id, '_ep_org_email', $email );
                update_post_meta( $app_post_id, '_ep_org_phone', $phone );
                update_post_meta( $app_post_id, '_ep_org_town', $town );
                update_post_meta( $app_post_id, '_ep_org_bank', $bank );
                update_post_meta( $app_post_id, '_ep_org_category', $category );
                update_post_meta( $app_post_id, '_ep_app_status', 'pending' );

                // SOW 4.2 & Requirement 3: Independent Organiser Email Verification Token
                $org_verify_token = wp_generate_password( 32, false );
                update_post_meta( $app_post_id, '_ep_org_email_verified', 0 );
                update_post_meta( $app_post_id, '_ep_org_email_verify_token', $org_verify_token );

                // Dispatch automated verification and admin alert notifications
                if ( class_exists('EventPass_Notification_Engine') ) {
                    EventPass_Notification_Engine::notify_organiser_email_verification( $app_post_id, $org_verify_token );
                    EventPass_Notification_Engine::notify_admin_new_organiser_app( $app_post_id );
                }

                $app_submitted = true;
            } else {
                $app_error = $app_post_id->get_error_message();
            }
        }
    }
}

get_header();
?>

<!-- Organiser Registration Hero -->
<section class="ep-page-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/business-summit.jpg'); ?>" alt="Organiser Registration" class="ep-page-hero-bg">
    <div class="ep-page-hero-overlay"></div>
    <div class="container ep-page-hero-content">
        <div style="max-width: 820px;">
            <div class="ep-page-pill">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--ep-cyan); box-shadow:0 0 10px var(--ep-cyan);"></span>
                PROMOTER ONBOARDING PORTAL
            </div>
            <h1 style="font-size: clamp(2.3rem, 5vw, 3.4rem); font-weight: 900; line-height: 1.15; margin-bottom: 1.25rem;">
                Register as an Event Organiser
            </h1>
            <p style="font-size: 1.15rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 1.5rem;">
                Join Namibia's leading event creators. Start listing events, selling tickets online, and checking in thousands of attendees with contactless QR scanners.
            </p>
        </div>
    </div>
</section>

<!-- Registration Layout -->
<div class="container" style="padding: 4.5rem 1.5rem 5.5rem;">
    <div class="ep-organiser-reg-grid" style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 3.5rem; align-items: flex-start;">
        
        <!-- Left: Promoter Benefits -->
        <div>
            <span style="color: var(--ep-teal-dark); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Why Partner with EventPass</span>
            <h3 style="font-size: 1.85rem; font-weight: 900; color: var(--ep-navy); margin: 0.4rem 0 1.25rem; line-height: 1.3;">
                Everything You Need to Sell Out & Secure Your Event
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <span style="color: var(--ep-teal-dark); font-weight: 900; font-size: 1.25rem;">✓</span>
                    <div>
                        <strong style="color: var(--ep-navy); font-size: 1rem; display: block;">Zero Upfront Costs</strong>
                        <span style="color: #64748B; font-size: 0.9rem;">Free to create organiser account, list events, and configure ticket tiers.</span>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <span style="color: var(--ep-teal-dark); font-weight: 900; font-size: 1.25rem;">✓</span>
                    <div>
                        <strong style="color: var(--ep-navy); font-size: 1rem; display: block;">Free Mobile Turnstile Scanner</strong>
                        <span style="color: #64748B; font-size: 0.9rem;">Equip your door staff with our high-speed optical barcode checking app.</span>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <span style="color: var(--ep-teal-dark); font-weight: 900; font-size: 1.25rem;">✓</span>
                    <div>
                        <strong style="color: var(--ep-navy); font-size: 1rem; display: block;">Direct Namibian Bank Settlement</strong>
                        <span style="color: #64748B; font-size: 0.9rem;">Fast EFT payouts to Bank Windhoek, FNB, Standard Bank, or Nedbank accounts.</span>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <span style="color: var(--ep-teal-dark); font-weight: 900; font-size: 1.25rem;">✓</span>
                    <div>
                        <strong style="color: var(--ep-navy); font-size: 1rem; display: block;">Live Revenue & Ticket Radar</strong>
                        <span style="color: #64748B; font-size: 0.9rem;">Watch ticket sales in real-time with comprehensive buyer analytics.</span>
                    </div>
                </div>
            </div>

            <div style="background: rgba(0,180,216,0.06); border: 1px solid rgba(0,180,216,0.25); border-radius: 12px; padding: 1.25rem;">
                <strong style="color: var(--ep-navy); font-size: 0.92rem; display: block; margin-bottom: 0.25rem;">Promoter Verification Guarantee</strong>
                <p style="color: #64748B; font-size: 0.85rem; line-height: 1.5; margin: 0;">
                    Our team verifies each organiser application within 2 business hours. You will receive an email confirmation and immediate access to your Organiser Hub.
                </p>
            </div>
        </div>

        <!-- Right: High-Conversion Form -->
        <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: 18px; padding: 2.75rem 2.25rem; box-shadow: var(--ep-shadow-lg);">
            <h3 style="font-size: 1.45rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Organiser Registration Form</h3>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; margin-bottom: 2rem;">Please provide your business and contact information.</p>

            <?php if ( $app_submitted ) : ?>
                <div style="background: #ECFDF5; border: 1px solid #10B981; color: #065F46; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
                    <span style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem;">🎉</span>
                    <h4 style="margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 800; color: #065F46;">Application Submitted Successfully!</h4>
                    <p style="margin: 0 0 1rem; font-size: 0.95rem; line-height: 1.6;">
                        Thank you for applying to partner with EventPass Namibia. Your application for <strong><?php echo esc_html($org_name); ?></strong> has been received and logged for administrative verification.
                    </p>
                    <span style="display: inline-block; background: #D1FAE5; color: #047857; font-size: 0.85rem; font-weight: 700; padding: 0.4rem 1rem; border-radius: 9999px;">
                        Status: Pending Admin Vetting (Est. 2 Hours)
                    </span>
                </div>
            <?php else : ?>

                <?php if ( ! empty($app_error) ) : ?>
                    <div style="background: #FEF2F2; border: 1px solid #FCA5A5; color: #991B1B; padding: 0.85rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>⚠️</span>
                        <span><?php echo esc_html($app_error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo esc_url(home_url('/organiser-registration')); ?>" method="POST">
                    <?php wp_nonce_field( 'ep_org_app_action', 'ep_org_app_nonce' ); ?>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Organisation / Business Name *</label>
                        <input type="text" name="org_name" required placeholder="e.g. Kalahari Arts & Events CC" value="<?php echo esc_attr($_POST['org_name'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;">
                    </div>

                    <div class="ep-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Primary Contact Person *</label>
                            <input type="text" name="contact_name" required placeholder="Full Name" value="<?php echo esc_attr($_POST['contact_name'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Mobile Phone *</label>
                            <input type="tel" name="phone" required placeholder="+264 81 123 4567" value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" pattern="^\+?[0-9\s\-]{9,20}$" maxlength="20" title="Enter a valid phone number e.g. +264 81 123 4567" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Business Email Address *</label>
                        <input type="email" name="email" required placeholder="name@business.com.na" value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem;">
                    </div>

                    <div class="ep-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Primary Town / Region *</label>
                            <select name="town" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                                <option value="Windhoek">Windhoek (Khomas)</option>
                                <option value="Swakopmund">Swakopmund (Erongo)</option>
                                <option value="Walvis Bay">Walvis Bay (Erongo)</option>
                                <option value="Oshakati">Oshakati (Oshana)</option>
                                <option value="Other">Other Region</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Primary Event Category *</label>
                            <select name="category" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                                <option value="Concerts & Music Festivals">Concerts & Music Festivals</option>
                                <option value="Conferences & Seminars">Conferences & Seminars</option>
                                <option value="Sports & Recreation">Sports & Recreation</option>
                                <option value="Cultural Events">Cultural Events</option>
                                <option value="Exhibitions & Trade Shows">Exhibitions & Trade Shows</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 700; color: var(--ep-slate-800); margin-bottom: 0.4rem; font-size: 0.88rem;">Preferred Namibian Settlement Bank *</label>
                        <select name="bank" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                            <option value="Bank Windhoek">Bank Windhoek</option>
                            <option value="First National Bank">First National Bank (FNB Namibia)</option>
                            <option value="Standard Bank Namibia">Standard Bank Namibia</option>
                            <option value="Nedbank Namibia">Nedbank Namibia</option>
                        </select>
                    </div>

                    <button type="submit" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%; justify-content: center;">
                        Submit Organiser Application &rarr;
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php
get_footer();
