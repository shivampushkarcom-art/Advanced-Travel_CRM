/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Premium Booking Wizard
 * Step-by-step booking flow
 * ═══════════════════════════════════════════════════════════════════
 */

(function($) {
    'use strict';
    
    const PremiumBooking = {
        currentStep: 1,
        totalSteps: 3,
        packageData: null,
        bookingData: {},
        
        init() {
            this.bindEvents();
            this.loadUrlParams();
        },
        
        bindEvents() {
            // Open booking modal - support both class and data-action
            $(document).on('click', '.atc-book-now, [data-action="book-now"]', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(e.currentTarget);
                const packageId = $btn.data('package-id');
                if (packageId) {
                    this.open(packageId);
                } else {
                    console.error('Package ID not found on booking button');
                }
            });
            
            // Close modal
            $(document).on('click', '.atc-premium-booking-close, .atc-premium-booking-overlay', () => {
                this.close();
            });
            
            // Navigation
            $(document).on('click', '#atc-booking-next-btn', () => {
                this.nextStep();
            });
            
            $(document).on('click', '#atc-booking-back-btn', () => {
                this.prevStep();
            });
            
            $(document).on('click', '#atc-booking-submit-btn', () => {
                this.submitBooking();
            });
            
            // Form changes
            $(document).on('input change', '#atc-booking-traveler-form input, #atc-booking-traveler-form select, #atc-booking-traveler-form textarea', () => {
                this.updateBookingData();
            });
            
            // Escape key to close
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('#atc-premium-booking-modal').hasClass('active')) {
                    this.close();
                }
            });
        },
        
        loadUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('book')) {
                const packageId = urlParams.get('book');
                this.open(packageId);
            }
        },
        
        async open(packageId) {
            if (!packageId) {
                console.error('Package ID is required');
                return;
            }
            
            this.currentStep = 1;
            this.bookingData = { package_id: packageId };
            
            // Show modal
            $('#atc-premium-booking-modal').addClass('active');
            $('body').addClass('atc-modal-open');
            
            // Load package details
            await this.loadPackageDetails(packageId);
            this.updateStep(1);
        },
        
        close() {
            $('#atc-premium-booking-modal').removeClass('active');
            $('body').removeClass('atc-modal-open');
            this.reset();
        },
        
        reset() {
            this.currentStep = 1;
            this.packageData = null;
            this.bookingData = {};
            $('.atc-booking-step').removeClass('active completed');
            $('.atc-booking-form-section').removeClass('active');
            $('#atc-booking-traveler-form')[0]?.reset();
        },
        
        async loadPackageDetails(packageId) {
            const container = $('#atc-booking-package-details');
            container.html(`
                <div class="atc-premium-loading">
                    <div class="atc-premium-spinner"></div>
                    <p>Loading package details...</p>
                </div>
            `);
            
            // Get config from multiple sources
            let config = typeof atcPremiumBookingConfig !== 'undefined' && atcPremiumBookingConfig ? atcPremiumBookingConfig : null;
            if (!config && typeof atcPremiumBooking !== 'undefined' && atcPremiumBooking && atcPremiumBooking.restUrl) {
                config = atcPremiumBooking;
            }
            if (!config || !config.restUrl) {
                console.error('REST URL not available for booking');
                container.html(`
                    <div class="atc-premium-empty">
                        <div class="atc-premium-empty-icon">⚠️</div>
                        <h3>Configuration error</h3>
                        <p>Please refresh the page.</p>
                    </div>
                `);
                return;
            }
            
            // Ensure restUrl is properly formatted
            let restUrl = String(config.restUrl || '');
            if (!restUrl.endsWith('/')) {
                restUrl += '/';
            }
            
            // Ensure packageId is numeric (use id, not package_id string)
            const numericId = parseInt(packageId, 10);
            if (isNaN(numericId) || numericId <= 0) {
                console.error('Invalid package ID:', packageId);
                container.html(`
                    <div class="atc-premium-empty">
                        <div class="atc-premium-empty-icon">⚠️</div>
                        <h3>Invalid package ID</h3>
                        <p>Please try again.</p>
                    </div>
                `);
                return;
            }
            
            const apiUrl = `${restUrl}package/${numericId}`;
            console.log('Loading package from:', apiUrl);
            
            try {
                const response = await fetch(apiUrl, {
                    headers: {
                        'X-WP-Nonce': config.nonce
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Handle both response formats
                let packageData = null;
                if (data.success && data.package) {
                    // Format: {success: true, package: {...}}
                    packageData = data.package;
                } else if (data.id) {
                    // Format: {id: 1, name: "...", ...}
                    packageData = data;
                } else {
                    throw new Error('Invalid package data format');
                }
                
                if (packageData && (packageData.id || packageData.name)) {
                    this.packageData = packageData;
                    this.renderPackageDetails(packageData);
                    this.updateTotal();
                } else {
                    container.html(`
                        <div class="atc-premium-empty">
                            <div class="atc-premium-empty-icon">⚠️</div>
                            <h3>Package not found</h3>
                            <p>Please try again or contact support.</p>
                        </div>
                    `);
                }
            } catch (error) {
                console.error('Failed to load package:', error);
                console.error('API URL:', apiUrl);
                console.error('Package ID:', packageId, 'Numeric ID:', numericId);
                container.html(`
                    <div class="atc-premium-empty">
                        <div class="atc-premium-empty-icon">⚠️</div>
                        <h3>Failed to load package</h3>
                        <p>Please try again later.</p>
                        <p style="font-size: 12px; color: #999; margin-top: 10px;">Error: ${error.message}</p>
                    </div>
                `);
            }
        },
        
        renderPackageDetails(pkg) {
            const price = parseFloat(pkg.price || 0);
            const originalPrice = parseFloat(pkg.original_price || 0);
            const imageUrl = pkg.image_url || pkg.images || '';
            
            const html = `
                <div class="atc-booking-package-card" style="display: flex; gap: 20px; padding: 20px; background: #f8fafc; border-radius: 12px; margin-bottom: 20px;">
                    ${imageUrl ? `
                        <div style="width: 200px; height: 150px; border-radius: 10px; overflow: hidden; flex-shrink: 0;">
                            <img src="${imageUrl}" alt="${pkg.name || 'Package'}" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    ` : ''}
                    <div style="flex: 1;">
                        <h3 style="margin: 0 0 10px 0; font-size: 20px; color: #0f172a;">${pkg.name || 'Package'}</h3>
                        ${pkg.destination ? `<p style="margin: 0 0 10px 0; color: #64748b;">📍 ${pkg.destination}</p>` : ''}
                        ${pkg.duration_days ? `<p style="margin: 0 0 10px 0; color: #64748b;">⏱️ ${pkg.duration_days} Days</p>` : ''}
                        ${pkg.description ? `<p style="margin: 10px 0; color: #475569; line-height: 1.6;">${this.truncate(pkg.description, 150)}</p>` : ''}
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                            ${originalPrice > price ? `
                                <span style="text-decoration: line-through; color: #94a3b8; margin-right: 10px;">
                                    ${atcPremiumBooking.currency}${this.formatPrice(originalPrice)}
                                </span>
                            ` : ''}
                            <span style="font-size: 24px; font-weight: 700; color: #0f172a;">
                                ${atcPremiumBooking.currency}${this.formatPrice(price)}
                            </span>
                            <span style="color: #64748b; margin-left: 5px;">per person</span>
                        </div>
                    </div>
                </div>
            `;
            
            $('#atc-booking-package-details').html(html);
            this.bookingData.price_total = price;
            this.bookingData.service = pkg.service_key || pkg.service || '';
        },
        
        updateStep(step) {
            this.currentStep = step;
            
            // Update step indicators
            $('.atc-booking-step').each(function() {
                const stepNum = parseInt($(this).data('step'));
                $(this).removeClass('active completed');
                
                if (stepNum < step) {
                    $(this).addClass('completed');
                } else if (stepNum === step) {
                    $(this).addClass('active');
                }
            });
            
            // Update form sections
            $('.atc-booking-form-section').removeClass('active');
            $(`.atc-booking-form-section[data-step="${step}"]`).addClass('active');
            
            // Update navigation buttons
            $('#atc-booking-back-btn').toggle(step > 1);
            $('#atc-booking-next-btn').toggle(step < this.totalSteps);
            $('#atc-booking-submit-btn').toggle(step === this.totalSteps);
        },
        
        nextStep() {
            if (this.currentStep < this.totalSteps) {
                // Validate current step
                if (this.validateStep(this.currentStep)) {
                    this.updateStep(this.currentStep + 1);
                    
                    // Load payment section if on step 3
                    if (this.currentStep === 3) {
                        this.loadPaymentSection();
                    }
                }
            }
        },
        
        prevStep() {
            if (this.currentStep > 1) {
                this.updateStep(this.currentStep - 1);
            }
        },
        
        validateStep(step) {
            if (step === 1) {
                // Package details - always valid if package is loaded
                return this.packageData !== null;
            } else if (step === 2) {
                // Traveler information
                const form = $('#atc-booking-traveler-form')[0];
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                return true;
            }
            return true;
        },
        
        updateBookingData() {
            const form = $('#atc-booking-traveler-form');
            if (!form.length) return;
            
            const formData = new FormData(form[0]);
            
            // Get adults and children from form (not from bookingData)
            const adults = parseInt(formData.get('adults') || 2);
            const children = parseInt(formData.get('children') || 0);
            
            // Update bookingData with form values
            this.bookingData.adults = adults;
            this.bookingData.children = children;
            this.bookingData.customer_name = formData.get('customer_name') || '';
            this.bookingData.customer_email = formData.get('customer_email') || '';
            this.bookingData.customer_phone = formData.get('customer_phone') || '';
            this.bookingData.special_requests = formData.get('special_requests') || '';
            
            // Update total based on adults/children
            if (this.packageData) {
                const basePrice = parseFloat(this.packageData.price || 0);
                const childDiscount = 0.5; // 50% discount for children
                
                // Calculate: base price per adult + (base price * child discount) per child
                const total = (basePrice * adults) + (basePrice * children * childDiscount);
                this.bookingData.price_total = total;
                this.updateTotal();
            }
        },
        
        updateTotal() {
            // Get config for currency
            let config = typeof atcPremiumBookingConfig !== 'undefined' && atcPremiumBookingConfig ? atcPremiumBookingConfig : null;
            if (!config && typeof atcPremiumBooking !== 'undefined' && atcPremiumBooking && atcPremiumBooking.currency) {
                config = atcPremiumBooking;
            }
            const currency = (config && config.currency) ? config.currency : '₹';
            
            const total = this.bookingData.price_total || 0;
            $('#atc-booking-total-amount').text(`${currency}${this.formatPrice(total)}`);
        },
        
        loadPaymentSection() {
            const container = $('#atc-booking-payment-section');
            const total = this.bookingData.price_total || 0;
            
            if (atcPremiumBooking.paymentEnabled == 1 && total > 0) {
                container.html(`
                    <div style="padding: 20px;">
                        <h3 style="margin-bottom: 20px;">Payment Options</h3>
                        <div style="display: grid; gap: 15px;">
                            <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.3s;">
                                <input type="radio" name="payment_method" value="razorpay" checked style="margin-right: 10px;">
                                <div>
                                    <strong>Razorpay</strong>
                                    <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Pay securely with Razorpay</p>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.3s;">
                                <input type="radio" name="payment_method" value="stripe" style="margin-right: 10px;">
                                <div>
                                    <strong>Stripe</strong>
                                    <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Pay with credit/debit card</p>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.3s;">
                                <input type="radio" name="payment_method" value="manual" style="margin-right: 10px;">
                                <div>
                                    <strong>Manual Payment</strong>
                                    <p style="margin: 5px 0 0 0; color: #64748b; font-size: 14px;">Bank transfer, UPI, or Cash</p>
                                </div>
                            </label>
                        </div>
                    </div>
                `);
            } else {
                container.html(`
                    <div style="padding: 20px; text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 20px;">✅</div>
                        <h3 style="margin-bottom: 10px;">No Payment Required</h3>
                        <p style="color: #64748b;">Your booking will be confirmed after submission.</p>
                    </div>
                `);
            }
        },
        
        async submitBooking() {
            if (!this.validateStep(this.currentStep)) {
                return;
            }
            
            // Get config for booking
            let bookingConfig = typeof atcPremiumBookingConfig !== 'undefined' && atcPremiumBookingConfig ? atcPremiumBookingConfig : null;
            if (!bookingConfig && typeof atcPremiumBooking !== 'undefined' && atcPremiumBooking && atcPremiumBooking.restUrl) {
                bookingConfig = atcPremiumBooking;
            }
            if (!bookingConfig || !bookingConfig.restUrl) {
                console.error('REST URL not available for booking submission');
                this.showError('Configuration error. Please refresh the page.');
                return;
            }
            
            // Ensure restUrl is properly formatted
            let bookingRestUrl = String(bookingConfig.restUrl || '');
            if (!bookingRestUrl.endsWith('/')) {
                bookingRestUrl += '/';
            }
            
            // Collect all booking data
            const form = $('#atc-booking-traveler-form');
            const formData = new FormData(form[0]);
            
            // Get adults and children from form
            const adults = parseInt(formData.get('adults') || this.bookingData.adults || 2);
            const children = parseInt(formData.get('children') || this.bookingData.children || 0);
            
            // Recalculate total based on actual adults/children
            let priceTotal = 0;
            if (this.packageData) {
                const basePrice = parseFloat(this.packageData.price || 0);
                const childDiscount = 0.5; // 50% discount for children
                priceTotal = (basePrice * adults) + (basePrice * children * childDiscount);
            }
            
            const bookingPayload = {
                ...this.bookingData,
                customer_name: formData.get('customer_name'),
                customer_email: formData.get('customer_email'),
                customer_phone: formData.get('customer_phone'),
                adults: adults,
                children: children,
                special_requests: formData.get('special_requests') || '',
                price_total: priceTotal,
                package_id: this.packageData ? (this.packageData.id || this.packageData.package_id) : null,
            };
            
            // Show loading
            const submitBtn = $('#atc-booking-submit-btn');
            const originalText = submitBtn.text();
            submitBtn.text('Processing...').prop('disabled', true);
            
            try {
                const response = await fetch(`${bookingRestUrl}booking/premium`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': bookingConfig.nonce
                    },
                    body: JSON.stringify(bookingPayload)
                });
                
                // Handle 401 Unauthorized (login required)
                if (response.status === 401) {
                    const errorData = await response.json().catch(() => ({}));
                    const loginUrl = (errorData.data && errorData.data.login_url) ? errorData.data.login_url : '/login/';
                    if (confirm('Please login to create a booking. Do you want to go to the login page?')) {
                        window.location.href = loginUrl + '?redirect=' + encodeURIComponent(window.location.href);
                    }
                    submitBtn.text(originalText).prop('disabled', false);
                    return;
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success
                    this.showSuccess(result);
                    
                    // Handle payment if required
                    const paymentEnabled = bookingConfig.paymentEnabled || 0;
                    if (paymentEnabled == 1 && result.price_total > 0) {
                        const paymentMethod = $('input[name="payment_method"]:checked').val();
                        if (paymentMethod && paymentMethod !== 'manual') {
                            // Initiate payment
                            this.initiatePayment(result.record_id, result.price_total, paymentMethod);
                        } else {
                            // Manual payment - show instructions
                            this.showManualPaymentInstructions(result);
                        }
                    }
                } else {
                    this.showError(result.message || 'Booking failed. Please try again.');
                    submitBtn.text(originalText).prop('disabled', false);
                }
            } catch (error) {
                console.error('Booking error:', error);
                this.showError('Failed to submit booking. Please try again.');
                submitBtn.text(originalText).prop('disabled', false);
            }
        },
        
        showSuccess(result) {
            $('.atc-premium-booking-body').html(`
                <div style="padding: 60px 20px; text-align: center;">
                    <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
                    <h2 style="margin-bottom: 10px; color: #0f172a;">Booking Confirmed!</h2>
                    <p style="color: #64748b; margin-bottom: 30px;">Your booking ID is: <strong>${result.booking_id}</strong></p>
                    <p style="color: #64748b; margin-bottom: 30px;">We've sent a confirmation email to your registered email address.</p>
                    <button class="atc-btn-premium atc-btn-premium-primary" onclick="location.reload()">
                        Book Another Package
                    </button>
                </div>
            `);
            
            $('.atc-premium-booking-footer').hide();
        },
        
        showError(message) {
            $('.atc-premium-booking-body').html(`
                <div style="padding: 60px 20px; text-align: center;">
                    <div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>
                    <h2 style="margin-bottom: 10px; color: #0f172a;">Booking Failed</h2>
                    <p style="color: #64748b; margin-bottom: 30px;">${message}</p>
                    <button class="atc-btn-premium atc-btn-premium-primary" onclick="location.reload()">
                        Try Again
                    </button>
                </div>
            `);
        },
        
        showManualPaymentInstructions(result) {
            $('.atc-premium-booking-body').html(`
                <div style="padding: 40px 20px;">
                    <h3 style="margin-bottom: 20px;">Manual Payment Instructions</h3>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                        <p style="margin-bottom: 10px;"><strong>Booking ID:</strong> ${result.booking_id}</p>
                        <p style="margin-bottom: 10px;"><strong>Amount:</strong> ${atcPremiumBooking.currency}${this.formatPrice(result.price_total)}</p>
                        <p style="margin-bottom: 20px;"><strong>Payment Methods:</strong> Bank Transfer, UPI, Cash</p>
                        <p style="color: #64748b;">Please complete the payment and upload the payment proof. Our team will confirm your booking within 24 hours.</p>
                    </div>
                    <button class="atc-btn-premium atc-btn-premium-primary" onclick="location.reload()">
                        Done
                    </button>
                </div>
            `);
        },
        
        initiatePayment(bookingId, amount, method) {
            // This will be handled by the payment system
            console.log('Initiating payment:', { bookingId, amount, method });
            // Trigger payment event
            window.dispatchEvent(new CustomEvent('atc-payment-initiate', {
                detail: { bookingId, amount, method }
            }));
        },
        
        // Utility functions
        truncate(str, length) {
            if (!str) return '';
            return str.length > length ? str.substring(0, length) + '...' : str;
        },
        
        formatPrice(price) {
            return parseFloat(price).toLocaleString('en-IN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(() => {
        PremiumBooking.init();
    });
    
    // Expose globally
    window.atcPremiumBooking = PremiumBooking;
    
})(jQuery);

