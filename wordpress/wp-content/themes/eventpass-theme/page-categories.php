<?php
/**
 * Template Name: Event Categories
 *
 * @package EventPass_Namibia
 * @version 1.1.0
 */

get_header();

// Dynamically fetch database-driven active event categories
$categories = function_exists('eventpass_get_event_categories') ? eventpass_get_event_categories(['status' => 'active']) : [];
?>

<!-- Categories Hero -->
<section class="ep-page-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/festival-crowd.jpg'); ?>" alt="Namibia Events" class="ep-page-hero-bg">
    <div class="ep-page-hero-overlay"></div>
    <div class="container ep-page-hero-content">
        <div style="max-width: 820px;">
            <div class="ep-page-pill">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--ep-cyan); box-shadow:0 0 10px var(--ep-cyan);"></span>
                14 REGIONS • DATABASE-DRIVEN CATEGORIES
            </div>
            <h1 style="font-size: clamp(2.3rem, 5vw, 3.4rem); font-weight: 900; line-height: 1.15; margin-bottom: 1.25rem;">
                Discover Events by Category
            </h1>
            <p style="font-size: 1.15rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 2rem;">
                From electrifying music festivals and high-impact business summits to coastal marathons and cultural celebrations, explore events tailored to what you love across Namibia.
            </p>
            <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 0.85rem; color: #94A3B8; font-weight: 700; text-transform: uppercase;">Quick Filter:</span>
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-badge" style="background: rgba(255,255,255,0.1); color: #fff; text-decoration: none;">All Events</a>
                <a href="<?php echo esc_url(home_url('/explore-events?location=windhoek')); ?>" class="ep-badge" style="background: rgba(255,255,255,0.1); color: #fff; text-decoration: none;">Windhoek</a>
                <a href="<?php echo esc_url(home_url('/explore-events?location=swakopmund')); ?>" class="ep-badge" style="background: rgba(255,255,255,0.1); color: #fff; text-decoration: none;">Swakopmund</a>
                <a href="<?php echo esc_url(home_url('/explore-events?location=walvis-bay')); ?>" class="ep-badge" style="background: rgba(255,255,255,0.1); color: #fff; text-decoration: none;">Walvis Bay</a>
                <a href="<?php echo esc_url(home_url('/explore-events?location=oshakati')); ?>" class="ep-badge" style="background: rgba(255,255,255,0.1); color: #fff; text-decoration: none;">Oshakati</a>
            </div>
        </div>
    </div>
</section>

<!-- Category Grid -->
<div class="container" style="padding: 4.5rem 1.5rem 5.5rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem; margin-bottom: 4rem;">
        
        <?php if (!empty($categories)) : ?>
            <?php foreach ($categories as $cat) : 
                $card_img = !empty($cat->image) ? $cat->image : get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg';
                // If relative path starting with /assets
                if (strpos($card_img, '/assets/') === 0) {
                    $card_img = get_template_directory_uri() . str_replace('/assets', '/assets', $card_img);
                }
                $event_count = isset($cat->count) ? (int)$cat->count : 0;
                $count_badge = $event_count > 0 ? "{$event_count} Published Event" . ($event_count === 1 ? '' : 's') : "Active Category";
                $cat_url = esc_url(home_url('/explore-events?category=' . $cat->slug));
            ?>
            <div class="ep-cat-card" style="display: flex; flex-direction: column; background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s;">
                <div class="ep-cat-img-wrap" style="position: relative; height: 190px; overflow: hidden; background: var(--ep-navy);">
                    <img src="<?php echo esc_url($card_img); ?>" alt="<?php echo esc_attr($cat->name); ?>" class="ep-cat-img" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.88; transition: transform 0.3s ease;">
                    <span class="ep-cat-badge" style="position: absolute; top: 1rem; right: 1rem; background: rgba(6, 14, 32, 0.85); backdrop-filter: blur(8px); color: var(--ep-cyan); font-weight: 700; font-size: 0.75rem; padding: 0.35rem 0.75rem; border-radius: 999px; border: 1px solid rgba(0,229,255,0.3);">
                        <?php echo esc_html($count_badge); ?>
                    </span>
                    <div style="position: absolute; bottom: 1rem; left: 1rem; width: 44px; height: 44px; border-radius: 10px; background: rgba(255,255,255,0.92); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                        <?php echo esc_html($cat->icon ?: '🎟️'); ?>
                    </div>
                </div>
                <div style="padding: 1.75rem; flex: 1; display: flex; flex-direction: column;">
                    <div style="font-size: 0.78rem; font-weight: 800; color: var(--ep-teal-dark); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.35rem;">
                        <?php echo esc_html(strtoupper(str_replace('-', ' ', $cat->slug))); ?>
                    </div>
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.65rem;">
                        <?php echo esc_html($cat->name); ?>
                    </h3>
                    <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                        <?php echo esc_html($cat->description ?: 'Browse upcoming verified events, tickets and passes in this category.'); ?>
                    </p>
                    <a href="<?php echo $cat_url; ?>" class="ep-btn ep-btn-primary ep-btn-sm" style="margin-top: auto; justify-content: center;">
                        Browse <?php echo esc_html($cat->name); ?> &rarr;
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 1.5rem; background: #ffffff; border-radius: 16px; border: 1px solid var(--ep-slate-200);">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📁</div>
                <h3 style="font-size: 1.4rem; color: var(--ep-navy); font-weight: 800;">No Active Categories Found</h3>
                <p style="color: #64748B; max-width: 500px; margin: 0 auto 1.5rem;">Event categories are dynamically managed through the platform administration panel. Please check back shortly.</p>
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary">Explore All Events</a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Organiser Callout Banner -->
    <div style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); border: 1px solid rgba(0,229,255,0.25); border-radius: 20px; padding: 3rem 2.5rem; color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
        <div style="max-width: 600px;">
            <span style="color: var(--ep-cyan); font-weight: 800; font-size: 0.85rem; text-transform: uppercase;">Promoter Partnership</span>
            <h3 style="font-size: 1.85rem; font-weight: 800; margin: 0.35rem 0 0.75rem;">Hosting an event in any category?</h3>
            <p style="color: #CBD5E1; font-size: 1rem; line-height: 1.6; margin: 0;">
                Publish your event in minutes, accept instant Namibian card payments through Finatic, and get free mobile turnstile QR scanners.
            </p>
        </div>
        <div>
            <a href="<?php echo esc_url(home_url('/list-your-event')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
                List Your Event Now &rarr;
            </a>
        </div>
    </div>
</div>

<?php
get_footer();
