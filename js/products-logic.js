import { db, collection, getDocs, doc, getDoc } from './firebase-config.js';
import { addToCart } from './cart.js';

// DOM Elements
const productGrid = document.getElementById('productGrid');
const productDetailsContainer = document.getElementById('productDetails');

// Helper to format currency
const formatPrice = (price) => `$${parseFloat(price).toFixed(2)}`;

// Helper to generate a product card HTML
const createProductCard = (id, data) => {
    const price = parseFloat(data.price);
    const discount = parseFloat(data.discount || 0);
    let priceHTML = '';
    let badgeHTML = '';
    
    if (discount > 0) {
        const discountedPrice = price * (1 - discount / 100);
        priceHTML = `<span style="text-decoration: line-through; color: var(--text-muted); font-size: 0.9rem; margin-right: 0.5rem;">$${price.toFixed(2)}</span><span style="font-weight: 600; color: var(--accent-color); font-size: 1.1rem;">$${discountedPrice.toFixed(2)}</span>`;
        badgeHTML = `<span style="position: absolute; top: 12px; left: 12px; background: var(--accent-color); color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; z-index: 2; box-shadow: 0 4px 10px rgba(0,0,0,0.15); font-family: 'Outfit', sans-serif;">-${discount}%</span>`;
    } else {
        priceHTML = `<span style="font-weight: 600; font-size: 1.1rem;">$${price.toFixed(2)}</span>`;
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
export async function loadProducts(limitCount = null, categoryFilter = null) {
    if (!productGrid) return;
    
    productGrid.innerHTML = '<div style="text-align:center; padding: 2rem; color: #999;">Loading products...</div>';
    
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
                        <span style="text-decoration: line-through; color: var(--text-muted); font-size: 1.25rem;">$${price.toFixed(2)}</span>
                        <span style="font-weight: 700; color: var(--accent-color); font-size: 1.8rem;">$${sellingPrice.toFixed(2)}</span>
                        <span style="background: rgba(159, 93, 68, 0.15); color: var(--accent-color); padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">SAVE ${discount}%</span>
                    </div>
                `;
            } else {
                priceHTML = `<p class="product-price-lg" style="font-size: 1.8rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem;">$${price.toFixed(2)}</p>`;
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

            // Payment gateway is not integrated yet
            const errorMsg = 'Payment gateway is not integrated yet.';
            if (window.showToast) {
                window.showToast(errorMsg, true);
            } else {
                alert(errorMsg);
            }
        });
    }
}

// Auto-initialize based on the page
document.addEventListener('DOMContentLoaded', () => {
    // If we're on products.html or index.html
    if (productGrid) {
        const isIndex = window.location.pathname.includes('index.html') || window.location.pathname.endsWith('/');
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        
        if (isIndex) {
            loadProducts(3); // Load only 3 featured products on home page
        } else {
            loadProducts(null, category); // Load all on products page, optionally filtered
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
