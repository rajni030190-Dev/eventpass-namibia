<?php
/**
 * Transactional Notification & Email Engine
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventPass_Notification_Engine {

    /**
     * Dispatch an HTML transactional email and log audit record
     */
    public static function send_email($to, $subject, $html_body, $context = 'general') {
        if (empty($to) || !is_email($to)) {
            return false;
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: EventPass Namibia <no-reply@eventpass.com.na>',
            'Reply-To: EventPass Support <support@eventpass.com.na>',
        ];

        // Wrap inside branded container
        $branded_html = self::render_email_template($subject, $html_body);

        // Record in notification audit log
        self::log_notification([
            'to'         => $to,
            'subject'    => $subject,
            'context'    => $context,
            'sent_at'    => current_time('mysql'),
            'status'     => 'dispatched',
        ]);

        return wp_mail($to, $subject, $branded_html, $headers);
    }

    /**
     * Branded HTML Email Wrapper
     */
    public static function render_email_template($heading, $content) {
        $logo_url = home_url('/wp-content/themes/eventpass-theme/assets/images/logo.png');
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
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * 1. Customer Booking Confirmation Email
     */
    public static function notify_order_completed($order_id, $issued_tickets = []) {
        $order = get_post($order_id);
        if (!$order) return;

        $email       = get_post_meta($order_id, '_ep_order_customer_email', true);
        $name        = get_post_meta($order_id, '_ep_order_customer_name', true);
        $order_num   = get_post_meta($order_id, '_ep_order_number', true);
        $total       = floatval(get_post_meta($order_id, '_ep_order_total', true));
        $event_id    = intval(get_post_meta($order_id, '_ep_order_event_id', true));
        $event_title = get_the_title($event_id);
        $venue       = get_post_meta($event_id, '_ep_event_venue', true) ?: 'Independence Stadium, Windhoek';
        $date        = get_post_meta($event_id, '_ep_event_date', true) ?: 'Upcoming 2026';

        $ticket_rows = '';
        if (is_array($issued_tickets)) {
            foreach ($issued_tickets as $t) {
                $tok = esc_html($t['token'] ?? '');
                $tier = esc_html($t['tier'] ?? 'General Admission');
                $ticket_rows .= "<tr>
                    <td style='padding: 8px 12px; border-bottom: 1px solid #E2E8F0;'><strong>{$tier}</strong></td>
                    <td style='padding: 8px 12px; border-bottom: 1px solid #E2E8F0;'><code style='color:#00B4D8; font-weight:700;'>{$tok}</code></td>
                </tr>";
            }
        }

        $dash_url = home_url('/customer-dashboard');

        $body = <<<HTML
<p>Dear <strong>{$name}</strong>,</p>
<p>Your ticket purchase for <strong>{$event_title}</strong> has been successfully confirmed and settled via Finatic Namibia Gateway.</p>

<table width="100%" style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; margin: 16px 0; border-collapse:collapse;">
    <tr><td style="padding:10px 14px; color:#64748B; width:35%;">Order Reference:</td><td style="padding:10px 14px;"><strong>{$order_num}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Date & Time:</td><td style="padding:10px 14px;"><strong>{$date}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Venue:</td><td style="padding:10px 14px;"><strong>{$venue}</strong></td></tr>
    <tr><td style="padding:10px 14px; color:#64748B;">Total Amount:</td><td style="padding:10px 14px;"><strong style="color:#0B1B3D;">N$ {$total}</strong></td></tr>
</table>

<h3 style="font-size:15px; color:#0B1B3D; margin-top:20px; margin-bottom:10px;">Your Digital Passes</h3>
<table width="100%" style="border-collapse:collapse; margin-bottom:20px;">
    <thead>
        <tr style="background:#F1F5F9; text-align:left; font-size:12px; color:#475569;">
            <th style="padding:8px 12px;">Category</th>
            <th style="padding:8px 12px;">Verification Token</th>
        </tr>
    </thead>
    <tbody>
        {$ticket_rows}
    </tbody>
</table>

<p style="text-align:center; margin: 24px 0;">
    <a href="{$dash_url}" style="background-color: #00B4D8; color: #ffffff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 700; display: inline-block;">
        View & Print Your Passes &rarr;
    </a>
</p>
<p style="font-size:13px; color:#64748B;">You can present your QR pass directly from your smartphone at the entrance gate for instant contactless verification.</p>
HTML;

        self::send_email($email, "Your Passes for {$event_title} [Order #{$order_num}]", $body, 'order_confirmation');
    }

    /**
     * 2. Organiser Event Status Transition Notification
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
     * 3. Organiser Payout Notification
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
     * 4. Contact Enquiry Notification
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
        return self::send_email($admin_email, $subject, $body, 'contact_enquiry');
    }

    /**
     * 5. Organiser Application Decision Notification
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
            $login_url = home_url('/customer-login');

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
     * Audit Log Persistence in wp_options
     */
    public static function log_notification($entry) {
        $logs = get_option('ep_notification_audit_log', []);
        if (!is_array($logs)) {
            $logs = [];
        }

        array_unshift($logs, $entry);
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

// Register Action Hooks
add_action('eventpass_order_completed', ['EventPass_Notification_Engine', 'notify_order_completed'], 10, 2);
add_action('transition_post_status', ['EventPass_Notification_Engine', 'handle_post_transition'], 10, 3);
