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

    console.log('EventPass Namibia Theme Script Initialized.');
});
