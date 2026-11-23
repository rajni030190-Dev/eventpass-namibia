<?php
/**
 * Digital Ticket & QR Code Generation Engine (ISO/IEC 18004 Standard Compliant)
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/phpqrcode.php';

class EventPass_QR_Engine {

    /**
     * Generate secure, tamper-proof ticket token
     */
    public static function generate_ticket_token($event_id, $booking_id, $tier, $index) {
        $salt = wp_salt('auth');
        $raw = sprintf('%d-%d-%s-%d-%s', $event_id, $booking_id, $tier, $index, $salt);
        $hash = substr(hash('sha256', $raw), 0, 10);
        return sprintf('EP-NAM-%s-%s', strtoupper(substr($tier, 0, 3)), strtoupper($hash));
    }

    /**
     * Create digital ticket record upon payment confirmation
     */
    public static function issue_ticket($order_id, $event_id, $tier, $holder_name) {
        $token = self::generate_ticket_token($event_id, $order_id, $tier, time());

        $ticket_id = wp_insert_post([
            'post_type'   => 'ep_ticket',
            'post_title'  => sprintf('Ticket: %s (%s)', $token, $holder_name),
            'post_status' => 'publish',
        ]);

        if ($ticket_id && !is_wp_error($ticket_id)) {
            update_post_meta($ticket_id, '_ep_ticket_token', $token);
            update_post_meta($ticket_id, '_ep_order_id', $order_id);
            update_post_meta($ticket_id, '_ep_event_id', $event_id);
            update_post_meta($ticket_id, '_ep_tier', $tier);
            update_post_meta($ticket_id, '_ep_holder_name', $holder_name);
            update_post_meta($ticket_id, '_ep_status', 'valid'); // valid, already_used, cancelled, refunded
            update_post_meta($ticket_id, '_ep_scanned_at', '');
        }

        return $token;
    }

    /**
     * Render authentic ISO/IEC 18004 compliant vector SVG QR code
     * Compatible with all commercial smartphone cameras, Google Lens, and hardware barcode scanners
     *
     * @param string $token Ticket Token (e.g. EP-NAM-VIP-8A2F1B)
     * @param int    $size  Dimension in pixels
     * @return string Valid SVG markup
     */
    public static function render_svg($token, $size = 130) {
        $token = trim((string)$token);
        if (empty($token)) {
            $token = 'EP-NAM-PASS';
        }

        // Generate ISO/IEC 18004 QR Matrix using Reed-Solomon Error Correction Level M (15% redundancy)
        $text_matrix = QRcode::text($token, false, QR_ECLEVEL_M, 1);
        if (empty($text_matrix) || !is_array($text_matrix)) {
            return '<div style="color:red; font-size:12px;">Failed to generate QR</div>';
        }

        $grid_size = count($text_matrix);
        $quiet_zone = 2; // ISO 18004 2-module quiet zone for reliable optical detection
        $total_size = $grid_size + ($quiet_zone * 2);

        $rects = '';
        for ($r = 0; $r < $grid_size; $r++) {
            $row_str = $text_matrix[$r];
            $row_len = strlen($row_str);
            for ($c = 0; $c < $row_len; $c++) {
                if ($row_str[$c] === '1') {
                    $x = $c + $quiet_zone;
                    $y = $r + $quiet_zone;
                    $rects .= sprintf('<rect x="%d" y="%d" width="1" height="1" fill="#0B1B3D"/>', $x, $y);
                }
            }
        }

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" shape-rendering="crispEdges" style="background:#ffffff; border-radius:8px; padding:6px; box-shadow:0 2px 8px rgba(0,0,0,0.12); display:block; margin:0 auto;" aria-label="Authentic ISO 18004 QR Pass for %s">%s</svg>',
            $size,
            $size,
            $total_size,
            $total_size,
            esc_attr($token),
            $rects
        );

        return $svg;
    }

    /**
     * Render Base64 PNG or SVG Data URL for mobile wallet passes / thermal printers
     */
    public static function render_data_url($token, $size = 4) {
        if (function_exists('imagecreate')) {
            ob_start();
            QRcode::png($token, null, QR_ECLEVEL_M, $size, 2);
            $image_data = ob_get_clean();
            if (!empty($image_data)) {
                return 'data:image/png;base64,' . base64_encode($image_data);
            }
        }

        // Resilient fallback to SVG Data URI
        $svg = self::render_svg($token, $size * 35);
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }
}

/**
 * Global helper function to output QR code SVG
 */
function eventpass_get_qr_svg($token, $size = 130) {
    return EventPass_QR_Engine::render_svg($token, $size);
}

// Hook into verified payment
add_action('eventpass_payment_verified', function ($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    foreach ($order->get_items() as $item) {
        $event_id = $item->get_meta('_ep_event_id') ?: 101;
        $tier = $item->get_meta('_ep_tier') ?: 'General';
        $qty = $item->get_quantity();

        for ($i = 0; $i < $qty; $i++) {
            EventPass_QR_Engine::issue_ticket($order_id, $event_id, $tier, $customer_name);
        }
    }
});
