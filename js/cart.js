// Cart Management UI Logic

document.addEventListener('DOMContentLoaded', () => {
    
    // Qty Counter Logic
    const qtyButtons = document.querySelectorAll('.qty-btn');
    
    qtyButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const span = e.target.parentElement.querySelector('span');
            let val = parseInt(span.innerText);
            
            if(e.target.innerText === '+') {
                val++;
            } else if (e.target.innerText === '-') {
                if(val > 1) val--;
            }
            
            span.innerText = val;
            updateTotal();
        });
    });

    // Remove item logic
    const removeButtons = document.querySelectorAll('.remove-btn');
    removeButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('.cart-item').remove();
            updateTotal();
            // Update badge
            const badge = document.querySelector('.cart-badge');
            let count = parseInt(badge.innerText);
            if(count > 0) badge.innerText = count - 1;
        });
    });

    function updateTotal() {
        console.log("Updating total via mock logic...");
    }

});
