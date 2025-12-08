async function submitSearch(form) {
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    
    try {
        const config = typeof atcVars !== 'undefined' ? atcVars : (typeof atc_vars !== 'undefined' ? atc_vars : null);
        if (!config) {
            console.error('atcVars not available');
            showMessage(form, 'Configuration error. Please refresh the page.', 'error');
            return;
        }
        const response = await fetch(config.restUrl + 'search-lead', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': config.restNonce,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        console.log('Search logged:', result);
        
        // Show success message
        showMessage(form, 'Search saved! We will get back to you soon.', 'success');
        
    } catch(err) {
        console.error('Search error:', err);
        showMessage(form, 'Failed to submit search. Please try again.', 'error');
    }
}

async function submitBooking(form) {
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    
    // Validate required fields
    if (!payload.customer_name || !payload.customer_email) {
        showMessage(form, 'Please fill in all required fields.', 'error');
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    
    try {
        const config = typeof atcVars !== 'undefined' ? atcVars : (typeof atc_vars !== 'undefined' ? atc_vars : null);
        if (!config) {
            console.error('atcVars not available');
            showMessage(form, 'Configuration error. Please refresh the page.', 'error');
            return;
        }
        
        // Step 1: Create booking
        const response = await fetch(config.restUrl + 'book', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': config.restNonce,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // ⭐ FIX #9: Check if payment is enabled and required
            const paymentEnabled = config.paymentEnabled == 1;
            const amount = parseFloat(result.price_total || 0);
            
            if (paymentEnabled && amount > 0) {
                // Step 2: Initiate payment
                await initiatePayment(result.record_id, amount, config);
            } else {
                // No payment required - show success
                showMessage(form, `✅ Booking successful! Your booking ID is: ${result.booking_id}`, 'success');
                form.reset();
                
                // Update total display
                const totalEl = form.querySelector('.atc-total');
                if (totalEl) totalEl.textContent = 'Total: ' + config.currencySymbol + ' 0.00';
            }
        } else {
            showMessage(form, result.message || 'Booking failed. Please try again.', 'error');
        }
        
    } catch(err) {
        console.error('Booking error:', err);
        showMessage(form, 'Failed to submit booking. Please try again.', 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

// ⭐ NEW FUNCTION: Initiate Payment
async function initiatePayment(bookingId, amount, config) {
    if (!config) {
        config = typeof atcVars !== 'undefined' ? atcVars : null;
        if (!config) {
            console.error('atcVars not available for payment');
            return;
        }
    }
    try {
        const response = await fetch(config.restUrl + 'payment/create', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': config.restNonce,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                booking_id: bookingId,
                amount: amount
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Payment creation successful - trigger payment modal
            // This will be handled by atc-payments.js
            window.dispatchEvent(new CustomEvent('atc-payment-ready', { 
                detail: result 
            }));
        }
    } catch (error) {
        console.error('Payment initiation failed:', error);
        alert('Booking created but payment failed. Please contact support.');
    }
}