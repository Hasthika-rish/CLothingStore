// Main Application Scripts - Anjiana Store (PHP Edition)

// Theme Toggling Logic
const themeToggleBtns = document.querySelectorAll('.theme-toggle, #themeToggleBtn');
const iconSuns = document.querySelectorAll('.icon-sun');
const iconMoons = document.querySelectorAll('.icon-moon');

// Check local storage or system preference
const storedTheme = localStorage.getItem('theme');
const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

const updateIcons = (theme) => {
    iconSuns.forEach(sun => sun.style.display = (theme === 'dark') ? 'none' : 'block');
    iconMoons.forEach(moon => moon.style.display = (theme === 'dark') ? 'block' : 'none');
};

if (storedTheme === 'dark' || (!storedTheme && systemDark)) {
    document.documentElement.setAttribute('data-theme', 'dark');
    updateIcons('dark');
} else {
    updateIcons('light');
}

themeToggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
            updateIcons('light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            updateIcons('dark');
        }
    });
});

// Navbar Scroll Effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    const theme = document.documentElement.getAttribute('data-theme');
    const alphaBg = theme === 'dark' ? 'rgba(18, 18, 18, 0.95)' : 'rgba(255, 255, 255, 0.95)';
    const defaultAlphaBg = theme === 'dark' ? 'rgba(18, 18, 18, 0.85)' : 'rgba(255, 255, 255, 0.85)';
    
    if (window.scrollY > 50) {
        navbar.style.background = alphaBg;
        navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.08)';
    } else {
        navbar.style.background = defaultAlphaBg;
        navbar.style.boxShadow = 'none';
    }
});

// Global Toast Notification
let toastTimeout;
window.showToast = function(message, isError = false) {
    const toast = document.getElementById('toastNotification');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');

    if (!toast) return;

    if (isError) {
        toast.classList.add('error');
        if (toastIcon) toastIcon.textContent = '✕';
        if (toastTitle) toastTitle.textContent = 'Warning';
    } else {
        toast.classList.remove('error');
        if (toastIcon) toastIcon.textContent = '✓';
        if (toastTitle) toastTitle.textContent = 'Success';
    }

    if (toastMessage) toastMessage.textContent = message;
    toast.classList.add('show');

    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 3500);
};

// Search Overlay & Drawer Interactions
document.addEventListener('DOMContentLoaded', () => {
    // Search Overlay Injection
    if (!document.querySelector('.search-overlay')) {
        const searchHtml = `
          <div class="search-overlay" id="searchOverlay">
            <div class="search-container">
              <button class="close-search" id="closeSearchBtn" aria-label="Close Search">✕</button>
              <form class="search-form" action="products.php" method="GET">
                <input type="text" name="search" id="searchInput" placeholder="Search dresses, jackets, shirts..." autocomplete="off">
                <button type="submit" class="search-submit-btn" aria-label="Submit Search">
                  <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
              </form>
            </div>
          </div>
        `;
        document.body.insertAdjacentHTML('beforeend', searchHtml);
    }

    const searchOverlay = document.getElementById('searchOverlay');
    const closeSearch = document.getElementById('closeSearchBtn');
    const searchInput = document.getElementById('searchInput');
    const searchBtns = document.querySelectorAll('.search-btn, #searchToggleBtn');

    searchBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (searchOverlay) {
                searchOverlay.style.display = 'flex';
                searchOverlay.classList.add('active');
                setTimeout(() => searchInput && searchInput.focus(), 100);
            }
        });
    });

    if (closeSearch && searchOverlay) {
        closeSearch.addEventListener('click', () => {
            searchOverlay.classList.remove('active');
            searchOverlay.style.display = 'none';
        });
    }

    // Close on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchOverlay && searchOverlay.classList.contains('active')) {
            searchOverlay.classList.remove('active');
            searchOverlay.style.display = 'none';
        }
    });

    // Mobile Hamburger Menu Injection
    const navbar = document.querySelector('.navbar');
    if (navbar && !document.querySelector('.hamburger-btn')) {
        const container = navbar.querySelector('.container');
        if (container) {
            const hamburgerBtn = document.createElement('button');
            hamburgerBtn.className = 'hamburger-btn';
            hamburgerBtn.setAttribute('aria-label', 'Toggle Menu');
            hamburgerBtn.innerHTML = `
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            `;
            const navIcons = container.querySelector('.nav-icons');
            if (navIcons) {
                container.insertBefore(hamburgerBtn, navIcons);
            } else {
                container.appendChild(hamburgerBtn);
            }

            const drawerHtml = `
                <div class="drawer-overlay" id="drawerOverlay"></div>
                <div class="mobile-drawer" id="mobileDrawer">
                    <div class="drawer-header">
                        <div class="logo">Anjiana Store</div>
                        <button class="drawer-close" id="drawerClose">✕</button>
                    </div>
                    <ul class="drawer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Shop All</a></li>
                        <li><a href="products.php?category=Women">Women</a></li>
                        <li><a href="products.php?category=Men">Men</a></li>
                        <li><a href="products.php?category=Kids%20Section">Kids Section</a></li>
                        <li><a href="products.php?category=Other">Accessories & Other</a></li>
                        <li><a href="your-orders.php">Track Orders</a></li>
                    </ul>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', drawerHtml);

            const mobileDrawer = document.getElementById('mobileDrawer');
            const drawerOverlay = document.getElementById('drawerOverlay');
            const drawerClose = document.getElementById('drawerClose');

            const toggleDrawer = () => {
                hamburgerBtn.classList.toggle('active');
                mobileDrawer.classList.toggle('active');
                drawerOverlay.classList.toggle('active');
            };

            hamburgerBtn.addEventListener('click', toggleDrawer);
            drawerClose.addEventListener('click', toggleDrawer);
            drawerOverlay.addEventListener('click', toggleDrawer);
        }
    }

    // Admin Mobile Sidebar Toggle
    const adminSidebar = document.querySelector('.admin-sidebar');
    const adminLayout = document.querySelector('.admin-layout');
    if (adminLayout && adminSidebar && !document.querySelector('.admin-mobile-header')) {
        const mobileHeader = document.createElement('div');
        mobileHeader.className = 'admin-mobile-header';
        mobileHeader.innerHTML = `
            <button class="admin-sidebar-toggle" id="adminSidebarToggle" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">☰</button>
            <div class="admin-mobile-logo" style="font-weight:800; font-size:1.1rem;">ANJIANA ADMIN</div>
            <div style="width:24px;"></div>
        `;
        adminLayout.insertBefore(mobileHeader, adminLayout.firstChild);

        const overlay = document.createElement('div');
        overlay.className = 'admin-sidebar-overlay';
        overlay.id = 'adminSidebarOverlay';
        document.body.appendChild(overlay);

        const toggleBtn = document.getElementById('adminSidebarToggle');
        const toggleAdmin = () => {
            adminSidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };
        toggleBtn.addEventListener('click', toggleAdmin);
        overlay.addEventListener('click', toggleAdmin);
    }

    // Hero Slider Initialization
    const heroSlider = document.querySelector('.hero-slider');
    if (heroSlider) {
        const slides = heroSlider.querySelectorAll('.slide');
        const prevBtn = heroSlider.querySelector('.slider-arrow.prev');
        const nextBtn = heroSlider.querySelector('.slider-arrow.next');
        const dots = heroSlider.querySelectorAll('.dot');
        let currentSlideIdx = 0;
        let slideTimer;

        const showSlide = (idx) => {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            currentSlideIdx = (idx + slides.length) % slides.length;
            slides[currentSlideIdx].classList.add('active');
            if (dots[currentSlideIdx]) {
                dots[currentSlideIdx].classList.add('active');
            }
        };

        const nextSlide = () => showSlide(currentSlideIdx + 1);
        const prevSlide = () => showSlide(currentSlideIdx - 1);

        const resetTimer = () => {
            clearInterval(slideTimer);
            slideTimer = setInterval(nextSlide, 6000);
        };

        if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetTimer(); });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetTimer();
            });
        });

        resetTimer();
    }

    // Wishlist Functionality using LocalStorage
    function getWishlist() {
        try {
            const list = localStorage.getItem('anjiana_wishlist');
            return list ? JSON.parse(list) : [];
        } catch (e) {
            return [];
        }
    }

    function saveWishlist(list) {
        localStorage.setItem('anjiana_wishlist', JSON.stringify(list));
        updateWishlistBadges();
        updateHeartIconsState();
    }

    function toggleWishlistItem(id) {
        const list = getWishlist();
        const index = list.indexOf(parseInt(id));
        let added = false;

        if (index === -1) {
            list.push(parseInt(id));
            added = true;
            window.showToast('Piece added to wishlist! ❤️');
        } else {
            list.splice(index, 1);
            window.showToast('Removed from wishlist.');
        }

        saveWishlist(list);
        return added;
    }

    function updateWishlistBadges() {
        const list = getWishlist();
        const badges = document.querySelectorAll('.wishlist-badge, #navWishlistBadge');
        badges.forEach(badge => {
            badge.textContent = list.length;
            badge.style.display = list.length > 0 ? 'flex' : 'none';
        });
    }

    function updateHeartIconsState() {
        const list = getWishlist();
        document.querySelectorAll('.card-wishlist-btn').forEach(btn => {
            const id = parseInt(btn.getAttribute('data-id'));
            const svg = btn.querySelector('svg');
            if (list.includes(id)) {
                btn.classList.add('in-wishlist');
                if (svg) {
                    svg.style.fill = 'var(--accent-color)';
                    svg.style.stroke = 'var(--accent-color)';
                }
            } else {
                btn.classList.remove('in-wishlist');
                if (svg) {
                    svg.style.fill = 'none';
                    svg.style.stroke = 'currentColor';
                }
            }
        });
    }

    // Event Delegation for Wishlist Buttons
    document.addEventListener('click', (e) => {
        const cardBtn = e.target.closest('.card-wishlist-btn');
        if (cardBtn) {
            e.preventDefault();
            e.stopPropagation();
            const id = cardBtn.getAttribute('data-id');
            if (id) toggleWishlistItem(id);
        }
    });

    // Quick Add to Cart via AJAX (Smooth No-reload addition)
    document.querySelectorAll('.quick-add-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('.btn-quick-add, button[type="submit"]');
            if (!submitBtn || submitBtn.classList.contains('is-loading')) return;

            const textSpan = submitBtn.querySelector('.btn-quick-add-text');
            const iconSpan = submitBtn.querySelector('.btn-quick-add-icon');
            const originalText = textSpan ? textSpan.textContent : submitBtn.textContent;
            const originalIcon = iconSpan ? iconSpan.innerHTML : '';

            submitBtn.classList.add('is-loading');

            try {
                const formData = new FormData(form);
                const response = await fetch('ajax/cart.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    submitBtn.classList.remove('is-loading');
                    submitBtn.classList.add('is-success');
                    if (textSpan) textSpan.textContent = 'Added';
                    if (iconSpan) {
                        iconSpan.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                    }

                    window.showToast(data.message || 'Added to cart!');
                    const badges = document.querySelectorAll('.cart-badge, #navCartBadge');
                    badges.forEach(b => {
                        b.textContent = data.cart_count;
                        b.style.display = data.cart_count > 0 ? 'flex' : 'none';
                        b.style.animation = 'none';
                        b.offsetHeight; // trigger reflow
                        b.style.animation = 'badgePop 0.45s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    });

                    setTimeout(() => {
                        submitBtn.classList.remove('is-success');
                        if (textSpan) textSpan.textContent = originalText;
                        if (iconSpan) iconSpan.innerHTML = originalIcon;
                    }, 1800);
                } else {
                    submitBtn.classList.remove('is-loading');
                    window.showToast(data.message || 'Failed to add', true);
                }
            } catch (err) {
                // Fallback to regular form submission if AJAX error
                form.submit();
                return;
            }
        });
    });

    // ==========================================
    // Catalog Multi-Attribute Filter Interactive Logic
    // ==========================================
    function setupDualPriceSlider(prefix) {
        const minSlider = document.getElementById(`${prefix}MinPriceSlider`);
        const maxSlider = document.getElementById(`${prefix}MaxPriceSlider`);
        const trackFill = document.getElementById(`${prefix}TrackFill`);
        const minDisplay = document.getElementById(`${prefix}MinPriceDisplay`);
        const maxDisplay = document.getElementById(`${prefix}MaxPriceDisplay`);

        if (!minSlider || !maxSlider || !trackFill) return;

        const minBound = parseFloat(minSlider.min);
        const maxBound = parseFloat(maxSlider.max);
        const range = maxBound - minBound || 1;

        function updateSlider() {
            let minVal = parseFloat(minSlider.value);
            let maxVal = parseFloat(maxSlider.value);

            if (minVal > maxVal) {
                if (this === minSlider) {
                    minSlider.value = maxVal;
                    minVal = maxVal;
                } else {
                    maxSlider.value = minVal;
                    maxVal = minVal;
                }
            }

            const leftPct = ((minVal - minBound) / range) * 100;
            const rightPct = ((maxVal - minBound) / range) * 100;

            trackFill.style.left = `${leftPct}%`;
            trackFill.style.width = `${rightPct - leftPct}%`;

            if (minDisplay) minDisplay.textContent = `Rs. ${minVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            if (maxDisplay) maxDisplay.textContent = `Rs. ${maxVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }

        minSlider.addEventListener('input', updateSlider);
        maxSlider.addEventListener('input', updateSlider);
        updateSlider();
    }

    setupDualPriceSlider('desktop');
    setupDualPriceSlider('mobile');

    // Mobile Filter Drawer Handlers
    const mobileDrawerTrigger = document.getElementById('mobileFilterTrigger');
    const mobileDrawerOverlay = document.getElementById('mobileDrawerOverlay');
    const mobileDrawerCloseBtn = document.getElementById('mobileDrawerCloseBtn');

    if (mobileDrawerTrigger && mobileDrawerOverlay) {
        mobileDrawerTrigger.addEventListener('click', () => {
            mobileDrawerOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        const closeDrawer = () => {
            mobileDrawerOverlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        if (mobileDrawerCloseBtn) {
            mobileDrawerCloseBtn.addEventListener('click', closeDrawer);
        }

        mobileDrawerOverlay.addEventListener('click', (e) => {
            if (e.target === mobileDrawerOverlay) {
                closeDrawer();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileDrawerOverlay.classList.contains('active')) {
                closeDrawer();
            }
        });
    }

    updateWishlistBadges();
    updateHeartIconsState();
});

