// Cart Management Logic using Local Storage

// Initialize cart from local storage
export function getCart() {
    const cart = localStorage.getItem('Anjiana Store_cart');
    return cart ? JSON.parse(cart) : [];
}

export function saveCart(cart) {
    localStorage.setItem('Anjiana Store_cart', JSON.stringify(cart));
    updateCartBadge();
}

export function addToCart(product, size, quantity) {
    const cart = getCart();
    
    // Check if item already exists in cart with same size
    const existingItemIndex = cart.findIndex(item => item.id === product.id && item.size === size);
    
    const originalPrice = parseFloat(product.price);
    const discount = parseFloat(product.discount || 0);
    const actualPrice = discount > 0 ? (originalPrice * (1 - discount / 100)) : originalPrice;

    if (existingItemIndex > -1) {
        cart[existingItemIndex].quantity += quantity;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: actualPrice,
            imageUrl: product.imageUrl,
            size: size,
            quantity: quantity
        });
    }
    
    saveCart(cart);
    alert(`${quantity}x ${product.name} added to cart!`);
}

export function removeFromCart(index) {
    const cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
    renderCart(); // Re-render if on cart page
}

export function updateQuantity(index, newQuantity) {
    const cart = getCart();
    if (newQuantity < 1) return;
    cart[index].quantity = newQuantity;
    saveCart(cart);
    renderCart();
}

export function updateCartBadge() {
    const cart = getCart();
    const badges = document.querySelectorAll('.cart-badge');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    badges.forEach(badge => {
        badge.innerText = totalItems;
        badge.style.display = totalItems > 0 ? 'flex' : 'none';
    });
}

// Render Cart on cart.html
export function renderCart() {
    const cartItemsContainer = document.getElementById('cartItems');
    const cartSubtotal = document.getElementById('cartSubtotal');
    const cartTotal = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (!cartItemsContainer) return; // Not on cart page

    const cart = getCart();
    cartItemsContainer.innerHTML = '';

    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<p style="text-align: center; padding: 2rem;">Your cart is empty. <a href="products.html" style="color: var(--primary-color);">Continue Shopping</a></p>';
        if (cartSubtotal) cartSubtotal.innerText = '$0.00';
        if (cartTotal) cartTotal.innerText = '$0.00';
        if (checkoutBtn) checkoutBtn.style.display = 'none';
        return;
    }

    if (checkoutBtn) checkoutBtn.style.display = 'block';

    let total = 0;

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        const cartItemHTML = `
            <div class="cart-item">
                <img src="${item.imageUrl || 'images/placeholder.png'}" class="cart-item-img" alt="${item.name}">
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between;">
                            <h3 style="font-size: 1.1rem;"><a href="product-details.html?id=${item.id}">${item.name}</a></h3>
                            <div style="font-weight: 500;">$${itemTotal.toFixed(2)}</div>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Size: ${item.size || 'N/A'}</p>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">$${parseFloat(item.price).toFixed(2)} each</p>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                        <div style="display: flex; align-items: center; border: 1px solid var(--border-color); border-radius: 4px; overflow: hidden;">
                            <button class="cart-qty-btn decrease" data-index="${index}" style="padding: 4px 10px; background: none; border: none; cursor: pointer;">-</button>
                            <span style="padding: 4px 10px; border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color); min-width: 30px; text-align: center;">${item.quantity}</span>
                            <button class="cart-qty-btn increase" data-index="${index}" style="padding: 4px 10px; background: none; border: none; cursor: pointer;">+</button>
                        </div>
                        <button class="cart-remove-btn" data-index="${index}" style="background: none; border: none; color: #C62828; text-decoration: underline; cursor: pointer; font-size: 0.9rem;">Remove</button>
                    </div>
                </div>
            </div>
        `;
        cartItemsContainer.insertAdjacentHTML('beforeend', cartItemHTML);
    });

    if (cartSubtotal) cartSubtotal.innerText = `$${total.toFixed(2)}`;
    // Assuming flat shipping of $10 for example, or free if over $100
    const shipping = total > 100 ? 0 : 10;
    const shippingElem = document.getElementById('cartShipping');
    if (shippingElem) shippingElem.innerText = shipping === 0 ? 'Free' : `$${shipping.toFixed(2)}`;
    
    if (cartTotal) cartTotal.innerText = `$${(total + shipping).toFixed(2)}`;

    // Attach event listeners for buttons
    attachCartListeners();
}

function attachCartListeners() {
    document.querySelectorAll('.cart-qty-btn.decrease').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const index = e.target.getAttribute('data-index');
            const cart = getCart();
            updateQuantity(index, cart[index].quantity - 1);
        });
    });

    document.querySelectorAll('.cart-qty-btn.increase').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const index = e.target.getAttribute('data-index');
            const cart = getCart();
            updateQuantity(index, cart[index].quantity + 1);
        });
    });

    document.querySelectorAll('.cart-remove-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const index = e.target.getAttribute('data-index');
            removeFromCart(index);
        });
    });
}

// Ensure cart badge is updated on all pages
document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();
    renderCart(); // Will only execute if cartItems container exists
});
