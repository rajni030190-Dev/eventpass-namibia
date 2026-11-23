<?php
/**
 * Custom Template Tags for EventPass Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display the site logo
 */
function eventpass_site_logo() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
        echo '<a href="' . esc_url(home_url('/')) . '" class="ep-logo-link" rel="home">';
        echo '<img src="' . esc_url($logo[0]) . '" alt="' . esc_attr(get_bloginfo('name')) . ' - Every Event. One Pass.">';
        echo '</a>';
    } else {
        $default_logo = get_template_directory_uri() . '/assets/images/logo.png';
        echo '<a href="' . esc_url(home_url('/')) . '" class="ep-logo-link" rel="home">';
        echo '<img src="' . esc_url($default_logo) . '" alt="' . esc_attr(get_bloginfo('name')) . ' - Every Event. One Pass.">';
        echo '</a>';
    }
}

/**
 * Format event dates cleanly
 */
function eventpass_format_event_date($start_timestamp, $end_timestamp = null) {
    if (!$start_timestamp) return '';
    $start_date = date('D, d M Y', $start_timestamp);
    $start_time = date('H:i', $start_timestamp);
    if ($end_timestamp) {
        $end_time = date('H:i', $end_timestamp);
        return "{$start_date} • {$start_time} - {$end_time}";
    }
    return "{$start_date} • {$start_time}";
}

/**
 * Render category badge
 */
function eventpass_render_category_badge($category_name) {
    echo '<span class="ep-category-badge">' . esc_html($category_name) . '</span>';
}
