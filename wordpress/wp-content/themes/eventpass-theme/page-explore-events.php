<?php
/**
 * Template Name: Explore Events
 *
 * @package EventPass_Namibia
 */

get_header();

// Fetch active database categories
$db_categories = function_exists('eventpass_get_event_categories') ? eventpass_get_event_categories(['status' => 'active']) : [];
if (empty($db_categories)) {
    $terms = get_terms([
        'taxonomy'   => 'ep_event_category',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($terms)) {
        $db_categories = $terms;
    }
}

// Read filter query parameters
$search_query   = isset($_GET['keyword']) ? sanitize_text_field(trim($_GET['keyword'])) : '';
$selected_cat   = isset($_GET['category']) ? sanitize_text_field(trim($_GET['category'])) : '';
$selected_loc   = isset($_GET['location']) ? sanitize_text_field(trim($_GET['location'])) : '';
$selected_price = isset($_GET['price']) ? sanitize_text_field(trim($_GET['price'])) : '';
$paged          = (get_query_var('paged')) ? get_query_var('paged') : (isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1);

// Build dynamic WP_Query arguments
$query_args = [
    'post_type'      => 'ep_event',
    'post_status'    => 'publish', // ONLY approved and published events appear publicly
    'posts_per_page' => 9,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

if (!empty($search_query)) {
    $query_args['s'] = $search_query;
}

// Category filter
if (!empty($selected_cat)) {
    $query_args['tax_query'] = [
        [
            'taxonomy' => 'ep_event_category',
            'field'    => 'slug',
            'terms'    => $selected_cat,
        ],
    ];
}

// Location and price filters
$meta_query = [];
if (!empty($selected_loc)) {
    $meta_query[] = [
        'key'     => '_ep_event_town',
        'value'   => $selected_loc,
        'compare' => 'LIKE',
    ];
}

if (!empty($selected_price)) {
    if ($selected_price === 'free') {
        $meta_query[] = [
            'key'     => '_ep_min_price',
            'value'   => 0,
            'type'    => 'NUMERIC',
            'compare' => '=',
        ];
    } elseif ($selected_price === 'under-200') {
        $meta_query[] = [
            'key'     => '_ep_min_price',
            'value'   => 200,
            'type'    => 'NUMERIC',
            'compare' => '<=',
        ];
    } elseif ($selected_price === '200-500') {
        $meta_query[] = [
            'key'     => '_ep_min_price',
            'value'   => [200, 500],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ];
    } elseif ($selected_price === 'above-500') {
        $meta_query[] = [
            'key'     => '_ep_min_price',
            'value'   => 500,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        ];
    }
}

if (!empty($meta_query)) {
    $query_args['meta_query'] = $meta_query;
}

$events_query = new WP_Query($query_args);
?>

<!-- Banner Section -->
<div class="ep-page-banner" style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); color: #ffffff; padding: 3.5rem 0 3rem;">
    <div class="container">
        <div style="max-width: 780px;">
            <span style="color: var(--ep-cyan); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Discover Experiences</span>
            <h1 style="font-size: 2.75rem; font-weight: 800; margin: 0.4rem 0 1rem; line-height: 1.2;">What's Happening in Namibia?</h1>
            <p style="font-size: 1.1rem; color: #CBD5E1; line-height: 1.6;">
                Discover concerts, festivals, conferences, sporting events, exhibitions and more. Find your next experience with EventPass Namibia.
            </p>
        </div>
    </div>
</div>

<!-- Main Discovery & Filter Content -->
<div class="container" style="padding: 3rem 1.5rem 5rem;">
    <!-- Filter Bar -->
    <div class="ep-filters-card" style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--ep-shadow-sm);">
        <form class="ep-filters-form" method="GET" action="<?php echo esc_url(home_url('/explore-events')); ?>" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px; gap: 1rem; align-items: end;">
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Search Event</label>
                <input type="text" name="keyword" value="<?php echo esc_attr($search_query); ?>" placeholder="Search by name or keyword..." style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Category</label>
                <select name="category" style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem; background: #fff;">
                    <option value="">All Categories</option>
                    <?php foreach ($db_categories as $cat) : ?>
                        <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($selected_cat, $cat->slug); ?>><?php echo esc_html($cat->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Town / Location</label>
                <select name="location" style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem; background: #fff;">
                    <option value="">All Namibia</option>
                    <option value="windhoek" <?php selected($selected_loc, 'windhoek'); ?>>Windhoek (Khomas)</option>
                    <option value="swakopmund" <?php selected($selected_loc, 'swakopmund'); ?>>Swakopmund (Erongo)</option>
                    <option value="walvis-bay" <?php selected($selected_loc, 'walvis-bay'); ?>>Walvis Bay (Erongo)</option>
                    <option value="oshakati" <?php selected($selected_loc, 'oshakati'); ?>>Oshakati (Oshana)</option>
                    <option value="rundu" <?php selected($selected_loc, 'rundu'); ?>>Rundu (Kavango)</option>
                    <option value="katima-mulilo" <?php selected($selected_loc, 'katima-mulilo'); ?>>Katima Mulilo (Zambezi)</option>
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Ticket Price</label>
                <select name="price" style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem; background: #fff;">
                    <option value="">Any Price</option>
                    <option value="free" <?php selected($selected_price, 'free'); ?>>Free Admission</option>
                    <option value="under-200" <?php selected($selected_price, 'under-200'); ?>>Under N$ 200</option>
                    <option value="200-500" <?php selected($selected_price, '200-500'); ?>>N$ 200 - N$ 500</option>
                    <option value="above-500" <?php selected($selected_price, 'above-500'); ?>>N$ 500+</option>
                </select>
            </div>

            <div>
                <button type="submit" class="ep-btn ep-btn-primary" style="width: 100%; height: 44px; justify-content: center;">
                    <span>Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Active Filters & Result Count -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;">
        <span style="font-size: 1.05rem; font-weight: 600; color: var(--ep-navy);">
            Found <strong><?php echo intval($events_query->found_posts); ?></strong> published event<?php echo $events_query->found_posts === 1 ? '' : 's'; ?>
            <?php if (!empty($selected_cat)) : ?>
                in <span style="color: var(--ep-teal-dark); font-weight: 800;"><?php echo esc_html(ucwords(str_replace('-', ' ', $selected_cat))); ?></span>
            <?php endif; ?>
            <?php if (!empty($selected_loc)) : ?>
                in <span style="color: var(--ep-teal-dark); font-weight: 800;"><?php echo esc_html(ucfirst($selected_loc)); ?></span>
            <?php endif; ?>
        </span>
        <?php if (!empty($selected_cat) || !empty($selected_loc) || !empty($search_query) || !empty($selected_price)) : ?>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" style="font-size: 0.85rem; color: var(--ep-teal-dark); font-weight: 700; text-decoration: underline;">Reset All Filters</a>
        <?php endif; ?>
    </div>

    <!-- Events Grid -->
    <?php if ($events_query->have_posts()) : ?>
        <div class="ep-event-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
            <?php
            while ($events_query->have_posts()) :
                $events_query->the_post();
                get_template_part('template-parts/event-card');
            endwhile;
            wp_reset_postdata();
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($events_query->max_num_pages > 1) : ?>
            <div style="margin-top: 3.5rem; text-align: center; display: flex; justify-content: center; gap: 0.5rem;">
                <?php
                echo paginate_links([
                    'total'        => $events_query->max_num_pages,
                    'current'      => $paged,
                    'prev_text'    => '&larr; Previous',
                    'next_text'    => 'Next &rarr;',
                    'type'         => 'plain',
                ]);
                ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <!-- Empty State -->
        <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 4.5rem 2rem; text-align: center;">
            <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">🔍</span>
            <h3 style="font-size: 1.45rem; font-weight: 800; color: var(--ep-navy); margin: 0 0 0.5rem;">No Published Events Match Your Criteria</h3>
            <p style="color: var(--ep-slate-600); max-width: 500px; margin: 0 auto 1.75rem; font-size: 0.95rem; line-height: 1.6;">
                We couldn't find any approved events matching your active filter criteria. Try adjusting your keyword search, selecting "All Categories", or resetting your filters.
            </p>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-md">
                <span>View All Namibian Events &rarr;</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
