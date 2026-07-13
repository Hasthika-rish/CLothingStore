import { db, collection, getDocs, doc, getDoc, addDoc, getCachedSettings } from './firebase-config.js';
import { query, where } from "https://www.gstatic.com/firebasejs/10.7.2/firebase-firestore.js";
import { addToCart } from './cart.js';

// DOM Elements
const productGrid = document.getElementById('productGrid');
const productDetailsContainer = document.getElementById('productDetails');

let activeCurrency = 'Rs.';

// Async helper to initialize currency symbol from Firestore settings
async function initCurrency() {
    try {
        const settings = await getCachedSettings();
        activeCurrency = settings.currency || 'Rs.';
    } catch (e) {
        console.error("Error setting currency: ", e);
    }
}

// Helper to format currency
const formatPrice = (price) => `${activeCurrency}${parseFloat(price).toFixed(2)}`;

// Helper to generate a product card HTML
const createProductCard = (id, data) => {
    const price = parseFloat(data.price);
    const discount = parseFloat(data.discount || 0);
    let priceHTML = '';
    let badgeHTML = '';

    if (discount > 0) {
        const discountedPrice = price * (1 - discount / 100);
        priceHTML = `<span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.9rem; margin-right: 0.5rem;">${activeCurrency}${price.toFixed(2)}</span><span style="font-weight: 600; color: var(--accent-color); font-size: 1.1rem;">${activeCurrency}${discountedPrice.toFixed(2)}</span>`;
        badgeHTML = `<span style="position: absolute; top: 12px; left: 12px; background: var(--accent-color); color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; z-index: 2; box-shadow: 0 4px 10px rgba(0,0,0,0.15); font-family: 'Outfit', sans-serif;">-${discount}%</span>`;
    } else {
        priceHTML = `<span style="font-weight: 600; font-size: 1.1rem;">${activeCurrency}${price.toFixed(2)}</span>`;
    }

    return `
    <article class="product-card" style="position: relative;">
        <button class="card-wishlist-btn" data-id="${id}" aria-label="Add to Wishlist">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </button>
        <a href="product-details.html?id=${id}" class="product-img-wrap" style="position: relative; display: block;">
            ${badgeHTML}
            <img src="${data.imageUrl || 'images/placeholder.png'}" alt="${data.name}" class="product-img">
            <div class="product-add-overlay">
                <button class="btn btn-accent quick-add-btn" data-id="${id}" style="width: 100%;">Quick Add 🛒</button>
            </div>
        </a>
        <div class="product-info">
            <p class="product-category">${data.category}</p>
            <h3 class="product-title"><a href="product-details.html?id=${id}">${data.name}</a></h3>
            <p class="product-price">${priceHTML}</p>
        </div>
    </article>
    `;
};

// Fetch and render all products (for products.html and index.html)
export async function loadProducts(limitCount = null, categoryFilter = null, tagFilter = null, searchFilter = null) {
    if (!productGrid) return;

    productGrid.innerHTML = '<div style="text-align:center; padding: 2rem; color: #999;">Loading products...</div>';

    await initCurrency();

    try {
        const querySnapshot = await getDocs(collection(db, "products"));
        productGrid.innerHTML = '';

        let productsHTML = '';
        let count = 0;

        querySnapshot.forEach((docSnap) => {
            const data = docSnap.data();

            // Filter by category if specified
            if (categoryFilter && !data.category.toLowerCase().includes(categoryFilter.toLowerCase())) {
                return;
            }

            // Filter by tag if specified
            if (tagFilter && !data.category.toLowerCase().includes(tagFilter.toLowerCase())) {
                return;
            }

            // Filter by search query if specified
            if (searchFilter) {
                const query = searchFilter.toLowerCase();
                const matchesName = data.name && data.name.toLowerCase().includes(query);
                const matchesCategory = data.category && data.category.toLowerCase().includes(query);
                const matchesDesc = data.description && data.description.toLowerCase().includes(query);
                if (!matchesName && !matchesCategory && !matchesDesc) {
                    return;
                }
            }

            // Enforce limit if specified
            if (limitCount && count >= limitCount) return;

            productsHTML += createProductCard(docSnap.id, data);
            count++;
        });

        if (productsHTML === '') {
            productGrid.innerHTML = '<div style="text-align:center; padding: 2rem; color: #999;">No products found.</div>';
        } else {
            productGrid.innerHTML = productsHTML;
            // Re-attach quick add listeners after rendering
            attachQuickAddListeners();
            if (window.updateHeartIconsState) window.updateHeartIconsState();
        }

        // Dynamically update the main <h1> header and document title in products.html based on active filters
        const pageTitleEl = document.querySelector('main h1');
        if (pageTitleEl) {
            if (categoryFilter && tagFilter) {
                const displayCategory = categoryFilter.charAt(0).toUpperCase() + categoryFilter.slice(1).toLowerCase();
                const displayTag = tagFilter.charAt(0).toUpperCase() + tagFilter.slice(1).toLowerCase();
                pageTitleEl.textContent = `${displayCategory}'s ${displayTag}`;
            } else if (categoryFilter) {
                const displayCategory = categoryFilter.charAt(0).toUpperCase() + categoryFilter.slice(1).toLowerCase();
                pageTitleEl.textContent = `${displayCategory}'s Collection`;
            } else if (tagFilter) {
                const displayTag = tagFilter.charAt(0).toUpperCase() + tagFilter.slice(1).toLowerCase();
                pageTitleEl.textContent = `${displayTag} Collection`;
            } else if (searchFilter) {
                pageTitleEl.textContent = `Search Results for "${searchFilter}"`;
            } else {
                pageTitleEl.textContent = "All Products";
            }
        }

        if (categoryFilter || tagFilter || searchFilter) {
            let docTitle = '';
            if (categoryFilter && tagFilter) {
                docTitle = `${categoryFilter.toUpperCase()} ${tagFilter.toUpperCase()}`;
            } else if (categoryFilter) {
                docTitle = `${categoryFilter.toUpperCase()}'S COLLECTION`;
            } else if (tagFilter) {
                docTitle = `${tagFilter.toUpperCase()} COLLECTION`;
            } else if (searchFilter) {
                docTitle = `SEARCH: ${searchFilter}`;
            }
            document.title = `${docTitle} | Sage Anjiana`;
        }

    } catch (error) {
        console.error("Error loading products: ", error);
        productGrid.innerHTML = '<div style="text-align:center; padding: 2rem; color: red;">Failed to load products.</div>';
    }
}

// Fetch and render a single product (for product-details.html)
export async function loadSingleProduct() {
    if (!productDetailsContainer) return;

    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    if (!productId) {
        productDetailsContainer.innerHTML = '<h2>Product not found.</h2>';
        return;
    }

    await initCurrency();

    try {
        const docRef = doc(db, "products", productId);
        const docSnap = await getDoc(docRef);

        if (docSnap.exists()) {
            const data = docSnap.data();

            // Generate sizes HTML
            let sizesHTML = '';
            if (data.sizes && data.sizes.length > 0) {
                sizesHTML = data.sizes.map(size => {
                    const trimmedSize = size ? size.trim() : '';
                    if (trimmedSize === '') return ''; // Prevent empty bubbles

                    let isOutOfStock = false;
                    if (data.sizeStock && data.sizeStock[trimmedSize] !== undefined) {
                        isOutOfStock = data.sizeStock[trimmedSize] <= 0;
                    } else {
                        isOutOfStock = data.stock <= 0;
                    }

                    if (isOutOfStock) {
                        return `<button class="size-opt out-of-stock" title="${trimmedSize} is out of stock">${trimmedSize}</button>`;
                    } else {
                        return `<button class="size-opt">${trimmedSize}</button>`;
                    }
                }).join('');
                if (sizesHTML === '') {
                    sizesHTML = '<span style="color:#999;">No sizes available</span>';
                }
            } else {
                sizesHTML = '<span style="color:#999;">No sizes specified</span>';
            }

            // Generate colors HTML
            let colorsHTML = '';
            if (data.colors && data.colors.length > 0) {
                colorsHTML = data.colors.map(color => {
                    const trimmedColor = color ? color.trim() : '';
                    if (trimmedColor === '') return '';
                    return `<button class="color-opt" data-color="${trimmedColor}" style="background-color: ${trimmedColor};" title="${trimmedColor}"></button>`;
                }).join('');
            }

            const inStock = data.stock > 0;
            const stockStatus = inStock
                ? `<span style="color: var(--success-color); font-weight: 500;">In Stock (${data.stock} available)</span>`
                : `<span style="color: var(--accent-color); font-weight: 500;">Out of Stock</span>`;

            const price = parseFloat(data.price);
            const discount = parseFloat(data.discount || 0);
            let priceHTML = '';
            let sellingPrice = price;
            if (discount > 0) {
                sellingPrice = price * (1 - discount / 100);
                priceHTML = `
                    <div style="display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 1rem;">
                        <span style="text-decoration: line-through; color: var(--text-muted); font-size: 1.25rem;">${activeCurrency}${price.toFixed(2)}</span>
                        <span style="font-weight: 700; color: var(--accent-color); font-size: 1.8rem;">${activeCurrency}${sellingPrice.toFixed(2)}</span>
                        <span style="background: rgba(159, 93, 68, 0.15); color: var(--accent-color); padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">SAVE ${discount}%</span>
                    </div>
                `;
            } else {
                priceHTML = `<p class="product-price-lg" style="font-size: 1.8rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem;">${activeCurrency}${price.toFixed(2)}</p>`;
            }

            productDetailsContainer.innerHTML = `
                <div class="product-gallery">
                    <img src="${data.imageUrl || 'images/placeholder.png'}" alt="${data.name}" class="main-image">
                </div>
                <div class="product-info-detailed">
                    <div class="product-meta">
                        <span class="category-tag">${data.category}</span>
                        ${stockStatus}
                    </div>
                    <h1 class="product-title-lg">${data.name}</h1>
                    ${priceHTML}
                    
                    <div class="product-desc">
                        <p>${data.description || 'No description available.'}</p>
                    </div>
 
                    <div class="product-options">
                        ${data.sizes && data.sizes.length > 0 ? `
                        <div class="option-group">
                            <label>Size</label>
                            <div class="size-selector">
                                ${sizesHTML}
                            </div>
                        </div>
                        ` : ''}
                        
                        ${colorsHTML ? `
                        <div class="option-group">
                            <label>Color</label>
                            <div class="color-options">
                                ${colorsHTML}
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="option-group">
                            <label>Quantity</label>
                            <div class="qty-selector">
                                <button class="qty-btn" id="qtyMinus">-</button>
                                <input type="number" id="qtyInput" value="1" min="1" max="${data.stock}" style="width: 50px; text-align: center; border: none;">
                                <button class="qty-btn" id="qtyPlus">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="product-actions" style="margin-top: 2rem; display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; gap: 1rem; width: 100%;">
                            <button id="addToCartBtn" class="btn btn-primary" style="flex: 1; padding: 16px; font-size: 1.1rem;" ${!inStock ? 'disabled' : ''}>
                                ${inStock ? 'Add to Cart - ' + formatPrice(sellingPrice) : 'Out of Stock'}
                            </button>
                            <button id="wishlistBtn" class="btn btn-secondary" style="width: 56px; padding: 16px; display: flex; align-items: center; justify-content: center;" title="Add to Wishlist">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
                        </div>
                        <button id="buyNowBtn" class="btn btn-accent" style="width: 100%; padding: 16px; font-size: 1.1rem;" ${!inStock ? 'disabled' : ''}>
                            ${inStock ? 'Buy Now' : 'Out of Stock'}
                        </button>
                    </div>
                </div>
            `;

            // Attach event listeners for dynamic elements
            attachProductDetailListeners(docSnap.id, data);

            // Load product reviews section dynamically
            loadProductReviews(docSnap.id, data.name);

        } else {
            productDetailsContainer.innerHTML = '<h2>Product not found.</h2>';
        }
    } catch (error) {
        console.error("Error loading product details: ", error);
        productDetailsContainer.innerHTML = '<h2 style="color: red;">Failed to load product details.</h2>';
    }
}

function attachQuickAddListeners() {
    document.querySelectorAll('.quick-add-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const productId = e.target.getAttribute('data-id');
            // Simplified quick add - ideally you'd have a cart utility
            alert('Quick add for ' + productId + ' not fully implemented. Please view details.');
        });
    });
}

function attachProductDetailListeners(id, data) {
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const qtyInput = document.getElementById('qtyInput');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');

    const getAvailableStock = (size, color) => {
        if (size && color) {
            const key = `${size}_${color}`;
            if (data.sizeColorStock && data.sizeColorStock[key] !== undefined) {
                return data.sizeColorStock[key];
            }
        } else if (size) {
            if (data.sizeStock && data.sizeStock[size] !== undefined) {
                return data.sizeStock[size];
            }
        } else if (color) {
            const key = `N/A_${color}`;
            if (data.sizeColorStock && data.sizeColorStock[key] !== undefined) {
                return data.sizeColorStock[key];
            }
        }
        return data.stock !== undefined ? data.stock : 0;
    };

    const updateStockSelectionDisplay = () => {
        const activeSizeOpt = document.querySelector('.size-opt.active');
        const activeColorOpt = document.querySelector('.color-opt.active');
        const selectedSize = activeSizeOpt ? activeSizeOpt.innerText.trim() : null;
        const selectedColor = activeColorOpt ? activeColorOpt.getAttribute('data-color') : null;

        // Update size bubbles out-of-stock state based on selected color
        if (selectedColor && data.sizeColorStock) {
            document.querySelectorAll('.size-opt').forEach(sizeOpt => {
                const sz = sizeOpt.innerText.trim();
                const key = `${sz}_${selectedColor}`;
                const stockVal = data.sizeColorStock[key] !== undefined ? data.sizeColorStock[key] : (data.sizeStock && data.sizeStock[sz] !== undefined ? data.sizeStock[sz] : data.stock);
                if (stockVal <= 0) {
                    sizeOpt.classList.add('out-of-stock');
                    if (sizeOpt.classList.contains('active')) {
                        sizeOpt.classList.remove('active');
                    }
                } else {
                    sizeOpt.classList.remove('out-of-stock');
                }
            });
        } else {
            // Fallback to sizeStock or global stock
            document.querySelectorAll('.size-opt').forEach(sizeOpt => {
                const sz = sizeOpt.innerText.trim();
                const stockVal = data.sizeStock && data.sizeStock[sz] !== undefined ? data.sizeStock[sz] : data.stock;
                if (stockVal <= 0) {
                    sizeOpt.classList.add('out-of-stock');
                } else {
                    sizeOpt.classList.remove('out-of-stock');
                }
            });
        }

        // Update color bubbles out-of-stock state based on selected size
        if (selectedSize && data.sizeColorStock) {
            document.querySelectorAll('.color-opt').forEach(colorOpt => {
                const col = colorOpt.getAttribute('data-color');
                const key = `${selectedSize}_${col}`;
                const stockVal = data.sizeColorStock[key] !== undefined ? data.sizeColorStock[key] : data.stock;
                if (stockVal <= 0) {
                    colorOpt.classList.add('out-of-stock');
                    if (colorOpt.classList.contains('active')) {
                        colorOpt.classList.remove('active');
                    }
                } else {
                    colorOpt.classList.remove('out-of-stock');
                }
            });
        } else {
            document.querySelectorAll('.color-opt').forEach(colorOpt => {
                const col = colorOpt.getAttribute('data-color');
                let colorHasStock = false;
                if (data.sizes && data.sizes.length > 0) {
                    data.sizes.forEach(sz => {
                        const key = `${sz}_${col}`;
                        if (data.sizeColorStock && data.sizeColorStock[key] > 0) {
                            colorHasStock = true;
                        }
                    });
                } else {
                    const key = `N/A_${col}`;
                    if (data.sizeColorStock && data.sizeColorStock[key] > 0) {
                        colorHasStock = true;
                    } else if (!data.sizeColorStock) {
                        colorHasStock = data.stock > 0;
                    }
                }
                if (!colorHasStock) {
                    colorOpt.classList.add('out-of-stock');
                } else {
                    colorOpt.classList.remove('out-of-stock');
                }
            });
        }

        // Get selected options again after potential deactivations
        const finalActiveSizeOpt = document.querySelector('.size-opt.active');
        const finalActiveColorOpt = document.querySelector('.color-opt.active');
        const finalSize = finalActiveSizeOpt ? finalActiveSizeOpt.innerText.trim() : null;
        const finalColor = finalActiveColorOpt ? finalActiveColorOpt.getAttribute('data-color') : null;

        const currentStock = getAvailableStock(finalSize, finalColor);

        if (qtyInput) {
            qtyInput.max = currentStock;
            if (parseInt(qtyInput.value) > currentStock && currentStock > 0) {
                qtyInput.value = currentStock;
            } else if (currentStock <= 0) {
                qtyInput.value = 0;
            }
        }

        if (addToCartBtn) {
            const inStock = currentStock > 0;
            const originalPrice = parseFloat(data.price);
            const discount = parseFloat(data.discount || 0);
            const sellingPrice = discount > 0 ? (originalPrice * (1 - discount / 100)) : originalPrice;

            addToCartBtn.disabled = !inStock;
            addToCartBtn.textContent = inStock ? `Add to Cart - ${formatPrice(sellingPrice)}` : 'Out of Stock';

            if (buyNowBtn) {
                buyNowBtn.disabled = !inStock;
                buyNowBtn.textContent = inStock ? 'Buy Now' : 'Out of Stock';
            }
        }
    };

    // Attach click listeners to size options
    const sizes = document.querySelectorAll('.size-opt');
    sizes.forEach(size => {
        size.addEventListener('click', () => {
            if (size.classList.contains('out-of-stock')) return; // Do nothing if out of stock

            if (size.classList.contains('active')) {
                size.classList.remove('active');
            } else {
                sizes.forEach(s => s.classList.remove('active'));
                size.classList.add('active');
            }
            updateStockSelectionDisplay();
        });
    });

    // Attach click listeners to color options
    const colors = document.querySelectorAll('.color-opt');
    colors.forEach(color => {
        color.addEventListener('click', () => {
            if (color.classList.contains('out-of-stock')) return; // Do nothing if out of stock

            if (color.classList.contains('active')) {
                color.classList.remove('active');
            } else {
                colors.forEach(c => c.classList.remove('active'));
                color.classList.add('active');
            }
            updateStockSelectionDisplay();
        });
    });

    // Initial check and display update
    updateStockSelectionDisplay();

    if (qtyMinus && qtyPlus && qtyInput) {
        qtyMinus.addEventListener('click', () => {
            if (parseInt(qtyInput.value) > 1) qtyInput.value--;
        });
        qtyPlus.addEventListener('click', () => {
            const activeSizeOpt = document.querySelector('.size-opt.active');
            const activeColorOpt = document.querySelector('.color-opt.active');
            const sizeVal = activeSizeOpt ? activeSizeOpt.innerText.trim() : null;
            const colorVal = activeColorOpt ? activeColorOpt.getAttribute('data-color') : null;
            const currentStock = getAvailableStock(sizeVal, colorVal);

            if (parseInt(qtyInput.value) < currentStock) qtyInput.value++;
        });
    }

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            const qty = parseInt(qtyInput.value);

            // Validate zero or less quantity
            if (qty <= 0 || isNaN(qty)) {
                if (window.showToast) {
                    window.showToast('Product quantity is not valid.', true);
                } else {
                    alert('Product quantity is not valid.');
                }
                return;
            }

            // Get selected size
            const activeSizeOpt = document.querySelector('.size-opt.active');
            const activeColorOpt = document.querySelector('.color-opt.active');

            let size = 'Standard';
            if (data.sizes && data.sizes.length > 0) {
                if (!activeSizeOpt) {
                    if (window.showToast) {
                        window.showToast('Please select a size first.', true);
                    } else {
                        alert('Please select a size first.');
                    }
                    return;
                }
                size = activeSizeOpt.innerText.trim();
            }

            let color = 'N/A';
            if (data.colors && data.colors.length > 0) {
                if (!activeColorOpt) {
                    if (window.showToast) {
                        window.showToast('Please select a color first.', true);
                    } else {
                        alert('Please select a color first.');
                    }
                    return;
                }
                color = activeColorOpt.getAttribute('data-color');
            }

            // Double check stock check on click
            const currentStock = getAvailableStock(activeSizeOpt ? size : null, activeColorOpt ? color : null);
            if (qty > currentStock) {
                const desc = `${size !== 'Standard' ? 'size ' + size : ''}${color !== 'N/A' ? (size !== 'Standard' ? ' and ' : '') + 'color ' + color : ''}`;
                if (window.showToast) {
                    window.showToast(`Only ${currentStock} units available for ${desc}.`, true);
                } else {
                    alert(`Only ${currentStock} units available for ${desc}.`);
                }
                return;
            }

            addToCart(data, size, qty, color);
        });
    }

    if (buyNowBtn) {
        buyNowBtn.addEventListener('click', () => {
            const qty = parseInt(qtyInput.value);

            // Validate zero or less quantity
            if (qty <= 0 || isNaN(qty)) {
                if (window.showToast) {
                    window.showToast('Product quantity is not valid.', true);
                } else {
                    alert('Product quantity is not valid.');
                }
                return;
            }

            // Get selected size
            const activeSizeOpt = document.querySelector('.size-opt.active');
            const activeColorOpt = document.querySelector('.color-opt.active');

            let size = 'Standard';
            if (data.sizes && data.sizes.length > 0) {
                if (!activeSizeOpt) {
                    if (window.showToast) {
                        window.showToast('Please select a size first.', true);
                    } else {
                        alert('Please select a size first.');
                    }
                    return;
                }
                size = activeSizeOpt.innerText.trim();
            }

            let color = 'N/A';
            if (data.colors && data.colors.length > 0) {
                if (!activeColorOpt) {
                    if (window.showToast) {
                        window.showToast('Please select a color first.', true);
                    } else {
                        alert('Please select a color first.');
                    }
                    return;
                }
                color = activeColorOpt.getAttribute('data-color');
            }

            // Double check stock check on click
            const currentStock = getAvailableStock(activeSizeOpt ? size : null, activeColorOpt ? color : null);
            if (qty > currentStock) {
                const desc = `${size !== 'Standard' ? 'size ' + size : ''}${color !== 'N/A' ? (size !== 'Standard' ? ' and ' : '') + 'color ' + color : ''}`;
                if (window.showToast) {
                    window.showToast(`Only ${currentStock} units available for ${desc}.`, true);
                } else {
                    alert(`Only ${currentStock} units available for ${desc}.`);
                }
                return;
            }

            // Call Quick Checkout modal
            openBuyNowModal(id, data, size, qty, color);
        });
    }
}

// Dynamic Category loader
async function loadStorefrontCategories() {
    const container = document.getElementById('categoriesContainer');
    if (!container) return;

    // Helper to map default categories to their original storefront assets
    const getCategoryImage = (parentName, subName, dbImage) => {
        const p = parentName.toLowerCase().trim();
        const s = subName.toLowerCase().trim();
        
        if (p === 'women') {
            if (s === 'new') return 'images/women_new.png';
            if (s === 'everyday') return 'images/women_everyday.png';
            if (s === 'night out' || s === 'nightout') return 'images/women_nightout.png';
            if (s === 'essentials') return 'images/women_essentials.png';
            if (s === 'for the occasion' || s === 'occasion') return 'images/women_occasion.png';
        } else if (p === 'men') {
            if (s === 'new') return 'images/men_new.png';
            if (s === 'everyday') return 'images/men_everyday.png';
            if (s === 'night out' || s === 'nightout') return 'images/men_nightout.png';
            if (s === 'essentials') return 'images/men_essentials.png';
            if (s === 'for the occasion' || s === 'occasion') return 'images/men_occasion.png';
        }
        return dbImage || 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=200';
    };

    try {
        const snap = await getDocs(collection(db, "categories"));
        const allCats = [];
        snap.forEach(docSnap => {
            allCats.push({ id: docSnap.id, ...docSnap.data() });
        });

        // Filter parent categories (parent == "")
        const parents = allCats.filter(c => !c.parent);
        
        if (parents.length === 0) return;

        // Clear container
        container.innerHTML = '';

        parents.forEach((parent, index) => {
            // Find all subcategories for this parent
            const subcategories = allCats.filter(c => c.parent === parent.name);
            if (subcategories.length === 0) return; // Skip if no subcategories

            const block = document.createElement('div');
            block.className = 'category-block';
            if (index > 0) {
                block.style.marginTop = '5rem';
            }

            block.innerHTML = `
                <h2 class="category-block-title">${parent.name}</h2>
                <div class="category-grid">
                    ${subcategories.map(sub => {
                        const imgSrc = getCategoryImage(parent.name, sub.name, sub.image);
                        return `
                            <a href="products.html?category=${parent.name.toLowerCase()}&tag=${sub.name.toLowerCase()}" class="category-card">
                                <div class="category-img-wrap">
                                    <img src="${imgSrc}" alt="${sub.name}" class="category-img">
                                </div>
                                <h3 class="category-title">${sub.name}</h3>
                            </a>
                        `;
                    }).join('')}
                </div>
            `;
            container.appendChild(block);
        });
    } catch (err) {
        console.error("Error loading categories dynamically:", err);
    }
}

// Auto-initialize based on the page
document.addEventListener('DOMContentLoaded', () => {
    // If we're on products.html or index.html
    if (productGrid) {
        const isIndex = window.location.pathname.includes('index.html') || window.location.pathname.endsWith('/');
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        const tag = urlParams.get('tag');
        const searchQuery = urlParams.get('q');

        if (isIndex) {
            loadProducts(3); // Load only 3 featured products on home page
            loadStorefrontCategories(); // Load categories dynamically from Firestore
        } else {
            loadProducts(null, category, tag, searchQuery); // Load all on products page, optionally filtered
        }
    }

    // If we're on product-details.html
    if (productDetailsContainer) {
        loadSingleProduct();
    }

    // If we're on wishlist.html
    const wishlistGrid = document.getElementById('wishlistGrid');
    if (wishlistGrid) {
        loadWishlistProducts();
    }
});

// Fetch and render wishlist products
export async function loadWishlistProducts() {
    const wishlistGrid = document.getElementById('wishlistGrid');
    if (!wishlistGrid) return;

    await initCurrency();

    const list = window.getWishlist ? window.getWishlist() : [];
    if (list.length === 0) {
        wishlistGrid.innerHTML = `
            <div style="text-align:center; padding: 4rem 2rem; color: var(--text-muted); grid-column: 1 / -1; width: 100%;">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1.5rem; opacity: 0.5; color: var(--accent-color);">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--primary-color);">Your Wishlist is Empty</h3>
                <p style="margin-bottom: 2rem;">Save items that you love to see them here.</p>
                <a href="products.html" class="btn btn-primary" style="display: inline-block;">Start Shopping</a>
            </div>
        `;
        return;
    }

    wishlistGrid.innerHTML = '<div style="text-align:center; padding: 2rem; color: #999; grid-column: 1 / -1;">Loading your wishlist...</div>';

    try {
        const querySnapshot = await getDocs(collection(db, "products"));
        wishlistGrid.innerHTML = '';

        let productsHTML = '';
        querySnapshot.forEach((docSnap) => {
            if (list.includes(docSnap.id)) {
                productsHTML += createProductCard(docSnap.id, docSnap.data());
            }
        });

        if (productsHTML === '') {
            wishlistGrid.innerHTML = `
                <div style="text-align:center; padding: 4rem 2rem; color: var(--text-muted); grid-column: 1 / -1; width: 100%;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--primary-color);">Your Wishlist is Empty</h3>
                    <p style="margin-bottom: 2rem;">Save items that you love to see them here.</p>
                    <a href="products.html" class="btn btn-primary" style="display: inline-block;">Start Shopping</a>
                </div>
            `;
        } else {
            wishlistGrid.innerHTML = productsHTML;
            attachQuickAddListeners();
            if (window.updateHeartIconsState) window.updateHeartIconsState();
        }
    } catch (error) {
        console.error("Error loading wishlist products: ", error);
        wishlistGrid.innerHTML = '<div style="text-align:center; padding: 2rem; color: red; grid-column: 1 / -1;">Failed to load wishlist items.</div>';
    }
}
window.loadWishlistProducts = loadWishlistProducts;

// Fetch, calculate summary, and render product reviews dynamically
export async function loadProductReviews(productId, productName) {
    const reviewsSection = document.getElementById('productReviewsSection');
    if (!reviewsSection) return;

    reviewsSection.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--text-muted);">Loading reviews...</div>';

    try {
        // Query approved reviews for this product
        const q = query(
            collection(db, "reviews"),
            where("productId", "==", productId),
            where("status", "==", "Approved")
        );
        const querySnapshot = await getDocs(q);

        let reviews = [];
        let totalRating = 0;
        
        querySnapshot.forEach(docSnap => {
            const rData = docSnap.data();
            reviews.push({ id: docSnap.id, ...rData });
            totalRating += rData.rating;
        });

        // Sort reviews by date descending (newest first)
        reviews.sort((a, b) => {
            const dateA = a.createdAt ? (a.createdAt.seconds ? a.createdAt.seconds * 1000 : new Date(a.createdAt).getTime()) : 0;
            const dateB = b.createdAt ? (b.createdAt.seconds ? b.createdAt.seconds * 1000 : new Date(b.createdAt).getTime()) : 0;
            return dateB - dateA;
        });

        const numReviews = reviews.length;
        const avgRating = numReviews > 0 ? (totalRating / numReviews).toFixed(1) : 0;

        // Render reviews section header & summary
        let summaryHTML = '';
        let listHTML = '';

        if (numReviews > 0) {
            const starsHTML = '⭐'.repeat(Math.round(avgRating)) + '☆'.repeat(5 - Math.round(avgRating));
            
            // Build rating breakdown bar percentages
            let starsCount = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
            reviews.forEach(r => {
                if (starsCount[r.rating] !== undefined) starsCount[r.rating]++;
            });

            summaryHTML = `
                <div class="reviews-summary-container">
                    <div class="reviews-rating-value-box">
                        <div class="avg-rating-big">${avgRating}</div>
                        <div class="avg-rating-stars">${starsHTML}</div>
                        <p class="avg-rating-count">Based on ${numReviews} review${numReviews > 1 ? 's' : ''}</p>
                    </div>
                    <div class="reviews-rating-bars-box">
                        ${[5, 4, 3, 2, 1].map(star => {
                            const count = starsCount[star];
                            const pct = numReviews > 0 ? ((count / numReviews) * 100).toFixed(0) : 0;
                            return `
                                <div class="rating-bar-row">
                                    <span class="rating-bar-label">${star} star</span>
                                    <div class="rating-bar-bg">
                                        <div class="rating-bar-fill" style="width: ${pct}%;"></div>
                                    </div>
                                    <span class="rating-bar-pct">${pct}%</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;

            // Build reviews list
            listHTML = `
                <div class="reviews-list-container">
                    ${reviews.map(r => {
                        const rStars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
                        const rDate = r.createdAt ? new Date(r.createdAt.seconds ? r.createdAt.seconds * 1000 : r.createdAt).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' }) : '';
                        
                        let replyHTML = '';
                        if (r.reply) {
                            replyHTML = `
                                <div class="review-reply-bubble">
                                    <div class="reply-header">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="reply-icon" style="margin-right: 6px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                                        <strong>Store Response</strong>
                                    </div>
                                    <p class="reply-body">${r.reply}</p>
                                </div>
                            `;
                        }

                        return `
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-meta-info">
                                        <h4 class="reviewer-name">${r.customerName}</h4>
                                        <div class="review-stars-static">${rStars}</div>
                                    </div>
                                    <span class="review-date">${rDate}</span>
                                </div>
                                <p class="review-comment">"${r.comment}"</p>
                                ${replyHTML}
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        } else {
            summaryHTML = `
                <div class="no-reviews-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="no-rev-icon"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <h3>No reviews yet</h3>
                    <p>Be the first to share your thoughts about this product!</p>
                </div>
            `;
        }

        // Write a Review Form HTML
        const formHTML = `
            <div class="write-review-container">
                <h3 class="write-review-title">Write a Review</h3>
                <form id="addReviewForm" class="add-review-form">
                    <div class="form-group">
                        <label for="reviewName" class="form-label">Your Name</label>
                        <input type="text" id="reviewName" class="form-control" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Your Rating</label>
                        <div class="star-rating-selector">
                            <input type="radio" id="star5" name="reviewRating" value="5" required /><label for="star5" title="5 stars">★</label>
                            <input type="radio" id="star4" name="reviewRating" value="4" /><label for="star4" title="4 stars">★</label>
                            <input type="radio" id="star3" name="reviewRating" value="3" /><label for="star3" title="3 stars">★</label>
                            <input type="radio" id="star2" name="reviewRating" value="2" /><label for="star2" title="2 stars">★</label>
                            <input type="radio" id="star1" name="reviewRating" value="1" /><label for="star1" title="1 star">★</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reviewComment" class="form-label">Review Comment</label>
                        <textarea id="reviewComment" class="form-control" rows="4" placeholder="Share your experience with this product..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; margin-top: 1rem;">Submit Review</button>
                </form>
            </div>
        `;

        reviewsSection.innerHTML = `
            <div class="reviews-layout-grid">
                <div class="reviews-display-column">
                    <h2 class="reviews-section-title">Customer Reviews</h2>
                    ${summaryHTML}
                    ${listHTML}
                </div>
                <div class="reviews-form-column">
                    ${formHTML}
                </div>
            </div>
        `;

        // Attach Submit Event Listener
        const reviewForm = document.getElementById('addReviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('reviewName').value.trim();
                const comment = document.getElementById('reviewComment').value.trim();
                
                // Get selected rating
                const ratingRadio = document.querySelector('input[name="reviewRating"]:checked');
                if (!ratingRadio) {
                    if (window.showToast) {
                        window.showToast("Please select a star rating.", true);
                    } else {
                        alert("Please select a star rating.");
                    }
                    return;
                }
                const rating = parseInt(ratingRadio.value);

                const submitBtn = reviewForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';

                try {
                    const reviewData = {
                        productId: productId,
                        productName: productName,
                        customerName: name,
                        rating: rating,
                        comment: comment,
                        status: "Pending", // Moderation default
                        reply: "",
                        createdAt: new Date()
                    };

                    await addDoc(collection(db, "reviews"), reviewData);
                    
                    const successMsg = "Thank you! Your review has been submitted for moderation.";
                    if (window.showToast) {
                        window.showToast(successMsg, false);
                    } else {
                        alert(successMsg);
                    }

                    // Reset form and re-enable submit btn
                    reviewForm.reset();
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Review';

                } catch (err) {
                    console.error("Error submitting review:", err);
                    const errorMsg = "Failed to submit review. Please try again.";
                    if (window.showToast) {
                        window.showToast(errorMsg, true);
                    } else {
                        alert(errorMsg);
                    }
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Review';
                }
            });
        }

    } catch (error) {
        console.error("Error loading product reviews:", error);
        reviewsSection.innerHTML = '<div style="text-align:center; padding: 2rem; color: red;">Failed to load reviews.</div>';
    }
}

function openBuyNowModal(prodId, data, size, qty, color) {
    const existing = document.getElementById('buyNowModal');
    if (existing) existing.remove();

    const originalPrice = parseFloat(data.price);
    const discount = parseFloat(data.discount || 0);
    const sellingPrice = discount > 0 ? (originalPrice * (1 - discount / 100)) : originalPrice;
    const totalAmount = sellingPrice * qty;

    const modal = document.createElement('div');
    modal.id = 'buyNowModal';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.background = 'rgba(0, 0, 0, 0.6)';
    modal.style.zIndex = '1000';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.backdropFilter = 'blur(4px)';
    modal.style.fontFamily = "'Outfit', sans-serif";

    const isDark = document.body.classList.contains('dark') || document.documentElement.getAttribute('data-theme') === 'dark';
    const bgColor = isDark ? '#282D24' : '#FFFFFF';
    const textColor = isDark ? '#FFFFFF' : '#000000';
    const cardBgColor = isDark ? '#1D211A' : '#F8F9FA';
    const borderColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(159, 93, 68, 0.15)';
    const textMutedColor = isDark ? '#A3A79A' : '#7B7E4B';

    modal.innerHTML = `
        <div style="
            background: ${bgColor};
            color: ${textColor};
            max-width: 500px;
            width: 95%;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            position: relative;
            box-sizing: border-box;
            border: 1px solid ${borderColor};
            animation: modalFadeIn 0.3s ease;
        ">
            <button id="closeBuyNowModal" style="
                position: absolute;
                top: 1rem; right: 1rem;
                background: none; border: none;
                font-size: 1.5rem; cursor: pointer;
                color: ${textMutedColor};
            ">&times;</button>
            
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 700; text-align: center;">Quick Checkout</h3>
            <p style="color: ${textMutedColor}; text-align: center; font-size: 0.9rem; margin-bottom: 1.5rem; line-height: 1.4;">
                You are buying <strong>${data.name}</strong><br>
                Size: <span style="color: var(--accent-color); font-weight: 600;">${size}</span> 
                ${color !== 'N/A' ? `• Color: <span style="background: ${color}; display: inline-block; width: 12px; height: 12px; border-radius: 50%; vertical-align: middle; border: 1px solid #ccc;"></span>` : ''} 
                • Qty: <span style="font-weight: 600;">${qty}</span>
            </p>

            <div id="buyNowPaymentSelection">
                <p style="font-weight: 600; margin-bottom: 1rem; text-align: center; font-size: 0.95rem;">Select Payment Option:</p>
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 1rem;">
                    <button id="btnSelectCOD" style="
                        display: flex; justify-content: space-between; align-items: center;
                        padding: 1.2rem; background: ${cardBgColor};
                        border: 1px solid ${borderColor}; border-radius: 8px;
                        cursor: pointer; text-align: left; transition: all 0.2s;
                        color: ${textColor};
                    ">
                        <div>
                            <strong style="display: block; font-size: 1rem; margin-bottom: 4px; color: ${textColor};">Cash on Delivery (COD)</strong>
                            <span style="font-size: 0.8rem; color: ${textMutedColor};">Pay cash upon delivery to your address.</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                    <button id="btnSelectDeposit" style="
                        display: flex; justify-content: space-between; align-items: center;
                        padding: 1.2rem; background: ${cardBgColor};
                        border: 1px solid ${borderColor}; border-radius: 8px;
                        cursor: pointer; text-align: left; transition: all 0.2s;
                        color: ${textColor};
                    ">
                        <div>
                            <strong style="display: block; font-size: 1rem; margin-bottom: 4px; color: ${textColor};">Cash Deposit / Bank Transfer</strong>
                            <span style="font-size: 0.8rem; color: ${textMutedColor};">Pay via bank deposit and upload slip to confirm.</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-color)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>

            <div id="buyNowCODForm" style="display: none;">
                <h4 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; color: var(--accent-color); text-align: center;">Delivery Information</h4>
                <form id="codQuickCheckoutForm" style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group" style="margin: 0;">
                            <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px; color: ${textColor};">First Name</label>
                            <input type="text" id="quickFname" class="form-control" required style="width: 100%; height: 38px; border-radius: 4px; border: 1px solid ${borderColor}; background: ${cardBgColor}; color: ${textColor}; padding: 0 10px;">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px; color: ${textColor};">Last Name</label>
                            <input type="text" id="quickLname" class="form-control" required style="width: 100%; height: 38px; border-radius: 4px; border: 1px solid ${borderColor}; background: ${cardBgColor}; color: ${textColor}; padding: 0 10px;">
                        </div>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px; color: ${textColor};">Phone Number</label>
                        <input type="tel" id="quickPhone" class="form-control" required style="width: 100%; height: 38px; border-radius: 4px; border: 1px solid ${borderColor}; background: ${cardBgColor}; color: ${textColor}; padding: 0 10px;">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 4px; color: ${textColor};">Delivery Address</label>
                        <textarea id="quickAddress" class="form-control" required style="width: 100%; height: 70px; border-radius: 4px; border: 1px solid ${borderColor}; background: ${cardBgColor}; color: ${textColor}; padding: 8px 10px; font-family: inherit; resize: none;"></textarea>
                    </div>
                    
                    <button type="submit" id="btnQuickSubmit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 0.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem;">
                        Confirm Purchase
                    </button>
                    <button type="button" id="btnBackToSelection" style="background: none; border: none; color: var(--accent-color); font-size: 0.85rem; cursor: pointer; text-decoration: underline; margin-top: 4px; font-weight: 500;">
                        Back to Payment Methods
                    </button>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    document.getElementById('closeBuyNowModal').onclick = () => modal.remove();
    
    const selectionDiv = document.getElementById('buyNowPaymentSelection');
    const codFormDiv = document.getElementById('buyNowCODForm');
    const backBtn = document.getElementById('btnBackToSelection');
    
    document.getElementById('btnSelectCOD').onclick = () => {
        selectionDiv.style.display = 'none';
        codFormDiv.style.display = 'block';
    };

    document.getElementById('btnSelectDeposit').onclick = () => {
        addToCart(data, size, qty, color);
        window.location.href = 'checkout.html';
        modal.remove();
    };

    backBtn.onclick = () => {
        codFormDiv.style.display = 'none';
        selectionDiv.style.display = 'block';
    };

    document.getElementById('codQuickCheckoutForm').onsubmit = async (e) => {
        e.preventDefault();
        
        const submitBtn = document.getElementById('btnQuickSubmit');
        submitBtn.disabled = true;
        submitBtn.innerText = "Placing Order...";

        try {
            const customer = {
                firstName: document.getElementById('quickFname').value.trim(),
                lastName: document.getElementById('quickLname').value.trim(),
                phone: document.getElementById('quickPhone').value.trim(),
                address: document.getElementById('quickAddress').value.trim(),
                city: '',
                district: '',
                postalCode: '',
                email: ''
            };

            let shipping = 10;
            try {
                const rulesSnap = await getDoc(doc(db, "settings", "shipping_rules"));
                if (rulesSnap.exists()) {
                    const rules = rulesSnap.data();
                    const threshold = rules.freeShippingThreshold !== undefined ? rules.freeShippingThreshold : 150;
                    const standardFee = rules.standardFee !== undefined ? rules.standardFee : 10;
                    shipping = totalAmount > threshold ? 0 : standardFee;
                }
            } catch (errRules) {
                console.error("Error loading rules:", errRules);
                shipping = totalAmount > 150 ? 0 : 10;
            }

            const orderItem = {
                id: prodId,
                name: data.name,
                price: sellingPrice,
                imageUrl: data.imageUrl || 'images/placeholder.png',
                size: size,
                color: color,
                quantity: qty
            };

            const order = {
                customer,
                items: [orderItem],
                subtotal: totalAmount,
                shipping: shipping,
                totalAmount: totalAmount + shipping,
                paymentMethod: 'COD',
                paymentStatus: 'PENDING',
                orderStatus: 'Pending',
                createdAt: new Date()
            };

            const docRef = await addDoc(collection(db, "orders"), order);
            modal.remove();
            
            alert(`Order placed successfully! Order ID: ${docRef.id}`);
            window.location.href = `order-details.html?id=${docRef.id}`;

        } catch (err) {
            console.error("Error creating quick order:", err);
            alert("There was an error placing your order. Please try again.");
            submitBtn.disabled = false;
            submitBtn.innerText = "Confirm Purchase";
        }
    };
}
