/**
 * EventPass Namibia Theme Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile navigation toggle
    const mobileToggle = document.getElementById('epMobileToggle');
    const mainNav = document.getElementById('site-navigation');

    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', function () {
            mainNav.classList.toggle('is-open');
            if (mainNav.classList.contains('is-open')) {
                mainNav.style.display = 'block';
                mainNav.style.position = 'absolute';
                mainNav.style.top = '80px';
                mainNav.style.left = '0';
                mainNav.style.right = '0';
                mainNav.style.background = '#ffffff';
                mainNav.style.padding = '1.5rem';
                mainNav.style.boxShadow = 'var(--ep-shadow-lg)';
                mainNav.style.borderBottom = '1px solid var(--ep-slate-200)';
                const ul = mainNav.querySelector('.ep-nav-menu');
                if (ul) {
                    ul.style.display = 'flex';
                    ul.style.flexDirection = 'column';
                    ul.style.gap = '1rem';
                }
            } else {
                mainNav.style.display = 'none';
            }
        });
    }

    // FAQ Accordion auto-collapse
    const detailsElements = document.querySelectorAll('details');
    detailsElements.forEach(detail => {
        detail.addEventListener('toggle', () => {
            if (detail.open) {
                detailsElements.forEach(otherDetail => {
                    if (otherDetail !== detail && otherDetail.open) {
                        otherDetail.open = false;
                    }
                });
            }
        });
    });

    // Lightweight Scroll Reveal Animations (IntersectionObserver)
    initScrollAnimations();

    console.log('EventPass Namibia Theme Script Initialized.');
});

/**
 * Initialize performant, one-time scroll reveal animations across all pages.
 * Respects prefers-reduced-motion and cleans up reveal classes after transition
 * so native hover micro-interactions remain completely unaffected.
 */
function initScrollAnimations() {
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        return;
    }

    const selectorGroups = [
        '.ep-section h2, .ep-section > .container > div:first-child',
        '.ep-event-card, .ep-cat-card, .ep-hiw-step-card, .ep-pass-card, .ep-calc-card, .ep-filters-card, .ep-dashboard-sidebar',
        '.ep-contact-grid > div, .ep-organiser-reg-grid > div, .ep-about-story-grid > div, .ep-single-event-grid > div, .ep-checkout-grid > div, .ep-cta-split-grid > div, .ep-cust-dash-grid > div',
        '.ep-section [style*="grid-template-columns"] > div, .container [style*="grid-template-columns"] > div',
        '.ep-footer-col',
        'section:not(.ep-hero-section):not(.ep-hiw-hero) img'
    ];

    const candidateSet = new Set();
    selectorGroups.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
            // Skip header, modals, hidden drawers/tabs, and hero background collage
            if (
                el.closest('header, #masthead, .ep-top-bar, .ep-hero-bg-collage, [id*="Modal"], .ep-mobile-drawer') ||
                el.offsetParent === null
            ) {
                return;
            }
            // Skip if an ancestor is already in the reveal set to avoid double-animating nested blocks
            let parent = el.parentElement;
            let hasAnimatedAncestor = false;
            while (parent) {
                if (candidateSet.has(parent)) {
                    hasAnimatedAncestor = true;
                    break;
                }
                parent = parent.parentElement;
            }
            if (!hasAnimatedAncestor) {
                candidateSet.add(el);
            }
        });
    });

    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    const targetsToObserve = [];

    candidateSet.forEach(el => {
        const rect = el.getBoundingClientRect();
        // Keep above-the-fold elements immediately visible on initial load
        if (rect.top < viewportHeight * 0.92) {
            return;
        }

        // Apply subtle stagger for sibling cards in a grid
        const parent = el.parentElement;
        if (parent) {
            const siblings = Array.from(parent.children).filter(child => candidateSet.has(child));
            const idx = siblings.indexOf(el);
            if (idx > 0) {
                el.style.transitionDelay = Math.min(idx * 75, 280) + 'ms';
            }
        }

        if (el.matches('.ep-event-card, .ep-cat-card, .ep-pass-card')) {
            el.classList.add('ep-reveal', 'ep-reveal-scale');
        } else if (el.tagName === 'IMG') {
            el.classList.add('ep-reveal', 'ep-reveal-fade');
        } else {
            el.classList.add('ep-reveal');
        }

        targetsToObserve.push(el);
    });

    if (!targetsToObserve.length) {
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) {
                return;
            }
            const target = entry.target;
            target.classList.add('ep-is-visible');
            obs.unobserve(target);

            // Clean up reveal classes after animation finishes so :hover transforms work natively
            const delayMs = parseFloat(target.style.transitionDelay || '0') || 0;
            setTimeout(() => {
                target.classList.remove('ep-reveal', 'ep-reveal-scale', 'ep-reveal-fade', 'ep-is-visible');
                target.style.transitionDelay = '';
            }, 680 + delayMs);
        });
    }, {
        root: null,
        rootMargin: '0px 0px -35px 0px',
        threshold: 0.05
    });

    targetsToObserve.forEach(el => observer.observe(el));
}
