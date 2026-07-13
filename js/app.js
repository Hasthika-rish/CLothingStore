// Main App Scripts

// Theme Toggling Logic
const themeToggleBtn = document.querySelector('.theme-toggle');
const iconSun = document.querySelector('.icon-sun');
const iconMoon = document.querySelector('.icon-moon');

// Check local storage or system preference
const storedTheme = localStorage.getItem('theme');
const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

const updateIcons = (theme) => {
    if(iconSun && iconMoon) {
        if (theme === 'dark') {
            iconSun.style.display = 'none';
            iconMoon.style.display = 'block';
        } else {
            iconSun.style.display = 'block';
            iconMoon.style.display = 'none';
        }
    }
}

if (storedTheme === 'dark' || (!storedTheme && systemDark)) {
    document.documentElement.setAttribute('data-theme', 'dark');
    updateIcons('dark');
} else {
    updateIcons('light');
}

if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
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
}

// Navbar Scroll Effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    // Ensure navbar scroll effect properly accounts for the theme background variable
    const theme = document.documentElement.getAttribute('data-theme');
    const alphaBg = theme === 'dark' ? 'rgba(18, 18, 18, 0.95)' : 'rgba(255, 255, 255, 0.95)';
    const defaultAlphaBg = theme === 'dark' ? 'rgba(18, 18, 18, 0.85)' : 'rgba(255, 255, 255, 0.85)';
    
    if (navbar) {
        if(window.scrollY > 50) {
            navbar.style.background = alphaBg;
            navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.05)';
        } else {
            navbar.style.background = defaultAlphaBg;
            navbar.style.boxShadow = 'none';
            navbar.style.borderBottom = '1px solid rgba(0,0,0,0.05)';
        }
    }
});

// Size selection logic
const sizes = document.querySelectorAll('.size-opt');
if (sizes.length > 0) {
    sizes.forEach(size => {
        size.addEventListener('click', () => {
            sizes.forEach(s => s.classList.remove('active'));
            size.classList.add('active');
        });
    });
}

// Search Overlay Logic
document.addEventListener('DOMContentLoaded', () => {
    // Inject the Search Overlay into the DOM globally
    const searchHtml = `
      <div class="search-overlay">
        <div class="search-container">
          <button class="close-search" aria-label="Close Search">✕</button>
          <form class="search-form" onsubmit="event.preventDefault(); window.location.href='products.html?q=' + encodeURIComponent(document.getElementById('searchInput').value);">
            <input type="text" id="searchInput" placeholder="Search for beautiful things..." autocomplete="off">
            <button type="submit" class="search-submit-btn" aria-label="Submit Search">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </button>
          </form>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', searchHtml);
    
    // Inject the Toast Notification into the DOM globally
    const toastHtml = `
      <div id="toastNotification" class="toast-notification">
        <div class="toast-icon" id="toastIcon">✓</div>
        <div class="toast-message-body">
          <h4 class="toast-title" id="toastTitle">Success</h4>
          <p class="toast-desc" id="toastMessage"></p>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', toastHtml);

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
        }, 3000);
    };
    
    const searchOverlay = document.querySelector('.search-overlay');
    const closeSearch = document.querySelector('.close-search');
    const searchInput = document.getElementById('searchInput');
    const searchBtns = document.querySelectorAll('.search-btn');

    searchBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            searchOverlay.classList.add('active');
            // Slight delay to allow display to manifest before focusing
            setTimeout(() => searchInput.focus(), 100);
        });
    });

    closeSearch.addEventListener('click', () => {
        searchOverlay.classList.remove('active');
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
            searchOverlay.classList.remove('active');
        }
    });

    // Admin return button logic
    if (sessionStorage.getItem('isAdminSession') === 'true' && !window.location.pathname.includes('/admin/')) {
        const adminBtn = document.createElement('a');
        adminBtn.href = 'admin/dashboard.html';
        adminBtn.innerText = '⚙️ Back to Admin';
        adminBtn.style.position = 'fixed';
        adminBtn.style.bottom = '30px';
        adminBtn.style.left = '30px';
        adminBtn.style.background = 'var(--primary-color)';
        adminBtn.style.color = 'var(--secondary-color)';
        adminBtn.style.padding = '12px 20px';
        adminBtn.style.borderRadius = '30px';
        adminBtn.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
        adminBtn.style.zIndex = '9999';
        adminBtn.style.textDecoration = 'none';
        adminBtn.style.fontWeight = '600';
        adminBtn.style.fontSize = '0.9rem';
        adminBtn.style.display = 'flex';
        adminBtn.style.alignItems = 'center';
        adminBtn.style.gap = '8px';
        adminBtn.style.border = '1px solid rgba(255,255,255,0.2)';
        adminBtn.style.transition = 'all var(--transition-fast)';
        
        // Hover effect
        adminBtn.addEventListener('mouseenter', () => {
            adminBtn.style.transform = 'translateY(-3px)';
            adminBtn.style.boxShadow = '0 15px 30px rgba(0,0,0,0.4)';
            adminBtn.style.background = 'var(--accent-color)';
            adminBtn.style.color = '#FFFFFF';
        });
        adminBtn.addEventListener('mouseleave', () => {
            adminBtn.style.transform = 'translateY(0)';
            adminBtn.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
            adminBtn.style.background = 'var(--primary-color)';
            adminBtn.style.color = 'var(--secondary-color)';
        });

        document.body.appendChild(adminBtn);
    }

    // 1. Storefront Mobile Navigation Drawer Injection & Logic
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        // Inject Hamburger Button if not exists
        const container = navbar.querySelector('.container');
        if (container && !container.querySelector('.hamburger-btn')) {
            const hamburgerBtn = document.createElement('button');
            hamburgerBtn.className = 'hamburger-btn';
            hamburgerBtn.setAttribute('aria-label', 'Toggle Menu');
            hamburgerBtn.innerHTML = `
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            `;
            // Insert before the nav-icons or at the end of container
            const navIcons = container.querySelector('.nav-icons');
            if (navIcons) {
                container.insertBefore(hamburgerBtn, navIcons);
            } else {
                container.appendChild(hamburgerBtn);
            }

            // Inject Drawer Markup
            const drawerHtml = `
                <div class="drawer-overlay" id="drawerOverlay"></div>
                <div class="mobile-drawer" id="mobileDrawer">
                    <div class="drawer-header">
                        <div class="logo">Sage Anjiana</div>
                        <button class="drawer-close" id="drawerClose">✕</button>
                    </div>
                    <ul class="drawer-links">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="products.html">Shop</a></li>
                        <li><a href="products.html?category=women">Women</a></li>
                        <li><a href="products.html?category=men">Men</a></li>
                    </ul>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', drawerHtml);

            // Drawer Toggle Logic
            const mobileDrawer = document.getElementById('mobileDrawer');
            const drawerOverlay = document.getElementById('drawerOverlay');
            const drawerClose = document.getElementById('drawerClose');

            const toggleDrawer = () => {
                hamburgerBtn.classList.toggle('active');
                mobileDrawer.classList.toggle('active');
                drawerOverlay.classList.toggle('active');
                if (mobileDrawer.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            };

            hamburgerBtn.addEventListener('click', toggleDrawer);
            drawerClose.addEventListener('click', toggleDrawer);
            drawerOverlay.addEventListener('click', toggleDrawer);

            // Close drawer when clicking a link
            const drawerLinks = mobileDrawer.querySelectorAll('.drawer-links a');
            drawerLinks.forEach(link => {
                link.addEventListener('click', () => {
                    hamburgerBtn.classList.remove('active');
                    mobileDrawer.classList.remove('active');
                    drawerOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });
        }
    }

    // 2. Admin Sidebar Slide-out Drawer Logic
    const adminLayout = document.querySelector('.admin-layout');
    if (adminLayout) {
        const adminSidebar = document.querySelector('.admin-sidebar');
        if (adminSidebar) {
            // Create Top Bar for Mobile if it doesn't exist
            if (!document.querySelector('.admin-mobile-header')) {
                const mobileHeader = document.createElement('div');
                mobileHeader.className = 'admin-mobile-header';
                mobileHeader.innerHTML = `
                    <button class="admin-sidebar-toggle" id="adminSidebarToggle">☰</button>
                    <div class="admin-mobile-logo">Sage Anjiana Admin</div>
                    <div style="width: 24px;"></div>
                `;
                // Insert before the first child of adminLayout
                adminLayout.insertBefore(mobileHeader, adminLayout.firstChild);

                // Create Overlay Backdrop
                const overlay = document.createElement('div');
                overlay.className = 'admin-sidebar-overlay';
                overlay.id = 'adminSidebarOverlay';
                document.body.appendChild(overlay);

                const toggleBtn = document.getElementById('adminSidebarToggle');
                
                const toggleAdminSidebar = () => {
                    adminSidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                    if (adminSidebar.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                };

                toggleBtn.addEventListener('click', toggleAdminSidebar);
                overlay.addEventListener('click', toggleAdminSidebar);

                // Close sidebar when clicking links inside it
                const sidebarLinks = adminSidebar.querySelectorAll('.admin-nav a');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        adminSidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                });
            }
        }
    }

    // ==========================================
    // Hero Slider Initialization
    // ==========================================
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

        const nextSlide = () => {
            showSlide(currentSlideIdx + 1);
        };

        const prevSlide = () => {
            showSlide(currentSlideIdx - 1);
        };

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

    // ==========================================
    // Wishlist Functionality
    // ==========================================
    function getWishlist() {
        try {
            const list = localStorage.getItem('wishlist');
            return list ? JSON.parse(list) : [];
        } catch (e) {
            return [];
        }
    }

    function saveWishlist(list) {
        localStorage.setItem('wishlist', JSON.stringify(list));
        updateWishlistBadges();
    }

    function toggleWishlistItem(id) {
        const list = getWishlist();
        const index = list.indexOf(id);
        let added = false;
        if (index === -1) {
            list.push(id);
            added = true;
            if (window.showToast) {
                window.showToast('Product added to wishlist! ❤️');
            }
        } else {
            list.splice(index, 1);
            if (window.showToast) {
                window.showToast('Product removed from wishlist.');
            }
        }
        saveWishlist(list);
        updateHeartIconsState();
        
        // Reactively reload the wishlist products grid if we are on wishlist.html
        if (window.location.pathname.includes('wishlist.html') && window.loadWishlistProducts) {
            window.loadWishlistProducts();
        }
        return added;
    }

    function updateWishlistBadges() {
        const list = getWishlist();
        const badges = document.querySelectorAll('.wishlist-badge');
        badges.forEach(badge => {
            badge.textContent = list.length;
            if (list.length > 0) {
                badge.style.display = 'flex';
                badge.style.alignItems = 'center';
                badge.style.justifyContent = 'center';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    function updateHeartIconsState() {
        const list = getWishlist();
        
        // Update wishlist button icons in product grid
        document.querySelectorAll('.card-wishlist-btn').forEach(btn => {
            const id = btn.getAttribute('data-id');
            const heartSvg = btn.querySelector('svg');
            if (list.includes(id)) {
                btn.classList.add('in-wishlist');
                if (heartSvg) {
                    heartSvg.style.fill = 'var(--accent-color)';
                    heartSvg.style.stroke = 'var(--accent-color)';
                }
            } else {
                btn.classList.remove('in-wishlist');
                if (heartSvg) {
                    heartSvg.style.fill = 'none';
                    heartSvg.style.stroke = 'currentColor';
                }
            }
        });

        // Update single product details page wishlist button
        const detailWishlistBtn = document.getElementById('wishlistBtn');
        if (detailWishlistBtn) {
            const id = new URLSearchParams(window.location.search).get('id');
            const heartSvg = detailWishlistBtn.querySelector('svg');
            if (list.includes(id)) {
                detailWishlistBtn.classList.add('in-wishlist');
                detailWishlistBtn.style.background = 'rgba(159, 93, 68, 0.1)';
                detailWishlistBtn.style.borderColor = 'var(--accent-color)';
                if (heartSvg) {
                    heartSvg.style.fill = 'var(--accent-color)';
                    heartSvg.style.stroke = 'var(--accent-color)';
                }
            } else {
                detailWishlistBtn.classList.remove('in-wishlist');
                detailWishlistBtn.style.background = 'transparent';
                detailWishlistBtn.style.borderColor = 'var(--border-color)';
                if (heartSvg) {
                    heartSvg.style.fill = 'none';
                    heartSvg.style.stroke = 'currentColor';
                }
            }
        }
    }

    // Expose helpers globally so modules can call them
    window.getWishlist = getWishlist;
    window.toggleWishlistItem = toggleWishlistItem;
    window.updateWishlistBadges = updateWishlistBadges;
    window.updateHeartIconsState = updateHeartIconsState;

    // Attach click events using event delegation
    document.addEventListener('click', (e) => {
        const cardBtn = e.target.closest('.card-wishlist-btn');
        if (cardBtn) {
            e.preventDefault();
            e.stopPropagation();
            const id = cardBtn.getAttribute('data-id');
            toggleWishlistItem(id);
            return;
        }

        const detailBtn = e.target.closest('#wishlistBtn');
        if (detailBtn) {
            e.preventDefault();
            const id = new URLSearchParams(window.location.search).get('id');
            if (id) {
                toggleWishlistItem(id);
            }
        }
    });

    // Initial load
    updateWishlistBadges();
    updateHeartIconsState();
});
