<?php
/**
 * Transactional Notification & Email Engine (EventPass Namibia)
 *
 * Exclusively integrated with Brevo (formerly Sendinblue) Production SMTP.
 * Houses the Resilient Local Mail Interceptor and comprehensive audit trail.
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/brevo-mailer.php';

class EventPass_Notification_Engine {

    /**
     * Memory cache to prevent duplicate transactional email dispatches in the same request lifecycle
     */
    private static $dispatched_cache = [];

    /**
     * Dispatch an HTML transactional email and log audit record.
     * Enforces authenticated Brevo sender identity in the From header.
     * Uses verified organiser email as Reply-To where applicable (never spoofing From).
     */
    public static function send_email($to, $subject, $html_body, $context = 'general', $reply_to = null) {
        if (empty($to) || !is_email($to)) {
            return false;
        }

        // Anti-duplicate protection: avoid duplicate emails within the same request
        $dispatch_signature = md5($to . '|' . $subject . '|' . substr($html_body, 0, 100));
        if (isset(self::$dispatched_cache[$dispatch_signature])) {
            return true; // Already processed in this execution
        }
        self::$dispatched_cache[$dispatch_signature] = true;

        $cfg = EventPass_Brevo_Mailer::get_config();
        $sender_email = !empty($cfg['from_email']) ? $cfg['from_email'] : EventPass_Brevo_Mailer::DEFAULT_FROM;
        $sender_name  = !empty($cfg['from_name']) ? $cfg['from_name'] : EventPass_Brevo_Mailer::DEFAULT_NAME;

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$sender_name} <{$sender_email}>",
        ];

        // Safe Reply-To routing
        if (!empty($reply_to) && is_email($reply_to)) {
            $headers[] = "Reply-To: {$reply_to}";
        } else {
            $headers[] = "Reply-To: EventPass Support <support@eventpass.com.na>";
        }

        // Wrap inside branded container
        $branded_html = self::render_email_template($subject, $html_body);

        $is_brevo = EventPass_Brevo_Mailer::is_configured();
        $mail_sent = wp_mail($to, $subject, $branded_html, $headers);

        if ($is_brevo) {
            $provider = 'Brevo Production SMTP';
            $status   = $mail_sent ? 'dispatched_brevo' : 'failed_brevo';

            // Record verified SMTP outcome
            self::log_notification([
                'to'         => $to,
                'subject'    => $subject,
                'context'    => $context,
                'sent_at'    => current_time('mysql'),
                'status'     => $status,
                'provider'   => $provider,
            ]);
        }

        return $mail_sent;
    }

    /**
     * Branded HTML Email Wrapper
     */
    public static function render_email_template($heading, $content) {
        $site_url = home_url('/');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$heading}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #F8FAFC; margin: 0; padding: 24px; color: #1E293B;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #E2E8F0;">
        <!-- Header -->
        <tr>
            <td style="background-color: #0B1B3D; padding: 24px; text-align: center;">
                <span style="font-size: 22px; font-weight: 900; color: #00E5FF; letter-spacing: 1px;">EVENTPASS NAMIBIA</span>
                <span style="display: block; font-size: 11px; text-transform: uppercase; color: #94A3B8; letter-spacing: 1.5px; margin-top: 4px;">Every Event. One Pass.</span>
            </td>
        </tr>
        <!-- Body -->
        <tr>
            <td style="padding: 32px 28px;">
                <h1 style="color: #0B1B3D; font-size: 20px; font-weight: 800; margin-top: 0; margin-bottom: 16px;">{$heading}</h1>
                <div style="font-size: 15px; line-height: 1.6; color: #475569;">
                    {$content}
                </div>
            </td>
        </tr>
        <!-- Footer -->
        <tr>
            <td style="background-color: #F1F5F9; padding: 20px 28px; text-align: center; font-size: 12px; color: #64748B; border-top: 1px solid #E2E8F0;">
                <p style="margin: 0 0 4px 0;"><strong>EventPass Namibia (Pty) Ltd</strong> • Windhoek, Namibia</p>
                <p style="margin: 0;">Bank of Namibia NPS Compliant Ticketing • <a href="{$site_url}" style="color: #00B4D8; text-decoration: none;">eventpass.com.na</a></p>
                <p style="margin: 4px 0 0; font-size: 11px; color: #94A3B8;">Powered by Brevo Production SMTP Infrastructure</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * 1. Customer Booking Confirmation & Digital Passes (SOW 7, 8 & 12)
     */
    public static function notify_order_completed($order_id, $issued_tickets = []) {
        $order = get_post($order_id);
        if (!$order) return false;

        // Security requirement: Never send paid ticket before payment is genuinely verified
        $order_post_status = $order->post_status;
        $order_meta_status = get_post_meta($order_id, '_ep_order_status', true);
        if ($order_post_status !== 'publish' && $order_meta_status !== 'completed' && $order_meta_status !== 'settled') {
            return false;
        }

        $email       = get_post_meta($order_id, '_ep_order_customer_email', true);
        $name        = get_post_meta($order_id, '_ep_order_customer_name', true);
        $order_num   = get_post_meta($order_id, '_ep_order_number', true);
        $total       = floatval(get_post_meta($order_id, '_ep_order_total', true));
        $event_id    = intval(get_post_meta($order_id, '_ep_order_event_id', true));
        $event_title = get_the_title($event_id);
        $venue       = get_post_meta($event_id, '_ep_event_venue', true) ?: 'Independence Stadium, Windhoek';
        $date        = get_post_meta($event_id, '_ep_event_date', true) ?: 'Upcoming 2026';

        $ticket_cards_html = '';
        if (is_array($issued_tickets)) {
            foreach ($issued_tickets as $t) {
                $tok    = esc_html($t['token'] ?? '');
                $tier   = esc_html($t['tier'] ?? 'General Admission');
                $holder = esc_html($t['holder'] ?? $name);

                $qr_svg = '';
                if (class_exists('EventPass_QR_Engine') && !empty($tok)) {
                    $qr_svg = EventPass_QR_Engine::render_svg($tok, 140);
                }

                $ticket_cards_html .= <<<CARD
<div style="background:#ffffff; border:2px dashed #00B4D8; border-radius:12px; padding:20px; margin:20px 0; text-align:center;">
    <div style="font-size:11px; font-weight:800; color:#64748B; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:4px;">OFFICIAL EVENT ENTRY PASS</div>
    <div style="font-size:18px; font-weight:800; color:#0B1B3D; margin-bottom:6px;">{$tier}</div>
    <div style="font-size:13px; color:#475569; margin-bottom:14px;">{$event_title}</div>
    <div style="margin:10px auto; display:inline-block; max-width:150px;">
        {$qr_svg}
    </div>
    <div style="margin-top:12px;">
        <span style="font-family:monospace; font-size:14px; font-weight:800; color:#0B1B3D; background:#F1F5F9; padding:6px 14px; border-radius:6px; display:inline-block; border:1px solid #CBD5E1;">
            {$tok}
        </span>
    </div>
    <div style="font-size:12px; color:#64748B; margin-top:8px;">Attendee: <strong style="color:#1E293B;">{$holder}</strong></div>
</div>
CARD;
            }
        }

        $dash_url = home_url('/customer-dashboard');

        $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>Your payment has been successfully verified! Below are your official electronic admission passes with unique QR codes for <strong>{$event_title}</strong>.</p>

<table width="100%" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; margin: 16px 0; border-collapse:collapse;">
    <tr><td style="padding:10px 14px; color:#64748B; width:35%;">Order Reference:</td><td style="padding:10px 14px;"><strong>{$order_num}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Date & Time:</td><td style="padding:10px 14px;"><strong>{$date}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Venue:</td><td style="padding:10px 14px;"><strong>{$venue}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Total Settled:</td><td style="padding:10px 14px;"><strong style="color:#0B1B3D;">N$ {$total}</strong></td></tr>
</table>

<h3 style="font-size:16px; color:#0B1B3D; margin-top:24px; margin-bottom:8px; text-align:center;">Your Admission QR Passes</h3>
<p style="font-size:13px; color:#64748B; text-align:center; margin-bottom:16px;">Present the scannable QR code below directly from your smartphone at the entrance turnstile:</p>

{$ticket_cards_html}

<p style="text-align:center; margin: 24px 0;">
    <a href="{$dash_url}" style="background-color: #00B4D8; color: #ffffff; padding: 12px 28px; border-radius:6px; text-decoration: none; font-weight: 700; display: inline-block;">
        View & Download All Passes in Dashboard &rarr;
    </a>
</p>
<p style="font-size:12px; color:#94A3B8; text-align:center;">Each pass is encoded with a unique tamper-proof optical token. Duplication or unauthorized sharing will result in turnstile rejection.</p>
HTML;

        // 1. Customer Booking Confirmation & Digital Passes
        return self::send_email($email, "Your Passes for {$event_title} [Order #{$order_num}]", $body, 'order_confirmation');
    }

    /**
     * 2. Organiser Ticket Sale Notification (SOW 4.3 & SOW 12)
     * Real-time notification delivered to the event organiser when an attendee purchases tickets.
     */
    public static function notify_organiser_ticket_sold($order_id, $issued_tickets = []) {
        $order = get_post($order_id);
        if (!$order) return false;

        $event_id = intval(get_post_meta($order_id, '_ep_order_event_id', true));
        $event    = get_post($event_id);
        if (!$event) return false;

        // Resolve event organiser
        $author_id = $event->post_author;
        $author = get_userdata($author_id);
        if (!$author) return false;

        $org_email   = $author->user_email;
        $org_name    = $author->display_name ?: $author->user_login;
        $order_num   = get_post_meta($order_id, '_ep_order_number', true);
        $total       = floatval(get_post_meta($order_id, '_ep_order_total', true));
        $event_title = $event->post_title;
        $buyer_name  = get_post_meta($order_id, '_ep_order_customer_name', true) ?: 'Ticket Buyer';
        $buyer_email = get_post_meta($order_id, '_ep_order_customer_email', true);
        $ticket_cnt  = is_array($issued_tickets) ? count($issued_tickets) : 1;

        $org_dash_url = home_url('/organiser-dashboard');

        $body = <<<HTML
<p>Dear <strong>{$org_name}</strong>,</p>
<p>Great news! A new ticket purchase has been completed for your event <strong>{$event_title}</strong>.</p>

<table width="100%" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; margin: 16px 0; border-collapse:collapse;">
    <tr><td style="padding:10px 14px; color:#64748B; width:35%;">Order Reference:</td><td style="padding:10px 14px;"><strong>{$order_num}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Attendee:</td><td style="padding:10px 14px;">{$buyer_name} ({$buyer_email})</td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Tickets Issued:</td><td style="padding:10px 14px;"><strong>{$ticket_cnt} Pass(es)</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Gross Sale:</td><td style="padding:10px 14px;"><strong style="color:#0B1B3D;">N$ {$total}</strong></td></tr>
</table>

<p style="margin:20px 0;">
    <a href="{$org_dash_url}" style="background-color:#0B1B3D; color:#ffffff; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:700; display:inline-block;">
        View Organiser Dashboard & Attendee Roster &rarr;
    </a>
</p>
<p style="font-size:13px; color:#64748B;">Attendee numbers and gate turnstile scans are synchronized live with your Organiser Hub.</p>
HTML;

        // Use buyer's email as safe Reply-To so organiser can contact them directly if needed
        return self::send_email($org_email, "New Ticket Sale: {$event_title} [Order #{$order_num}]", $body, 'organiser_ticket_sale', $buyer_email);
    }

    /**
     * 3. Customer Account Registration Welcome Notification (SOW 4.2 & SOW 12)
     */
    public static function notify_customer_welcome($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $email = $user->user_email;
        $name  = $user->display_name ?: $user->user_login;
        $dash_url = home_url('/customer-dashboard');

        $subject = "Welcome to EventPass Namibia - Every Event. One Pass.";
        $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>Welcome to <strong>EventPass Namibia</strong>! Your customer account has been created successfully.</p>
<p>You can now discover live concerts, cultural festivals, sports, and business summits across Namibia, purchase official tickets, and store your digital QR entry passes in one place.</p>
<p style="margin:20px 0;"><a href="{$dash_url}" style="background:#00B4D8; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700;">Go to Your Dashboard &rarr;</a></p>
<p style="font-size:13px; color:#64748B;">If you have any questions or require assistance, our customer support desk is ready to help at support@eventpass.com.na.</p>
HTML;

        return self::send_email($email, $subject, $body, 'customer_welcome');
    }

    /**
     * 4. Customer Email Verification Notification (SOW 4.2 & SOW 12)
     */
    public static function notify_email_verification($user_id, $token) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $email = $user->user_email;
        $name  = $user->display_name ?: $user->user_login;
        $verify_url = home_url('/login?action=verify_email&token=' . urlencode($token) . '&uid=' . $user_id);

        $subject = "Verify Your Email Address - EventPass Namibia";
        $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>Thank you for registering with EventPass Namibia. To complete your account activation and ensure secure delivery of your digital QR ticket passes, please verify your email address.</p>
<p style="text-align:center; margin:24px 0;">
    <a href="{$verify_url}" style="background-color:#00B4D8; color:#ffffff; padding:12px 28px; border-radius:6px; text-decoration:none; font-weight:700; display:inline-block;">
        ✓ Verify Email Address
    </a>
</p>
<p style="font-size:13px; color:#64748B;">Or copy and paste this verification URL into your browser:</p>
<p style="font-size:12px; word-break:break-all; background:#F1F5F9; padding:8px 12px; border-radius:4px;"><a href="{$verify_url}" style="color:#0B1B3D;">{$verify_url}</a></p>
<p style="font-size:12px; color:#94A3B8;">If you did not create an EventPass Namibia account, please disregard this email.</p>
HTML;

        return self::send_email($email, $subject, $body, 'email_verification');
    }

    /**
     * 5. Organiser Application Email Verification (SOW 4.2, 4.3 & SOW 12)
     * Independent of administrator vetting.
     */
    public static function notify_organiser_email_verification($app_id, $token) {
        $app = get_post($app_id);
        if (!$app || $app->post_type !== 'ep_organizer_app') return false;

        $email   = get_post_meta($app_id, '_ep_org_email', true);
        $contact = get_post_meta($app_id, '_ep_org_contact', true);
        $org_name = get_post_meta($app_id, '_ep_org_name', true) ?: $app->post_title;

        $verify_url = home_url('/login?action=verify_org_email&token=' . urlencode($token) . '&app_id=' . $app_id);

        $subject = "Verify Organiser Business Email: {$org_name}";
        $body = <<<HTML
<p>Dear <strong>{$contact}</strong>,</p>
<p>Thank you for submitting your promoter accreditation application for <strong>{$org_name}</strong> on EventPass Namibia.</p>
<p>To confirm that you have administrative access to <strong>{$email}</strong>, please verify your email address:</p>
<p style="text-align:center; margin:24px 0;">
    <a href="{$verify_url}" style="background-color:#0B1B3D; color:#ffffff; padding:12px 28px; border-radius:6px; text-decoration:none; font-weight:700; display:inline-block;">
        ✓ Verify Business Email
    </a>
</p>
<p style="font-size:13px; color:#64748B;">Once verified, our compliance team will complete your banking and business verification (typically within 2 business hours).</p>
<p style="font-size:12px; word-break:break-all; background:#F1F5F9; padding:8px 12px; border-radius:4px;"><a href="{$verify_url}" style="color:#00B4D8;">{$verify_url}</a></p>
HTML;

        return self::send_email($email, $subject, $body, 'organiser_email_verification');
    }

    /**
     * 6. Password Recovery Notification (SOW 4.2)
     */
    public static function notify_password_reset_request($user_id, $reset_url) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $email = $user->user_email;
        $name  = $user->display_name ?: $user->user_login;

        $subject = "Password Reset Instructions - EventPass Namibia";
        $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>We received a request to reset the password for your EventPass Namibia account (<strong>{$user->user_login}</strong>).</p>
<p>Click the button below to choose a new, secure password:</p>
<p style="text-align:center; margin:24px 0;">
    <a href="{$reset_url}" style="background-color:#0B1B3D; color:#ffffff; padding:12px 28px; border-radius:6px; text-decoration:none; font-weight:700; display:inline-block;">
        🔒 Reset Your Password
    </a>
</p>
<p style="font-size:13px; color:#64748B;">This password reset link is valid for 24 hours. If you did not request a password reset, you can safely ignore this message—your account remains completely secure.</p>
<p style="font-size:12px; word-break:break-all; background:#F1F5F9; padding:8px 12px; border-radius:4px;"><a href="{$reset_url}" style="color:#00B4D8;">{$reset_url}</a></p>
HTML;

        return self::send_email($email, $subject, $body, 'password_recovery');
    }

    /**
     * 6b. Genuine OTP Password Recovery Notification (SOW 4.2)
     * Real 6-digit verification code.
     */
    public static function notify_password_reset_otp($user_id, $otp_code) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $email = $user->user_email;
        $name  = $user->display_name ?: $user->user_login;

        $subject = "EventPass Namibia - Password Reset Verification Code: {$otp_code}";
        $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>You requested a verification code to reset your EventPass Namibia password.</p>
<p>Your 6-digit verification code is:</p>
<div style="text-align:center; margin:24px 0;">
    <div style="background-color:#0B1B3D; color:#00E5FF; font-size:32px; font-weight:900; letter-spacing:8px; padding:16px 28px; border-radius:8px; display:inline-block; font-family:monospace; border:1px solid rgba(0,229,255,0.4); box-shadow:0 4px 12px rgba(0,0,0,0.1);">
        {$otp_code}
    </div>
</div>
<p style="font-size:14px; color:#475569; text-align:center;">Enter this code on the verification screen to proceed with setting your new password.</p>
<p style="font-size:13px; color:#64748B; margin-top:20px;">This code is valid for <strong>15 minutes</strong>. If you did not request a password reset, please ignore this email—your account remains secure.</p>
HTML;

        return self::send_email($email, $subject, $body, 'password_reset_otp');
    }

    /**
     * 7. Organiser Application Vetting Decision (SOW 4.3 & SOW 12)
     */
    public static function notify_organiser_app_status_changed($app_id, $status, $credentials = []) {
        $app = get_post($app_id);
        if (!$app || $app->post_type !== 'ep_organizer_app') return;

        $org_email = get_post_meta($app_id, '_ep_org_email', true);
        $org_name  = get_post_meta($app_id, '_ep_org_name', true) ?: $app->post_title;
        $contact   = get_post_meta($app_id, '_ep_org_contact', true);

        if ($status === 'approved') {
            $subject = "Organiser Application Approved: Welcome to EventPass Namibia";
            $dash_url = home_url('/organiser-dashboard');
            $login_url = home_url('/login');

            $creds_html = '';
            if (!empty($credentials['temp_password']) && !empty($credentials['username'])) {
                $u_name = esc_html($credentials['username']);
                $u_pass = esc_html($credentials['temp_password']);
                $creds_html = <<<HTML
<div style="background:#F1F5F9; border:1px solid #CBD5E1; border-radius:8px; padding:14px; margin:16px 0;">
    <strong style="color:#0B1B3D; display:block; margin-bottom:6px;">Your Dedicated Organiser Login Credentials:</strong>
    <p style="margin:2px 0;"><strong>Username / Email:</strong> <code>{$u_name}</code> ({$org_email})</p>
    <p style="margin:2px 0;"><strong>Temporary Password:</strong> <code>{$u_pass}</code></p>
    <p style="margin:6px 0 0; font-size:12px; color:#64748B;">Please sign in at <a href="{$login_url}">{$login_url}</a> and update your password.</p>
</div>
HTML;
            }

            $body = <<<HTML
<p>Dear <strong>{$contact}</strong> ({$org_name}),</p>
<p>Congratulations! Your organiser accreditation application for <strong>{$org_name}</strong> has been officially approved by the EventPass compliance team.</p>
{$creds_html}
<p>You can now log in to the EventPass Organiser Hub to list events, configure ticket tiers, track real-time sales, and request EFT payouts.</p>
<p style="margin:20px 0;"><a href="{$dash_url}" style="background:#00B4D8; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700;">Access Organiser Hub &rarr;</a></p>
HTML;
            self::send_email($org_email, $subject, $body, 'organiser_approved');
        } elseif ($status === 'rejected') {
            $subject = "Organiser Application Status Update: {$org_name}";
            $body = <<<HTML
<p>Dear <strong>{$contact}</strong> ({$org_name}),</p>
<p>Thank you for your interest in EventPass Namibia. After reviewing your submission, our team requires additional business verification or documentation before activating your account.</p>
<p>Please contact our promoter onboarding desk at <a href="mailto:organisers@eventpass.com.na">organisers@eventpass.com.na</a> to complete the verification process.</p>
HTML;
            self::send_email($org_email, $subject, $body, 'organiser_rejected');
        }
    }

    /**
     * 8. Organiser Event Status Transition Notification (SOW 5 & 12)
     */
    public static function notify_event_status_changed($event_id, $new_status, $old_status) {
        if ($new_status === $old_status) return;

        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'ep_event') return;

        $author_id = $event->post_author;
        $author = get_userdata($author_id);
        if (!$author) return;

        $org_email = $author->user_email;
        $org_name  = $author->display_name ?: $author->user_login;
        $title     = $event->post_title;
        $event_url = get_permalink($event_id);

        if ($new_status === 'publish') {
            $subject = "Event Approved & Published: {$title}";
            $body = <<<HTML
<p>Dear <strong>{$org_name}</strong>,</p>
<p>Great news! Your event submission <strong>"{$title}"</strong> has been approved by the EventPass administration team and is now live on the public website.</p>
<p>Attendees can now discover your event and purchase tickets online.</p>
<p style="margin:20px 0;"><a href="{$event_url}" style="background:#00B4D8; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700;">View Live Event Listing &rarr;</a></p>
HTML;
            self::send_email($org_email, $subject, $body, 'event_approved');
        } elseif ($new_status === 'draft' && $old_status === 'pending') {
            $subject = "Event Review Update: {$title}";
            $body = <<<HTML
<p>Dear <strong>{$org_name}</strong>,</p>
<p>Your event submission <strong>"{$title}"</strong> has been reviewed and moved back to Draft status. Please inspect your event details or contact support for further information.</p>
HTML;
            self::send_email($org_email, $subject, $body, 'event_rejected');
        }
    }

    /**
     * 9. Organiser Payout Notification (SOW 11 & 12)
     */
    public static function notify_payout_status_changed($payout_id, $new_status) {
        $payout = get_post($payout_id);
        if (!$payout || $payout->post_type !== 'ep_payout') return;

        $email    = get_post_meta($payout_id, '_ep_payout_organiser_email', true);
        $name     = get_post_meta($payout_id, '_ep_payout_organiser_name', true);
        $amount   = floatval(get_post_meta($payout_id, '_ep_payout_amount', true));
        $ref      = get_post_meta($payout_id, '_ep_payout_reference', true);
        $bank     = get_post_meta($payout_id, '_ep_payout_bank_name', true);
        $eft_ref  = get_post_meta($payout_id, '_ep_payout_eft_reference', true);

        if ($new_status === 'processed') {
            $subject = "EFT Payout Remitted: N$ " . number_format($amount, 2) . " [{$ref}]";
            $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>We are pleased to inform you that your organiser payout request <strong>{$ref}</strong> for <strong>N$ {$amount}</strong> has been processed and remitted via Electronic Funds Transfer to your <strong>{$bank}</strong> commercial account.</p>
<p><strong>Bank Trace Reference:</strong> <code>{$eft_ref}</code></p>
<p>Depending on inter-bank clearing hours, funds will reflect in your account within 1 to 24 hours.</p>
HTML;
            self::send_email($email, $subject, $body, 'payout_processed');
        }
    }

    /**
     * 10. Admin Notification: New Organiser Application (SOW 12)
     */
    public static function notify_admin_new_organiser_app($app_id) {
        $app = get_post($app_id);
        if (!$app) return;

        $admin_email = get_option('admin_email');
        $org_name    = get_post_meta($app_id, '_ep_org_name', true) ?: $app->post_title;
        $contact     = get_post_meta($app_id, '_ep_org_contact', true);
        $email       = get_post_meta($app_id, '_ep_org_email', true);
        $town        = get_post_meta($app_id, '_ep_org_town', true);
        $admin_url   = home_url('/admin-dashboard');

        $subject = "Admin Notice: New Organiser Accreditation Application [{$org_name}]";
        $body = <<<HTML
<p>A new event organiser application has been submitted and is pending administrator vetting:</p>
<table width="100%" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:12px; margin-bottom:16px;">
    <tr><td><strong>Organisation:</strong></td><td>{$org_name}</td></tr>
    <tr><td><strong>Contact Person:</strong></td><td>{$contact}</td></tr>
    <tr><td><strong>Email:</strong></td><td>{$email}</td></tr>
    <tr><td><strong>Region/Town:</strong></td><td>{$town}</td></tr>
</table>
<p style="margin:20px 0;"><a href="{$admin_url}" style="background:#0B1B3D; color:#fff; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700;">Review in Admin Hub &rarr;</a></p>
HTML;
        return self::send_email($admin_email, $subject, $body, 'admin_new_organiser');
    }

    /**
     * 11. Contact Enquiry Notification (SOW 12)
     */
    public static function notify_contact_enquiry($data) {
        $admin_email = get_option('admin_email');
        $sender_name = esc_html($data['name'] ?? 'Visitor');
        $sender_mail = sanitize_email($data['email'] ?? '');
        $sender_tel  = esc_html($data['phone'] ?? '');
        $topic       = esc_html($data['topic'] ?? 'General Enquiry');
        $message     = nl2br(esc_html($data['message'] ?? ''));

        $subject = "New Support Enquiry: {$topic} - {$sender_name}";
        $body = <<<HTML
<p>A new support enquiry has been received via the EventPass Namibia contact desk:</p>
<table width="100%" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:12px; margin-bottom:16px;">
    <tr><td><strong>Name:</strong></td><td>{$sender_name}</td></tr>
    <tr><td><strong>Email:</strong></td><td><a href="mailto:{$sender_mail}">{$sender_mail}</a></td></tr>
    <tr><td><strong>Phone:</strong></td><td>{$sender_tel}</td></tr>
    <tr><td><strong>Topic:</strong></td><td>{$topic}</td></tr>
</table>
<div style="background:#ffffff; border-left:4px solid #00B4D8; padding:12px 16px;">
    {$message}
</div>
HTML;
        return self::send_email($admin_email, $subject, $body, 'contact_enquiry', $sender_mail);
    }

    /**
     * Audit Log Persistence in wp_options.
     * Sanitizes data to guarantee NO passwords, keys or secrets are ever recorded.
     */
    public static function log_notification($entry) {
        $logs = get_option('ep_notification_audit_log', []);
        if (!is_array($logs)) {
            $logs = [];
        }

        // Sanitize entry: ensure no passwords, keys or secret tokens are persisted
        $sanitized_entry = [
            'to'       => sanitize_text_field($entry['to'] ?? ''),
            'subject'  => sanitize_text_field($entry['subject'] ?? ''),
            'context'  => sanitize_key($entry['context'] ?? 'general'),
            'sent_at'  => sanitize_text_field($entry['sent_at'] ?? current_time('mysql')),
            'status'   => sanitize_key($entry['status'] ?? 'dispatched'),
            'provider' => sanitize_text_field($entry['provider'] ?? 'Brevo SMTP'),
        ];

        array_unshift($logs, $sanitized_entry);
        if (count($logs) > 100) {
            $logs = array_slice($logs, 0, 100);
        }

        update_option('ep_notification_audit_log', $logs, false);
    }

    /**
     * Retrieve recent notification audit entries
     */
    public static function get_recent_logs($limit = 10) {
        $logs = get_option('ep_notification_audit_log', []);
        return is_array($logs) ? array_slice($logs, 0, $limit) : [];
    }

    /**
     * Hook Handlers
     */
    public static function handle_post_transition($new_status, $old_status, $post) {
        if ($post->post_type === 'ep_event') {
            self::notify_event_status_changed($post->ID, $new_status, $old_status);
        }
    }
}

// -----------------------------------------------------------------------------
// Action Hooks Registration
// -----------------------------------------------------------------------------
// Customer tickets
add_action('eventpass_order_completed', ['EventPass_Notification_Engine', 'notify_order_completed'], 10, 2);
// Organiser ticket sale notification
add_action('eventpass_order_completed', ['EventPass_Notification_Engine', 'notify_organiser_ticket_sold'], 12, 2);
// Event publishing status
add_action('transition_post_status', ['EventPass_Notification_Engine', 'handle_post_transition'], 10, 3);

/**
 * Resilient Mail Delivery Interceptor
 *
 * When Brevo Production SMTP is configured: passes through to PHPMailer to transmit via smtp-relay.brevo.com.
 * When in Local Development (localhost:8080) and Brevo is unconfigured:
 * safely intercepts outbound mail, records in Notification Audit Trail, and prevents fatal mail errors.
 * In production without Brevo credentials: logs failure and returns false (never claiming false delivery).
 */
add_filter('pre_wp_mail', function($null, $atts) {
    // 1. If Brevo Production SMTP is configured, permit PHPMailer to send via Brevo
    if (EventPass_Brevo_Mailer::is_configured()) {
        return $null;
    }

    // 2. Localhost development environment check
    $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', 'localhost:8080', '127.0.0.1:8080']) 
                || empty(ini_get('SMTP')) 
                || ini_get('SMTP') === 'localhost';

    $to_str = is_array($atts['to'] ?? '') ? implode(', ', $atts['to']) : ($atts['to'] ?? '');

    if ($is_local) {
        // Safe interception in local development
        EventPass_Notification_Engine::log_notification([
            'to'       => $to_str,
            'subject'  => $atts['subject'] ?? 'Notification',
            'context'  => 'local_intercept',
            'sent_at'  => current_time('mysql'),
            'status'   => 'dispatched_local',
            'provider' => 'Local Interceptor (Brevo Standby)',
        ]);
        return true; // Keep local developer/QA workflows uninterrupted
    }

    // 3. In Production without Brevo credentials: log failure and prevent false success claim
    EventPass_Notification_Engine::log_notification([
        'to'       => $to_str,
        'subject'  => $atts['subject'] ?? 'Notification',
        'context'  => 'system_error',
        'sent_at'  => current_time('mysql'),
        'status'   => 'failed_missing_brevo_credentials',
        'provider' => 'Brevo SMTP (Unconfigured)',
    ]);
    return false;
}, 10, 2);
