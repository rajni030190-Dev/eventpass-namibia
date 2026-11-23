    </div><!-- #content -->

    <footer id="colophon" class="ep-site-footer">
        <div class="container">
            <div class="ep-footer-grid">
                <!-- Col 1: Brand & Bio -->
                <div class="ep-footer-col ep-footer-brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-white.png'); ?>" alt="EventPass Namibia - Every Event. One Pass.">
                    </a>
                    <p style="margin-top: 1rem; line-height: 1.6; font-size: 0.95rem; color: #94A3B8;">
                        <strong>Every Event. One Pass.</strong><br>
                        Discover exciting events, book tickets online and experience more of Namibia. From music festivals and sports to executive business conferences.
                    </p>
                    <div class="ep-footer-social" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                        <a href="https://facebook.com" target="_blank" rel="noopener" style="color: #94A3B8; font-size: 0.9rem;">Facebook</a>
                        <span style="color: rgba(255,255,255,0.2);">•</span>
                        <a href="https://instagram.com" target="_blank" rel="noopener" style="color: #94A3B8; font-size: 0.9rem;">Instagram</a>
                        <span style="color: rgba(255,255,255,0.2);">•</span>
                        <a href="https://tiktok.com" target="_blank" rel="noopener" style="color: #94A3B8; font-size: 0.9rem;">TikTok</a>
                        <span style="color: rgba(255,255,255,0.2);">•</span>
                        <a href="https://linkedin.com" target="_blank" rel="noopener" style="color: #94A3B8; font-size: 0.9rem;">LinkedIn</a>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="ep-footer-col">
                    <h4 class="ep-footer-heading">Quick Links</h4>
                    <ul class="ep-footer-links">
                        <li><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                        <li><a href="<?php echo esc_url(home_url('/explore-events')); ?>">Explore Events</a></li>
                        <li><a href="<?php echo esc_url(home_url('/categories')); ?>">Event Categories</a></li>
                        <li><a href="<?php echo esc_url(home_url('/about-us')); ?>">About Us</a></li>
                        <li><a href="<?php echo esc_url(home_url('/how-it-works')); ?>">How It Works</a></li>
                        <li><a href="<?php echo esc_url(home_url('/contact-us')); ?>">Contact Us</a></li>
                        <li><a href="<?php echo esc_url(home_url('/faqs')); ?>">FAQs</a></li>
                    </ul>
                </div>

                <!-- Col 3: For Organisers -->
                <div class="ep-footer-col">
                    <h4 class="ep-footer-heading">For Organisers</h4>
                    <ul class="ep-footer-links">
                        <li><a href="<?php echo esc_url(home_url('/list-your-event')); ?>">List Your Event</a></li>
                        <li><a href="<?php echo esc_url(home_url('/organiser-registration')); ?>">Organiser Registration</a></li>
                        <li><a href="<?php echo esc_url(home_url('/organiser-dashboard')); ?>">Organiser Dashboard</a></li>
                        <li><a href="<?php echo esc_url(home_url('/gate-scanner')); ?>">Ticket Scanner App</a></li>
                        <li><a href="<?php echo esc_url(home_url('/organiser-terms')); ?>">Organiser Agreement</a></li>
                    </ul>
                </div>

                <!-- Col 4: Legal & Compliance -->
                <div class="ep-footer-col">
                    <h4 class="ep-footer-heading">Legal & Policies</h4>
                    <ul class="ep-footer-links">
                        <li><a href="<?php echo esc_url(home_url('/terms-and-conditions')); ?>">Terms and Conditions</a></li>
                        <li><a href="<?php echo esc_url(home_url('/privacy-policy')); ?>">Privacy Policy</a></li>
                        <li><a href="<?php echo esc_url(home_url('/refund-and-cancellation')); ?>">Refund and Cancellation</a></li>
                        <li><a href="<?php echo esc_url(home_url('/organiser-terms')); ?>">Organiser Terms & Conditions</a></li>
                    </ul>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(255,255,255,0.04); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                        <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--ep-teal); font-weight: 700; margin-bottom: 0.25rem;">Payment Security</span>
                        <p style="font-size: 0.8rem; color: #94A3B8; margin: 0;">Verified Namibian Gateway. Visa, Mastercard, EFT & QR.</p>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="ep-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EventPass Namibia. All Rights Reserved. Regulated under the laws of the Republic of Namibia.</p>
                <div style="display: flex; gap: 1.5rem; align-items: center;">
                    <span style="color: #64748B;">Targeted for Namibia & Southern Africa</span>
                    <a href="#page" style="color: var(--ep-teal); font-weight: 600;">Back to top &uarr;</a>
                </div>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
