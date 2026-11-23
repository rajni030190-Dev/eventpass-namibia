<?php
/**
 * Event Card Component Template Part
 *
 * @package EventPass_Namibia
 */

$event_id    = $args['id'] ?? get_the_ID();
$title       = $args['title'] ?? get_the_title($event_id);
$terms       = get_the_terms($event_id, 'ep_event_category');
$category    = $args['category'] ?? (!empty($terms) && !is_wp_error($terms) ? $terms[0]->name : 'General Event');
$cat_slug    = !empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : sanitize_title($category);

$date_meta   = get_post_meta($event_id, '_ep_event_date', true);
$date        = $args['date'] ?? ($date_meta ?: 'Upcoming 2026');
$month       = $args['month'] ?? (get_post_meta($event_id, '_ep_event_month', true) ?: strtoupper(date('M', strtotime($date_meta ?: 'now'))));
$day         = $args['day'] ?? (get_post_meta($event_id, '_ep_event_day', true) ?: date('d', strtotime($date_meta ?: 'now')));

$venue_meta  = get_post_meta($event_id, '_ep_event_venue', true);
$town_meta   = get_post_meta($event_id, '_ep_event_town', true);
$venue       = $args['venue'] ?? ($venue_meta ? ($venue_meta . ($town_meta ? ', ' . ucfirst($town_meta) : '')) : 'Windhoek, Namibia');

$min_price   = get_post_meta($event_id, '_ep_min_price', true);
$price       = $args['price'] ?? ($min_price !== '' ? ('N$ ' . number_format((float)$min_price, 2)) : 'N$ 150.00');

$thumb_url   = get_the_post_thumbnail_url($event_id, 'ep-event-card');
$custom_img  = get_post_meta($event_id, '_ep_event_image', true);
$image       = $args['image'] ?? ($thumb_url ?: ($custom_img ?: get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg'));

$is_featured = $args['is_featured'] ?? (bool) get_post_meta($event_id, '_ep_is_featured', true);
$permalink   = esc_url(get_permalink($event_id));
?>

<div class="ep-event-card">
    <div class="ep-card-poster">
        <a href="<?php echo $permalink; ?>">
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
        </a>
        <span class="ep-category-badge ep-cat-<?php echo esc_attr($cat_slug); ?>"><?php echo esc_html($category); ?></span>
        <div class="ep-date-badge">
            <span class="month"><?php echo esc_html($month); ?></span>
            <span class="day"><?php echo esc_html($day); ?></span>
        </div>
    </div>

    <div class="ep-card-body">
        <h3 class="ep-card-title">
            <a href="<?php echo $permalink; ?>" style="color: inherit; text-decoration: none;">
                <?php echo esc_html($title); ?>
            </a>
        </h3>

        <div class="ep-card-meta">
            <div class="ep-card-meta-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span><?php echo esc_html($date); ?></span>
            </div>
            <div class="ep-card-meta-item">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span><?php echo esc_html($venue); ?></span>
            </div>
        </div>

        <div class="ep-card-footer">
            <div>
                <span class="ep-price-label">Tickets from</span>
                <span class="ep-price-value"><?php echo esc_html($price); ?></span>
            </div>
            <a href="<?php echo $permalink; ?>" class="ep-btn ep-btn-primary ep-btn-book ep-btn-sm">
                <span>Book Tickets &rarr;</span>
            </a>
        </div>
    </div>
</div>
