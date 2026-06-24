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
        badgeHTML = `<span style="position: absolute; top: 12px; left: 12px; background: var(--accent-color); color: #1A1A1A; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; z-index: 2; box-shadow: 0 4px 10px rgba(0,0,0,0.15); font-family: 'Outfit', sans-serif;">-${discount}%</span>`;
    } else {
        priceHTML = `<span style="font-weight: 600; font-size: 1.1rem;">$${price.toFixed(2)}</span>`;
    }

    return `
    <article class="product-card" style="position: relative;">
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
            const sizesHTML = data.sizes && data.sizes.length > 0 
                ? data.sizes.map(size => `<button class="size-opt">${size}</button>`).join('')
                : '<span style="color:#999;">No sizes specified</span>';

            const inStock = data.stock > 0;
            const stockStatus = inStock 
                ? `<span style="color: green; font-weight: 500;">In Stock (${data.stock} available)</span>`
                : `<span style="color: red; font-weight: 500;">Out of Stock</span>`;

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
                        <span style="background: rgba(212, 175, 55, 0.15); color: var(--accent-color); padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">SAVE ${discount}%</span>
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
                        <div class="option-group">
                            <label>Size</label>
                            <div class="size-selector">
                                ${sizesHTML}
                            </div>
                        </div>
                        
                        <div class="option-group">
                            <label>Quantity</label>
                            <div class="qty-selector">
                                <button class="qty-btn" id="qtyMinus">-</button>
                                <input type="number" id="qtyInput" value="1" min="1" max="${data.stock}" style="width: 50px; text-align: center; border: 1px solid var(--border-color);">
                                <button class="qty-btn" id="qtyPlus">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="product-actions" style="margin-top: 2rem;">
                        <button id="addToCartBtn" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 1.1rem;" ${!inStock ? 'disabled' : ''}>
                            ${inStock ? 'Add to Cart - ' + formatPrice(sellingPrice) : 'Out of Stock'}
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

    if (qtyMinus && qtyPlus && qtyInput) {
        qtyMinus.addEventListener('click', () => {
            if (qtyInput.value > 1) qtyInput.value--;
        });
        qtyPlus.addEventListener('click', () => {
            if (parseInt(qtyInput.value) < data.stock) qtyInput.value++;
        });
    }

    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            const qty = parseInt(qtyInput.value);
            
            // Get selected size
            const activeSizeOpt = document.querySelector('.size-opt.active');
            let size = 'Standard';
            if (activeSizeOpt) {
                size = activeSizeOpt.innerText;
            } else if (data.sizes && data.sizes.length > 0) {
                // if there are sizes but none active, maybe alert user?
                alert('Please select a size first.');
                return;
            }

            addToCart(data, size, qty);
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
});
