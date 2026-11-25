<?php
/**
 * Single Event Details Template
 *
 * @package EventPass_Namibia
 */

get_header();

// Determine event ID
$event_id = get_the_ID();
if (!$event_id && isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
}

$event_post = get_post($event_id);

// Check validity and permissions
$can_view = false;
if ($event_post && $event_post->post_type === 'ep_event') {
    if ($event_post->post_status === 'publish') {
        $can_view = true;
    } elseif (is_user_logged_in() && (current_user_can('edit_post', $event_id) || intval($event_post->post_author) === get_current_user_id())) {
        $can_view = true; // Author / Admin preview
    }
}
?>

<?php if (!$can_view || !$event_post) : ?>
    <!-- Event Not Found / Pending Review State -->
    <div class="container" style="padding: 6rem 1.5rem; text-align: center;">
        <span style="font-size: 3.5rem; display: block; margin-bottom: 1rem;">🔒</span>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Event Not Available or Pending Approval</h1>
        <p style="color: var(--ep-slate-600); max-width: 500px; margin: 0 auto 2rem; font-size: 1rem; line-height: 1.6;">
            This event listing is currently undergoing administrative verification or has not yet been published live.
        </p>
        <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-md">
            <span>&larr; Return to Explore Events</span>
        </a>
    </div>
<?php else : 
    // Extract database attributes
    $title       = get_the_title($event_id);
    $description = apply_filters('the_content', $event_post->post_content);
    if (empty(trim($description))) {
        $description = get_post_meta($event_id, '_ep_event_description', true) ?: 'Join us for this premier Namibian event. Tickets are available online with instant contactless QR verification.';
    }

    $terms       = get_the_terms($event_id, 'ep_event_category');
    $category    = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'General Event';
    $cat_slug    = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : 'general';

    $date_meta   = get_post_meta($event_id, '_ep_event_date', true) ?: 'Upcoming 2026';
    $time_meta   = get_post_meta($event_id, '_ep_event_time', true) ?: 'Gates open 2 hours prior to show';
    $venue_meta  = get_post_meta($event_id, '_ep_event_venue', true) ?: 'Independence Stadium';
    $town_meta   = get_post_meta($event_id, '_ep_event_town', true) ?: 'Windhoek';
    $address     = $venue_meta . ', ' . ucfirst($town_meta) . ', Namibia';

    $org_name    = get_post_meta($event_id, '_ep_event_organizer_name', true) ?: (get_post_meta($event_id, '_ep_event_organiser', true) ?: get_the_author_meta('display_name', $event_post->post_author));
    $org_contact = get_post_meta($event_id, '_ep_event_organizer_email', true) ?: (get_post_meta($event_id, '_ep_event_contact', true) ?: 'events@eventpass.com.na');

    $thumb_url   = get_the_post_thumbnail_url($event_id, 'full');
    $custom_img  = get_post_meta($event_id, '_ep_event_image', true);
    $image       = $thumb_url ?: ($custom_img ?: get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg');

    // Retrieve database ticket tiers
    $tiers = eventpass_get_event_tiers($event_id);
    if (empty($tiers)) {
        // Fallback default tier
        $tiers = [
            'general' => [
                'id'                 => 'general',
                'name'               => 'General Admission',
                'price'              => 250.00,
                'total_quantity'     => 500,
                'available_quantity' => 500,
                'sold_quantity'      => 0,
                'desc'               => 'Standard event entry with access to main arena and food court.'
            ]
        ];
    }

    // Schema.org Event Structured Data (SOW Module 20)
    $schema_offers = [];
    foreach ($tiers as $tier_id => $tier_info) {
        $schema_offers[] = [
            '@type'         => 'Offer',
            'name'          => $tier_info['name'] ?? 'Ticket',
            'price'         => floatval($tier_info['price'] ?? 0),
            'priceCurrency' => 'NAD',
            'availability'  => (intval($tier_info['available_quantity'] ?? 0) > 0) ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
            'url'           => get_permalink($event_id),
            'validFrom'     => date('c', strtotime($event_post->post_date)),
        ];
    }

    $schema_event = [
        '@context'            => 'https://schema.org',
        '@type'               => 'Event',
        'name'                => $title,
        'description'         => wp_strip_all_tags(wp_trim_words($description, 50)),
        'startDate'           => $date_meta,
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'eventStatus'         => 'https://schema.org/EventScheduled',
        'image'               => [$image],
        'location'            => [
            '@type'   => 'Place',
            'name'    => $venue_meta,
            'address' => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $venue_meta,
                'addressLocality' => $town_meta,
                'addressCountry'  => 'NA',
            ],
        ],
        'organizer'           => [
            '@type' => 'Organization',
            'name'  => $org_name,
            'url'   => home_url('/'),
        ],
        'offers'              => $schema_offers,
    ];
?>

<script type="application/ld+json">
<?php echo wp_json_encode($schema_event, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

    <?php if ($event_post->post_status === 'pending') : ?>
        <div style="background: #FEF3C7; border-bottom: 2px solid #F59E0B; color: #92400E; padding: 0.75rem 1.5rem; text-align: center; font-size: 0.9rem; font-weight: 700;">
            ⚠️ PREVIEW MODE: This event is currently in <strong>Pending Review</strong> status. It is only visible to administrators and the event organiser.
        </div>
    <?php endif; ?>

    <!-- Event Hero Header -->
    <div class="ep-event-hero" style="background: var(--ep-navy-deep); position: relative; color: #ffffff;">
        <div style="height: 380px; position: relative; overflow: hidden;">
            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.45;">
            <div style="position: absolute; inset: 0; background: linear-gradient(to top, var(--ep-navy-deep) 0%, rgba(11,27,61,0.5) 60%, transparent 100%);"></div>
        </div>

        <div class="container" style="position: relative; margin-top: -160px; padding-bottom: 3.5rem;">
            <div style="max-width: 820px;">
                <span class="ep-category-badge ep-cat-<?php echo esc_attr($cat_slug); ?>" style="position: static; display: inline-block; margin-bottom: 1rem; font-size: 0.85rem; padding: 0.35rem 0.9rem;">
                    <?php echo esc_html($category); ?>
                </span>
                <h1 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; line-height: 1.2; margin: 0 0 1rem;">
                    <?php echo esc_html($title); ?>
                </h1>

                <div style="display: flex; gap: 2rem; flex-wrap: wrap; color: #E2E8F0; font-size: 0.95rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <strong><?php echo esc_html($date_meta); ?></strong>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span><?php echo esc_html($address); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Main Details & Booking Grid -->
    <div class="container" style="padding: 3.5rem 1.5rem 5rem;">
        <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 3.5rem; align-items: start;">
            
            <!-- Left Column: Details, Venue, Organiser -->
            <div>
                <div style="margin-bottom: 3rem;">
                    <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1rem;">About This Event</h2>
                    <div style="font-size: 1.05rem; line-height: 1.8; color: var(--ep-slate-700);">
                        <?php echo $description; ?>
                    </div>
                </div>

                <div style="margin-bottom: 3rem; background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.75rem; box-shadow: var(--ep-shadow-sm);">
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.25rem;">Date, Time & Venue Details</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div>
                            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-slate-600); font-weight: 700; display: block; margin-bottom: 0.25rem;">Event Date</span>
                            <strong style="color: var(--ep-navy); font-size: 1rem;"><?php echo esc_html($date_meta); ?></strong>
                            <span style="display: block; font-size: 0.85rem; color: var(--ep-slate-600); margin-top: 0.2rem;"><?php echo esc_html($time_meta); ?></span>
                        </div>
                        <div>
                            <span style="font-size: 0.8rem; text-transform: uppercase; color: var(--ep-slate-600); font-weight: 700; display: block; margin-bottom: 0.25rem;">Venue Location</span>
                            <strong style="color: var(--ep-navy); font-size: 1rem;"><?php echo esc_html($venue_meta); ?></strong>
                            <span style="display: block; font-size: 0.85rem; color: var(--ep-slate-600); margin-top: 0.2rem;"><?php echo esc_html(ucfirst($town_meta)); ?>, Namibia</span>
                        </div>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 1.75rem; box-shadow: var(--ep-shadow-sm);">
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1rem;">Organiser & Support</h3>
                    <strong style="color: var(--ep-navy); font-size: 1.05rem; display: block; margin-bottom: 0.25rem;"><?php echo esc_html($org_name); ?></strong>
                    <span style="font-size: 0.9rem; color: var(--ep-slate-600); display: block;">Official Contact: <?php echo esc_html($org_contact); ?></span>
                </div>
            </div>

            <!-- Right Column: Interactive Ticket Selection -->
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-lg); padding: 2rem; box-shadow: var(--ep-shadow-lg); position: sticky; top: 100px;">
                <h3 style="font-size: 1.45rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Select Your Passes</h3>
                <p style="font-size: 0.88rem; color: var(--ep-slate-600); margin-bottom: 1.5rem;">Passes are issued with unique, contactless QR codes upon booking.</p>

                <form id="epBookingForm" action="<?php echo esc_url(home_url('/checkout')); ?>" method="POST">
                    <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">

                    <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 1.75rem;">
                        <?php foreach ($tiers as $tier_key => $tier) : 
                            $tier_id    = $tier['id'] ?? $tier_key;
                            $tier_name  = $tier['name'] ?? 'Tier Pass';
                            $tier_price = floatval($tier['price'] ?? 0);
                            $available  = intval($tier['available_quantity'] ?? 0);
                            $tier_desc  = $tier['desc'] ?? '';
                            $is_sold_out = ($available <= 0);
                        ?>
                            <div style="border: 1px solid var(--ep-slate-200); border-radius: var(--ep-radius-md); padding: 1rem; background: <?php echo $is_sold_out ? '#F8FAFC' : 'var(--ep-slate-50)'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.4rem;">
                                    <div>
                                        <strong style="font-size: 0.95rem; color: var(--ep-navy); display: block;"><?php echo esc_html($tier_name); ?></strong>
                                        <span style="font-size: 0.8rem; color: var(--ep-slate-600);"><?php echo esc_html($tier_desc); ?></span>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="font-weight: 800; color: var(--ep-teal-dark); font-size: 1.1rem;">N$ <?php echo number_format($tier_price, 2); ?></span>
                                        <?php if ($is_sold_out) : ?>
                                            <span style="display: block; font-size: 0.75rem; color: #DC2626; font-weight: 700;">SOLD OUT</span>
                                        <?php else : ?>
                                            <span style="display: block; font-size: 0.7rem; color: #64748B;"><?php echo esc_html($available); ?> remaining</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06);">
                                    <span style="font-size: 0.8rem; font-weight: 600; color: var(--ep-slate-600);">Quantity:</span>
                                    <?php if ($is_sold_out) : ?>
                                        <span style="font-size: 0.8rem; color: #94A3B8; font-style: italic;">Unavailable</span>
                                    <?php else : ?>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <button type="button" class="ep-qty-btn" onclick="updateQty('<?php echo esc_js($tier_id); ?>', -1, <?php echo esc_js($tier_price); ?>)" style="width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--ep-slate-200); background: #fff; cursor: pointer; font-weight: 700;">-</button>
                                            <input type="number" id="qty_<?php echo esc_attr($tier_id); ?>" name="tickets[<?php echo esc_attr($tier_id); ?>]" value="0" min="0" max="<?php echo min(10, $available); ?>" readonly style="width: 44px; text-align: center; border: 1px solid var(--ep-slate-200); border-radius: 6px; padding: 0.25rem 0; font-weight: 700; background: #fff;">
                                            <button type="button" class="ep-qty-btn" onclick="updateQty('<?php echo esc_js($tier_id); ?>', 1, <?php echo esc_js($tier_price); ?>)" style="width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--ep-slate-200); background: #fff; cursor: pointer; font-weight: 700;">+</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Booking Summary Subtotal -->
                    <div style="border-top: 2px solid var(--ep-slate-200); padding-top: 1.25rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; color: var(--ep-navy);">
                            <span>Total (NAD):</span>
                            <span id="epGrandTotal">N$ 0.00</span>
                        </div>
                        <span style="font-size: 0.75rem; color: var(--ep-slate-600); display: block; margin-top: 0.2rem;">All prices include Namibian statutory VAT</span>
                    </div>

                    <?php if ( ! is_user_logged_in() ) : ?>
                        <div style="background: rgba(0, 180, 216, 0.08); border: 1px solid rgba(0, 180, 216, 0.35); border-radius: 8px; padding: 0.65rem 0.85rem; font-size: 0.78rem; color: var(--ep-navy); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; line-height: 1.4;">
                            <span>🔒</span>
                            <span><strong>Registration Required:</strong> Tickets are registered to your verified account for instant QR passes.</span>
                        </div>
                    <?php endif; ?>

                    <button type="submit" id="epCheckoutBtn" class="ep-btn ep-btn-primary ep-btn-lg" style="width: 100%; justify-content: center; opacity: 0.5; pointer-events: none;">
                        <span><?php echo is_user_logged_in() ? 'Proceed to Checkout &rarr;' : 'Sign In / Register & Book &rarr;'; ?></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    const tierQuantities = {};
    const tierPrices = {};

    function updateQty(tierId, delta, price) {
        if (!tierQuantities[tierId]) tierQuantities[tierId] = 0;
        tierPrices[tierId] = price;

        const input = document.getElementById('qty_' + tierId);
        if (!input) return;

        const maxQty = parseInt(input.getAttribute('max') || '10');
        let newQty = tierQuantities[tierId] + delta;
        if (newQty < 0) newQty = 0;
        if (newQty > maxQty) newQty = maxQty;

        tierQuantities[tierId] = newQty;
        input.value = newQty;

        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let total = 0;
        let totalTickets = 0;

        for (const [id, qty] of Object.entries(tierQuantities)) {
            total += qty * (tierPrices[id] || 0);
            totalTickets += qty;
        }

        document.getElementById('epGrandTotal').textContent = 'N$ ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        const btn = document.getElementById('epCheckoutBtn');
        if (btn) {
            if (totalTickets > 0) {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            } else {
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
            }
        }
    }
    </script>
<?php endif; ?>

<?php
get_footer();
