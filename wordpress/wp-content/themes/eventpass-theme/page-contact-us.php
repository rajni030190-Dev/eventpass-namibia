<?php
/**
 * Template Name: Contact Us
 *
 * @package EventPass_Namibia
 */

$form_success = false;
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ep_contact_submit'])) {
    if (!isset($_POST['ep_contact_nonce']) || !wp_verify_nonce($_POST['ep_contact_nonce'], 'ep_contact_action')) {
        $form_error = 'Security verification failed. Please refresh and try again.';
    } else {
        $name    = sanitize_text_field($_POST['full_name'] ?? '');
        $phone   = sanitize_text_field($_POST['phone'] ?? '');
        $email   = sanitize_email($_POST['email'] ?? '');
        $topic   = sanitize_text_field($_POST['topic'] ?? 'General Inquiry');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message) || !is_email($email)) {
            $form_error = 'Please fill in all required fields with a valid email address.';
        } else {
            if (class_exists('EventPass_Notification_Engine')) {
                EventPass_Notification_Engine::notify_contact_enquiry([
                    'name'    => $name,
                    'phone'   => $phone,
                    'email'   => $email,
                    'topic'   => $topic,
                    'message' => $message,
                ]);
            }
            $form_success = true;
        }
    }
}

get_header();
?>

<!-- Contact Us Hero -->
<section class="ep-page-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg'); ?>" alt="Contact EventPass" class="ep-page-hero-bg">
    <div class="ep-page-hero-overlay"></div>
    <div class="container ep-page-hero-content">
        <div style="max-width: 820px;">
            <div class="ep-page-pill">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--ep-cyan); box-shadow:0 0 10px var(--ep-cyan);"></span>
                WE'RE HERE TO HELP • 7 DAYS A WEEK
            </div>
            <h1 style="font-size: clamp(2.3rem, 5vw, 3.4rem); font-weight: 900; line-height: 1.15; margin-bottom: 1.25rem;">
                Get in Touch with EventPass Namibia
            </h1>
            <p style="font-size: 1.15rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 1.5rem;">
                Have questions about booking tickets, need assistance retrieving your QR pass, or want to partner with us as an event promoter? Our Windhoek-based support desk is ready.
            </p>
        </div>
    </div>
</section>

<!-- Main Contact Grid -->
<div class="container" style="padding: 4.5rem 1.5rem 5.5rem;">
    <div class="ep-contact-grid" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 3rem; align-items: flex-start;">
        
        <!-- Contact Form Card -->
        <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: 16px; padding: 2.5rem; box-shadow: var(--ep-shadow-md);">
            <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Send Us a Direct Message</h3>
            <p style="color: var(--ep-slate-600); font-size: 0.95rem; margin-bottom: 2rem;">Fill in the form below and an EventPass ticketing specialist will get back to you shortly.</p>

            <?php if ($form_success) : ?>
                <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.75rem; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">✅</div>
                    <h4 style="font-size: 1.15rem; font-weight: 800; color: #065F46; margin-bottom: 0.5rem;">Message Dispatched Successfully</h4>
                    <p style="color: #047857; font-size: 0.92rem; line-height: 1.5; margin: 0;">
                        Thank you <strong><?php echo esc_html($name); ?></strong>! Your enquiry regarding <em><?php echo esc_html($topic); ?></em> has been delivered to the EventPass Windhoek operations desk. We will respond within 30 minutes.
                    </p>
                </div>
            <?php elseif (!empty($form_error)) : ?>
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; color: #DC2626; font-size: 0.9rem; font-weight: 600;">
                    ⚠️ <?php echo esc_html($form_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php wp_nonce_field('ep_contact_action', 'ep_contact_nonce'); ?>
                <div class="ep-form-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--ep-slate-700); margin-bottom: 0.4rem;">Full Name *</label>
                        <input type="text" name="full_name" required value="<?php echo esc_attr($_POST['full_name'] ?? ''); ?>" placeholder="e.g. Selma Amadhila" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.95rem;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--ep-slate-700); margin-bottom: 0.4rem;">Mobile Phone *</label>
                        <input type="tel" name="phone" required value="<?php echo esc_attr($_POST['phone'] ?? ''); ?>" placeholder="+264 81 123 4567" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.95rem;">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--ep-slate-700); margin-bottom: 0.4rem;">Email Address *</label>
                    <input type="email" name="email" required value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" placeholder="you@domain.com.na" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--ep-slate-700); margin-bottom: 0.4rem;">Topic / Department *</label>
                    <select name="topic" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.95rem; background: #fff;">
                        <option value="Ticket Booking &amp; QR Pass Resend">Ticket Booking &amp; QR Pass Resend</option>
                        <option value="Event Organiser Partnership &amp; Scanner Request">Event Organiser Partnership &amp; Scanner Request</option>
                        <option value="Finatic Payment &amp; Bank Verification">Finatic Payment &amp; Bank Verification</option>
                        <option value="Refund or Event Postponement Query">Refund or Event Postponement Query</option>
                        <option value="General Inquiry">General Inquiry</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 700; font-size: 0.85rem; color: var(--ep-slate-700); margin-bottom: 0.4rem;">Your Message *</label>
                    <textarea name="message" rows="4" required placeholder="How can we assist you with EventPass?" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: 8px; font-size: 0.95rem; resize: vertical;"><?php echo esc_textarea($_POST['message'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="ep_contact_submit" value="1" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%; justify-content: center;">
                    Submit Message &rarr;
                </button>
            </form>
        </div>

        <!-- Contact Info Channels -->
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            
            <!-- WhatsApp Priority Box -->
            <div style="background: linear-gradient(135deg, #060E20 0%, #0B1B3D 100%); border: 1px solid rgba(0,229,255,0.25); border-radius: 16px; padding: 2rem; color: #fff;">
                <span style="color: var(--ep-cyan); font-weight: 800; font-size: 0.8rem; text-transform: uppercase;">Instant Chat Assistance</span>
                <h4 style="font-size: 1.35rem; font-weight: 800; margin: 0.35rem 0 0.5rem;">WhatsApp Direct Support</h4>
                <p style="color: #CBD5E1; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                    Need urgent ticket re-send or assistance at the gate? Message our standby desk on WhatsApp.
                </p>
                <a href="https://wa.me/264819200000" target="_blank" class="ep-whatsapp-btn">
                    <span>💬 Chat on WhatsApp (+264 81 920 0000)</span>
                </a>
            </div>

            <!-- Attendee Support Card -->
            <div class="ep-support-card">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(0,180,216,0.1); color: var(--ep-teal-dark); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">🎟️</div>
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">Attendee Ticketing Desk</h4>
                    <p style="color: #64748B; font-size: 0.88rem; margin: 0 0 0.4rem;">For booking queries, digital passes and receipt requests.</p>
                    <a href="mailto:support@eventpass.com.na" style="color: var(--ep-teal-dark); font-weight: 700; font-size: 0.95rem; text-decoration: none;">support@eventpass.com.na</a>
                </div>
            </div>

            <!-- Organiser Support Card -->
            <div class="ep-support-card">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(0,180,216,0.1); color: var(--ep-teal-dark); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">💼</div>
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">Promoter & Organiser Desk</h4>
                    <p style="color: #64748B; font-size: 0.88rem; margin: 0 0 0.4rem;">For event publishing, scanner equipment and payout settlements.</p>
                    <a href="mailto:organisers@eventpass.com.na" style="color: var(--ep-teal-dark); font-weight: 700; font-size: 0.95rem; text-decoration: none;">organisers@eventpass.com.na</a>
                </div>
            </div>

            <!-- Office & Operating Hours Card -->
            <div class="ep-support-card">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(11,27,61,0.08); color: var(--ep-navy); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">📍</div>
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">Windhoek Operations Office</h4>
                    <p style="color: #64748B; font-size: 0.88rem; margin: 0 0 0.4rem;">Maerua Mall Office Park, Jan Jonker Road, Windhoek, Namibia.</p>
                    <div style="font-size: 0.82rem; color: #475569; font-weight: 600;">Mon - Fri: 08:00 - 18:00 CAT | Event Days: 08:00 - 02:00 CAT</div>
                </div>
            </div>

        </div>

    </div>
</div>

<?php
get_footer();
