import { db, collection, addDoc } from './firebase-config.js';
import { getCart, saveCart } from './cart.js';

const checkoutForm = document.getElementById('checkoutForm');
const orderSummaryContainer = document.getElementById('orderSummaryContainer');
const subtotalEl = document.getElementById('checkoutSubtotal');
const shippingEl = document.getElementById('checkoutShipping');
const totalEl = document.getElementById('checkoutTotal');
const checkoutBtn = document.getElementById('placeOrderBtn');

function renderOrderSummary() {
    if (!orderSummaryContainer) return;

    const cart = getCart();
    orderSummaryContainer.innerHTML = '';

    if (cart.length === 0) {
        orderSummaryContainer.innerHTML = '<p>Your cart is empty.</p>';
        checkoutBtn.disabled = true;
        return;
    }

    let subtotal = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;

        orderSummaryContainer.innerHTML += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <div style="display: flex; gap: 1rem;">
                    <img src="${item.imageUrl || 'images/placeholder.png'}" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px;" alt="${item.name}">
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 500;">${item.name}</h4>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Size: ${item.size} • Qty: ${item.quantity}</p>
                    </div>
                </div>
                <div style="font-weight: 500;">$${itemTotal.toFixed(2)}</div>
            </div>
        `;
    });

    const shipping = subtotal > 100 ? 0 : 10;
    const total = subtotal + shipping;

    if (subtotalEl) subtotalEl.innerText = `$${subtotal.toFixed(2)}`;
    if (shippingEl) shippingEl.innerText = shipping === 0 ? 'Free' : `$${shipping.toFixed(2)}`;
    if (totalEl) totalEl.innerText = `$${total.toFixed(2)}`;
    
    return { cart, subtotal, shipping, total };
}

if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const orderDataInfo = renderOrderSummary();
        if (!orderDataInfo || orderDataInfo.cart.length === 0) {
            alert('Your cart is empty.');
            return;
        }

        checkoutBtn.disabled = true;
        checkoutBtn.innerText = 'Processing Order...';

        try {
            // Collect customer details
            const customer = {
                firstName: document.getElementById('fname').value,
                lastName: document.getElementById('lname').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                address: document.getElementById('address').value,
                city: document.getElementById('city').value,
                district: document.getElementById('district').value,
                postalCode: document.getElementById('zip').value,
            };

            const order = {
                customer,
                items: orderDataInfo.cart,
                subtotal: orderDataInfo.subtotal,
                shipping: orderDataInfo.shipping,
                totalAmount: orderDataInfo.total,
                paymentStatus: 'Pending',
                orderStatus: 'Pending',
                createdAt: new Date()
            };

            // Save to Firestore
            const docRef = await addDoc(collection(db, "orders"), order);

            // Clear Cart
            saveCart([]);

            // Simulate PayHere integration or redirect to success page
            alert(`Order placed successfully! Order ID: ${docRef.id}`);
            window.location.href = 'index.html';

        } catch (error) {
            console.error("Error placing order: ", error);
            alert("There was an error placing your order. Please try again.");
            checkoutBtn.disabled = false;
            checkoutBtn.innerText = 'Place Order & Pay';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    renderOrderSummary();
});
