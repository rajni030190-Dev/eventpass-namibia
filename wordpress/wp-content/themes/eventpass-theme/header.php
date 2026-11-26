<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php
    global $wp;
    $ep_current_url = home_url(add_query_arg([], $wp->request ?? ''));
    $ep_og_title = wp_get_document_title();
    $ep_og_desc = 'Discover premier live music, cultural festivals, sports, and business conferences across Namibia. Secure online ticketing with instant QR passes.';
    $ep_og_image = get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg';
    $ep_og_type = 'website';

    if (is_singular('ep_event')) {
        $ep_ev_id = get_the_ID();
        $ep_og_type = 'article';
        $ep_desc_meta = get_post_meta($ep_ev_id, '_ep_event_description', true);
        if (!empty($ep_desc_meta)) {
            $ep_og_desc = wp_strip_all_tags(wp_trim_words($ep_desc_meta, 28));
        }
        $ep_poster_meta = get_post_meta($ep_ev_id, '_ep_event_poster', true);
        if (!empty($ep_poster_meta)) {
            $ep_og_image = $ep_poster_meta;
        }
    }
    ?>
    <meta name="description" content="<?php echo esc_attr($ep_og_desc); ?>">
    <link rel="canonical" href="<?php echo esc_url($ep_current_url); ?>">
    <!-- OpenGraph / Social Metadata -->
    <meta property="og:site_name" content="EventPass Namibia">
    <meta property="og:type" content="<?php echo esc_attr($ep_og_type); ?>">
    <meta property="og:title" content="<?php echo esc_attr($ep_og_title); ?>">
    <meta property="og:description" content="<?php echo esc_attr($ep_og_desc); ?>">
    <meta property="og:url" content="<?php echo esc_url($ep_current_url); ?>">
    <meta property="og:image" content="<?php echo esc_url($ep_og_image); ?>">
    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($ep_og_title); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr($ep_og_desc); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($ep_og_image); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <!-- Top Announcement Strip -->
    <div class="ep-top-bar" style="background: var(--ep-navy-deep); color: #94A3B8; font-size: 0.75rem; padding: 0.25rem 0; border-bottom: 1px solid rgba(255,255,255,0.06);">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="ep-top-left">
                <span>🇳🇦 Namibia's Premier Online Event & Ticketing Marketplace</span>
            </div>
            <div class="ep-top-right" style="display: flex; gap: 1.25rem;">
                <a href="<?php echo esc_url(home_url('/faqs')); ?>" style="color: #94A3B8;">Help & FAQs</a>
                <?php
                $hdr_is_admin = is_user_logged_in() && current_user_can('manage_options');
                $hdr_user     = is_user_logged_in() ? wp_get_current_user() : null;
                $hdr_is_org   = $hdr_user && (in_array('ep_organiser', (array)$hdr_user->roles, true) || in_array('event_organiser', (array)$hdr_user->roles, true) || current_user_can('edit_ep_events'));
                if ($hdr_is_admin || $hdr_is_org) : ?>
                    <a href="<?php echo esc_url(home_url('/gate-scanner')); ?>" style="color: var(--ep-teal);">Gate Check-In Portal</a>
                <?php endif; ?>
                <?php if ($hdr_is_admin) : ?>
                    <a href="<?php echo esc_url(home_url('/admin-dashboard')); ?>" style="color: #F59E0B;">Platform Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header id="masthead" class="ep-site-header">
        <div class="container ep-header-inner">
            <div class="ep-header-brand">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="ep-logo-link">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo.png'); ?>" alt="EventPass Namibia - Every Event. One Pass.">
                </a>
            </div>

            <!-- Desktop Navigation Links (Hidden on tablet/mobile) -->
            <nav id="site-navigation" class="ep-desktop-nav" aria-label="<?php esc_attr_e('Primary Menu', 'eventpass-namibia'); ?>">
                <ul class="ep-nav-menu">
                    <li><a href="<?php echo esc_url(home_url('/')); ?>" class="ep-nav-link <?php echo is_front_page() ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-nav-link <?php echo is_page('explore-events') ? 'active' : ''; ?>">Explore Events</a></li>
                    <li><a href="<?php echo esc_url(home_url('/categories')); ?>" class="ep-nav-link <?php echo is_page('categories') ? 'active' : ''; ?>">Categories</a></li>
                    <li><a href="<?php echo esc_url(home_url('/how-it-works')); ?>" class="ep-nav-link <?php echo is_page('how-it-works') ? 'active' : ''; ?>">How It Works</a></li>
                    <li><a href="<?php echo esc_url(home_url('/about-us')); ?>" class="ep-nav-link <?php echo is_page('about-us') ? 'active' : ''; ?>">About Us</a></li>
                    <li><a href="<?php echo esc_url(home_url('/contact-us')); ?>" class="ep-nav-link <?php echo is_page('contact-us') ? 'active' : ''; ?>">Contact Us</a></li>
                </ul>
            </nav>

            <!-- Desktop Action CTAs (Hidden on tablet/mobile) -->
            <div class="ep-header-actions">
                <a href="<?php echo esc_url(home_url('/list-your-event')); ?>" class="ep-btn ep-btn-outline ep-btn-sm" style="border-color: var(--ep-teal); color: var(--ep-teal-dark);">
                    <span>List Your Event</span>
                </a>
                <?php if ( is_user_logged_in() ) : 
                    $curr_user = wp_get_current_user();
                    $short_name = explode(' ', trim($curr_user->display_name ?: $curr_user->user_login))[0];
                    $dash_url = home_url('/customer-dashboard');
                    if (current_user_can('manage_options')) {
                        $dash_url = home_url('/admin-dashboard');
                    } elseif (in_array('ep_organiser', (array)$curr_user->roles) || in_array('event_organiser', (array)$curr_user->roles)) {
                        $dash_url = home_url('/organiser-dashboard');
                    }
                ?>
                    <a href="<?php echo esc_url($dash_url); ?>" class="ep-btn ep-btn-secondary ep-btn-sm">
                        <span>👤 <?php echo esc_html( $short_name ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( wp_logout_url( home_url('/login?loggedout=1') ) ); ?>" class="ep-btn ep-btn-sm" style="background: rgba(239,68,68,0.1); color: #DC2626; border: 1px solid rgba(239,68,68,0.25);">
                        <span>Sign Out</span>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/login')); ?>" class="ep-btn ep-btn-secondary ep-btn-sm">
                        <span>Sign In</span>
                    </a>
                <?php endif; ?>
                <?php if (!is_user_logged_in() || $hdr_is_admin || $hdr_is_org) : ?>
                <a href="<?php echo esc_url(home_url('/organiser-dashboard')); ?>" class="ep-btn ep-btn-primary ep-btn-sm">
                    <span>Organiser Hub</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Hamburger Toggle Button (Shown on mobile/tablet) -->
            <button type="button" class="ep-mobile-toggle" id="epMobileToggle" aria-label="Toggle navigation" onclick="toggleMobileMenu()">
                <span class="ep-hamburger-bar"></span>
                <span class="ep-hamburger-bar"></span>
                <span class="ep-hamburger-bar"></span>
            </button>
        </div>

        <!-- Mobile Navigation Drawer -->
        <div class="ep-mobile-drawer" id="epMobileDrawer">
            <div class="ep-mobile-drawer-inner">
                <ul class="ep-mobile-nav-list">
                    <li><a href="<?php echo esc_url(home_url('/')); ?>" class="ep-mobile-nav-link <?php echo is_front_page() ? 'active' : ''; ?>"><span>🏠 Home</span> <span>&rarr;</span></a></li>
                    <li><a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-mobile-nav-link <?php echo is_page('explore-events') ? 'active' : ''; ?>"><span>🎟️ Explore Events</span> <span>&rarr;</span></a></li>
                    <li><a href="<?php echo esc_url(home_url('/categories')); ?>" class="ep-mobile-nav-link <?php echo is_page('categories') ? 'active' : ''; ?>"><span>📁 Categories</span> <span>&rarr;</span></a></li>
                    <li><a href="<?php echo esc_url(home_url('/how-it-works')); ?>" class="ep-mobile-nav-link <?php echo is_page('how-it-works') ? 'active' : ''; ?>"><span>💡 How It Works</span> <span>&rarr;</span></a></li>
                    <li><a href="<?php echo esc_url(home_url('/about-us')); ?>" class="ep-mobile-nav-link <?php echo is_page('about-us') ? 'active' : ''; ?>"><span>🇳🇦 About Us</span> <span>&rarr;</span></a></li>
                    <li><a href="<?php echo esc_url(home_url('/contact-us')); ?>" class="ep-mobile-nav-link <?php echo is_page('contact-us') ? 'active' : ''; ?>"><span>📞 Contact Us</span> <span>&rarr;</span></a></li>
                </ul>

                <div class="ep-mobile-action-buttons">
                    <a href="<?php echo esc_url(home_url('/list-your-event')); ?>" class="ep-btn ep-btn-outline ep-btn-md" style="width: 100%; justify-content: center; border-color: var(--ep-teal); color: var(--ep-teal-dark);">
                        + List Your Event
                    </a>
                    <?php if ( is_user_logged_in() ) : 
                        $m_user = wp_get_current_user();
                        $m_dash = home_url('/customer-dashboard');
                        if (current_user_can('manage_options')) {
                            $m_dash = home_url('/admin-dashboard');
                        } elseif (in_array('ep_organiser', (array)$m_user->roles) || in_array('event_organiser', (array)$m_user->roles)) {
                            $m_dash = home_url('/organiser-dashboard');
                        }
                    ?>
                        <a href="<?php echo esc_url($m_dash); ?>" class="ep-btn ep-btn-secondary ep-btn-md" style="width: 100%; justify-content: center;">
                            👤 My Account (<?php echo esc_html( $m_user->display_name ?: $m_user->user_login ); ?>)
                        </a>
                        <a href="<?php echo esc_url( wp_logout_url( home_url('/login?loggedout=1') ) ); ?>" class="ep-btn ep-btn-md" style="width: 100%; justify-content: center; background: rgba(239,68,68,0.1); color: #DC2626; border: 1px solid rgba(239,68,68,0.25);">
                            🚪 Sign Out
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(home_url('/login')); ?>" class="ep-btn ep-btn-secondary ep-btn-md" style="width: 100%; justify-content: center;">
                            👤 Sign In / Register
                        </a>
                    <?php endif; ?>
                    <?php if (!is_user_logged_in() || $hdr_is_admin || $hdr_is_org) : ?>
                    <a href="<?php echo esc_url(home_url('/organiser-dashboard')); ?>" class="ep-btn ep-btn-primary ep-btn-md" style="width: 100%; justify-content: center;">
                        💼 Organiser Hub
                    </a>
                    <?php endif; ?>
                </div>

                <div class="ep-mobile-drawer-footer">
                    <a href="<?php echo esc_url(home_url('/faqs')); ?>">Help & FAQs</a>
                    <span>•</span>
                    <a href="<?php echo esc_url(home_url('/gate-scanner')); ?>" style="color: var(--ep-teal-dark); font-weight: 700;">Gate Scanner Portal</a>
                </div>
            </div>
        </div>
    </header>

    <script>
    function toggleMobileMenu() {
        var toggle = document.getElementById('epMobileToggle');
        var drawer = document.getElementById('epMobileDrawer');
        if (!toggle || !drawer) return;
        toggle.classList.toggle('is-active');
        drawer.classList.toggle('is-open');
    }
    document.addEventListener('click', function(e) {
        var toggle = document.getElementById('epMobileToggle');
        var drawer = document.getElementById('epMobileDrawer');
        if (!toggle || !drawer || !drawer.classList.contains('is-open')) return;
        if (!toggle.contains(e.target) && !drawer.contains(e.target)) {
            toggle.classList.remove('is-active');
            drawer.classList.remove('is-open');
        }
    });
    </script>

    <div id="content" class="site-content">
