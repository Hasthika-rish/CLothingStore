import { db, storage, doc, getDoc, updateDoc, auth, ref, uploadBytes, getDownloadURL, getCachedSettings } from './firebase-config.js';

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('id');

    const trackSearchSection = document.getElementById('trackSearchSection');
    const orderDetailsSection = document.getElementById('orderDetailsSection');
    const trackOrderForm = document.getElementById('trackOrderForm');
    const trackOrderIdInput = document.getElementById('trackOrderId');

    // Routing: Search vs Details
    if (!orderId) {
        if (trackSearchSection) trackSearchSection.style.display = 'block';
        if (orderDetailsSection) orderDetailsSection.style.display = 'none';

        if (trackOrderForm) {
            trackOrderForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const idVal = trackOrderIdInput.value.trim();
                if (idVal) {
                    window.location.href = `order-details.html?id=${idVal}`;
                }
            });
        }
    } else {
        if (trackSearchSection) trackSearchSection.style.display = 'none';
        if (orderDetailsSection) orderDetailsSection.style.display = 'block';
        await loadOrderDetails(orderId);
    }
});

async function loadOrderDetails(orderId) {
    const displayOrderId = document.getElementById('displayOrderId');
    const orderStatusBadge = document.getElementById('orderStatusBadge');
    const orderDateEl = document.getElementById('orderDate');
    const itemsContainer = document.getElementById('itemsContainer');
    
    const detailSubtotal = document.getElementById('detailSubtotal');
    const detailShipping = document.getElementById('detailShipping');
    const detailTotal = document.getElementById('detailTotal');
    
    const detailAddress = document.getElementById('detailAddress');
    const detailCustomerName = document.getElementById('detailCustomerName');
    const detailPhone = document.getElementById('detailPhone');
    const detailEmail = document.getElementById('detailEmail');
    
    const detailPaymentMethod = document.getElementById('detailPaymentMethod');
    const paymentStatusBadge = document.getElementById('paymentStatusBadge');

    try {
        const docRef = doc(db, "orders", orderId);
        const docSnap = await getDoc(docRef);

        if (!docSnap.exists()) {
            if (orderDetailsSection) {
                orderDetailsSection.innerHTML = `
                    <div style="text-align: center; padding: 3rem 0;">
                        <h2 style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;">Order Not Found</h2>
                        <p style="color: var(--text-muted); margin-bottom: 2rem;">We couldn't find an order matching the ID: <strong>${orderId}</strong></p>
                        <a href="order-details.html" class="btn btn-primary">Track Another Order</a>
                    </div>
                `;
            }
            return;
        }

        const order = docSnap.data();

        // 1. Header Information
        if (displayOrderId) displayOrderId.innerText = orderId;
        
        if (orderStatusBadge) {
            orderStatusBadge.innerText = order.orderStatus || 'Pending';
            orderStatusBadge.className = 'order-status-badge'; // reset
            const statusClass = 'status-' + (order.orderStatus || 'pending').toLowerCase();
            orderStatusBadge.classList.add(statusClass);
        }

        if (orderDateEl && order.createdAt) {
            const date = order.createdAt.toDate ? order.createdAt.toDate() : new Date(order.createdAt);
            orderDateEl.innerText = `Placed on ${date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`;
        }

        // 2. Settings & Currency
        const settings = await getCachedSettings();
        const activeCurrency = settings.currency || 'Rs.';

        // 3. Render Items
        if (itemsContainer) {
            itemsContainer.innerHTML = '';
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    itemsContainer.innerHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--border-color);">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <img src="${item.imageUrl || 'images/placeholder.png'}" style="width: 50px; height: 60px; object-fit: cover; border-radius: 4px;" alt="${item.name}">
                                <div>
                                    <h4 style="font-size: 1rem; font-weight: 500; margin: 0;">${item.name}</h4>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 2px 0 0 0;">Size: ${item.size} • Qty: ${item.quantity}</p>
                                </div>
                            </div>
                            <div style="font-weight: 600; color: var(--primary-color);">${activeCurrency}${(item.price * item.quantity).toFixed(2)}</div>
                        </div>
                    `;
                });
            } else {
                itemsContainer.innerHTML = '<p>No items found in this order.</p>';
            }
        }

        // 4. Summaries
        if (detailSubtotal) detailSubtotal.innerText = `${activeCurrency}${(order.subtotal || 0).toFixed(2)}`;
        if (detailShipping) {
            const shippingVal = order.shipping || 0;
            detailShipping.innerText = shippingVal === 0 ? 'Free' : `${activeCurrency}${shippingVal.toFixed(2)}`;
        }
        if (detailTotal) detailTotal.innerText = `${activeCurrency}${(order.totalAmount || 0).toFixed(2)}`;

        // 5. Customer Delivery Info
        if (order.customer) {
            const cust = order.customer;
            if (detailCustomerName) detailCustomerName.innerText = `${cust.firstName || ''} ${cust.lastName || ''}`;
            if (detailPhone) detailPhone.innerText = cust.phone || '';
            if (detailEmail) detailEmail.innerText = cust.email || '';
            
            if (detailAddress) {
                detailAddress.innerHTML = `
                    ${cust.address || ''}<br>
                    ${cust.city || ''}, ${cust.district || ''}<br>
                    ${cust.postalCode || ''}
                `;
            }
        }

        // 6. Payment Information Status and Flow
        const paymentMethod = order.paymentMethod || 'COD';
        const paymentStatus = order.paymentStatus || 'PENDING';

        if (detailPaymentMethod) {
            let methodLabel = paymentMethod;
            if (paymentMethod === 'COD') methodLabel = 'Cash on Delivery';
            else if (paymentMethod === 'BANK_DEPOSIT') methodLabel = 'Bank Deposit';
            detailPaymentMethod.innerText = methodLabel;
        }

        if (paymentStatusBadge) {
            paymentStatusBadge.innerText = paymentStatus;
            paymentStatusBadge.className = 'order-status-badge'; // reset
            
            if (paymentStatus.toUpperCase() === 'PENDING') {
                paymentStatusBadge.classList.add('status-pending');
            } else if (paymentStatus.toUpperCase() === 'VERIFYING') {
                paymentStatusBadge.classList.add('status-verifying');
            } else if (paymentStatus.toUpperCase() === 'PAID') {
                paymentStatusBadge.classList.add('status-delivered');
            } else {
                paymentStatusBadge.classList.add('status-cancelled');
            }
        }

        // 7. Conditional Bank Slip Upload & Verification Status Display
        setupPaymentFlow(orderId, paymentMethod, paymentStatus, order.paymentProofUrl);

    } catch (error) {
        console.error("Error loading order details:", error);
        if (orderDetailsSection) {
            orderDetailsSection.innerHTML = `
                <div style="text-align: center; padding: 3rem 0;">
                    <h2 style="font-size: 2rem; color: var(--error-color); margin-bottom: 1rem;">System Error</h2>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">There was an error loading the order details. Please try again.</p>
                </div>
            `;
        }
    }
}

function setupPaymentFlow(orderId, paymentMethod, paymentStatus, paymentProofUrl) {
    const slipUploadSection = document.getElementById('slipUploadSection');
    const paymentVerifyingSection = document.getElementById('paymentVerifyingSection');
    const paymentCompletedSection = document.getElementById('paymentCompletedSection');
    
    const bankSlipInput = document.getElementById('bankSlipInput');
    const uploadWrapper = document.getElementById('uploadWrapper');
    const uploadSpinner = document.getElementById('uploadSpinner');
    const uploadStatus = document.getElementById('uploadStatus');
    const uploadInstruction = document.getElementById('uploadInstruction');
    
    const proofPreviewContainer = document.getElementById('proofPreviewContainer');
    const proofPreviewImage = document.getElementById('proofPreviewImage');

    // Reset visibility
    if (slipUploadSection) slipUploadSection.style.display = 'none';
    if (paymentVerifyingSection) paymentVerifyingSection.style.display = 'none';
    if (paymentCompletedSection) paymentCompletedSection.style.display = 'none';
    if (proofPreviewContainer) proofPreviewContainer.style.display = 'none';

    if (paymentMethod === 'BANK_DEPOSIT') {
        const statusUpper = paymentStatus.toUpperCase();

        if (statusUpper === 'PENDING') {
            if (slipUploadSection) slipUploadSection.style.display = 'block';
            
            if (bankSlipInput) {
                bankSlipInput.addEventListener('change', async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    // Basic size validation (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        alert("File is too large. Please select an image smaller than 5MB.");
                        return;
                    }

                    try {
                        // Start upload state
                        if (uploadSpinner) uploadSpinner.style.display = 'block';
                        if (bankSlipInput) bankSlipInput.disabled = true;
                        if (uploadWrapper) {
                            uploadWrapper.style.opacity = '0.6';
                            uploadWrapper.style.pointerEvents = 'none';
                        }
                        if (uploadStatus) uploadStatus.innerText = "Uploading bank slip...";

                        // Upload to Storage: /payment_slips/{userId}/{orderId}
                        const userId = auth.currentUser ? auth.currentUser.uid : 'guest';
                        const storagePath = `payment_slips/${userId}/${orderId}`;
                        const storageRef = ref(storage, storagePath);

                        await uploadBytes(storageRef, file);
                        const downloadUrl = await getDownloadURL(storageRef);

                        // Update Firestore Order Document
                        const docRef = doc(db, "orders", orderId);
                        await updateDoc(docRef, {
                            paymentProofUrl: downloadUrl,
                            paymentStatus: 'VERIFYING'
                        });

                        if (uploadStatus) {
                            uploadStatus.innerText = "Upload successful! Verifying...";
                            uploadStatus.style.color = "var(--success-color)";
                        }

                        // Short delay before refreshing UI
                        setTimeout(() => {
                            loadOrderDetails(orderId);
                        }, 1500);

                    } catch (err) {
                        console.error("Upload error:", err);
                        alert("Failed to upload payment proof. Please try again.");
                        
                        // Reset upload UI state
                        if (uploadSpinner) uploadSpinner.style.display = 'none';
                        if (bankSlipInput) bankSlipInput.disabled = false;
                        if (uploadWrapper) {
                            uploadWrapper.style.opacity = '1';
                            uploadWrapper.style.pointerEvents = 'auto';
                        }
                        if (uploadStatus) {
                            uploadStatus.innerText = "Upload failed. Try again.";
                            uploadStatus.style.color = "var(--error-color)";
                        }
                    }
                });
            }
        } else if (statusUpper === 'VERIFYING') {
            if (paymentVerifyingSection) paymentVerifyingSection.style.display = 'block';
            if (paymentProofUrl && proofPreviewImage && proofPreviewContainer) {
                // If it is an image, preview it
                const isPdf = paymentProofUrl.toLowerCase().includes('.pdf?') || paymentProofUrl.toLowerCase().endsWith('.pdf');
                if (!isPdf) {
                    proofPreviewImage.src = paymentProofUrl;
                    proofPreviewContainer.style.display = 'block';
                }
            }
        } else if (statusUpper === 'PAID') {
            if (paymentCompletedSection) paymentCompletedSection.style.display = 'block';
        }
    }
}
