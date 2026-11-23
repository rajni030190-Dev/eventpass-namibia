<?php
/**
 * Custom Post Types for Events and Applications
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

function eventpass_register_cpts() {
    // 1. Event Custom Post Type
    $event_labels = [
        'name'               => _x('Events', 'post type general name', 'eventpass-core'),
        'singular_name'      => _x('Event', 'post type singular name', 'eventpass-core'),
        'menu_name'          => _x('EventPass Events', 'admin menu', 'eventpass-core'),
        'add_new'            => _x('Add New Event', 'event', 'eventpass-core'),
        'add_new_item'       => __('Add New Event', 'eventpass-core'),
        'edit_item'          => __('Edit Event', 'eventpass-core'),
        'new_item'           => __('New Event', 'eventpass-core'),
        'all_items'          => __('All Events', 'eventpass-core'),
        'view_item'          => __('View Event', 'eventpass-core'),
        'search_items'       => __('Search Events', 'eventpass-core'),
        'not_found'          => __('No events found', 'eventpass-core'),
    ];

    register_post_type('ep_event', [
        'labels'             => $event_labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => ['slug' => 'event', 'with_front' => false],
        'capability_type'    => 'post',
        'has_archive'        => 'events',
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-tickets-alt',
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt', 'author', 'custom-fields'],
        'show_in_rest'       => true,
    ]);

    // 2. Organiser Application CPT (Private Admin Vetting)
    register_post_type('ep_organizer_app', [
        'labels'             => [
            'name'          => __('Organiser Applications', 'eventpass-core'),
            'singular_name' => __('Organiser Application', 'eventpass-core'),
        ],
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => 'edit.php?post_type=ep_event',
        'supports'           => ['title', 'custom-fields'],
    ]);
}
add_action('init', 'eventpass_register_cpts');

/**
 * Add custom columns to ep_event admin list
 */
function eventpass_ep_event_columns($columns) {
    $new_cols = [];
    foreach ($columns as $k => $v) {
        $new_cols[$k] = $v;
        if ($k === 'title') {
            $new_cols['event_category'] = __('Category', 'eventpass-core');
            $new_cols['event_date']     = __('Event Date', 'eventpass-core');
            $new_cols['event_venue']    = __('Venue & Town', 'eventpass-core');
            $new_cols['event_pricing']  = __('Tickets From', 'eventpass-core');
            $new_cols['event_capacity'] = __('Sold / Capacity', 'eventpass-core');
        }
    }
    return $new_cols;
}
add_filter('manage_ep_event_posts_columns', 'eventpass_ep_event_columns');

/**
 * Output custom column content for ep_event
 */
function eventpass_ep_event_custom_column($column, $post_id) {
    switch ($column) {
        case 'event_category':
            $terms = get_the_terms($post_id, 'ep_event_category');
            if (!empty($terms) && !is_wp_error($terms)) {
                $names = wp_list_pluck($terms, 'name');
                echo esc_html(implode(', ', $names));
            } else {
                echo '<span style="color:#94A3B8;">—</span>';
            }
            break;

        case 'event_date':
            $d = get_post_meta($post_id, '_ep_event_date', true);
            $t = get_post_meta($post_id, '_ep_event_time', true);
            echo '<strong>' . esc_html($d ?: 'Upcoming') . '</strong>';
            if ($t) {
                echo '<br><span style="font-size:0.8rem; color:#64748B;">' . esc_html($t) . '</span>';
            }
            break;

        case 'event_venue':
            $v = get_post_meta($post_id, '_ep_event_venue', true);
            $town = get_post_meta($post_id, '_ep_event_town', true);
            echo esc_html($v ?: 'TBA');
            if ($town) {
                echo '<br><span style="font-size:0.8rem; color:#0284C7; font-weight:600;">' . esc_html(ucfirst($town)) . '</span>';
            }
            break;

        case 'event_pricing':
            $min = get_post_meta($post_id, '_ep_min_price', true);
            if ($min !== '') {
                echo '<strong>N$ ' . number_format((float)$min, 2) . '</strong>';
            } else {
                echo '<span style="color:#94A3B8;">Free / TBA</span>';
            }
            break;

        case 'event_capacity':
            $tiers = get_post_meta($post_id, '_ep_ticket_tiers', true);
            if (is_array($tiers) && !empty($tiers)) {
                $total_cap = 0;
                $total_sold = 0;
                foreach ($tiers as $tier) {
                    $total_cap += intval($tier['total_quantity'] ?? 0);
                    $total_sold += intval($tier['sold_quantity'] ?? 0);
                }
                echo '<strong>' . number_format($total_sold) . '</strong> / ' . number_format($total_cap);
            } else {
                echo '<span style="color:#94A3B8;">No Tiers</span>';
            }
            break;
    }
}
add_action('manage_ep_event_posts_custom_column', 'eventpass_ep_event_custom_column', 10, 2);

/**
 * Helper: Retrieve structured ticket tiers for an event
 */
function eventpass_get_event_tiers($event_id) {
    $tiers = get_post_meta($event_id, '_ep_ticket_tiers', true);
    if (!is_array($tiers)) {
        return [];
    }
    return $tiers;
}

/**
 * Helper: Retrieve minimum ticket price for an event
 */
function eventpass_get_min_ticket_price($event_id) {
    $min = get_post_meta($event_id, '_ep_min_price', true);
    if ($min !== '') {
        return (float) $min;
    }
    $tiers = eventpass_get_event_tiers($event_id);
    if (empty($tiers)) {
        return 0.0;
    }
    $prices = [];
    foreach ($tiers as $tier) {
        $prices[] = (float) ($tier['price'] ?? 0);
    }
    $min_val = min($prices);
    update_post_meta($event_id, '_ep_min_price', $min_val);
    return $min_val;
}
