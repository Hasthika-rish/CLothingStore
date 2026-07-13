import { db, collection, addDoc, getCachedSettings, getCachedShippingRules } from './firebase-config.js';
import { getCart, saveCart } from './cart.js';

const checkoutForm = document.getElementById('checkoutForm');
const orderSummaryContainer = document.getElementById('orderSummaryContainer');
const subtotalEl = document.getElementById('checkoutSubtotal');
const shippingEl = document.getElementById('checkoutShipping');
const totalEl = document.getElementById('checkoutTotal');
const checkoutBtn = document.getElementById('placeOrderBtn');

let activeCurrency = 'Rs.';

async function renderOrderSummary() {
    if (!orderSummaryContainer) return;

    const cart = getCart();
    orderSummaryContainer.innerHTML = '';

    if (cart.length === 0) {
        orderSummaryContainer.innerHTML = '<p>Your cart is empty.</p>';
        checkoutBtn.disabled = true;
        return;
    }

    // Load active currency and shipping rules dynamically
    const settings = await getCachedSettings();
    activeCurrency = settings.currency || 'Rs.';
    const rules = await getCachedShippingRules();

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
                <div style="font-weight: 500;">${activeCurrency}${itemTotal.toFixed(2)}</div>
            </div>
        `;
    });

    const threshold = rules.freeShippingThreshold !== undefined ? rules.freeShippingThreshold : 150;
    const standardFee = rules.standardFee !== undefined ? rules.standardFee : 10;
    const shipping = subtotal > threshold ? 0 : standardFee;
    const total = subtotal + shipping;

    if (subtotalEl) subtotalEl.innerText = `${activeCurrency}${subtotal.toFixed(2)}`;
    if (shippingEl) shippingEl.innerText = shipping === 0 ? 'Free' : `${activeCurrency}${shipping.toFixed(2)}`;
    if (totalEl) totalEl.innerText = `${activeCurrency}${total.toFixed(2)}`;
    
    return { cart, subtotal, shipping, total };
}

// Toggle Card Details Section and Button Text based on Payment Method
const paymentRadios = document.querySelectorAll('input[name="payment"]');
const cardDetailsSection = document.getElementById('cardDetailsSection');

function updatePaymentUI() {
    if (!cardDetailsSection) return;
    const selectedRadio = document.querySelector('input[name="payment"]:checked');
    if (!selectedRadio) return;
    const selectedPayment = selectedRadio.value;
    const bankDetailsSection = document.getElementById('bankDetailsSection');
    
    if (selectedPayment === 'cod') {
        cardDetailsSection.style.display = 'none';
        if (bankDetailsSection) bankDetailsSection.style.display = 'none';
        checkoutBtn.innerText = 'Place Order (Cash on Delivery)';
        // Make inputs not required
        document.getElementById('card').required = false;
        document.getElementById('exp').required = false;
        document.getElementById('cvv').required = false;
    } else if (selectedPayment === 'bank_deposit') {
        cardDetailsSection.style.display = 'none';
        if (bankDetailsSection) bankDetailsSection.style.display = 'block';
        checkoutBtn.innerText = 'Place Order (Bank Deposit)';
        // Make inputs not required
        document.getElementById('card').required = false;
        document.getElementById('exp').required = false;
        document.getElementById('cvv').required = false;
    } else {
        cardDetailsSection.style.display = 'block';
        if (bankDetailsSection) bankDetailsSection.style.display = 'none';
        checkoutBtn.innerText = 'Place Order & Pay';
        // Make inputs required for payment processing demo
        document.getElementById('card').required = true;
        document.getElementById('exp').required = true;
        document.getElementById('cvv').required = true;
    }
}

if (paymentRadios.length > 0) {
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', updatePaymentUI);
    });
}

if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const orderDataInfo = await renderOrderSummary();
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

            const selectedPayment = document.querySelector('input[name="payment"]:checked').value;
            let paymentMethod = 'COD';
            let paymentStatus = 'PENDING';
            
            if (selectedPayment === 'card') {
                paymentMethod = 'Card Payments';
                paymentStatus = 'Paid';
            } else if (selectedPayment === 'paypal') {
                paymentMethod = 'PayPal';
                paymentStatus = 'Paid';
            } else if (selectedPayment === 'bank_deposit') {
                paymentMethod = 'BANK_DEPOSIT';
                paymentStatus = 'PENDING';
            } else if (selectedPayment === 'cod') {
                paymentMethod = 'COD';
                paymentStatus = 'PENDING';
            }

            const order = {
                customer,
                items: orderDataInfo.cart,
                subtotal: orderDataInfo.subtotal,
                shipping: orderDataInfo.shipping,
                totalAmount: orderDataInfo.total,
                paymentMethod: paymentMethod,
                paymentStatus: paymentStatus,
                orderStatus: 'Pending',
                createdAt: new Date()
            };

            // Save to Firestore
            const docRef = await addDoc(collection(db, "orders"), order);

            // Clear Cart
            saveCart([]);

            // Display success modal
            const successModal = document.getElementById('successModal');
            const successOrderId = document.getElementById('successOrderId');
            const successPaymentInfo = document.getElementById('successPaymentInfo');
            const successBankDetails = document.getElementById('successBankDetails');
            const successActionBtn = document.getElementById('successActionBtn');

            if (successModal) {
                if (successOrderId) successOrderId.innerText = docRef.id;
                
                if (selectedPayment === 'bank_deposit') {
                    if (successPaymentInfo) successPaymentInfo.innerHTML = 'Please complete the bank transfer of <strong>' + activeCurrency + orderDataInfo.total.toFixed(2) + '</strong> using the details below.';
                    if (successBankDetails) successBankDetails.style.display = 'block';
                    if (successActionBtn) {
                        successActionBtn.innerText = 'Upload Bank Slip';
                        successActionBtn.onclick = () => {
                            window.location.href = `order-details.html?id=${docRef.id}`;
                        };
                    }
                } else if (selectedPayment === 'cod') {
                    if (successPaymentInfo) successPaymentInfo.innerHTML = 'Your order total is <strong>' + activeCurrency + orderDataInfo.total.toFixed(2) + '</strong>. You will pay in cash upon delivery.';
                    if (successBankDetails) successBankDetails.style.display = 'none';
                    if (successActionBtn) {
                        successActionBtn.innerText = 'View Order Details';
                        successActionBtn.onclick = () => {
                            window.location.href = `order-details.html?id=${docRef.id}`;
                        };
                    }
                } else {
                    if (successPaymentInfo) successPaymentInfo.innerHTML = 'Payment of <strong>' + activeCurrency + orderDataInfo.total.toFixed(2) + '</strong> received successfully via card/PayPal.';
                    if (successBankDetails) successBankDetails.style.display = 'none';
                    if (successActionBtn) {
                        successActionBtn.innerText = 'View Order Details';
                        successActionBtn.onclick = () => {
                            window.location.href = `order-details.html?id=${docRef.id}`;
                        };
                    }
                }
                
                successModal.style.display = 'flex';
            } else {
                alert(`Order placed successfully! Order ID: ${docRef.id}`);
                window.location.href = `order-details.html?id=${docRef.id}`;
            }

        } catch (error) {
            console.error("Error placing order: ", error);
            alert("There was an error placing your order. Please try again.");
            checkoutBtn.disabled = false;
            updatePaymentUI();
        }
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    await renderOrderSummary();
    updatePaymentUI();
});
