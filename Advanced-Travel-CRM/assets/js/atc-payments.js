/**
 * ========================================
 * FILE: atc-payments.js
 * Payment gateway integration
 * ========================================
 */
(function($) {
    'use strict';
    
    const Payments = {
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            $(document).on('submit', '.atc-booking-form', (e) => {
                e.preventDefault();
                this.processBooking(e.target);
            });
        },
        
        async processBooking(form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            if (!atcPayments.gateway || !data.price_total || data.price_total == 0) {
                return this.submitBooking(data);
            }
            
            try {
                const config = typeof atcVars !== 'undefined' ? atcVars : null;
                if (!config) {
                    console.error('atcVars not available');
                    alert('Configuration error. Please refresh the page.');
                    return;
                }
                // Create payment
                const response = await fetch(`${config.restUrl}payment/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.restNonce
                    },
                    body: JSON.stringify({
                        booking_id: data.booking_id,
                        amount: data.price_total
                    })
                });
                
                const result = await response.json();
                
                if (atcPayments.gateway === 'razorpay') {
                    this.initRazorpay(result, data);
                } else if (atcPayments.gateway === 'stripe') {
                    this.initStripe(result, data);
                }
            } catch (error) {
                console.error('Payment error:', error);
                alert('Payment failed. Please try again.');
            }
        },
        
        initRazorpay(paymentData, bookingData) {
            const options = {
                key: atcPayments.razorpayKey,
                amount: paymentData.amount * 100,
                currency: paymentData.currency,
                name: bookingData.customer_name,
                description: 'Booking Payment',
                order_id: paymentData.order_id,
                handler: (response) => {
                    this.verifyPayment('razorpay', response);
                },
                prefill: {
                    name: bookingData.customer_name,
                    email: bookingData.customer_email,
                    contact: bookingData.customer_phone
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        },
        
        async verifyPayment(gateway, paymentResponse) {
            try {
                const config = typeof atcVars !== 'undefined' ? atcVars : null;
                if (!config) {
                    console.error('atcVars not available');
                    alert('Configuration error. Please refresh the page.');
                    return;
                }
                const response = await fetch(`${config.restUrl}payment/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.restNonce
                    },
                    body: JSON.stringify({
                        gateway: gateway,
                        ...paymentResponse
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Payment successful! Booking confirmed.');
                    window.location.reload();
                } else {
                    alert('Payment verification failed.');
                }
            } catch (error) {
                console.error('Verification error:', error);
                alert('Payment verification failed.');
            }
        }
    };
    
    $(document).ready(() => Payments.init());
    
})(jQuery);
