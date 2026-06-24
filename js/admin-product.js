import { db, collection, addDoc, storage, ref, uploadBytes, getDownloadURL } from './firebase-config.js';

const addProductForm = document.getElementById('addProductForm');

// Helper to show custom toast notifications
function showToast(message, isError = false) {
    const toast = document.getElementById('toastNotification');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');

    if (!toast) return;

    if (isError) {
        toast.classList.add('error');
        if (toastIcon) toastIcon.textContent = '✕';
        if (toastTitle) toastTitle.textContent = 'Error';
    } else {
        toast.classList.remove('error');
        if (toastIcon) toastIcon.textContent = '✓';
        if (toastTitle) toastTitle.textContent = 'Success';
    }

    if (toastMessage) toastMessage.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

if (addProductForm) {
    addProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        try {
            const name = document.getElementById('pname').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const discountInput = document.getElementById('discount');
            const discount = discountInput && discountInput.value ? parseFloat(discountInput.value) : 0;
            
            if (isNaN(discount) || discount < 0 || discount > 100) {
                throw new Error('Discount percentage must be between 0 and 100.');
            }

            const stock = parseInt(document.getElementById('stock').value);
            const category = document.getElementById('category').value;
            const description = document.getElementById('desc').value.trim();

            // Get selected sizes
            const sizeCheckboxes = document.querySelectorAll('input[name="sizes"]:checked');
            const sizes = Array.from(sizeCheckboxes).map(cb => cb.value);

            let imageUrl = '';

            // Upload image to Firebase Storage if one was selected (optional)
            const fileInput = document.getElementById('productImage');
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                submitBtn.textContent = 'Uploading Image...';

                const fileExtension = file.name.split('.').pop();
                const uniqueFileName = `${Date.now()}_${Math.random().toString(36).substring(2, 9)}.${fileExtension}`;
                const imageRef = ref(storage, `products/${uniqueFileName}`);

                const uploadResult = await uploadBytes(imageRef, file);
                imageUrl = await getDownloadURL(uploadResult.ref);
            }

            submitBtn.textContent = 'Saving Product...';

            // Save product to Firestore
            const productData = {
                name,
                price,
                discount: discount,
                stock,
                category,
                sizes,
                description,
                imageUrl,
                createdAt: new Date()
            };

            await addDoc(collection(db, 'products'), productData);

            showToast('Product successfully added!', false);

            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 2000);

        } catch (error) {
            console.error('Error adding product:', error.code, error.message);
            showToast(`Failed to save product: ${error.code || error.message}`, true);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Product';
        }
    });
}
