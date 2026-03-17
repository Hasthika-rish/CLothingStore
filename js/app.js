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
    
    if(window.scrollY > 50) {
        navbar.style.background = alphaBg;
        navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.05)';
    } else {
        navbar.style.background = defaultAlphaBg;
        navbar.style.boxShadow = 'none';
        navbar.style.borderBottom = '1px solid rgba(0,0,0,0.05)';
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
});
