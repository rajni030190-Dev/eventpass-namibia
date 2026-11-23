<?php
/**
 * Template Name: List Your Event
 *
 * @package EventPass_Namibia
 */

get_header();
?>

<!-- List Your Event Hero -->
<section class="ep-page-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/business-summit.jpg'); ?>" alt="Organiser Hub" class="ep-page-hero-bg">
    <div class="ep-page-hero-overlay"></div>
    <div class="container ep-page-hero-content">
        <div style="max-width: 820px;">
            <div class="ep-page-pill">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--ep-cyan); box-shadow:0 0 10px var(--ep-cyan);"></span>
                FOR EVENT ORGANISERS & PROMOTERS
            </div>
            <h1 style="font-size: clamp(2.3rem, 5vw, 3.4rem); font-weight: 900; line-height: 1.15; margin-bottom: 1.25rem;">
                Reach More People. Sell Out Your Event.
            </h1>
            <p style="font-size: 1.15rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 2rem;">
                From intimate workshops and business conferences to 15,000-capacity music festivals, EventPass Namibia gives you enterprise ticketing, turnstile scanners, and real-time revenue analytics with zero upfront setup fees.
            </p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo esc_url(home_url('/organiser-registration')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
                    Register as an Organiser &rarr;
                </a>
                <a href="#calculator" class="ep-btn ep-btn-outline ep-btn-lg" style="border-color: rgba(255,255,255,0.35); color: #fff;">
                    Calculate Your Revenue
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Revenue Calculator & Value Proposition -->
<div class="container" style="padding: 5rem 1.5rem 5.5rem;">
    
    <!-- Interactive Calculator Section -->
    <div id="calculator" style="margin-bottom: 5.5rem;">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 2.5rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Live Profit Simulator</span>
            <h2 style="font-size: 2.2rem; font-weight: 900; color: var(--ep-navy); margin: 0.4rem 0;">Calculate Your Ticket Revenue</h2>
            <p style="color: var(--ep-slate-600); font-size: 1rem;">Transparent 5% platform fee with zero hidden hosting charges. See how much you take home.</p>
        </div>

        <div style="max-width: 900px; margin: 0 auto; background: linear-gradient(135deg, #060E20 0%, #0B1B3D 100%); border: 1px solid rgba(0,229,255,0.25); border-radius: 20px; padding: 2.5rem; color: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 2.5rem; align-items: center;">
                
                <!-- Inputs -->
                <div>
                    <div style="margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 0.5rem;">
                            <span>Expected Attendees:</span>
                            <span id="calcAttendeesDisplay" style="color: var(--ep-cyan); font-size: 1.25rem;">500</span>
                        </div>
                        <input type="range" id="calcAttendeesInput" min="50" max="5000" step="50" value="500" style="width: 100%; accent-color: var(--ep-cyan); cursor: pointer;" oninput="updateRevenueCalc()">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748B; margin-top: 0.25rem;">
                            <span>50</span>
                            <span>2,500</span>
                            <span>5,000+</span>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-bottom: 0.5rem;">
                            <span>Average Ticket Price (NAD):</span>
                            <span id="calcPriceDisplay" style="color: var(--ep-cyan); font-size: 1.25rem;">N$ 200.00</span>
                        </div>
                        <input type="range" id="calcPriceInput" min="20" max="2000" step="10" value="200" style="width: 100%; accent-color: var(--ep-cyan); cursor: pointer;" oninput="updateRevenueCalc()">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748B; margin-top: 0.25rem;">
                            <span>N$ 20.00</span>
                            <span>N$ 1,000.00</span>
                            <span>N$ 2,000.00</span>
                        </div>
                    </div>
                </div>

                <!-- Calculation Results Box -->
                <div style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 2rem; text-align: center;">
                    <div style="font-size: 0.85rem; color: #94A3B8; font-weight: 700; text-transform: uppercase;">Gross Ticket Sales</div>
                    <div id="calcGrossDisplay" style="font-size: 1.85rem; font-weight: 900; color: #fff; margin: 0.25rem 0 1.25rem;">N$ 100,000.00</div>

                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #94A3B8; padding: 0.5rem 0; border-top: 1px solid rgba(255,255,255,0.1);">
                        <span>Platform Fee (5%):</span>
                        <span id="calcFeeDisplay" style="color: #F87171; font-weight: 700;">- N$ 5,000.00</span>
                    </div>

                    <div style="padding-top: 1rem; border-top: 1px solid rgba(0,229,255,0.3); margin-top: 0.5rem;">
                        <div style="font-size: 0.85rem; color: var(--ep-cyan); font-weight: 800; text-transform: uppercase;">Your Estimated Net Payout</div>
                        <div id="calcNetDisplay" style="font-size: 2.3rem; font-weight: 900; color: #34D399; margin: 0.25rem 0;">N$ 95,000.00</div>
                        <div style="font-size: 0.75rem; color: #94A3B8;">Remitted via EFT directly to your Namibian business account.</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Organiser Advantage Pillars -->
    <div style="margin-bottom: 5rem;">
        <div style="text-align: center; max-width: 650px; margin: 0 auto 3rem;">
            <span style="color: var(--ep-teal-dark); font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;">Why EventPass</span>
            <h2 style="font-size: 2.2rem; font-weight: 900; color: var(--ep-navy); margin: 0.4rem 0;">Built for Modern Event Promoters</h2>
            <p style="color: var(--ep-slate-600); font-size: 1rem;">Complete control from ticket tier creation to post-event financial settlement.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.75rem;">
            <div style="background: #ffffff; padding: 2rem; border-radius: 16px; border: 1px solid var(--ep-slate-200); box-shadow: 0 8px 20px rgba(0,0,0,0.03);">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(0,180,216,0.12); color: var(--ep-teal-dark); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">⚡</div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.65rem;">5-Minute Event Setup</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                    Configure unlimited ticket tiers (Early Bird, VIP, Table Packages), add custom seat counts, and publish your event live instantly.
                </p>
            </div>

            <div style="background: #ffffff; padding: 2rem; border-radius: 16px; border: 1px solid var(--ep-slate-200); box-shadow: 0 8px 20px rgba(0,0,0,0.03);">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(0,180,216,0.12); color: var(--ep-teal-dark); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">📱</div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.65rem;">Free Turnstile Scanners</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                    Equip gate bouncers with our mobile scanner app. Validates encrypted QR codes under 1 second and works offline if mobile signal drops.
                </p>
            </div>

            <div style="background: #ffffff; padding: 2rem; border-radius: 16px; border: 1px solid var(--ep-slate-200); box-shadow: 0 8px 20px rgba(0,0,0,0.03);">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(0,180,216,0.12); color: var(--ep-teal-dark); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">📊</div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.65rem;">Real-Time Sales Radar</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                    Watch ticket sales happen live on your promoter dashboard. Track buyer locations, tier popularity, and remaining venue capacity.
                </p>
            </div>

            <div style="background: #ffffff; padding: 2rem; border-radius: 16px; border: 1px solid var(--ep-slate-200); box-shadow: 0 8px 20px rgba(0,0,0,0.03);">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(0,180,216,0.12); color: var(--ep-teal-dark); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">🇳🇦</div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--ep-navy); margin-bottom: 0.65rem;">Direct Namibian EFT</h3>
                <p style="color: var(--ep-slate-600); font-size: 0.95rem; line-height: 1.6; margin: 0;">
                    Zero international cross-border transfer delays. Receive full event proceeds straight into Bank Windhoek, FNB, Standard Bank, or Nedbank.
                </p>
            </div>
        </div>
    </div>

    <!-- Bottom CTA -->
    <div style="text-align: center; background: #ffffff; border: 2px dashed var(--ep-teal); border-radius: 20px; padding: 3.5rem 2rem;">
        <h3 style="font-size: 2rem; font-weight: 900; color: var(--ep-navy); margin-bottom: 0.5rem;">Ready to sell tickets to thousands of Namibians?</h3>
        <p style="color: var(--ep-slate-600); font-size: 1.05rem; max-width: 600px; margin: 0 auto 2rem;">
            Join over 250+ event organizers who trust EventPass for sold-out events and seamless turnstile scanning.
        </p>
        <a href="<?php echo esc_url(home_url('/organiser-registration')); ?>" class="ep-btn ep-btn-primary ep-btn-lg">
            Create Your Organiser Account Now &rarr;
        </a>
    </div>

</div>

<script>
function updateRevenueCalc() {
    var attendees = parseInt(document.getElementById('calcAttendeesInput').value);
    var price = parseFloat(document.getElementById('calcPriceInput').value);

    document.getElementById('calcAttendeesDisplay').innerText = attendees.toLocaleString();
    document.getElementById('calcPriceDisplay').innerText = 'N$ ' + price.toFixed(2);

    var gross = attendees * price;
    var fee = gross * 0.05;
    var net = gross - fee;

    document.getElementById('calcGrossDisplay').innerText = 'N$ ' + gross.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('calcFeeDisplay').innerText = '- N$ ' + fee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('calcNetDisplay').innerText = 'N$ ' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php
get_footer();
