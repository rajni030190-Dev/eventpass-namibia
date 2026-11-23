<?php
/**
 * EventPass Namibia Theme Functions and Definitions
 *
 * @package EventPass_Namibia
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('EVENTPASS_THEME_VERSION', '1.0.0');
define('EVENTPASS_THEME_DIR', get_template_directory());
define('EVENTPASS_THEME_URI', get_template_directory_uri());

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function eventpass_theme_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title.
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support('post-thumbnails');
    add_image_size('ep-event-card', 640, 360, true);
    add_image_size('ep-event-hero', 1920, 800, true);
    add_image_size('ep-event-gallery', 800, 600, true);

    // Switch default core markup for search form, comment form, and comments to output valid HTML5.
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    // Add support for core custom logo.
    add_theme_support('custom-logo', [
        'height'      => 90,
        'width'       => 300,
        'flex-width'  => true,
        'flex-height' => true,
    ]);

    // WooCommerce support
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    // Register Navigation Menus
    register_nav_menus([
        'primary-menu'   => __('Primary Header Menu', 'eventpass-namibia'),
        'footer-quick'   => __('Footer Quick Links', 'eventpass-namibia'),
        'footer-legal'   => __('Footer Legal Links', 'eventpass-namibia'),
        'organiser-menu' => __('Organiser Dashboard Menu', 'eventpass-namibia'),
    ]);
}
add_action('after_setup_theme', 'eventpass_theme_setup');

/**
 * Enqueue scripts and styles.
 */
function eventpass_theme_scripts() {
    // Google Fonts Inter
    wp_enqueue_style(
        'ep-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        [],
        null
    );

    // Main Theme Stylesheet
    wp_enqueue_style(
        'eventpass-style',
        get_stylesheet_uri(),
        ['ep-google-fonts'],
        EVENTPASS_THEME_VERSION
    );

    // Main Theme JavaScript
    wp_enqueue_script(
        'eventpass-main',
        EVENTPASS_THEME_URI . '/assets/js/main.js',
        ['jquery'],
        EVENTPASS_THEME_VERSION,
        true
    );

    // Localize data for AJAX endpoints
    wp_localize_script('eventpass-main', 'eventpass_data', [
        'ajax_url'     => admin_url('admin-ajax.php'),
        'rest_url'     => esc_url_raw(rest_url('eventpass/v1/')),
        'nonce'        => wp_create_nonce('wp_rest'),
        'currency'     => 'N$',
        'home_url'     => home_url('/'),
    ]);
}
add_action('wp_enqueue_scripts', 'eventpass_theme_scripts');

/**
 * Add Namibian Dollar (NAD) currency support to WooCommerce if not present.
 */
function eventpass_add_nad_currency($currencies) {
    if (!isset($currencies['NAD'])) {
        $currencies['NAD'] = __('Namibian Dollar (N$)', 'eventpass-namibia');
    }
    return $currencies;
}
add_filter('woocommerce_currencies', 'eventpass_add_nad_currency');

function eventpass_add_nad_currency_symbol($currency_symbol, $currency) {
    if ($currency === 'NAD') {
        $currency_symbol = 'N$ ';
    }
    return $currency_symbol;
}
add_filter('woocommerce_currency_symbol', 'eventpass_add_nad_currency_symbol', 10, 2);

/**
 * Helper: Format price in Namibian Dollar.
 */
function eventpass_format_price($amount) {
    return 'N$ ' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Support /login as canonical alias for the authentication portal
 */
function ep_login_rewrite_rules() {
    add_rewrite_rule('^login/?$', 'index.php?pagename=customer-login', 'top');
}
add_action('init', 'ep_login_rewrite_rules');

/**
 * Custom template tags and filters
 */
require_once EVENTPASS_THEME_DIR . '/template-parts/template-tags.php';
