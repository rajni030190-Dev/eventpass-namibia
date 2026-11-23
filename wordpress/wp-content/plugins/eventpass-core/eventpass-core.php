<?php
/**
 * Plugin Name: EventPass Namibia Core Platform
 * Plugin URI: https://eventpass.com.na
 * Description: Mission-critical marketplace engine for EventPass Namibia. Manages custom event post types, multi-tier ticketing, anti-oversell inventory, Finatic payment integration, cryptographic QR codes, gate check-in REST API, and organiser commissions.
 * Version: 1.0.0
 * Author: EventPass Solutions Architecture
 * Author URI: https://eventpass.com.na
 * Text Domain: eventpass-core
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EVENTPASS_CORE_VERSION', '1.0.0');
define('EVENTPASS_CORE_PATH', plugin_dir_path(__FILE__));
define('EVENTPASS_CORE_URL', plugin_dir_url(__FILE__));

/**
 * Autoload core modules
 */
require_once EVENTPASS_CORE_PATH . 'includes/cpt-events.php';
require_once EVENTPASS_CORE_PATH . 'includes/cpt-tickets.php';
require_once EVENTPASS_CORE_PATH . 'includes/cpt-orders.php';
require_once EVENTPASS_CORE_PATH . 'includes/taxonomies.php';
require_once EVENTPASS_CORE_PATH . 'includes/qr-engine.php';
require_once EVENTPASS_CORE_PATH . 'includes/scanner-api.php';
require_once EVENTPASS_CORE_PATH . 'includes/commission-payout.php';
require_once EVENTPASS_CORE_PATH . 'includes/notifications.php';
require_once EVENTPASS_CORE_PATH . 'includes/finatic-gateway.php';

/**
 * Activation Hook: Register custom roles
 */
function eventpass_core_activate() {
    // 1. Event Organiser Role
    add_role('event_organiser', __('Event Organiser', 'eventpass-core'), [
        'read'                   => true,
        'edit_ep_events'         => true,
        'publish_ep_events'      => false, // Requires Admin Approval
        'upload_files'           => true,
        'view_event_sales'       => true,
        'export_attendee_lists'  => true,
        'request_payouts'        => true,
    ]);

    // 2. Event Gate Staff Role
    add_role('event_gate_staff', __('Gate Access Staff', 'eventpass-core'), [
        'read'                   => true,
        'scan_event_tickets'     => true,
        'view_checkin_stats'     => true,
    ]);

    // 3. Customer Role
    add_role('event_customer', __('Event Attendee', 'eventpass-core'), [
        'read'                   => true,
        'download_tickets'       => true,
        'view_order_history'     => true,
    ]);

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'eventpass_core_activate');

/**
 * Deactivation Hook
 */
function eventpass_core_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'eventpass_core_deactivate');
