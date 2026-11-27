<?php
/**
 * The template for displaying the EventPass Namibia Homepage
 *
 * @package EventPass_Namibia
 */

get_header();

// Fetch dynamic, database-driven active event categories
$db_categories = function_exists('eventpass_get_event_categories') ? eventpass_get_event_categories(['status' => 'active']) : [];
?>

<!-- 1. Hero Section with Cinematic Event Image Collage -->
<section class="ep-hero-section">
    <!-- Background 6-Tile Photographic Event Collage -->
    <div class="ep-hero-bg-collage" aria-hidden="true">
        <!-- Tile 1: Live Concert with Stage Lights & Crowd -->
        <div class="ep-hero-collage-item">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg'); ?>" alt="Concert with large crowds and stage lights" loading="eager">
        </div>
        <!-- Tile 2: Music Festival Outdoor Celebration -->
        <div class="ep-hero-collage-item">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/festival-crowd.jpg'); ?>" alt="Music festival crowd celebration" loading="eager">
        </div>
        <!-- Tile 3: Electronic Music DJ & Nightlife with Cyan Lasers -->
        <div class="ep-hero-collage-item">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/dj-performance.jpg'); ?>" alt="DJ performance with electric cyan lasers" loading="eager">
        </div>
        <!-- Tile 4: Stadium Arena & Sporting Event -->
        <div class="ep-hero-collage-item">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/sports-stadium.jpg'); ?>" alt="Stadium packed with spectators" loading="lazy">
        </div>
        <!-- Tile 5: Cultural Celebration & Social Lifestyle -->
        <div class="ep-hero-collage-item">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/cultural-night.jpg'); ?>" alt="Cultural festival and social celebration" loading="lazy">
        </div>
        <!-- Tile 6: Business Conference & Networking Summit -->
        <div class="ep-hero-collage-item">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/business-summit.jpg'); ?>" alt="Business conference and leadership summit" loading="lazy">
        </div>
    </div>

    <!-- Multi-Layer Atmospheric Dark Navy Scrim & Radial Glow Overlays -->
    <div class="ep-hero-overlay" aria-hidden="true"></div>

    <!-- Foreground Content -->
    <div class="container" style="position: relative; z-index: 3;">
        <div class="ep-hero-content">
            <div class="ep-hero-badge">
                <span class="ep-pulse-dot"></span>
                <span>Every Event. One Pass. • Namibia Live</span>
            </div>

            <h1 class="ep-hero-title">
                Discover Events. <span class="ep-title-gradient">Book Tickets.</span> Create Memories.
            </h1>

            <p class="ep-hero-desc">
                Your next unforgettable experience starts here. Welcome to <strong>EventPass Namibia</strong>, your online destination for discovering exciting events and purchasing tickets across Namibia. From live concerts and festivals to business conferences, sporting events, exhibitions and lifestyle experiences, find the events you love and secure your place with ease.
            </p>

            <div class="ep-hero-actions">
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    <span>Explore Events</span>
                </a>
                <a href="<?php echo esc_url(home_url('/list-your-event')); ?>" class="ep-btn ep-btn-outline ep-btn-lg" style="color: #ffffff; border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.08); backdrop-filter: blur(8px);">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    <span>List Your Event</span>
                </a>
            </div>
        </div>

        <!-- 3. Integrated Search Events Bar -->
        <div class="ep-hero-search-box">
            <form action="<?php echo esc_url(home_url('/explore-events')); ?>" method="GET" class="ep-hero-search-form">
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Search Event</label>
                    <input type="text" name="keyword" placeholder="Concert, conference, festival..." style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Category</label>
                    <select name="category" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem; background: #fff;">
                        <option value="">All Categories</option>
                        <?php foreach ($db_categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--ep-slate-600); margin-bottom: 0.4rem;">Town / Location</label>
                    <select name="location" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem; background: #fff;">
                        <option value="">All Locations</option>
                        <option value="windhoek">Windhoek</option>
                        <option value="swakopmund">Swakopmund</option>
                        <option value="walvis-bay">Walvis Bay</option>
                        <option value="oshakati">Oshakati</option>
                        <option value="rundu">Rundu</option>
                        <option value="katima-mulilo">Katima Mulilo</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="ep-btn ep-btn-primary" style="width: 100%; height: 48px;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <span>Find Events</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Popular Locations Quick Chips (Inspired by NamEvents) -->
        <div class="ep-quick-pills">
            <span style="color: #94A3B8; font-weight: 600;">Popular in Namibia:</span>
            <a href="<?php echo esc_url(home_url('/explore-events?location=windhoek')); ?>" class="ep-quick-pill-link">Windhoek</a>
            <a href="<?php echo esc_url(home_url('/explore-events?location=swakopmund')); ?>" class="ep-quick-pill-link">Swakopmund</a>
            <a href="<?php echo esc_url(home_url('/explore-events?location=walvis-bay')); ?>" class="ep-quick-pill-link">Walvis Bay</a>
            <a href="<?php echo esc_url(home_url('/explore-events?location=oshakati')); ?>" class="ep-quick-pill-link">Oshakati</a>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-quick-pill-link" style="color: var(--ep-cyan); border-color: rgba(0, 229, 255, 0.4);">Browse All Events &rarr;</a>
        </div>

    </div>

    <!-- Full-Width Infinite Looping Category Ticker Carousel (NamEvents Benchmark Upgraded) -->
    <div class="ep-hero-ticker-carousel">
        <div class="ep-ticker-track">
            <!-- Group 1 -->
            <div class="ep-ticker-group">
                <?php foreach ($db_categories as $cat) : ?>
                <a href="<?php echo esc_url(home_url('/explore-events?category=' . $cat->slug)); ?>" class="ep-ticker-card">
                    <span class="ep-ticker-kicker"><?php echo esc_html(strtoupper(str_replace('-', ' ', $cat->slug))); ?></span>
                    <span class="ep-ticker-title"><?php echo esc_html($cat->icon . ' ' . $cat->name); ?> <span class="ep-ticker-arrow">&rarr;</span></span>
                </a>
                <?php endforeach; ?>
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-ticker-card ep-ticker-card-highlight">
                    <span class="ep-ticker-kicker ep-kicker-cyan">LIVE NOW</span>
                    <span class="ep-ticker-title">⚡ All Namibian Events <span class="ep-ticker-arrow">&rarr;</span></span>
                </a>
            </div>

            <!-- Group 2 (Duplicate for continuous seamless loop) -->
            <div class="ep-ticker-group" aria-hidden="true">
                <?php foreach ($db_categories as $cat) : ?>
                <a href="<?php echo esc_url(home_url('/explore-events?category=' . $cat->slug)); ?>" class="ep-ticker-card">
                    <span class="ep-ticker-kicker"><?php echo esc_html(strtoupper(str_replace('-', ' ', $cat->slug))); ?></span>
                    <span class="ep-ticker-title"><?php echo esc_html($cat->icon . ' ' . $cat->name); ?> <span class="ep-ticker-arrow">&rarr;</span></span>
                </a>
                <?php endforeach; ?>
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-ticker-card ep-ticker-card-highlight">
                    <span class="ep-ticker-kicker ep-kicker-cyan">LIVE NOW</span>
                    <span class="ep-ticker-title">⚡ All Namibian Events <span class="ep-ticker-arrow">&rarr;</span></span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 4. Featured Events Section -->
<section class="ep-section" style="padding: 4.5rem 0;">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem;">
            <div>
                <span style="color: var(--ep-teal-dark); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Curated Highlights</span>
                <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">Find Your Next Experience</h2>
                <p style="color: var(--ep-slate-600); font-size: 1rem; max-width: 650px; margin-top: 0.5rem;">
                    Discover what's happening around Namibia. Explore upcoming events, exciting entertainment, business gatherings, sporting activities and unforgettable experiences.
                </p>
            </div>
            <a href="<?php echo esc_url(home_url('/explore-events?filter=featured')); ?>" class="ep-btn ep-btn-outline ep-btn-sm" style="border-color: var(--ep-slate-200);">
                <span>View All Featured</span>
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>

        <!-- Featured Event Cards Grid -->
        <div class="ep-event-grid ep-event-grid-4">
            <?php
            $featured_query = new WP_Query([
                'post_type'      => 'ep_event',
                'post_status'    => 'publish',
                'posts_per_page' => 4,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);

            if ($featured_query->have_posts()) :
                while ($featured_query->have_posts()) : $featured_query->the_post();
                    get_template_part('template-parts/event-card');
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--ep-slate-600); padding: 2rem;">No featured events currently available. Check back soon!</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 6. Browse by Event Category -->
<section class="ep-section" style="background: #ffffff; padding: 5rem 0; border-top: 1px solid var(--ep-slate-200); border-bottom: 1px solid var(--ep-slate-200);">
    <div class="container">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3.5rem;">
            <span style="color: var(--ep-teal); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1.5px; background: rgba(0, 180, 216, 0.1); padding: 0.35rem 0.9rem; border-radius: var(--ep-radius-full); display: inline-block; margin-bottom: 0.75rem;">Categories</span>
            <h2 style="font-size: 2.35rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem; letter-spacing: -0.02em;">Explore Events by Category</h2>
            <p style="color: var(--ep-slate-600); margin-top: 0.5rem; font-size: 1.05rem;">Browse events dynamically managed through the platform database by category, location, date and ticket price.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.75rem;">
            <?php
            if (!empty($db_categories)) :
                foreach ($db_categories as $cat) :
                    $cat_link = esc_url(home_url('/explore-events?category=' . $cat->slug));
            ?>
            <a href="<?php echo $cat_link; ?>" class="ep-cat-card">
                <div class="ep-cat-card-icon"><?php echo esc_html($cat->icon ?: '🎟️'); ?></div>
                <h3><?php echo esc_html($cat->name); ?></h3>
                <p><?php echo esc_html($cat->description); ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 0.75rem;">
                    <span class="ep-cat-explore">Explore Events &rarr;</span>
                    <?php if (isset($cat->count) && $cat->count > 0) : ?>
                        <span style="font-size: 0.75rem; color: #64748B; background: #F1F5F9; padding: 2px 8px; border-radius: 999px; font-weight: 600;"><?php echo esc_html($cat->count); ?> <?php echo $cat->count === 1 ? 'event' : 'events'; ?></span>
                    <?php endif; ?>
                </div>
            </a>
            <?php
                endforeach;
            else :
            ?>
                <p style="text-align: center; color: #64748B; grid-column: 1 / -1;">No active event categories configured.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 5. Upcoming & 7. Popular Events Section -->
<section class="ep-section" style="padding: 4.5rem 0;">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem;">
            <div>
                <span style="color: var(--ep-teal-dark); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Upcoming Calendar</span>
                <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">Upcoming Events Across Namibia</h2>
                <p style="color: var(--ep-slate-600); font-size: 1rem; margin-top: 0.5rem;">Secure your tickets in advance. Instant delivery with unique QR codes.</p>
            </div>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-outline ep-btn-sm">
                <span>View Full Calendar</span>
            </a>
        </div>

        <div class="ep-event-grid">
            <?php
            $upcoming_query = new WP_Query([
                'post_type'      => 'ep_event',
                'post_status'    => 'publish',
                'posts_per_page' => 6,
                'orderby'        => 'meta_value',
                'meta_key'       => '_ep_event_date',
                'order'          => 'ASC',
            ]);

            if ($upcoming_query->have_posts()) :
                while ($upcoming_query->have_posts()) : $upcoming_query->the_post();
                    get_template_part('template-parts/event-card');
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--ep-slate-600); padding: 2rem;">No upcoming events scheduled at this moment. Stay tuned!</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 8. Why Choose EventPass Section -->
<section class="ep-section" style="background: var(--ep-slate-100); padding: 5rem 0;">
    <div class="container">
        <div style="text-align: center; max-width: 700px; margin: 0 auto 3.5rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Platform Advantage</span>
            <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">Why Choose EventPass?</h2>
            <p style="color: var(--ep-slate-600); margin-top: 0.5rem; font-size: 1.05rem;">Everything You Need for a Great Event Experience</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            <!-- Benefit 1 -->
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="width: 44px; height: 44px; background: rgba(0, 180, 216, 0.12); color: var(--ep-teal-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Discover More</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6;">Find exciting events happening across Namibia across diverse categories and towns.</p>
            </div>

            <!-- Benefit 2 -->
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="width: 44px; height: 44px; background: rgba(0, 180, 216, 0.12); color: var(--ep-teal-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Easy Online Booking</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6;">Browse available tickets and complete your booking online through secure payment gateways.</p>
            </div>

            <!-- Benefit 3 -->
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="width: 44px; height: 44px; background: rgba(0, 180, 216, 0.12); color: var(--ep-teal-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Digital Tickets</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6;">Receive your ticket electronically after successful independent payment verification.</p>
            </div>

            <!-- Benefit 4 -->
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="width: 44px; height: 44px; background: rgba(0, 180, 216, 0.12); color: var(--ep-teal-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Convenient Access</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6;">Present your digital ticket and QR code at participating event entrances for instant scan entry.</p>
            </div>

            <!-- Benefit 5 -->
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="width: 44px; height: 44px; background: rgba(0, 180, 216, 0.12); color: var(--ep-teal-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Trusted Information</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6;">Access verified event dates, venues, ticket categories, terms and organiser contact credentials.</p>
            </div>

            <!-- Benefit 6 -->
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="width: 44px; height: 44px; background: rgba(0, 180, 216, 0.12); color: var(--ep-teal-dark); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </div>
                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">One Convenient Platform</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6;">Discover, book, manage and store all your tickets in one secure account portal.</p>
            </div>
        </div>
    </div>
</section>

<!-- 9. How It Works Section -->
<section class="ep-section" style="padding: 5rem 0; background: #ffffff;">
    <div class="container">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3.5rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">User Journey</span>
            <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">How It Works</h2>
            <p style="color: var(--ep-slate-600); margin-top: 0.5rem; font-size: 1.05rem;">Getting Your EventPass Is Easy</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 1.5rem; position: relative;">
            <!-- Step 1 -->
            <div style="text-align: center; padding: 1.5rem 1rem;">
                <div style="width: 56px; height: 56px; background: var(--ep-navy); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 1.25rem; font-weight: 800; border: 3px solid var(--ep-teal);">1</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Discover</h4>
                <p style="font-size: 0.875rem; color: var(--ep-slate-600); line-height: 1.5;">Browse upcoming events and find something you would love to attend.</p>
            </div>

            <!-- Step 2 -->
            <div style="text-align: center; padding: 1.5rem 1rem;">
                <div style="width: 56px; height: 56px; background: var(--ep-navy); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 1.25rem; font-weight: 800; border: 3px solid var(--ep-teal);">2</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Choose Your Ticket</h4>
                <p style="font-size: 0.875rem; color: var(--ep-slate-600); line-height: 1.5;">Select your preferred ticket category and quantity.</p>
            </div>

            <!-- Step 3 -->
            <div style="text-align: center; padding: 1.5rem 1rem;">
                <div style="width: 56px; height: 56px; background: var(--ep-navy); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 1.25rem; font-weight: 800; border: 3px solid var(--ep-teal);">3</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Book & Pay</h4>
                <p style="font-size: 0.875rem; color: var(--ep-slate-600); line-height: 1.5;">Enter your details and complete payment using available methods.</p>
            </div>

            <!-- Step 4 -->
            <div style="text-align: center; padding: 1.5rem 1rem;">
                <div style="width: 56px; height: 56px; background: var(--ep-navy); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 1.25rem; font-weight: 800; border: 3px solid var(--ep-teal);">4</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Receive Ticket</h4>
                <p style="font-size: 0.875rem; color: var(--ep-slate-600); line-height: 1.5;">Get your booking confirmation and digital ticket with unique QR code.</p>
            </div>

            <!-- Step 5 -->
            <div style="text-align: center; padding: 1.5rem 1rem;">
                <div style="width: 56px; height: 56px; background: var(--ep-teal); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; font-size: 1.25rem; font-weight: 800; box-shadow: 0 4px 14px rgba(0, 180, 216, 0.4);">5</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: var(--ep-navy); margin-bottom: 0.5rem;">Attend & Enjoy</h4>
                <p style="font-size: 0.875rem; color: var(--ep-slate-600); line-height: 1.5;">Present your QR-coded ticket at the event entrance for fast scanning.</p>
            </div>
        </div>
    </div>
</section>

<!-- 10. For Event Organisers Promotional Section -->
<section class="ep-section" style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); color: #ffffff; padding: 5rem 0;">
    <div class="container">
        <div class="ep-cta-split-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3.5rem; align-items: center;">
            <div>
                <span style="color: var(--ep-cyan); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">For Event Organisers</span>
                <h2 style="font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin: 0.5rem 0 1.25rem;">
                    Your Event Deserves to Be Seen.
                </h2>
                <p style="color: #CBD5E1; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Are you planning a concert, conference, festival, sporting event or special occasion?
                    EventPass Namibia gives event organisers an opportunity to promote their events, sell tickets online and manage registrations through one convenient platform.
                </p>
                <p style="color: #94A3B8; font-size: 0.95rem; margin-bottom: 2rem;">
                    Reach a wider audience, simplify ticket sales and keep track of your event performance with real-time analytics.
                </p>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="<?php echo esc_url(home_url('/organiser-registration')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
                        <span>Become an Event Organiser</span>
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?php echo esc_url(home_url('/list-your-event')); ?>" class="ep-btn ep-btn-outline ep-btn-lg" style="color: #ffffff; border-color: rgba(255,255,255,0.25);">
                        <span>Learn More</span>
                    </a>
                </div>
            </div>

            <!-- Benefits List Cards -->
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: var(--ep-radius-lg); padding: 2.25rem;">
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #ffffff; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: var(--ep-teal);">★</span> Benefits for Organisers:
                </h3>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.9rem;">
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Create and publish event listings with rich media and agendas.</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Set tiered ticket categories, early birds, VIP passes and custom prices.</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Manage ticket quantities, purchase limits and inventory availability.</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Monitor real-time ticket sales and registration metrics on your dashboard.</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Access attendee information and export downloadable guest lists (CSV/Excel).</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Validate tickets using our mobile-friendly camera QR-code check-in scanner.</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; gap: 0.75rem; color: #E2E8F0; font-size: 0.95rem;">
                        <svg style="flex-shrink: 0; color: var(--ep-teal); margin-top: 0.2rem;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Track sales revenue, platform commission deductions and settlement payouts.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- 11. Testimonials & Partner Statement Section (Client Guideline: Only genuine/authentic statements) -->
<section class="ep-section" style="padding: 4.5rem 0; background: var(--ep-slate-50);">
    <div class="container">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Trust & Community</span>
            <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">Built for Namibia's Event Ecosystem</h2>
            <p style="color: var(--ep-slate-600); margin-top: 0.5rem;">Connecting audiences, artists, organizers and venue managers under one verified standard.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="color: var(--ep-teal); font-size: 1.5rem; margin-bottom: 0.75rem;">“</div>
                <p style="font-style: italic; color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Having a unified, secure digital ticketing system with instant QR verification eliminates counterfeit tickets at concert entrances in Windhoek.
                </p>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--ep-navy); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">WH</div>
                    <div>
                        <strong style="display: block; font-size: 0.95rem; color: var(--ep-navy);">Windhoek Music Collective</strong>
                        <span style="font-size: 0.8rem; color: var(--ep-slate-600);">Live Concert Promoter</span>
                    </div>
                </div>
            </div>

            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="color: var(--ep-teal); font-size: 1.5rem; margin-bottom: 0.75rem;">“</div>
                <p style="font-style: italic; color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Delegates booked tickets effortlessly and had their passes directly on their phones. Real-time attendee lists simplified conference registration immensely.
                </p>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--ep-teal-dark); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">NB</div>
                    <div>
                        <strong style="display: block; font-size: 0.95rem; color: var(--ep-navy);">Namibia Business Summits</strong>
                        <span style="font-size: 0.8rem; color: var(--ep-slate-600);">Corporate Conference Organiser</span>
                    </div>
                </div>
            </div>

            <div style="background: #ffffff; padding: 2rem; border-radius: var(--ep-radius-lg); border: 1px solid var(--ep-slate-200); box-shadow: var(--ep-shadow-sm);">
                <div style="color: var(--ep-teal); font-size: 1.5rem; margin-bottom: 0.75rem;">“</div>
                <p style="font-style: italic; color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    EventPass makes it painless for sports fans across Swakopmund and Walvis Bay to purchase tickets via debit or credit card without standing in physical queues.
                </p>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--ep-success); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">CS</div>
                    <div>
                        <strong style="display: block; font-size: 0.95rem; color: var(--ep-navy);">Coastal Sports Association</strong>
                        <span style="font-size: 0.8rem; color: var(--ep-slate-600);">Athletics & Tournament Coordinator</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 12. Newsletter Section -->
<section class="ep-newsletter-section" style="background: #ffffff; padding: 4rem 0; border-top: 1px solid var(--ep-slate-200);">
    <div class="container">
        <div style="background: linear-gradient(135deg, rgba(0, 180, 216, 0.08) 0%, rgba(11, 27, 61, 0.04) 100%); border: 1px solid rgba(0, 180, 216, 0.25); border-radius: var(--ep-radius-lg); padding: 3rem 2rem; max-width: 960px; margin: 0 auto; text-align: center;">
            <h3 style="font-size: 1.75rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Never Miss An Event in Namibia</h3>
            <p style="color: var(--ep-slate-600); max-width: 580px; margin: 0 auto 1.75rem; font-size: 0.95rem;">
                Subscribe to the EventPass weekly dispatch for early-bird announcements, festival line-ups and exclusive ticket presales.
            </p>
            <form action="#" method="POST" style="display: flex; gap: 0.75rem; max-width: 520px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
                <input type="email" placeholder="Enter your email address" required style="flex: 1; min-width: 260px; padding: 0.85rem 1.25rem; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); font-size: 0.95rem;">
                <button type="submit" class="ep-btn ep-btn-primary">
                    <span>Subscribe</span>
                </button>
            </form>
            <span style="display: block; margin-top: 0.75rem; font-size: 0.75rem; color: #94A3B8;">We respect your privacy. Unsubscribe at any time.</span>
        </div>
    </div>
</section>

<!-- Final Home Page CTA Banner -->
<section class="ep-final-cta-section" style="background: linear-gradient(90deg, var(--ep-navy) 0%, var(--ep-navy-light) 100%); color: #ffffff; padding: 4.5rem 0; text-align: center;">
    <div class="container">
        <div style="max-width: 720px; margin: 0 auto;">
            <h2 style="font-size: 2.25rem; font-weight: 800; margin-bottom: 1rem;">Your Next Great Experience Is Waiting.</h2>
            <p style="font-size: 1.1rem; color: #CBD5E1; margin-bottom: 2rem;">
                Don't miss out on the events happening around you. Explore upcoming events and secure your tickets today.
            </p>
            <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <span>Find Events</span>
            </a>
        </div>
    </div>
</section>

<?php
get_footer();
