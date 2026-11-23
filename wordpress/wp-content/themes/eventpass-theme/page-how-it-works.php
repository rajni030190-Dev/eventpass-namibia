<?php
/**
 * Template Name: How It Works
 *
 * @package EventPass_Namibia
 */

get_header();
?>

<!-- Cinematic Hero Header with Photographic Backdrop -->
<section class="ep-hiw-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/festival-crowd.jpg'); ?>" alt="EventPass Namibia Event Audience" class="ep-hiw-hero-bg">
    <div class="ep-hiw-hero-overlay"></div>
    <div class="container" style="position: relative; z-index: 3;">
        <div class="ep-hero-badge">
            <span class="ep-pulse-dot"></span>
            <span>Simple • Secure • Instant Digital Entry</span>
        </div>
        <h1 style="font-size: clamp(2.3rem, 4.8vw, 3.6rem); font-weight: 800; margin: 0.5rem 0 1rem; color: #fff; line-height: 1.15;">
            How EventPass <span class="ep-title-gradient">Works</span>
        </h1>
        <p style="font-size: 1.1rem; color: #CBD5E1; max-width: 720px; margin: 0 auto 1.5rem; line-height: 1.6;">
            Your complete guide to discovering, booking, and hosting memorable experiences across Namibia. Seamless for attendees, powerful for organisers.
        </p>

        <!-- Interactive Dual-Audience Tab Switcher -->
        <div class="ep-hiw-toggle-wrap">
            <button type="button" id="hiwBtnBuyer" class="ep-hiw-toggle-btn active" onclick="switchHiwTab('buyer')">
                <span>🎟️</span>
                <span>For Ticket Buyers (Attend Events)</span>
            </button>
            <button type="button" id="hiwBtnOrganiser" class="ep-hiw-toggle-btn" onclick="switchHiwTab('organiser')">
                <span>💼</span>
                <span>For Event Organisers (Host & Sell)</span>
            </button>
        </div>
    </div>
</section>

<!-- Main Step-by-Step Interactive Guide -->
<div class="container" style="padding: 4.5rem 1.5rem 5rem;">

    <!-- ================= BUYER JOURNEY (DEFAULT) ================= -->
    <div id="hiwBuyerFlow">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3.5rem;">
            <span style="color: var(--ep-teal); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1.5px; background: rgba(0, 180, 216, 0.1); padding: 0.35rem 0.9rem; border-radius: var(--ep-radius-full); display: inline-block; margin-bottom: 0.75rem;">Attendee Journey</span>
            <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">From Discovery to Gate Entry in 4 Steps</h2>
            <p style="color: var(--ep-slate-600); margin-top: 0.5rem; font-size: 1.05rem;">Book your tickets in under 2 minutes with instant digital delivery.</p>
        </div>

        <!-- Step 1: Discover -->
        <div class="ep-hiw-step-card">
            <div>
                <span class="ep-hiw-step-pill">Step 01 • Discovery</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Explore Events Across Namibia</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Browse live concerts in Windhoek, beach festivals in Swakopmund, marathons, or executive summits. Filter effortlessly by date, town, category, or ticket price.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Town-by-Town Filtering:</strong> Windhoek, Swakopmund, Walvis Bay, Oshakati.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Verified Hosts:</strong> Every listing is reviewed for safety and authenticity.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Live Seat Counters:</strong> Check remaining tickets in real time.</span></li>
                </ul>
            </div>
            <div class="ep-hiw-visual-box" style="background: linear-gradient(135deg, #060E20 0%, #0B1B3D 100%); color: #fff; padding: 1.75rem; border: 1px solid rgba(0,229,255,0.25);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="font-size: 0.75rem; background: rgba(0,229,255,0.18); color: var(--ep-cyan); padding: 0.25rem 0.65rem; border-radius: var(--ep-radius-full); font-weight: 700;">🎵 MUSIC & FESTIVALS</span>
                    <span style="font-size: 0.75rem; color: #94A3B8;">Windhoek • Live</span>
                </div>
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/concert-lights.jpg'); ?>" alt="Concert preview" style="width: 100%; height: 160px; object-fit: cover; border-radius: 12px; margin-bottom: 1rem; border: 1px solid rgba(255,255,255,0.1);">
                <h4 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 0.35rem;">Windhoek International Music Festival</h4>
                <div style="font-size: 0.85rem; color: #CBD5E1; margin-bottom: 0.75rem;">📅 Fri, 27 Nov • 📍 Independence Stadium</div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.75rem;">
                    <span style="color: var(--ep-cyan); font-weight: 800; font-size: 1.15rem;">From N$ 180.00</span>
                    <span style="background: var(--ep-teal); color: #fff; padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">Select &rarr;</span>
                </div>
            </div>
        </div>

        <!-- Step 2: Select Tickets -->
        <div class="ep-hiw-step-card reverse">
            <div class="ep-hiw-visual-box" style="background: #ffffff; padding: 1.5rem; border: 2px solid var(--ep-teal); box-shadow: 0 10px 30px rgba(0, 180, 216, 0.15);">
                <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--ep-teal-dark); display: block; margin-bottom: 0.25rem;">Interactive Tier Selector</span>
                <h4 style="font-size: 1.25rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1rem;">Choose Your Tickets</h4>

                <!-- Tier 1 Selected -->
                <div style="border: 2px solid var(--ep-teal); background: rgba(0, 180, 216, 0.05); border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--ep-navy); font-size: 0.95rem;">Early Bird (Limited)</strong>
                            <span style="font-size: 0.75rem; color: var(--ep-teal-dark); display: block;">✓ 45 tickets remaining</span>
                        </div>
                        <span style="font-weight: 800; color: var(--ep-teal-dark); font-size: 1.05rem;">N$ 180.00</span>
                    </div>
                </div>

                <!-- Tier 2 -->
                <div style="border: 1px solid var(--ep-slate-200); background: #fff; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--ep-navy); font-size: 0.95rem;">General Admission</strong>
                            <span style="font-size: 0.75rem; color: #64748B; display: block;">Standard arena entry</span>
                        </div>
                        <span style="font-weight: 800; color: var(--ep-navy); font-size: 1.05rem;">N$ 250.00</span>
                    </div>
                </div>

                <!-- Tier 3 -->
                <div style="border: 1px solid var(--ep-slate-200); background: #fff; border-radius: 10px; padding: 0.85rem 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--ep-navy); font-size: 0.95rem;">VIP Golden Circle</strong>
                            <span style="font-size: 0.75rem; color: #64748B; display: block;">Front stage + Private VIP Bar</span>
                        </div>
                        <span style="font-weight: 800; color: var(--ep-navy); font-size: 1.05rem;">N$ 650.00</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid var(--ep-slate-200); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: var(--ep-slate-600);">Total (1 Ticket):</span>
                    <span style="font-size: 1.25rem; font-weight: 800; color: var(--ep-teal-dark);">N$ 180.00</span>
                </div>
            </div>
            <div>
                <span class="ep-hiw-step-pill">Step 02 • Ticket Selection</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Choose Your Preferred Ticket Tier</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Pick from General Admission, Student passes, Early Bird specials, or VIP Hospitality packages. Pricing is transparent in Namibian Dollars (N$) with zero hidden booking surcharges.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Transparent NAD Pricing:</strong> What you see is what you pay.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Group Bookings:</strong> Secure up to 10 tickets in one transaction.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>10-Minute Cart Reservation:</strong> Your selected tickets are held securely.</span></li>
                </ul>
            </div>
        </div>

        <!-- Step 3: Payment -->
        <div class="ep-hiw-step-card">
            <div>
                <span class="ep-hiw-step-pill">Step 03 • Secure Payment</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Fast Namibian Card Checkout</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Pay securely with your Visa or Mastercard issued by any Namibian financial institution through <strong>Finatic Namibia</strong>. Protected with bank-grade 256-bit TLS encryption.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Supported Banks:</strong> Bank Windhoek, FNB Namibia, Standard Bank, Nedbank.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Bank of Namibia NPS Compliant:</strong> Certified local payment rails.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Instant Verification:</strong> No delays; ticket generated immediately.</span></li>
                </ul>
            </div>
            <div class="ep-hiw-visual-box" style="background: linear-gradient(135deg, #0B1B3D 0%, #060E20 100%); color: #fff; padding: 2rem; border: 1px solid rgba(0, 180, 216, 0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <strong style="color: #fff; font-size: 1.05rem;">Finatic Namibia Payment Terminal</strong>
                    <span style="font-size: 0.72rem; background: var(--ep-success); color: #fff; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700;">256-BIT TLS</span>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                    <span style="background: rgba(255,255,255,0.1); padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">Bank Windhoek</span>
                    <span style="background: rgba(255,255,255,0.1); padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">FNB Namibia</span>
                    <span style="background: rgba(255,255,255,0.1); padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">Standard Bank</span>
                    <span style="background: rgba(255,255,255,0.1); padding: 0.35rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700;">Nedbank</span>
                </div>
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 10px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.4rem;">🛡️</span>
                    <div>
                        <strong style="color: #34D399; font-size: 0.9rem; display: block;">Payment Approved & Confirmed</strong>
                        <span style="font-size: 0.75rem; color: #CBD5E1;">Trans ID: FIN-84920 • Amount: N$ 180.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Digital QR Ticket & Gate Entry -->
        <div class="ep-hiw-step-card reverse">
            <div class="ep-hiw-visual-box" style="background: #ffffff; padding: 1.75rem; border: 2px solid var(--ep-teal); border-radius: 18px; box-shadow: 0 15px 35px rgba(0, 180, 216, 0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #CBD5E1; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <div>
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo.png'); ?>" alt="EventPass" style="height: 28px; width: auto;">
                        <span style="display: block; font-size: 0.7rem; color: #64748B; font-weight: 700; text-transform: uppercase;">Digital Gate Pass</span>
                    </div>
                    <span style="background: rgba(16, 185, 129, 0.15); color: #059669; font-weight: 800; font-size: 0.75rem; padding: 0.25rem 0.65rem; border-radius: var(--ep-radius-full);">● ACTIVE & VALID</span>
                </div>
                <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.25rem;">Windhoek Music Festival 2026</h4>
                <div style="font-size: 0.8rem; color: #64748B; margin-bottom: 1rem;">Attendee: <strong>Johannes Shilongo</strong> • VIP Pass</div>
                
                <!-- Visual QR Mockup -->
                <div style="text-align: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1rem;">
                    <svg width="120" height="120" viewBox="0 0 100 100" style="margin: 0 auto; display: block;">
                        <rect width="100" height="100" fill="#ffffff" />
                        <rect x="5" y="5" width="25" height="25" fill="#0B1B3D" />
                        <rect x="10" y="10" width="15" height="15" fill="#ffffff" />
                        <rect x="13" y="13" width="9" height="9" fill="#00B4D8" />
                        <rect x="70" y="5" width="25" height="25" fill="#0B1B3D" />
                        <rect x="75" y="10" width="15" height="15" fill="#ffffff" />
                        <rect x="78" y="13" width="9" height="9" fill="#00B4D8" />
                        <rect x="5" y="70" width="25" height="25" fill="#0B1B3D" />
                        <rect x="10" y="75" width="15" height="15" fill="#ffffff" />
                        <rect x="13" y="78" width="9" height="9" fill="#00B4D8" />
                        <rect x="35" y="10" width="5" height="20" fill="#0B1B3D" />
                        <rect x="45" y="5" width="15" height="5" fill="#0B1B3D" />
                        <rect x="40" y="20" width="20" height="8" fill="#00B4D8" />
                        <rect x="35" y="35" width="30" height="30" fill="#0B1B3D" />
                        <rect x="42" y="42" width="16" height="16" fill="#ffffff" />
                        <rect x="46" y="46" width="8" height="8" fill="#00B4D8" />
                        <rect x="70" y="40" width="10" height="20" fill="#0B1B3D" />
                        <rect x="85" y="35" width="10" height="10" fill="#0B1B3D" />
                        <rect x="35" y="75" width="20" height="5" fill="#0B1B3D" />
                        <rect x="65" y="70" width="15" height="25" fill="#0B1B3D" />
                        <rect x="85" y="80" width="10" height="15" fill="#00B4D8" />
                    </svg>
                    <span style="font-family: monospace; font-size: 0.75rem; color: #64748B; margin-top: 0.5rem; display: block; letter-spacing: 2px;">#EP-84920-WHK</span>
                </div>
                <div style="font-size: 0.78rem; text-align: center; color: var(--ep-teal-dark); font-weight: 700;">
                    ⚡ Scanned in 1 second at Independence Stadium turnstiles
                </div>
            </div>
            <div>
                <span class="ep-hiw-step-pill">Step 04 • Gate Entry</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Digital Ticket & Instant Gate Entry</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    No printer needed! Your ticket is delivered straight to your email and SMS. Present your phone screen at the venue gate for instant turnstile check-in.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>100% Mobile Access:</strong> Show the QR code directly on your phone screen.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Anti-Counterfeit:</strong> Unique cryptographic code prevents duplicate passes.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Offline Scannable:</strong> Gate marshals scan even if cell coverage drops.</span></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ================= ORGANISER JOURNEY (TOGGLED) ================= -->
    <div id="hiwOrganiserFlow" style="display: none;">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3.5rem;">
            <span style="color: var(--ep-teal); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1.5px; background: rgba(0, 180, 216, 0.1); padding: 0.35rem 0.9rem; border-radius: var(--ep-radius-full); display: inline-block; margin-bottom: 0.75rem;">Organiser Journey</span>
            <h2 style="font-size: 2.25rem; font-weight: 800; color: var(--ep-navy); margin-top: 0.25rem;">Host, Sell & Scan Events with Ease</h2>
            <p style="color: var(--ep-slate-600); margin-top: 0.5rem; font-size: 1.05rem;">Manage ticket tiers, track real-time revenue, and check in thousands of attendees.</p>
        </div>

        <!-- Org Step 1 -->
        <div class="ep-hiw-step-card">
            <div>
                <span class="ep-hiw-step-pill">Organiser Step 01</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Register as an Event Organiser</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Complete our quick promoter onboarding. Submit your organization name, contact details, and verified Namibian bank account for automated payouts.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Verified Promoter Status:</strong> Builds attendee trust across Namibia.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Fast Onboarding:</strong> Get approved and start publishing in 24 hours.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Dedicated Portal:</strong> Custom hub to manage all your current and future events.</span></li>
                </ul>
            </div>
            <div class="ep-hiw-visual-box" style="background: #ffffff; padding: 2rem; border: 1px solid var(--ep-slate-200);">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700; display: block; margin-bottom: 0.25rem;">Promoter Onboarding</span>
                <h4 style="font-size: 1.2rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.25rem;">Kalahari Arts Festival CC</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="background: var(--ep-slate-50); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--ep-slate-200); font-size: 0.85rem;">
                        <span style="color: #64748B; display: block; font-size: 0.75rem;">Contact Person:</span>
                        <strong>Martin Shikongo (Promoter)</strong>
                    </div>
                    <div style="background: var(--ep-slate-50); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--ep-slate-200); font-size: 0.85rem;">
                        <span style="color: #64748B; display: block; font-size: 0.75rem;">Settlement Account:</span>
                        <strong>Bank Windhoek • Business Cheque Account</strong>
                    </div>
                    <div style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 0.5rem 0.85rem; border-radius: 6px; font-size: 0.8rem; font-weight: 700; text-align: center;">
                        ✓ Organiser Status: Approved & Verified
                    </div>
                </div>
            </div>
        </div>

        <!-- Org Step 2 -->
        <div class="ep-hiw-step-card reverse">
            <div class="ep-hiw-visual-box" style="background: #ffffff; padding: 2rem; border: 1px solid var(--ep-slate-200);">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-teal-dark); font-weight: 700; display: block; margin-bottom: 0.25rem;">Event Publishing Studio</span>
                <h4 style="font-size: 1.2rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 1.25rem;">Tier Configuration</h4>
                <div style="display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; padding: 0.6rem; background: var(--ep-slate-50); border-radius: 6px;">
                        <span>Early Bird Cap: <strong>100 Tickets</strong></span>
                        <strong style="color: var(--ep-teal-dark);">N$ 180.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.6rem; background: var(--ep-slate-50); border-radius: 6px;">
                        <span>General Admission: <strong>500 Tickets</strong></span>
                        <strong style="color: var(--ep-teal-dark);">N$ 250.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.6rem; background: var(--ep-slate-50); border-radius: 6px;">
                        <span>VIP Hospitality: <strong>50 Tickets</strong></span>
                        <strong style="color: var(--ep-teal-dark);">N$ 650.00</strong>
                    </div>
                </div>
            </div>
            <div>
                <span class="ep-hiw-step-pill">Organiser Step 02</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Publish & Customize Your Event</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Create high-impact listings with venue location maps, event schedules, custom ticket tiers, discount promo codes, and automated sales cut-off deadlines.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Unlimited Tiers:</strong> Early bird, VIP, backstage, media passes.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>High-Resolution Artwork:</strong> Showcase concert posters and trailers.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Immediate Exposure:</strong> Featured in the EventPass discovery feed.</span></li>
                </ul>
            </div>
        </div>

        <!-- Org Step 3 -->
        <div class="ep-hiw-step-card">
            <div>
                <span class="ep-hiw-step-pill">Organiser Step 03</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Real-Time Sales & Revenue Dashboard</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Watch your event sales update live as tickets are purchased. View attendee demographics, sales velocity graphs, and download attendee lists anytime.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Live NAD Revenue Counter:</strong> Real-time financial clarity.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Export Attendee Lists:</strong> CSV and Excel download with one click.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Automated Settlements:</strong> Fast EFT payouts into your Namibian bank account.</span></li>
                </ul>
            </div>
            <div class="ep-hiw-visual-box" style="background: var(--ep-navy-deep); color: #fff; padding: 2rem; border: 1px solid rgba(0, 229, 255, 0.3);">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--ep-cyan); font-weight: 700; display: block; margin-bottom: 0.25rem;">Live Analytics</span>
                <h4 style="font-size: 1.85rem; font-weight: 800; color: #fff; margin-bottom: 1rem;">N$ 84,250.00</h4>
                <div style="background: rgba(255,255,255,0.08); border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.4rem;">
                        <span>Tickets Sold:</span>
                        <strong>412 / 650 (63%)</strong>
                    </div>
                    <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.2); border-radius: 4px; overflow: hidden;">
                        <div style="width: 63%; height: 100%; background: linear-gradient(90deg, #00C2CB, #00E5FF);"></div>
                    </div>
                </div>
                <span style="font-size: 0.8rem; color: #34D399; font-weight: 700;">▲ +24% sales spike in the last 24 hours</span>
            </div>
        </div>

        <!-- Org Step 4 -->
        <div class="ep-hiw-step-card reverse">
            <div class="ep-hiw-visual-box" style="background: linear-gradient(135deg, #0B1B3D 0%, #060E20 100%); color: #fff; padding: 2rem; border: 1px solid rgba(0, 180, 216, 0.4);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <strong style="color: var(--ep-cyan);">EventPass Gate Scanner</strong>
                    <span style="font-size: 0.72rem; background: var(--ep-teal); color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px;">OFFLINE-READY</span>
                </div>
                <div style="background: rgba(0,0,0,0.5); border: 2px dashed var(--ep-cyan); border-radius: 12px; padding: 2rem 1rem; text-align: center; margin-bottom: 1rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📷</div>
                    <span style="font-size: 0.85rem; color: #CBD5E1;">Align Attendee QR Code in Frame</span>
                </div>
                <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10B981; border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.25rem;">✓</span>
                    <div style="font-size: 0.85rem;">
                        <strong style="color: #34D399; display: block;">ENTRY GRANTED: VIP PASS</strong>
                        <span style="color: #E2E8F0;">Johannes Shilongo • Turnstile 02</span>
                    </div>
                </div>
            </div>
            <div>
                <span class="ep-hiw-step-pill">Organiser Step 04</span>
                <h3 style="font-size: 1.85rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.85rem; line-height: 1.25;">Scan Tickets with the Free Mobile App</h3>
                <p style="color: var(--ep-slate-600); font-size: 1.05rem; line-height: 1.65; margin-bottom: 1.25rem;">
                    Turn any staff smartphone or tablet into a high-speed gate validator. Validate tickets in under 1 second per attendee, with real-time sync across multiple gate entrances.
                </p>
                <ul class="ep-hiw-bullets">
                    <li><span class="ep-check-icon">✓</span> <span><strong>Zero Extra Hardware:</strong> Works on standard iOS & Android phones.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Offline Mode:</strong> Gate check-in continues smoothly even without venue Wi-Fi.</span></li>
                    <li><span class="ep-check-icon">✓</span> <span><strong>Duplicate Alerts:</strong> Instantly detects and blocks forwarded or reused passes.</span></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Trust & Technology Feature Grid -->
    <div class="ep-hiw-feature-grid">
        <div class="ep-hiw-feat-box">
            <div style="width: 48px; height: 48px; background: rgba(0, 180, 216, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">⚡</div>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Instant Delivery</h4>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; line-height: 1.6;">Digital tickets generated within 5 seconds of payment and delivered directly to your device.</p>
        </div>
        <div class="ep-hiw-feat-box">
            <div style="width: 48px; height: 48px; background: rgba(16, 185, 129, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">🛡️</div>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Anti-Scalp Protection</h4>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; line-height: 1.6;">Every pass is encrypted with unique cryptographic tokens that invalidate once scanned at gate.</p>
        </div>
        <div class="ep-hiw-feat-box">
            <div style="width: 48px; height: 48px; background: rgba(99, 102, 241, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">🇳🇦</div>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">100% Namibian Banks</h4>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; line-height: 1.6;">Integrated with Bank Windhoek, FNB, Standard Bank and Nedbank for seamless local NAD checkout.</p>
        </div>
        <div class="ep-hiw-feat-box">
            <div style="width: 48px; height: 48px; background: rgba(245, 158, 11, 0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">📱</div>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.5rem;">Offline Gate Validation</h4>
            <p style="color: var(--ep-slate-600); font-size: 0.9rem; line-height: 1.6;">Turnstiles scan tickets without interruptions even when stadium cellular coverage is saturated.</p>
        </div>
    </div>

    <!-- Bottom High-Impact Call to Action Banner -->
    <div style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, #0B1B3D 100%); border-radius: 24px; padding: 3.5rem 2.5rem; text-align: center; color: #fff; position: relative; overflow: hidden; border: 1px solid rgba(0, 229, 255, 0.35); box-shadow: 0 25px 60px rgba(0,0,0,0.4);">
        <div style="position: relative; z-index: 2; max-width: 680px; margin: 0 auto;">
            <span style="color: var(--ep-cyan); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1.5px; display: block; margin-bottom: 0.5rem;">Every Event. One Pass.</span>
            <h3 style="font-size: 2.35rem; font-weight: 800; margin-bottom: 1rem; line-height: 1.2;">Ready to Experience Events in Namibia?</h3>
            <p style="color: #CBD5E1; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6;">
                Discover upcoming entertainment, secure your tickets online with verified Namibian banks, and walk straight through the gates.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo esc_url(home_url('/explore-events')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
                    <span>Explore Upcoming Events &rarr;</span>
                </a>
                <a href="<?php echo esc_url(home_url('/list-your-event')); ?>" class="ep-btn ep-btn-outline ep-btn-lg" style="color: #ffffff; border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.08);">
                    <span>Host an Event with Us</span>
                </a>
            </div>
        </div>
    </div>

</div>

<script>
function switchHiwTab(type) {
    const buyerFlow = document.getElementById('hiwBuyerFlow');
    const organiserFlow = document.getElementById('hiwOrganiserFlow');
    const btnBuyer = document.getElementById('hiwBtnBuyer');
    const btnOrganiser = document.getElementById('hiwBtnOrganiser');

    if (type === 'buyer') {
        buyerFlow.style.display = 'block';
        organiserFlow.style.display = 'none';
        btnBuyer.classList.add('active');
        btnOrganiser.classList.remove('active');
    } else {
        buyerFlow.style.display = 'none';
        organiserFlow.style.display = 'block';
        btnOrganiser.classList.add('active');
        btnBuyer.classList.remove('active');
    }
}
</script>

<?php
get_footer();
