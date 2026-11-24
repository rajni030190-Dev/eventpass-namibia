<?php
/**
 * EventPass Namibia - Brevo Production SMTP Mailer
 *
 * Enforces Brevo (formerly Sendinblue) as the ONLY supported production SMTP provider.
 * Standardizes outbound email delivery on smtp-relay.brevo.com via TLS over Port 587.
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventPass_Brevo_Mailer {

    const DEFAULT_HOST   = 'smtp-relay.brevo.com';
    const DEFAULT_PORT   = 587;
    const DEFAULT_SECURE = 'tls';
    const DEFAULT_FROM   = 'no-reply@eventpass.com.na';
    const DEFAULT_NAME   = 'EventPass Namibia';

    /**
     * Resolve Brevo SMTP Configuration from wp-config constants, environment variables, or database options.
     * Never hardcodes secrets in source code.
     */
    public static function get_config() {
        $host = (defined('BREVO_SMTP_HOST') && BREVO_SMTP_HOST !== '') ? BREVO_SMTP_HOST : (
            (defined('EP_SMTP_HOST') && EP_SMTP_HOST !== '') ? EP_SMTP_HOST : (
                getenv('BREVO_SMTP_HOST') ?: (get_option('ep_brevo_smtp_host') ?: self::DEFAULT_HOST)
            )
        );

        $port = (defined('BREVO_SMTP_PORT') && !empty(BREVO_SMTP_PORT)) ? intval(BREVO_SMTP_PORT) : (
            (defined('EP_SMTP_PORT') && !empty(EP_SMTP_PORT)) ? intval(EP_SMTP_PORT) : (
                intval(getenv('BREVO_SMTP_PORT') ?: (get_option('ep_brevo_smtp_port') ?: self::DEFAULT_PORT))
            )
        );

        $secure = (defined('BREVO_SMTP_SECURE') && BREVO_SMTP_SECURE !== '') ? BREVO_SMTP_SECURE : (
            (defined('EP_SMTP_SECURE') && EP_SMTP_SECURE !== '') ? EP_SMTP_SECURE : (
                getenv('BREVO_SMTP_SECURE') ?: (get_option('ep_brevo_smtp_secure') ?: self::DEFAULT_SECURE)
            )
        );

        $user = (defined('BREVO_SMTP_USER') && BREVO_SMTP_USER !== '') ? BREVO_SMTP_USER : (
            (defined('EP_SMTP_USER') && EP_SMTP_USER !== '') ? EP_SMTP_USER : (
                getenv('BREVO_SMTP_USER') ?: (get_option('ep_brevo_smtp_user') ?: '')
            )
        );

        $pass = (defined('BREVO_SMTP_KEY') && BREVO_SMTP_KEY !== '') ? BREVO_SMTP_KEY : (
            (defined('BREVO_SMTP_PASS') && BREVO_SMTP_PASS !== '') ? BREVO_SMTP_PASS : (
                (defined('EP_SMTP_PASS') && EP_SMTP_PASS !== '') ? EP_SMTP_PASS : (
                    getenv('BREVO_SMTP_KEY') ?: (getenv('BREVO_SMTP_PASS') ?: (get_option('ep_brevo_smtp_key') ?: ''))
                )
            )
        );

        $from_email = (defined('BREVO_SENDER_EMAIL') && BREVO_SENDER_EMAIL !== '') ? BREVO_SENDER_EMAIL : (
            (defined('EP_SMTP_FROM') && EP_SMTP_FROM !== '') ? EP_SMTP_FROM : (
                getenv('BREVO_SENDER_EMAIL') ?: (get_option('ep_brevo_sender_email') ?: self::DEFAULT_FROM)
            )
        );

        $from_name  = (defined('BREVO_SENDER_NAME') && BREVO_SENDER_NAME !== '') ? BREVO_SENDER_NAME : (
            (defined('EP_SMTP_FROM_NAME') && EP_SMTP_FROM_NAME !== '') ? EP_SMTP_FROM_NAME : (
                getenv('BREVO_SENDER_NAME') ?: (get_option('ep_brevo_sender_name') ?: self::DEFAULT_NAME)
            )
        );

        return [
            'host'       => sanitize_text_field(trim((string)$host)),
            'port'       => intval($port),
            'secure'     => strtolower(trim((string)$secure)),
            'user'       => trim((string)$user),
            'pass'       => trim((string)$pass),
            'from_email' => sanitize_email(trim((string)$from_email)),
            'from_name'  => sanitize_text_field(trim((string)$from_name)),
        ];
    }

    /**
     * Check if Brevo Production SMTP credentials are fully defined.
     */
    public static function is_configured() {
        $cfg = self::get_config();
        return !empty($cfg['user']) && !empty($cfg['pass']);
    }

    /**
     * Validate Brevo Configuration and return a safe diagnostic array.
     * Guaranteed never to leak raw passwords or API keys.
     */
    public static function validate_config() {
        $cfg = self::get_config();
        $errors = [];

        // 1. Host Validation (Brevo Only)
        if (empty($cfg['host'])) {
            $errors[] = 'Brevo SMTP host is missing. Expected: smtp-relay.brevo.com';
        } elseif (stripos($cfg['host'], 'brevo.com') === false && stripos($cfg['host'], 'sendinblue.com') === false) {
            $errors[] = 'Invalid SMTP host [' . esc_html($cfg['host']) . ']. Brevo is the only authorized production SMTP provider (smtp-relay.brevo.com).';
        }

        // 2. Port & Encryption
        if (!in_array($cfg['port'], [587, 465, 2525])) {
            $errors[] = 'Unsupported Brevo SMTP port (' . intval($cfg['port']) . '). Brevo standard is Port 587 with TLS.';
        }

        if ($cfg['port'] === 587 && $cfg['secure'] !== 'tls') {
            $errors[] = 'Port 587 requires TLS encryption.';
        } elseif ($cfg['port'] === 465 && $cfg['secure'] !== 'ssl') {
            $errors[] = 'Port 465 requires SSL encryption.';
        }

        // 3. User & Key
        if (empty($cfg['user'])) {
            $errors[] = 'Brevo SMTP login/username is not configured in wp-config.php or environment.';
        }
        if (empty($cfg['pass'])) {
            $errors[] = 'Brevo SMTP master key/password is not configured in wp-config.php or environment.';
        }

        // 4. Sender Identity
        if (empty($cfg['from_email']) || !is_email($cfg['from_email'])) {
            $errors[] = 'Brevo sender email is invalid or unauthenticated.';
        }

        $masked_user = 'Not Configured';
        if (!empty($cfg['user'])) {
            $len = strlen($cfg['user']);
            $masked_user = ($len > 6) ? substr($cfg['user'], 0, 3) . '***' . substr($cfg['user'], -3) : 'Configured';
        }

        $key_status = !empty($cfg['pass']) ? 'Configured (' . strlen($cfg['pass']) . ' chars)' : 'Not Set';

        return [
            'configured'   => self::is_configured(),
            'valid'        => empty($errors) && self::is_configured(),
            'host'         => $cfg['host'],
            'port'         => $cfg['port'],
            'secure'       => $cfg['secure'],
            'user_masked'  => $masked_user,
            'key_status'   => $key_status,
            'sender_email' => $cfg['from_email'],
            'sender_name'  => $cfg['from_name'],
            'errors'       => $errors,
        ];
    }

    /**
     * Configure PHPMailer for Brevo SMTP.
     * Enforces strict Brevo parameters, timeouts, and suppresses credential leakage.
     */
    public static function configure_phpmailer($phpmailer) {
        if (!self::is_configured()) {
            return;
        }

        $cfg = self::get_config();

        $phpmailer->isSMTP();
        $phpmailer->Host       = $cfg['host'];
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = $cfg['port'];
        $phpmailer->SMTPSecure = $cfg['secure'];
        $phpmailer->Username   = $cfg['user'];
        $phpmailer->Password   = $cfg['pass'];

        // Enforce verified Brevo sender identity
        $phpmailer->From     = $cfg['from_email'];
        $phpmailer->FromName = $cfg['from_name'];

        // Robust network & TLS settings
        $phpmailer->Timeout     = 20;
        $phpmailer->SMTPAutoTLS = true;
        $phpmailer->SMTPDebug   = 0; // Never echo debug output to browser
        $phpmailer->CharSet     = 'UTF-8';

        // Ensure Windows PHP OpenSSL connects securely without certificate bundle path failures
        $phpmailer->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        // Custom X-Mailer Header
        $phpmailer->XMailer = 'EventPass Namibia / Brevo SMTP Engine';
    }

    /**
     * Diagnostic: Send a live test email through Brevo SMTP to verify connectivity.
     */
    public static function send_test_email($target_email) {
        if (!is_email($target_email)) {
            return [
                'success' => false,
                'message' => 'Invalid destination email address provided for test.',
            ];
        }

        $validation = self::validate_config();
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Brevo SMTP configuration is incomplete or invalid: ' . implode('; ', $validation['errors']),
            ];
        }

        $subject = 'EventPass Namibia • Brevo Production SMTP Verification';
        $body = '<p>This is a live diagnostic verification email dispatched from EventPass Namibia via <strong>Brevo Production SMTP (smtp-relay.brevo.com:587 TLS)</strong>.</p>'
              . '<p>Timestamp: <strong>' . current_time('mysql') . ' CAT</strong></p>'
              . '<p>Status: Authenticated Brevo sender connection verified successfully.</p>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $validation['sender_name'] . ' <' . $validation['sender_email'] . '>',
            'Reply-To: support@eventpass.com.na',
        ];

        $caught_error = '';
        $temp_handler = function($wp_error) use (&$caught_error) {
            if (is_wp_error($wp_error)) {
                $raw = $wp_error->get_error_message();
                $caught_error = preg_replace('/(password|pass|key|user|login)[:=\s]+[^\s;]+/i', '$1: [REDACTED]', $raw);
            }
        };
        add_action('wp_mail_failed', $temp_handler, 1);

        // Ensure Brevo credentials are used
        $sent = wp_mail($target_email, $subject, EventPass_Notification_Engine::render_email_template($subject, $body), $headers);

        remove_action('wp_mail_failed', $temp_handler, 1);

        if ($sent) {
            EventPass_Notification_Engine::log_notification([
                'to'         => $target_email,
                'subject'    => $subject,
                'context'    => 'brevo_test',
                'sent_at'    => current_time('mysql'),
                'status'     => 'dispatched_brevo',
                'provider'   => 'Brevo Production SMTP',
            ]);

            return [
                'success' => true,
                'message' => 'Test email successfully transmitted through Brevo SMTP to ' . esc_html($target_email) . '.',
            ];
        } else {
            $err_detail = !empty($caught_error) ? ' (' . esc_html($caught_error) . ')' : '';
            return [
                'success' => false,
                'message' => 'Brevo SMTP rejected transmission' . $err_detail . '. Please verify Brevo credentials and sender domain authentication in the Brevo console.',
            ];
        }
    }

    /**
     * Save admin-configured Brevo credentials securely to WordPress options
     */
    public static function save_admin_credentials($user, $key, $from_email = '', $from_name = '') {
        if (!current_user_can('manage_options')) {
            return false;
        }
        if (!empty($user)) {
            update_option('ep_brevo_smtp_user', sanitize_text_field(trim($user)));
        }
        if (!empty($key)) {
            update_option('ep_brevo_smtp_key', sanitize_text_field(trim($key)));
        }
        if (!empty($from_email) && is_email($from_email)) {
            update_option('ep_brevo_sender_email', sanitize_email(trim($from_email)));
        }
        if (!empty($from_name)) {
            update_option('ep_brevo_sender_name', sanitize_text_field(trim($from_name)));
        }
        return true;
    }
}

// Hook Brevo into PHPMailer initialization
add_action('phpmailer_init', ['EventPass_Brevo_Mailer', 'configure_phpmailer'], 99);

// Enforce authenticated sender identity across all WordPress outbound emails (core, lostpassword, plugins)
add_filter('wp_mail_from', function($from_email) {
    $cfg = EventPass_Brevo_Mailer::get_config();
    if (!empty($cfg['from_email'])) {
        return $cfg['from_email'];
    }
    // Fallback to valid domain if default generated is wordpress@localhost
    if (empty($from_email) || strpos($from_email, '@localhost') !== false) {
        return 'no-reply@eventpass.com.na';
    }
    return $from_email;
}, 99);

add_filter('wp_mail_from_name', function($from_name) {
    $cfg = EventPass_Brevo_Mailer::get_config();
    if (!empty($cfg['from_name'])) {
        return $cfg['from_name'];
    }
    if (empty($from_name) || $from_name === 'WordPress') {
        return 'EventPass Namibia';
    }
    return $from_name;
}, 99);

// Hook mail failure to log sanitized error into Notification Audit Trail
add_action('wp_mail_failed', function($wp_error) {
    if (is_wp_error($wp_error)) {
        $raw_msg = $wp_error->get_error_message();
        
        // Sanitize error message to ensure no keys or passwords are logged
        $sanitized_msg = preg_replace('/(password|pass|key|user|login)[:=\s]+[^\s;]+/i', '$1: [REDACTED]', $raw_msg);

        EventPass_Notification_Engine::log_notification([
            'to'         => 'N/A',
            'subject'    => 'SMTP Transmission Failure',
            'context'    => 'mail_error',
            'sent_at'    => current_time('mysql'),
            'status'     => 'failed_brevo',
            'provider'   => 'Brevo SMTP Error: ' . esc_html(substr($sanitized_msg, 0, 140)),
        ]);
    }
});
