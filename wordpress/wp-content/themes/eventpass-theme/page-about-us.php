<?php
/**
 * Template Name: About Us
 *
 * @package EventPass_Namibia
 */

get_header();
?>

<!-- About Us Hero -->
<section class="ep-page-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/cultural-night.jpg'); ?>" alt="EventPass Namibia" class="ep-page-hero-bg">
    <div class="ep-page-hero-overlay"></div>
    <div class="container ep-page-hero-content">
        <div style="max-width: 820px;">
            <div class="ep-page-pill">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--ep-cyan); box-shadow:0 0 10px var(--ep-cyan);"></span>
                PROUDLY NAMIBIAN • EVERY EVENT. ONE PASS.
            </div>
            <h1 style="font-size: clamp(2.3rem, 5vw, 3.4rem); font-weight: 900; line-height: 1.15; margin-bottom: 1.25rem;">
                Powering Namibia's Live Experience Economy
            </h1>
            <p style="font-size: 1.15rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 2rem;">
                EventPass Namibia is an online event discovery, digital ticketing, and gate management platform created to connect audiences with unforgettable live experiences across all 14 regions.
            </p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary">Explore All Events</a>
                <a href="<?php echo esc_url(home_url('/contact-us')); ?>" class="ep-btn ep-btn-outline" style="border-color: rgba(255,255,255,0.4); color: #fff;">Contact Our Team</a>
            </div>
        </div>
    </div>
</section>

<!-- Metric Counter Bar -->
<div class="container" style="margin-top: -2.5rem; position: relative; z-index: 10;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
        <div class="ep-stat-card">
            <div class="ep-stat-number">50,000+</div>
            <div class="ep-stat-label">Tickets Issued Safely</div>
        </div>
        <div class="ep-stat-card">
            <div class="ep-stat-number">250+</div>
            <div class="ep-stat-label">Events Hosted Across Namibia</div>
        </div>
        <div class="ep-stat-card">
            <div class="ep-stat-number">14 Regions</div>
            <div class="ep-stat-label">Nationwide Coverage</div>
        </div>
        <div class="ep-stat-card">
            <div class="ep-stat-number">100%</div>
            <div class="ep-stat-label">Namibian Bank Settlement</div>
        </div>
    </div>
</div>

<!-- Purpose & Story Split -->
<div class="container" style="padding: 5.5rem 1.5rem 4rem;">
    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 3.5rem; align-items: center; margin-bottom: 5rem;">
        <div>
            <span style="color: var(--ep-teal-dark); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Our Story</span>
            <h2 style="font-size: clamp(1.8rem, 3.5vw, 2.4rem); font-weight: 900; color: var(--ep-navy); margin: 0.5rem 0 1.25rem; line-height: 1.25;">
                Simplifying Event Access & Eradicating Fraud Across Namibia
            </h2>
            <p style="color: var(--ep-slate-600); line-height: 1.75; font-size: 1.05rem; margin-bottom: 1.25rem;">
                Before EventPass Namibia, securing tickets often meant long queues at physical ticket desks, handling paper vouchers prone to damage or counterfeiting, and organizers dealing with stressful gate verification.
            </p>
            <p style="color: var(--ep-slate-600); line-height: 1.75; font-size: 1.05rem; margin-bottom: 1.5rem;">
                We engineered EventPass to modernize this experience entirely: an attendee can discover a festival, pay in seconds using their Namibian bank card through Finatic, and present an encrypted QR code right from their phone screen.
            </p>
            <div style="background: rgba(0,180,216,0.06); border-left: 4px solid var(--ep-teal); padding: 1rem 1.5rem; border-radius: 0 8px 8px 0; margin-bottom: 2rem;">
                <p style="margin: 0; font-weight: 700; color: var(--ep-navy); font-size: 1rem;">
                    "Every Event. One Pass. We believe that securing your place at a live event should take under two minutes with complete peace of mind."
                </p>
            </div>
        </div>

        <!-- Mission & Vision Cards -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: 16px; padding: 2.25rem; box-shadow: var(--ep-shadow-md);">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(0,180,216,0.1); color: var(--ep-teal-dark); padding: 0.35rem 0.8rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 1rem;">
                    🎯 Our Mission
                </div>
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.75rem;">Connecting Namibia to Experiences</h3>
                <p style="color: var(--ep-slate-600); line-height: 1.65; margin: 0; font-size: 0.98rem;">
                    To simplify event discovery and ticketing in Namibia by providing an accessible, convenient and technology-driven platform that connects event organisers and attendees seamlessly.
                </p>
            </div>

            <div style="background: #ffffff; border: 1px solid var(--ep-slate-200); border-radius: 16px; padding: 2.25rem; box-shadow: var(--ep-shadow-md);">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(11,27,61,0.08); color: var(--ep-navy); padding: 0.35rem 0.8rem; border-radius: 6px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 1rem;">
                    🌍 Our Vision
                </div>
                <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.75rem;">Leading Digital Ticketing in Southern Africa</h3>
                <p style="color: var(--ep-slate-600); line-height: 1.65; margin: 0; font-size: 0.98rem;">
                    To become the leading and most trusted event discovery and digital ticketing platform in Namibia, with the potential to expand seamless ticketing across Southern Africa.
                </p>
            </div>
        </div>
    </div>

    <!-- Core Values Grid -->
    <div style="margin-bottom: 5rem;">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Our Guiding Principles</span>
            <h2 style="font-size: 2.2rem; font-weight: 900; color: var(--ep-navy); margin: 0.4rem 0;">Our Core Values</h2>
            <p style="color: var(--ep-slate-600); font-size: 1rem;">The commitments that drive every product innovation and customer interaction at EventPass Namibia.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;">
            <div style="background: #ffffff; padding: 1.85rem; border-radius: 14px; border: 1px solid var(--ep-slate-200); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 2rem; margin-bottom: 0.75rem;">⚡</div>
                <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Innovation</h4>
                <p style="font-size: 0.9rem; color: #64748B; line-height: 1.6; margin: 0;">We embrace modern cloud and mobile technology to elevate live event experiences.</p>
            </div>
            <div style="background: #ffffff; padding: 1.85rem; border-radius: 14px; border: 1px solid var(--ep-slate-200); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 2rem; margin-bottom: 0.75rem;">🛡️</div>
                <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Trust & Security</h4>
                <p style="font-size: 0.9rem; color: #64748B; line-height: 1.6; margin: 0;">Bank of Namibia compliant, 256-bit TLS encrypted, zero-scalping cryptographic passes.</p>
            </div>
            <div style="background: #ffffff; padding: 1.85rem; border-radius: 14px; border: 1px solid var(--ep-slate-200); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 2rem; margin-bottom: 0.75rem;">🇳🇦</div>
                <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Local Ecosystem</h4>
                <p style="font-size: 0.9rem; color: #64748B; line-height: 1.6; margin: 0;">Built for Namibian bank cards (Bank Windhoek, FNB, Standard Bank, Nedbank) with NAD currency.</p>
            </div>
            <div style="background: #ffffff; padding: 1.85rem; border-radius: 14px; border: 1px solid var(--ep-slate-200); box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="font-size: 2rem; margin-bottom: 0.75rem;">🤝</div>
                <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Promoter First</h4>
                <p style="font-size: 0.9rem; color: #64748B; line-height: 1.6; margin: 0;">Free mobile gate scanners, real-time sales visibility, and rapid EFT remittances.</p>
            </div>
        </div>
    </div>

    <!-- Key Venues Ribbon -->
    <div style="background: var(--ep-navy); border-radius: 20px; padding: 2.75rem 2rem; color: #fff; text-align: center;">
        <span style="color: var(--ep-cyan); font-weight: 800; font-size: 0.85rem; text-transform: uppercase;">Nationwide Footprint</span>
        <h3 style="font-size: 1.75rem; font-weight: 800; margin: 0.4rem 0 1.25rem;">Trusted at Premier Namibian Venues</h3>
        <div style="display: flex; gap: 0.8rem; justify-content: center; flex-wrap: wrap;">
            <span style="background: rgba(255,255,255,0.1); padding: 0.5rem 1.1rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem;">🏟️ Independence Stadium Windhoek</span>
            <span style="background: rgba(255,255,255,0.1); padding: 0.5rem 1.1rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem;">⛳ Windhoek Country Club Resort</span>
            <span style="background: rgba(255,255,255,0.1); padding: 0.5rem 1.1rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem;">🌊 The Mole Swakopmund</span>
            <span style="background: rgba(255,255,255,0.1); padding: 0.5rem 1.1rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem;">⚽ Sam Nujoma Stadium</span>
            <span style="background: rgba(255,255,255,0.1); padding: 0.5rem 1.1rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem;">🌳 Zoo Park Windhoek</span>
        </div>
    </div>
</div>

<?php
get_footer();
