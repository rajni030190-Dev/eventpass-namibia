<?php
/**
 * Template Name: FAQs
 *
 * @package EventPass_Namibia
 */

get_header();
?>

<!-- FAQs Hero -->
<section class="ep-page-hero">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/hero/dj-performance.jpg'); ?>" alt="FAQs" class="ep-page-hero-bg">
    <div class="ep-page-hero-overlay"></div>
    <div class="container ep-page-hero-content">
        <div style="max-width: 820px;">
            <div class="ep-page-pill">
                <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--ep-cyan); box-shadow:0 0 10px var(--ep-cyan);"></span>
                KNOWLEDGE BASE & SUPPORT
            </div>
            <h1 style="font-size: clamp(2.3rem, 5vw, 3.4rem); font-weight: 900; line-height: 1.15; margin-bottom: 1.25rem;">
                Frequently Asked Questions
            </h1>
            <p style="font-size: 1.15rem; color: #CBD5E1; line-height: 1.7; margin-bottom: 1.5rem;">
                Instant answers about purchasing tickets, accepted Namibian bank payment options, mobile QR gate check-ins, and promoter tools.
            </p>
        </div>
    </div>
</section>

<!-- FAQs Interactive Section -->
<div class="container" style="padding: 4.5rem 1.5rem 5.5rem;">
    
    <!-- Category Filter Chips -->
    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 2.5rem; justify-content: center;">
        <button type="button" class="ep-faq-filter-btn active" onclick="filterFaqs('all', this)">All Questions</button>
        <button type="button" class="ep-faq-filter-btn" onclick="filterFaqs('buying', this)">Buying Tickets</button>
        <button type="button" class="ep-faq-filter-btn" onclick="filterFaqs('payment', this)">Payments & Namibian Banks</button>
        <button type="button" class="ep-faq-filter-btn" onclick="filterFaqs('gate', this)">Gate Entry & QR Codes</button>
        <button type="button" class="ep-faq-filter-btn" onclick="filterFaqs('organiser', this)">Event Organisers</button>
    </div>

    <!-- Accordion Items -->
    <div style="max-width: 850px; margin: 0 auto 4rem;">
        
        <div class="ep-faq-card open" data-category="buying">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>How do I receive my ticket after completing payment?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                Your digital ticket is generated immediately upon payment confirmation! You will receive an instant email and SMS containing your secure ticket link. You can also view and download it anytime from <strong>My Account</strong> or save the encrypted QR pass directly to your smartphone wallet.
            </div>
        </div>

        <div class="ep-faq-card" data-category="payment">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>Which Namibian payment cards and banks are supported?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                We accept all Visa and Mastercard debit and credit cards issued by <strong>Bank Windhoek</strong>, <strong>FNB Namibia</strong>, <strong>Standard Bank</strong>, and <strong>Nedbank</strong> through our certified Finatic Namibia payment gateway. All transactions are settled in Namibian Dollars (NAD) and protected with bank-grade 256-bit TLS encryption.
            </div>
        </div>

        <div class="ep-faq-card" data-category="gate">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>Do I need to print my ticket or can I show my phone?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                No printing needed! EventPass Namibia is 100% paperless. Simply open your digital ticket email, SMS, or pass on your mobile phone screen at the venue gate. Our high-speed optical scanners validate your cryptographic QR code in under 1 second.
            </div>
        </div>

        <div class="ep-faq-card" data-category="buying">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>Can I buy tickets for friends or group bookings?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                Yes! You can select multiple tickets in a single checkout (up to 10 tickets per transaction). Each ticket issued includes a distinct cryptographic QR code and unique serial number, making it easy to transfer or forward individual tickets to friends.
            </div>
        </div>

        <div class="ep-faq-card" data-category="buying">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>What happens if an event is postponed or cancelled?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                If an event is rescheduled or cancelled by the organiser, you will be notified immediately via email and SMS. In accordance with Bank of Namibia regulations and our consumer protection policy, full refunds or transferred entry passes are processed swiftly through the original payment method.
            </div>
        </div>

        <div class="ep-faq-card" data-category="organiser">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>How quickly do event organisers receive ticket sales payouts?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                Organiser payouts are processed via electronic funds transfer (EFT) directly to your verified Namibian bank account. Payout reports and automated statements are accessible inside your Organiser Hub.
            </div>
        </div>

        <div class="ep-faq-card" data-category="gate">
            <div class="ep-faq-question" onclick="toggleFaq(this)">
                <span>Can tickets be scanned at the venue if mobile network coverage is poor?</span>
                <div class="ep-faq-icon">+</div>
            </div>
            <div class="ep-faq-answer">
                Yes! The EventPass Gate Scanner app features offline synchronization. The guest list is cached securely on the scanner device, allowing your gate security team to scan and validate tickets even with zero network coverage.
            </div>
        </div>

    </div>

    <!-- Still Have Questions Card -->
    <div style="background: linear-gradient(135deg, var(--ep-navy-deep) 0%, var(--ep-navy) 100%); border: 1px solid rgba(0,229,255,0.25); border-radius: 20px; padding: 3rem 2.5rem; color: #fff; text-align: center; max-width: 850px; margin: 0 auto;">
        <span style="color: var(--ep-cyan); font-weight: 800; font-size: 0.85rem; text-transform: uppercase;">Still have questions?</span>
        <h3 style="font-size: 1.85rem; font-weight: 800; margin: 0.4rem 0 1rem;">We're Always Happy to Help</h3>
        <p style="color: #CBD5E1; font-size: 1rem; max-width: 550px; margin: 0 auto 2rem;">
            Can't find the answer you're looking for? Reach out to our customer care team on WhatsApp or send an email.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="https://wa.me/264819200000" target="_blank" class="ep-whatsapp-btn">
                <span>💬 Message on WhatsApp</span>
            </a>
            <a href="<?php echo esc_url(home_url('/contact-us')); ?>" class="ep-btn ep-btn-primary">
                Contact Support Desk &rarr;
            </a>
        </div>
    </div>

</div>

<script>
function toggleFaq(header) {
    var card = header.parentElement;
    card.classList.toggle('open');
}

function filterFaqs(category, btn) {
    var buttons = document.querySelectorAll('.ep-faq-filter-btn');
    buttons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    var cards = document.querySelectorAll('.ep-faq-card');
    cards.forEach(card => {
        if (category === 'all' || card.getAttribute('data-category') === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php
get_footer();
