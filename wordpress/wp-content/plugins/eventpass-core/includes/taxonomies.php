<?php
/**
 * Taxonomies for Event Categories and Namibian Towns
 * Fully Dynamic, Database-Driven Event Category Management
 *
 * @package EventPass_Core
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Register Custom Taxonomies with REST API and Custom Capability Support
 */
function eventpass_register_taxonomies() {
    // 1. Event Category Taxonomy
    $category_labels = [
        'name'                       => _x('Event Categories', 'taxonomy general name', 'eventpass-core'),
        'singular_name'              => _x('Event Category', 'taxonomy singular name', 'eventpass-core'),
        'search_items'               => __('Search Event Categories', 'eventpass-core'),
        'popular_items'              => __('Popular Event Categories', 'eventpass-core'),
        'all_items'                  => __('All Event Categories', 'eventpass-core'),
        'parent_item'                => __('Parent Category', 'eventpass-core'),
        'parent_item_colon'          => __('Parent Category:', 'eventpass-core'),
        'edit_item'                  => __('Edit Category', 'eventpass-core'),
        'update_item'                => __('Update Category', 'eventpass-core'),
        'add_new_item'               => __('Add New Category', 'eventpass-core'),
        'new_item_name'              => __('New Category Name', 'eventpass-core'),
        'separate_items_with_commas' => __('Separate categories with commas', 'eventpass-core'),
        'add_or_remove_items'        => __('Add or remove categories', 'eventpass-core'),
        'choose_from_most_used'      => __('Choose from most used categories', 'eventpass-core'),
        'not_found'                  => __('No event categories found.', 'eventpass-core'),
        'menu_name'                  => __('Categories', 'eventpass-core'),
    ];

    register_taxonomy('ep_event_category', ['ep_event'], [
        'hierarchical'          => true,
        'labels'                => $category_labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'query_var'             => true,
        'rewrite'               => ['slug' => 'event-category', 'with_front' => false],
        'show_in_rest'          => true,
        'rest_base'             => 'event-categories',
        'rest_controller_class' => 'WP_REST_Terms_Controller',
        'capabilities'          => [
            'manage_terms' => 'manage_categories',
            'edit_terms'   => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ],
    ]);

    // 2. Town / Region in Namibia Taxonomy
    register_taxonomy('ep_event_city', ['ep_event'], [
        'hierarchical'      => true,
        'labels'            => [
            'name'          => __('Towns & Regions', 'eventpass-core'),
            'singular_name' => __('Town / Region', 'eventpass-core'),
            'all_items'     => __('All Towns & Regions', 'eventpass-core'),
            'edit_item'     => __('Edit Town', 'eventpass-core'),
            'view_item'     => __('View Town', 'eventpass-core'),
            'update_item'   => __('Update Town', 'eventpass-core'),
            'add_new_item'  => __('Add New Town', 'eventpass-core'),
            'new_item_name' => __('New Town Name', 'eventpass-core'),
            'search_items'  => __('Search Towns', 'eventpass-core'),
        ],
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => ['slug' => 'town', 'with_front' => false],
        'show_in_rest'      => true,
        'rest_base'         => 'event-towns',
    ]);

    // Register Term Meta Fields for ep_event_category
    register_term_meta('ep_event_category', '_ep_category_image', [
        'type'              => 'string',
        'description'       => 'High-resolution hero/card banner image URL for category',
        'single'            => true,
        'sanitize_callback' => 'esc_url_raw',
        'show_in_rest'      => true,
    ]);

    register_term_meta('ep_event_category', '_ep_category_icon', [
        'type'              => 'string',
        'description'       => 'Category icon symbol or emoji (e.g. 🎵, 💼, ⚽)',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ]);

    register_term_meta('ep_event_category', '_ep_category_status', [
        'type'              => 'string',
        'description'       => 'Category status: active or inactive',
        'single'            => true,
        'default'           => 'active',
        'sanitize_callback' => 'sanitize_key',
        'show_in_rest'      => true,
    ]);

    register_term_meta('ep_event_category', '_ep_created_at', [
        'type'              => 'string',
        'description'       => 'Category creation timestamp',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ]);

    register_term_meta('ep_event_category', '_ep_updated_at', [
        'type'              => 'string',
        'description'       => 'Category last updated timestamp',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ]);
}
add_action('init', 'eventpass_register_taxonomies');

/**
 * 2. Admin UI: Custom Fields on "Add New Category" Screen
 */
function eventpass_category_add_form_fields($taxonomy) {
    wp_nonce_field('eventpass_category_meta_nonce', 'ep_category_nonce');
    ?>
    <div class="form-field term-icon-wrap">
        <label for="ep_category_icon"><?php esc_html_e('Category Icon / Emoji', 'eventpass-core'); ?></label>
        <input type="text" name="ep_category_icon" id="ep_category_icon" value="🎟️" style="max-width: 120px; font-size: 1.25rem;">
        <p class="description"><?php esc_html_e('Enter an emoji or dashicon class (e.g. 🎵, 💼, ⚽, 🎭, 🦁).', 'eventpass-core'); ?></p>
    </div>

    <div class="form-field term-image-wrap">
        <label for="ep_category_image"><?php esc_html_e('Category Banner Image URL', 'eventpass-core'); ?></label>
        <input type="url" name="ep_category_image" id="ep_category_image" value="" placeholder="https://example.com/image.jpg">
        <p class="description"><?php esc_html_e('Direct URL to category hero / promotional card background photo.', 'eventpass-core'); ?></p>
    </div>

    <div class="form-field term-status-wrap">
        <label for="ep_category_status"><?php esc_html_e('Category Status', 'eventpass-core'); ?></label>
        <select name="ep_category_status" id="ep_category_status" style="max-width: 200px;">
            <option value="active" selected><?php esc_html_e('Active (Visible Publicly)', 'eventpass-core'); ?></option>
            <option value="inactive"><?php esc_html_e('Inactive (Hidden from Frontend)', 'eventpass-core'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Inactive categories are hidden from the homepage, explore filters, and organiser event creation.', 'eventpass-core'); ?></p>
    </div>
    <?php
}
add_action('ep_event_category_add_form_fields', 'eventpass_category_add_form_fields', 10, 1);

/**
 * 3. Admin UI: Custom Fields on "Edit Category" Screen
 */
function eventpass_category_edit_form_fields($term, $taxonomy) {
    wp_nonce_field('eventpass_category_meta_nonce', 'ep_category_nonce');
    $image      = get_term_meta($term->term_id, '_ep_category_image', true);
    $icon       = get_term_meta($term->term_id, '_ep_category_icon', true) ?: '🎟️';
    $status     = get_term_meta($term->term_id, '_ep_category_status', true) ?: 'active';
    $created_at = get_term_meta($term->term_id, '_ep_created_at', true) ?: current_time('mysql');
    $updated_at = get_term_meta($term->term_id, '_ep_updated_at', true) ?: current_time('mysql');
    ?>
    <tr class="form-field term-icon-wrap">
        <th scope="row"><label for="ep_category_icon"><?php esc_html_e('Category Icon / Emoji', 'eventpass-core'); ?></label></th>
        <td>
            <input type="text" name="ep_category_icon" id="ep_category_icon" value="<?php echo esc_attr($icon); ?>" style="max-width: 120px; font-size: 1.25rem;">
            <p class="description"><?php esc_html_e('Enter an emoji or symbol representing this event category.', 'eventpass-core'); ?></p>
        </td>
    </tr>

    <tr class="form-field term-image-wrap">
        <th scope="row"><label for="ep_category_image"><?php esc_html_e('Category Banner Image URL', 'eventpass-core'); ?></label></th>
        <td>
            <input type="url" name="ep_category_image" id="ep_category_image" value="<?php echo esc_url($image); ?>" class="regular-text">
            <?php if (!empty($image)) : ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($term->name); ?>" style="max-width: 200px; height: 110px; object-fit: cover; border-radius: 8px; border: 1px solid #ccd0d4;">
                </div>
            <?php endif; ?>
            <p class="description"><?php esc_html_e('Direct URL to category background photo.', 'eventpass-core'); ?></p>
        </td>
    </tr>

    <tr class="form-field term-status-wrap">
        <th scope="row"><label for="ep_category_status"><?php esc_html_e('Active / Inactive Status', 'eventpass-core'); ?></label></th>
        <td>
            <select name="ep_category_status" id="ep_category_status" style="max-width: 220px;">
                <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active (Publicly Discoverable)', 'eventpass-core'); ?></option>
                <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inactive (Hidden from Platform)', 'eventpass-core'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Inactive categories are hidden from all public directories and organiser event creation.', 'eventpass-core'); ?></p>
        </td>
    </tr>

    <tr class="form-field term-timestamps-wrap">
        <th scope="row"><?php esc_html_e('Category Timestamps', 'eventpass-core'); ?></th>
        <td>
            <p><strong><?php esc_html_e('Created:', 'eventpass-core'); ?></strong> <?php echo esc_html($created_at); ?></p>
            <p><strong><?php esc_html_e('Last Modified:', 'eventpass-core'); ?></strong> <?php echo esc_html($updated_at); ?></p>
        </td>
    </tr>
    <?php
}
add_action('ep_event_category_edit_form_fields', 'eventpass_category_edit_form_fields', 10, 2);

/**
 * 4. Save Category Meta Data (Add & Edit)
 */
function eventpass_save_category_meta($term_id) {
    if (!isset($_POST['ep_category_nonce']) || !wp_verify_nonce($_POST['ep_category_nonce'], 'eventpass_category_meta_nonce')) {
        return;
    }

    if (!current_user_can('manage_categories')) {
        return;
    }

    // Category Icon
    if (isset($_POST['ep_category_icon'])) {
        $icon = sanitize_text_field($_POST['ep_category_icon']);
        update_term_meta($term_id, '_ep_category_icon', $icon);
    }

    // Category Image
    if (isset($_POST['ep_category_image'])) {
        $image = esc_url_raw($_POST['ep_category_image']);
        update_term_meta($term_id, '_ep_category_image', $image);
    }

    // Category Status
    if (isset($_POST['ep_category_status'])) {
        $status = sanitize_key($_POST['ep_category_status']);
        $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
        update_term_meta($term_id, '_ep_category_status', $status);
    }

    // Timestamps
    $now = current_time('mysql');
    if (!get_term_meta($term_id, '_ep_created_at', true)) {
        update_term_meta($term_id, '_ep_created_at', $now);
    }
    update_term_meta($term_id, '_ep_updated_at', $now);
}
add_action('created_ep_event_category', 'eventpass_save_category_meta', 10, 1);
add_action('edited_ep_event_category', 'eventpass_save_category_meta', 10, 1);

/**
 * 5. Admin Category List Table Columns
 */
function eventpass_category_columns($columns) {
    $new_columns = [];
    $new_columns['cb']          = $columns['cb'];
    $new_columns['thumb']       = __('Visual', 'eventpass-core');
    $new_columns['name']        = $columns['name'];
    $new_columns['icon']        = __('Icon', 'eventpass-core');
    $new_columns['status']      = __('Status', 'eventpass-core');
    $new_columns['description'] = $columns['description'];
    $new_columns['slug']        = $columns['slug'];
    $new_columns['posts']       = __('Published Events', 'eventpass-core');
    return $new_columns;
}
add_filter('manage_edit-ep_event_category_columns', 'eventpass_category_columns');

function eventpass_category_custom_column($content, $column_name, $term_id) {
    switch ($column_name) {
        case 'thumb':
            $image = get_term_meta($term_id, '_ep_category_image', true);
            if ($image) {
                return '<img src="' . esc_url($image) . '" style="width: 44px; height: 32px; object-fit: cover; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
            }
            return '<span style="color:#94a3b8; font-size:11px;">No Image</span>';

        case 'icon':
            $icon = get_term_meta($term_id, '_ep_category_icon', true) ?: '🎟️';
            return '<span style="font-size: 1.4rem;">' . esc_html($icon) . '</span>';

        case 'status':
            $status = get_term_meta($term_id, '_ep_category_status', true) ?: 'active';
            if ($status === 'active') {
                return '<span style="display:inline-block; padding: 2px 8px; border-radius: 999px; background: #ECFDF5; color: #065F46; font-size: 11px; font-weight: 700; text-transform: uppercase;">Active</span>';
            } else {
                return '<span style="display:inline-block; padding: 2px 8px; border-radius: 999px; background: #F1F5F9; color: #64748B; font-size: 11px; font-weight: 700; text-transform: uppercase;">Inactive</span>';
            }
    }
    return $content;
}
add_filter('manage_ep_event_category_custom_column', 'eventpass_category_custom_column', 10, 3);

/**
 * 6. Category Deletion Safeguard
 * When an administrator deletes an event category, protect all associated events
 * by reassigning them to the fallback "Other Events" category rather than leaving them orphaned
 * or accidentally deleting event posts.
 */
function eventpass_safeguard_category_deletion($term_id, $taxonomy) {
    if ($taxonomy !== 'ep_event_category') {
        return;
    }

    $term = get_term($term_id, 'ep_event_category');
    if (!$term || is_wp_error($term)) {
        return;
    }

    // Do not allow deleting the primary fallback category if it is "other-events"
    if ($term->slug === 'other-events') {
        wp_die(
            __('Action Prohibited: "Other Events" is the system fallback event category and cannot be deleted.', 'eventpass-core'),
            __('Category Deletion Blocked', 'eventpass-core'),
            ['back_link' => true]
        );
    }

    // Ensure fallback category exists
    $fallback_term = get_term_by('slug', 'other-events', 'ep_event_category');
    if (!$fallback_term) {
        $created = wp_insert_term('Other Events', 'ep_event_category', [
            'slug'        => 'other-events',
            'description' => 'General and miscellaneous events in Namibia.',
        ]);
        if (!is_wp_error($created)) {
            $fallback_term_id = $created['term_id'];
            update_term_meta($fallback_term_id, '_ep_category_status', 'active');
            update_term_meta($fallback_term_id, '_ep_category_icon', '📌');
        } else {
            $fallback_term_id = 0;
        }
    } else {
        $fallback_term_id = $fallback_term->term_id;
    }

    // Find all events associated with the deleted category
    $events = get_posts([
        'post_type'      => 'ep_event',
        'posts_per_page' => -1,
        'tax_query'      => [
            [
                'taxonomy' => 'ep_event_category',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ],
        ],
        'fields'         => 'ids',
    ]);

    if (!empty($events) && $fallback_term_id) {
        foreach ($events as $event_id) {
            // Reassign to fallback category
            wp_set_object_terms($event_id, [$fallback_term_id], 'ep_event_category', false);
        }
    }
}
add_action('pre_delete_term', 'eventpass_safeguard_category_deletion', 10, 2);

/**
 * 7. Default Migration & Seeding Hook
 * Pre-populates the Scope of Work Section 5 categories dynamically if database is empty.
 */
function eventpass_seed_default_categories() {
    if (get_option('eventpass_categories_seeded_v1')) {
        return;
    }

    $sow_categories = [
        [
            'name'        => 'Concerts & Music Festivals',
            'slug'        => 'concerts-music-festivals',
            'description' => 'Live concerts, music festivals, electronic DJ nights and acoustic showcases featuring premier Namibian and international artists.',
            'image'       => '/assets/images/hero/concert-lights.jpg',
            'icon'        => '🎵',
        ],
        [
            'name'        => 'Conferences & Seminars',
            'slug'        => 'conferences-seminars',
            'description' => 'Executive conferences, fintech symposiums, entrepreneurship masterclasses, and regional networking summits for industry leaders.',
            'image'       => '/assets/images/hero/business-summit.jpg',
            'icon'        => '💼',
        ],
        [
            'name'        => 'Corporate Events',
            'slug'        => 'corporate-events',
            'description' => 'Annual corporate banquets, awards galas, shareholder general meetings, and leadership executive retreats.',
            'image'       => '/assets/images/hero/business-summit.jpg',
            'icon'        => '🏢',
        ],
        [
            'name'        => 'Sports & Recreation',
            'slug'        => 'sports-recreation',
            'description' => 'Coastal marathons, mountain bike trails, rugby tournaments, soccer championships and athletics events across Namibia.',
            'image'       => '/assets/images/hero/sports-stadium.jpg',
            'icon'        => '⚽',
        ],
        [
            'name'        => 'Cultural Events',
            'slug'        => 'cultural-events',
            'description' => 'Traditional Namibian heritage celebrations, indigenous arts festivals, language exhibitions and folklore evenings.',
            'image'       => '/assets/images/hero/cultural-night.jpg',
            'icon'        => '🎭',
        ],
        [
            'name'        => 'Education & Training',
            'slug'        => 'education-training',
            'description' => 'Accredited professional masterclasses, university symposiums, developer bootcamps and skills training workshops.',
            'image'       => '/assets/images/hero/business-summit.jpg',
            'icon'        => '🎓',
        ],
        [
            'name'        => 'Exhibitions & Trade Shows',
            'slug'        => 'exhibitions-trade-shows',
            'description' => 'Industrial expos, maritime and mining showcases, consumer fairs and national trade conventions.',
            'image'       => '/assets/images/hero/dj-performance.jpg',
            'icon'        => '🏛️',
        ],
        [
            'name'        => 'Tourism & Entertainment',
            'slug'        => 'tourism-entertainment',
            'description' => 'Namib desert eco-safaris, stargazing expeditions, coastal catamaran cruises and wildlife adventures.',
            'image'       => '/assets/images/hero/festival-crowd.jpg',
            'icon'        => '🦁',
        ],
        [
            'name'        => 'Social Events',
            'slug'        => 'social-events',
            'description' => 'Gourmet kapana cookoffs, craft beer tasting sessions, comedy nights, open-air cinema, and community picnics.',
            'image'       => '/assets/images/hero/cultural-night.jpg',
            'icon'        => '🎉',
        ],
        [
            'name'        => 'Other Events',
            'slug'        => 'other-events',
            'description' => 'Special interest gatherings, charity fundraisers, community pop-ups, and miscellaneous events.',
            'image'       => '/assets/images/hero/festival-crowd.jpg',
            'icon'        => '📌',
        ],
    ];

    foreach ($sow_categories as $cat) {
        $existing = get_term_by('slug', $cat['slug'], 'ep_event_category');
        if (!$existing) {
            $inserted = wp_insert_term($cat['name'], 'ep_event_category', [
                'slug'        => $cat['slug'],
                'description' => $cat['description'],
            ]);

            if (!is_wp_error($inserted)) {
                $term_id = $inserted['term_id'];
                update_term_meta($term_id, '_ep_category_icon', $cat['icon']);
                update_term_meta($term_id, '_ep_category_image', $cat['image']);
                update_term_meta($term_id, '_ep_category_status', 'active');
                update_term_meta($term_id, '_ep_created_at', current_time('mysql'));
                update_term_meta($term_id, '_ep_updated_at', current_time('mysql'));
            }
        }
    }

    update_option('eventpass_categories_seeded_v1', 1);
}
add_action('admin_init', 'eventpass_seed_default_categories');

/**
 * 8. WordPress REST API Endpoints for Event Categories
 * Provides endpoints for frontend queries, Vue/React integrations, and mobile scanner app.
 */
function eventpass_register_category_rest_routes() {
    register_rest_route('eventpass/v1', '/categories', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'eventpass_rest_get_categories',
            'permission_callback' => '__return_true',
            'args'                => [
                'status' => [
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => 'active',
                    'sanitize_callback' => 'sanitize_key',
                ],
                'include_all' => [
                    'required'          => false,
                    'type'              => 'boolean',
                    'default'           => false,
                ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'eventpass_rest_create_category',
            'permission_callback' => function () {
                return current_user_can('manage_categories');
            },
        ],
    ]);

    register_rest_route('eventpass/v1', '/categories/(?P<id>\d+)', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'eventpass_rest_get_single_category',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'eventpass_rest_update_category',
            'permission_callback' => function () {
                return current_user_can('manage_categories');
            },
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'eventpass_rest_delete_category',
            'permission_callback' => function () {
                return current_user_can('manage_categories');
            },
        ],
    ]);
}
add_action('rest_api_init', 'eventpass_register_category_rest_routes');

/**
 * REST: Get Categories
 */
function eventpass_rest_get_categories($request) {
    $include_all = $request->get_param('include_all');
    $status_req  = $request->get_param('status') ?: 'active';

    $terms = get_terms([
        'taxonomy'   => 'ep_event_category',
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        return new WP_Error('category_fetch_failed', __('Failed to fetch categories', 'eventpass-core'), ['status' => 500]);
    }

    $response = [];
    foreach ($terms as $term) {
        $status     = get_term_meta($term->term_id, '_ep_category_status', true) ?: 'active';
        $image      = get_term_meta($term->term_id, '_ep_category_image', true) ?: '';
        $icon       = get_term_meta($term->term_id, '_ep_category_icon', true) ?: '🎟️';
        $created_at = get_term_meta($term->term_id, '_ep_created_at', true) ?: '';
        $updated_at = get_term_meta($term->term_id, '_ep_updated_at', true) ?: '';

        // If not include_all and user not requesting inactive, skip inactive
        if (!$include_all && $status !== $status_req) {
            continue;
        }

        // Count only published & approved events
        $event_count = eventpass_get_category_events_count($term->term_id);

        $response[] = [
            'id'          => $term->term_id,
            'name'        => html_entity_decode($term->name),
            'slug'        => $term->slug,
            'description' => html_entity_decode($term->description),
            'image'       => $image,
            'icon'        => $icon,
            'status'      => $status,
            'event_count' => $event_count,
            'created_at'  => $created_at,
            'updated_at'  => $updated_at,
        ];
    }

    return rest_ensure_response($response);
}

/**
 * REST: Get Single Category
 */
function eventpass_rest_get_single_category($request) {
    $term_id = (int)$request['id'];
    $term = get_term($term_id, 'ep_event_category');

    if (!$term || is_wp_error($term)) {
        return new WP_Error('not_found', __('Category not found', 'eventpass-core'), ['status' => 404]);
    }

    $status      = get_term_meta($term->term_id, '_ep_category_status', true) ?: 'active';
    $image       = get_term_meta($term->term_id, '_ep_category_image', true) ?: '';
    $icon        = get_term_meta($term->term_id, '_ep_category_icon', true) ?: '🎟️';
    $created_at  = get_term_meta($term->term_id, '_ep_created_at', true) ?: '';
    $updated_at  = get_term_meta($term->term_id, '_ep_updated_at', true) ?: '';
    $event_count = eventpass_get_category_events_count($term->term_id);

    return rest_ensure_response([
        'id'          => $term->term_id,
        'name'        => html_entity_decode($term->name),
        'slug'        => $term->slug,
        'description' => html_entity_decode($term->description),
        'image'       => $image,
        'icon'        => $icon,
        'status'      => $status,
        'event_count' => $event_count,
        'created_at'  => $created_at,
        'updated_at'  => $updated_at,
    ]);
}

/**
 * REST: Create Category
 */
function eventpass_rest_create_category($request) {
    $name        = sanitize_text_field($request->get_param('name'));
    $slug        = sanitize_title($request->get_param('slug') ?: $name);
    $description = sanitize_textarea_field($request->get_param('description') ?: '');
    $image       = esc_url_raw($request->get_param('image') ?: '');
    $icon        = sanitize_text_field($request->get_param('icon') ?: '🎟️');
    $status      = sanitize_key($request->get_param('status') ?: 'active');
    $status      = in_array($status, ['active', 'inactive'], true) ? $status : 'active';

    if (empty($name)) {
        return new WP_Error('missing_name', __('Category name is required', 'eventpass-core'), ['status' => 400]);
    }

    // Check slug duplication
    if (term_exists($slug, 'ep_event_category')) {
        return new WP_Error('slug_exists', __('A category with this slug already exists', 'eventpass-core'), ['status' => 409]);
    }

    $inserted = wp_insert_term($name, 'ep_event_category', [
        'slug'        => $slug,
        'description' => $description,
    ]);

    if (is_wp_error($inserted)) {
        return $inserted;
    }

    $term_id = $inserted['term_id'];
    $now = current_time('mysql');
    update_term_meta($term_id, '_ep_category_image', $image);
    update_term_meta($term_id, '_ep_category_icon', $icon);
    update_term_meta($term_id, '_ep_category_status', $status);
    update_term_meta($term_id, '_ep_created_at', $now);
    update_term_meta($term_id, '_ep_updated_at', $now);

    return rest_ensure_response([
        'success' => true,
        'id'      => $term_id,
        'message' => __('Category created successfully', 'eventpass-core'),
    ]);
}

/**
 * REST: Update Category
 */
function eventpass_rest_update_category($request) {
    $term_id = (int)$request['id'];
    $term = get_term($term_id, 'ep_event_category');

    if (!$term || is_wp_error($term)) {
        return new WP_Error('not_found', __('Category not found', 'eventpass-core'), ['status' => 404]);
    }

    $args = [];
    if ($request->has_param('name')) {
        $args['name'] = sanitize_text_field($request->get_param('name'));
    }
    if ($request->has_param('slug')) {
        $slug = sanitize_title($request->get_param('slug'));
        // Check for duplicate slug
        $existing = get_term_by('slug', $slug, 'ep_event_category');
        if ($existing && $existing->term_id !== $term_id) {
            return new WP_Error('slug_exists', __('Slug already in use', 'eventpass-core'), ['status' => 409]);
        }
        $args['slug'] = $slug;
    }
    if ($request->has_param('description')) {
        $args['description'] = sanitize_textarea_field($request->get_param('description'));
    }

    if (!empty($args)) {
        wp_update_term($term_id, 'ep_event_category', $args);
    }

    if ($request->has_param('image')) {
        update_term_meta($term_id, '_ep_category_image', esc_url_raw($request->get_param('image')));
    }
    if ($request->has_param('icon')) {
        update_term_meta($term_id, '_ep_category_icon', sanitize_text_field($request->get_param('icon')));
    }
    if ($request->has_param('status')) {
        $status = sanitize_key($request->get_param('status'));
        if (in_array($status, ['active', 'inactive'], true)) {
            update_term_meta($term_id, '_ep_category_status', $status);
        }
    }

    update_term_meta($term_id, '_ep_updated_at', current_time('mysql'));

    return rest_ensure_response([
        'success' => true,
        'message' => __('Category updated successfully', 'eventpass-core'),
    ]);
}

/**
 * REST: Delete Category with Safeguard
 */
function eventpass_rest_delete_category($request) {
    $term_id = (int)$request['id'];
    $term = get_term($term_id, 'ep_event_category');

    if (!$term || is_wp_error($term)) {
        return new WP_Error('not_found', __('Category not found', 'eventpass-core'), ['status' => 404]);
    }

    if ($term->slug === 'other-events') {
        return new WP_Error('cannot_delete_fallback', __('The fallback category "Other Events" cannot be deleted', 'eventpass-core'), ['status' => 400]);
    }

    // pre_delete_term hook will safely reassign any existing events
    $deleted = wp_delete_term($term_id, 'ep_event_category');
    if (is_wp_error($deleted)) {
        return $deleted;
    }

    return rest_ensure_response([
        'success' => true,
        'message' => __('Category deleted and events reassigned to Other Events', 'eventpass-core'),
    ]);
}

/**
 * 9. Helper Functions for Theme and Template Integration
 */

/**
 * Retrieve database categories (Active by default)
 */
function eventpass_get_event_categories($args = []) {
    $defaults = [
        'status'     => 'active',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ];
    $parsed_args = wp_parse_args($args, $defaults);

    $terms = get_terms([
        'taxonomy'   => 'ep_event_category',
        'hide_empty' => $parsed_args['hide_empty'],
        'orderby'    => $parsed_args['orderby'],
        'order'      => $parsed_args['order'],
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }

    $categories = [];
    foreach ($terms as $term) {
        $status = get_term_meta($term->term_id, '_ep_category_status', true) ?: 'active';

        // Filter by status if specified
        if ($parsed_args['status'] && $status !== $parsed_args['status']) {
            continue;
        }

        $categories[] = (object)[
            'id'          => $term->term_id,
            'term_id'     => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'description' => $term->description,
            'image'       => get_term_meta($term->term_id, '_ep_category_image', true) ?: '',
            'icon'        => get_term_meta($term->term_id, '_ep_category_icon', true) ?: '🎟️',
            'status'      => $status,
            'count'       => eventpass_get_category_events_count($term->term_id),
            'url'         => get_term_link($term, 'ep_event_category'),
        ];
    }

    return $categories;
}

/**
 * Count published, approved events in a category
 */
function eventpass_get_category_events_count($term_id) {
    $query = new WP_Query([
        'post_type'      => 'ep_event',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'tax_query'      => [
            [
                'taxonomy' => 'ep_event_category',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ],
        ],
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ]);

    return (int)$query->found_posts;
}
