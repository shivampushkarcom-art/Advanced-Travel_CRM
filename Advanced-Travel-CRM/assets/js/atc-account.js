/**
 * Advanced Travel CRM - Customer Account JavaScript
 * Handles all customer portal interactions
 */
(function($) {
    'use strict';
    
    const AccountDashboard = {
        profileMenuInitialized: false,
        
        init() {
            this.bindEvents();
            this.initPasswordStrength();
            this.initOTPInput();
            this.initResendOTP();
        },
        
        bindEvents() {
            // View booking details
            $(document).on('click', '.atc-view-booking-details, .atc-view-booking, .atc-view-booking-details', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('booking-id') || $(this).closest('[data-booking-id]').data('booking-id');
                if (bookingId) {
                    AccountDashboard.showBookingDetails(bookingId);
                }
            });
            
            // Cancel booking
            $(document).on('click', '.atc-cancel-booking, .atc-request-cancellation', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('booking-id') || $(this).closest('[data-booking-id]').data('booking-id');
                if (bookingId) {
                    AccountDashboard.cancelBooking(bookingId);
                } else {
                    console.error('Booking ID not found');
                }
            });
            
            // Download invoice
            $(document).on('click', '.atc-download-invoice', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('booking-id') || $(this).closest('[data-booking-id]').data('booking-id');
                if (bookingId) {
                    AccountDashboard.downloadInvoice(bookingId);
                }
            });
            
            // Resend email
            $(document).on('click', '.atc-resend-email', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('booking-id') || $(this).closest('[data-booking-id]').data('booking-id');
                if (bookingId) {
                    AccountDashboard.resendEmail(bookingId);
                }
            });
            
            // Password confirmation match
            $(document).on('input', '#reg_password_confirm, #confirm_password', function() {
                const password = $(this).closest('form').find('input[type="password"]').first().val();
                const confirm = $(this).val();
                
                if (confirm && password !== confirm) {
                    $(this).css('border-color', '#ef4444');
                } else {
                    $(this).css('border-color', '#10b981');
                }
            });
            
            // Ensure logout links work
            $(document).on('click', 'a[href*="wp-logout"], a[href*="logout"]', function(e) {
                // Let default behavior work for logout
                return true;
            });
        },
        
        /**
         * Initialize OTP input (auto-focus, auto-submit)
         */
        initOTPInput() {
            const otpInput = $('#atc-otp-verification-form #otp_code');
            if (otpInput.length) {
                // Auto-focus
                otpInput.focus();
                
                // Only allow numbers
                otpInput.on('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Auto-submit when 6 digits entered
                    if (this.value.length === 6) {
                        $(this).closest('form').submit();
                    }
                });
                
                // Paste handler
                otpInput.on('paste', function(e) {
                    e.preventDefault();
                    const paste = (e.originalEvent || e).clipboardData.getData('text');
                    const numbers = paste.replace(/[^0-9]/g, '').substring(0, 6);
                    $(this).val(numbers);
                    if (numbers.length === 6) {
                        $(this).closest('form').submit();
                    }
                });
            }
        },
        
        /**
         * Initialize Resend OTP button (WhatsApp OTP via Phone)
         */
        initResendOTP() {
            $(document).on('click', '.atc-resend-otp-btn', function(e) {
                e.preventDefault();
                const btn = $(this);
                // Use phone instead of email (WhatsApp OTP)
                const phone = btn.data('phone') || btn.data('email'); // Support both for backward compatibility
                
                if (!phone) {
                    alert('Phone number is required');
                    return;
                }
                
                // Disable button
                btn.prop('disabled', true).text('Sending...');
                
                // AJAX request
                $.ajax({
                    url: atcAccount.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'atc_resend_verification_otp',
                        nonce: atcAccount.nonce,
                        phone: phone
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || 'New OTP code sent! Please check your WhatsApp.');
                            btn.text('Resend OTP');
                            
                            // Enable after 60 seconds
                            setTimeout(function() {
                                btn.prop('disabled', false);
                            }, 60000);
                        } else {
                            alert(response.data.message || 'Failed to send OTP. Please try again.');
                            btn.prop('disabled', false).text('Resend OTP');
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        btn.prop('disabled', false).text('Resend OTP');
                    }
                });
            });
        },
        
        /**
         * Show booking details in modal
         */
        async showBookingDetails(bookingId) {
            if (!bookingId || bookingId === '0' || bookingId === '') {
                console.error('Invalid booking ID:', bookingId);
                alert('Invalid booking ID. Please try again.');
                return;
            }
            
            // Show loading indicator
            const loadingMsg = $('<div class="atc-loading">Loading booking details...</div>');
            $('body').append(loadingMsg);
            
            try {
                const response = await $.ajax({
                    url: atcAccount.ajaxUrl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'atc_get_booking_details',
                        nonce: atcAccount?.nonce || '',
                        booking_id: parseInt(bookingId)
                    }
                });
                
                loadingMsg.remove();
                
                if (response && response.success && response.data) {
                    this.renderBookingModal(response.data);
                } else {
                    const errorMsg = response?.data?.message || atcAccount?.strings?.error || 'Failed to load booking details';
                    alert(errorMsg);
                    console.error('Booking details error:', response);
                }
            } catch (error) {
                loadingMsg.remove();
                console.error('Error fetching booking details:', error);
                alert(atcAccount?.strings?.error || 'An error occurred while loading booking details. Please try again.');
            }
        },
        
        /**
         * Render booking details modal
         */
        renderBookingModal(booking) {
            if (!booking) {
                alert('Invalid booking data');
                return;
            }
            
            // Helper function to safely get values
            const getValue = (val, defaultVal = 'N/A') => {
                return val && val !== 'null' && val !== 'undefined' ? val : defaultVal;
            };
            
            // Format date safely
            const formatDate = (dateStr) => {
                if (!dateStr || dateStr === 'null' || dateStr === 'undefined') return 'N/A';
                try {
                    const date = new Date(dateStr);
                    if (isNaN(date.getTime())) return dateStr; // Return original if invalid
                    return date.toLocaleDateString();
                } catch (e) {
                    return dateStr;
                }
            };
            
            const bookingId = getValue(booking.booking_id || booking.id, 'N/A');
            const service = getValue(booking.service, 'N/A');
            const status = getValue(booking.status, 'pending');
            const currency = getValue(booking.currency, 'INR');
            const priceTotal = parseFloat(booking.price_total || 0).toFixed(2);
            const paymentStatus = getValue(booking.payment_status, 'unpaid');
            const bookingDate = booking.created_at_formatted || formatDate(booking.created_at);
            const travelDate = booking.travel_date ? formatDate(booking.travel_date) : null;
            const destination = getValue(booking.destination, '');
            
            const modal = $('<div class="atc-modal atc-booking-modal">').html(`
                <div class="atc-modal-overlay"></div>
                <div class="atc-modal-content">
                    <div class="atc-modal-header">
                        <h2>📋 Booking Details</h2>
                        <button class="atc-modal-close">&times;</button>
                    </div>
                    <div class="atc-modal-body">
                        <div class="atc-booking-details">
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Booking ID:</span>
                                <span class="atc-detail-value"><strong>${bookingId}</strong></span>
                            </div>
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Service:</span>
                                <span class="atc-detail-value">${this.capitalize(service)}</span>
                            </div>
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Status:</span>
                                <span class="atc-detail-value">
                                    <span class="atc-status-badge atc-status-${status}">
                                        ${this.capitalize(status)}
                                    </span>
                                </span>
                            </div>
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Amount:</span>
                                <span class="atc-detail-value"><strong>${currency} ${priceTotal}</strong></span>
                            </div>
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Payment Status:</span>
                                <span class="atc-detail-value">
                                    <span class="atc-status-badge atc-status-${paymentStatus}">
                                        ${this.capitalize(paymentStatus)}
                                    </span>
                                </span>
                            </div>
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Booking Date:</span>
                                <span class="atc-detail-value">${bookingDate}</span>
                            </div>
                            ${travelDate && travelDate !== 'N/A' ? `
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Travel Date:</span>
                                <span class="atc-detail-value">${travelDate}</span>
                            </div>
                            ` : ''}
                            ${destination && destination !== 'N/A' ? `
                            <div class="atc-detail-row">
                                <span class="atc-detail-label">Destination:</span>
                                <span class="atc-detail-value">${destination}</span>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div class="atc-modal-actions">
                            ${paymentStatus === 'paid' ? `
                                <button class="atc-btn atc-btn-outline atc-download-invoice" data-booking-id="${booking.id}">
                                    📄 Download Invoice
                                </button>
                            ` : ''}
                            <button class="atc-btn atc-btn-text atc-modal-close">Close</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            modal.fadeIn(300);
            
            // Close modal
            modal.find('.atc-modal-close, .atc-modal-overlay').on('click', function() {
                modal.fadeOut(300, function() {
                    modal.remove();
                });
            });
        },
        
        /**
         * Cancel booking
         */
        async cancelBooking(bookingId) {
            if (!bookingId || bookingId === '0' || bookingId === '') {
                alert('Invalid booking ID');
                return;
            }
            
            if (!confirm(atcAccount?.strings?.confirmCancel || 'Are you sure you want to cancel this booking?')) {
                return;
            }
            
            const reason = prompt('Please provide a reason for cancellation (optional):');
            
            try {
                const response = await $.ajax({
                    url: atcAccount?.ajaxUrl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'atc_cancel_booking',
                        nonce: atcAccount?.nonce || '',
                        booking_id: parseInt(bookingId),
                        reason: reason || ''
                    }
                });
                
                if (response && response.success) {
                    alert(response.data?.message || 'Booking cancelled successfully!');
                    location.reload();
                } else {
                    alert(response?.data?.message || atcAccount?.strings?.error || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error cancelling booking:', error);
                alert(atcAccount?.strings?.error || 'An error occurred while cancelling the booking');
            }
        },
        
        /**
         * Download invoice
         */
        async downloadInvoice(bookingId) {
            try {
                const response = await $.ajax({
                    url: atcAccount.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'atc_download_invoice',
                        nonce: atcAccount.nonce,
                        booking_id: bookingId
                    }
                });
                
                if (response.success && response.data.invoice_url) {
                    window.open(response.data.invoice_url, '_blank');
                } else {
                    alert(response.data.message || 'Invoice not available');
                }
            } catch (error) {
                console.error('Error downloading invoice:', error);
                alert(atcAccount.strings.error);
            }
        },
        
        /**
         * Resend email
         */
        async resendEmail(bookingId) {
            try {
                const response = await $.ajax({
                    url: atcAccount.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'atc_resend_email',
                        nonce: atcAccount.nonce,
                        booking_id: bookingId
                    }
                });
                
                if (response.success) {
                    alert(atcAccount.strings.emailSent);
                } else {
                    alert(response.data.message || atcAccount.strings.error);
                }
            } catch (error) {
                console.error('Error resending email:', error);
                alert(atcAccount.strings.error);
            }
        },
        
        /**
         * Initialize password strength indicator
         */
        initPasswordStrength() {
            $('#reg_password, #new_password').on('input', function() {
                const password = $(this).val();
                const strength = AccountDashboard.calculatePasswordStrength(password);
                
                // Remove existing indicator
                $(this).next('.atc-password-strength').remove();
                
                if (password.length > 0) {
                    const colors = {
                        weak: '#ef4444',
                        medium: '#f59e0b',
                        strong: '#10b981'
                    };
                    
                    const indicator = $('<div class="atc-password-strength">').css({
                        'height': '4px',
                        'background': colors[strength],
                        'margin-top': '5px',
                        'border-radius': '2px',
                        'transition': 'all 0.3s'
                    });
                    
                    $(this).after(indicator);
                }
            });
        },
        
        /**
         * Calculate password strength
         */
        calculatePasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            if (strength <= 2) return 'weak';
            if (strength <= 4) return 'medium';
            return 'strong';
        },
        
        /**
         * Capitalize string
         */
        capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    };
    
    // Initialize Profile Dropdown Menu - Simplified and more reliable
    AccountDashboard.initProfileMenu = function() {
        // Wait for DOM to be fully ready
        function initMenu() {
            const trigger = $('#atc-profile-menu-trigger');
            const dropdown = $('#atc-profile-dropdown');
            
            // Check if elements exist
            if (trigger.length === 0) {
                return; // Trigger not found, exit silently
            }
            
            // If logged out, trigger is a link to login page - no dropdown to initialize
            if (trigger.hasClass('atc-profile-menu-login-btn') && trigger.is('a')) {
                return; // Login link - no dropdown menu, just navigation
            } else if (trigger.is('a') && !trigger.hasClass('atc-profile-menu-login-btn')) {
                return; // Regular link - no menu to initialize
            }
            
            // Check if dropdown exists (for both logged in users and login dropdown)
            if (dropdown.length === 0) {
                return; // Dropdown not found, exit silently
            }
            
            // CRITICAL: Check if inline handler is already set up (skip main JS handler)
            if (trigger[0] && trigger[0].hasAttribute('data-inline-handler')) {
                return; // Inline handler is active, skip main JS handler
            }
            
            // CRITICAL: Check if already initialized on this element (prevent duplicates)
            if (trigger[0] && trigger[0].hasAttribute('data-menu-initialized')) {
                return; // Already initialized, skip
            }
            
            // Mark as initialized
            AccountDashboard.profileMenuInitialized = true;
            
            // Get overlay (should exist in DOM from PHP, but create if missing)
            let overlay = $('.atc-profile-dropdown-overlay');
            if (overlay.length === 0) {
                // Create overlay and append to body for proper full-screen coverage
                overlay = $('<div class="atc-profile-dropdown-overlay"></div>');
                $('body').append(overlay);
            }
            
            // CRITICAL: Ensure overlay is completely disabled when menu is closed
            // This prevents it from blocking touches on mobile
            overlay.removeClass('active');
            overlay.css({
                'display': 'none',
                'visibility': 'hidden',
                'pointer-events': 'none',
                'opacity': '0',
                'z-index': '-1'
            });
            
            let isOpen = false;
            let touchStartTime = 0;
            let touchStartX = 0;
            let touchStartY = 0;
            
            // Open dropdown - FORCE OPEN WITH INLINE STYLES
            const openDropdown = function() {
                // Check if already open
                if (dropdown.hasClass('active')) return;
                
                // Update state
                isOpen = true;
                
                // Add classes
                dropdown.addClass('active');
                trigger.addClass('active').attr('aria-expanded', 'true');
                overlay.addClass('active');
                $('body').addClass('atc-profile-menu-open');
                
                // Position dropdown on mobile - FORCE WITH INLINE STYLES
                if (window.innerWidth <= 768) {
                    // Apply via jQuery
                    dropdown.css({
                        'position': 'fixed',
                        'top': 'auto',
                        'bottom': '20px',
                        'left': '10px',
                        'right': '10px',
                        'max-width': 'calc(100vw - 20px)',
                        'min-width': 'auto',
                        'width': 'auto',
                        'z-index': '99999',
                        'opacity': '1',
                        'visibility': 'visible',
                        'display': 'block',
                        'pointer-events': 'auto',
                        'transform': 'translateY(0) scale(1)',
                        '-webkit-transform': 'translateY(0) scale(1)',
                        '-moz-transform': 'translateY(0) scale(1)',
                        '-ms-transform': 'translateY(0) scale(1)',
                        '-o-transform': 'translateY(0) scale(1)'
                    });
                    
                    // ALSO set as inline style for maximum priority
                    if (dropdown[0]) {
                        dropdown[0].style.setProperty('position', 'fixed', 'important');
                        dropdown[0].style.setProperty('top', 'auto', 'important');
                        dropdown[0].style.setProperty('bottom', '20px', 'important');
                        dropdown[0].style.setProperty('left', '10px', 'important');
                        dropdown[0].style.setProperty('right', '10px', 'important');
                        dropdown[0].style.setProperty('max-width', 'calc(100vw - 20px)', 'important');
                        dropdown[0].style.setProperty('z-index', '99999', 'important');
                        dropdown[0].style.setProperty('opacity', '1', 'important');
                        dropdown[0].style.setProperty('visibility', 'visible', 'important');
                        dropdown[0].style.setProperty('display', 'block', 'important');
                        dropdown[0].style.setProperty('pointer-events', 'auto', 'important');
                    }
                    
                    // Force reflow
                    dropdown[0].offsetHeight;
                }
            };
            
            // Close dropdown - CHECK ACTUAL DOM STATE
            const closeDropdown = function() {
                // Check actual DOM state
                if (!dropdown.hasClass('active')) return;
                
                isOpen = false;
                dropdown.removeClass('active');
                trigger.removeClass('active').attr('aria-expanded', 'false');
                overlay.removeClass('active');
                $('body').removeClass('atc-profile-menu-open');
                
                // CRITICAL: Completely disable overlay when closing
                // This prevents it from blocking touches on mobile
                overlay.css({
                    'display': 'none',
                    'visibility': 'hidden',
                    'pointer-events': 'none',
                    'opacity': '0',
                    'z-index': '-1'
                });
                
                // Reset mobile positioning
                if (window.innerWidth <= 768 && dropdown[0]) {
                    dropdown.css({
                        'position': '',
                        'top': '',
                        'bottom': '',
                        'right': '',
                        'left': '',
                        'max-width': '',
                        'min-width': '',
                        'width': '',
                        'z-index': '',
                        'transform': '',
                        '-webkit-transform': '',
                        '-moz-transform': '',
                        '-ms-transform': '',
                        '-o-transform': ''
                    });
                    
                    // Remove inline styles
                    dropdown[0].style.removeProperty('position');
                    dropdown[0].style.removeProperty('top');
                    dropdown[0].style.removeProperty('bottom');
                    dropdown[0].style.removeProperty('left');
                    dropdown[0].style.removeProperty('right');
                    dropdown[0].style.removeProperty('max-width');
                    dropdown[0].style.removeProperty('z-index');
                    dropdown[0].style.removeProperty('opacity');
                    dropdown[0].style.removeProperty('visibility');
                    dropdown[0].style.removeProperty('display');
                    dropdown[0].style.removeProperty('pointer-events');
                }
            };
            
            // Toggle dropdown - CHECK ACTUAL DOM STATE
            const toggleDropdown = function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                }
                
                // Check actual DOM state (not just variable)
                const actuallyOpen = dropdown.hasClass('active');
                
                if (actuallyOpen) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            };
            
            // Track if this is a touch device
            let isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || 
                               (navigator.userAgent.match(/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile|Opera Mini)/i));
            let touchStarted = false;
            let touchHandled = false;
            let lastTouchTime = 0;
            
            // CRITICAL: Remove ALL existing handlers first to avoid conflicts
            trigger.off('touchstart.menu touchend.menu touchcancel.menu click.toggleMenu mousedown.menu');
            
            // Mark as initialized to prevent duplicates
            if (trigger[0]) {
                trigger[0].setAttribute('data-menu-initialized', 'true');
            }
            
            // SIMPLIFIED MOBILE HANDLER - More reliable for mobile/tablet
            // Handle touchstart (for mobile) - SIMPLIFIED
            trigger.on('touchstart.menu', function(e) {
                // Only handle if it's a button, not a link
                if ($(this).is('a') || $(this).hasClass('atc-profile-menu-login-btn')) {
                    touchHandled = false;
                    return true; // Allow default navigation for links
                }
                
                // Mark that we're handling a touch
                touchStarted = true;
                touchHandled = false;
                touchStartTime = Date.now();
                
                // Get touch coordinates (with fallback)
                try {
                    touchStartX = e.originalEvent.touches ? e.originalEvent.touches[0].clientX : 0;
                    touchStartY = e.originalEvent.touches ? e.originalEvent.touches[0].clientY : 0;
                } catch (err) {
                    touchStartX = 0;
                    touchStartY = 0;
                }
                
                $(this).addClass('touching');
                
                // Don't prevent default yet - wait for touchend
                // This allows better touch handling on iOS
            }, { passive: true });
            
            // Handle touchend (for mobile) - SIMPLIFIED AND MORE RELIABLE
            trigger.on('touchend.menu', function(e) {
                const $this = $(this);
                
                // Only handle if it's a button, not a link
                if ($this.is('a') || $this.hasClass('atc-profile-menu-login-btn')) {
                    $this.removeClass('touching');
                    touchStarted = false;
                    touchHandled = false;
                    return true; // Allow default navigation
                }
                
                // Prevent default immediately to stop click event
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Remove touching class
                $this.removeClass('touching');
                
                // SIMPLIFIED: Always toggle on touchend (no complex swipe detection)
                // This is more reliable on mobile devices
                // Only check if enough time has passed to prevent rapid double-taps
                const currentTime = Date.now();
                const timeSinceLastTouch = currentTime - lastTouchTime;
                
                // Prevent double-trigger (minimum 300ms between touches)
                if (timeSinceLastTouch < 300 && lastTouchTime > 0) {
                    touchStarted = false;
                    touchHandled = false;
                    return false;
                }
                
                // Mark as handled
                touchHandled = true;
                lastTouchTime = currentTime;
                
                // Toggle menu immediately
                setTimeout(function() {
                    toggleDropdown(e);
                    touchStarted = false;
                    touchHandled = false;
                }, 10); // Small delay to ensure event propagation is stopped
                
                return false;
            }, { passive: false });
            
            // Handle touchcancel (mobile)
            trigger.on('touchcancel.menu', function() {
                $(this).removeClass('touching');
                touchStarted = false;
                touchHandled = false;
            }, { passive: true });
            
            // Click handler (for desktop and mobile fallback)
            // CRITICAL: On mobile, click fires AFTER touchend, so we need to handle it
            trigger.on('click.toggleMenu', function(e) {
                const $this = $(this);
                
                // If this is a login link (logged out users), allow normal navigation
                if ($this.hasClass('atc-profile-menu-login-btn') || $this.is('a')) {
                    return true;
                }
                
                // On touch devices, if touchend already handled it, prevent click
                if (isTouchDevice) {
                    // If touch was just handled (within last 500ms), prevent click
                    const timeSinceTouch = Date.now() - lastTouchTime;
                    if (touchHandled || (touchStarted && timeSinceTouch < 500)) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        touchHandled = false;
                        touchStarted = false;
                        return false;
                    }
                }
                
                // Desktop click handler
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                toggleDropdown(e);
                return false;
            });
            
            // Overlay click handler - ONLY closes when clicking directly on overlay
            overlay.on('click', function(e) {
                if (!isOpen) return;
                // Only close if clicking directly on overlay element itself
                if (e.target === overlay[0] || $(e.target).hasClass('atc-profile-dropdown-overlay')) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDropdown();
                    return false;
                }
            });
            
            // Overlay touch handler for mobile
            overlay.on('touchstart', function(e) {
                if (!isOpen) return;
                // Only close if touching directly on overlay
                if (e.target === overlay[0] || $(e.target).hasClass('atc-profile-dropdown-overlay')) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeDropdown();
                    return false;
                }
            });
            
            // CRITICAL: Prevent ALL events inside dropdown from reaching overlay
            dropdown.off('click.dropdown touchstart.dropdown mousedown.dropdown')
                   .on('click.dropdown touchstart.dropdown mousedown.dropdown', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
            });
            
            // CRITICAL: Prevent ALL events on menu items from bubbling
            dropdown.find('a, button, .atc-profile-menu-item').off('click.menuItem touchstart.menuItem')
                   .on('click.menuItem touchstart.menuItem', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
            });
            
            // Handle menu item clicks - AJAX navigation or normal navigation
            dropdown.off('click', 'a.atc-profile-menu-item')
                   .on('click', 'a.atc-profile-menu-item', function(e) {
                const item = $(this);
                const route = item.data('route');
                const href = item.attr('href');
                const url = item.data('url') || href;
                
                // Stop propagation immediately to prevent overlay from closing menu
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // Don't handle logout with AJAX
                if (route === 'logout' || item.hasClass('atc-profile-menu-logout')) {
                    closeDropdown();
                    // Allow normal navigation for logout
                    return true;
                }
                
                // Close dropdown first
                closeDropdown();
                
                // Handle navigation
                if (url && url.indexOf('/my-account/') !== -1) {
                    // If already on account page, use AJAX
                    if (window.location.href.indexOf('/my-account/') !== -1) {
                        e.preventDefault();
                        setTimeout(function() {
                            loadAccountPage(url);
                        }, 100);
                    } else {
                        // Navigate normally if not on account page
                        e.preventDefault();
                        setTimeout(function() {
                            window.location.href = url;
                        }, 100);
                    }
                } else {
                    // Navigate normally for non-account pages
                    if (href) {
                        e.preventDefault();
                        setTimeout(function() {
                            window.location.href = href;
                        }, 100);
                    }
                }
                
                return false;
            });
            
            // AJAX function to load account pages
            function loadAccountPage(url) {
                // Show loading indicator
                const accountContent = $('.atc-account-dashboard, .atc-account-content, [class*="atc-account"]').first();
                if (accountContent.length) {
                    accountContent.css('opacity', '0.5').css('pointer-events', 'none');
                }
                
                // Update URL without reload
                if (window.history && window.history.pushState) {
                    window.history.pushState({}, '', url);
                }
                
                // Load content via AJAX
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    success: function(response) {
                        // Extract main content from response
                        const $response = $(response);
                        const newContent = $response.find('.atc-account-dashboard, .atc-account-content, [class*="atc-account"]').first();
                        
                        if (newContent.length && accountContent.length) {
                            accountContent.replaceWith(newContent);
                            // Update active menu item
                            updateActiveMenuItem(url);
                            // Reinitialize any scripts
                            if (typeof AccountDashboard !== 'undefined') {
                                AccountDashboard.init();
                            }
                        } else {
                            // Fallback: reload page
                            window.location.href = url;
                        }
                        
                        // Restore opacity
                        $('.atc-account-dashboard, .atc-account-content, [class*="atc-account"]').first()
                            .css('opacity', '1').css('pointer-events', 'auto');
                    },
                    error: function() {
                        // On error, reload page
                        window.location.href = url;
                    }
                });
            }
            
            // Update active menu item
            function updateActiveMenuItem(url) {
                $('.atc-profile-menu-item').removeClass('active');
                const currentTab = url.match(/[?&]tab=([^&]+)/);
                if (currentTab) {
                    $('.atc-profile-menu-item[data-route="' + currentTab[1] + '"]').addClass('active');
                } else if (url.indexOf('/my-account/') !== -1 && url.indexOf('tab=') === -1) {
                    $('.atc-profile-menu-item[data-route="dashboard"]').addClass('active');
                }
            }
            
            // Close on escape key
            $(document).on('keydown.profileMenu', function(e) {
                if (e.key === 'Escape' && isOpen) {
                    closeDropdown();
                }
            });
            
            // Fallback: Close when clicking outside (backup method)
            $(document).off('click.profileMenuFallback').on('click.profileMenuFallback', function(e) {
                if (!isOpen) return;
                
                const $target = $(e.target);
                const isInsideMenu = $target.closest('.atc-account-menu-wrapper').length > 0;
                const isInsideDropdown = $target.closest('.atc-profile-dropdown').length > 0;
                const isOverlay = $target.hasClass('atc-profile-dropdown-overlay') || 
                                 $target.closest('.atc-profile-dropdown-overlay').length > 0;
                
                // Close if clicking outside menu system
                if (!isInsideMenu && !isInsideDropdown) {
                    // If it's the overlay, let overlay handler deal with it
                    if (!isOverlay) {
                        closeDropdown();
                    }
                }
            });
            
            // Handle window resize
            $(window).on('resize.profileMenu', function() {
                if (isOpen && window.innerWidth <= 768) {
                    // Reposition on resize
                    const triggerRect = trigger[0].getBoundingClientRect();
                    const dropdownHeight = dropdown.outerHeight() || 300;
                    const windowHeight = window.innerHeight;
                    const spaceBelow = windowHeight - triggerRect.bottom;
                    const spaceAbove = triggerRect.top;
                    
                    let topPosition;
                    if (spaceBelow >= dropdownHeight + 20) {
                        topPosition = triggerRect.bottom + 10;
                    } else if (spaceAbove >= dropdownHeight + 20) {
                        topPosition = triggerRect.top - dropdownHeight - 10;
                    } else {
                        topPosition = Math.max(10, windowHeight - dropdownHeight - 20);
                    }
                    
                    dropdown.css('top', topPosition + 'px');
                }
            });
            
        }
        
        // Initialize immediately if DOM is ready, otherwise wait
        if (document.readyState === 'loading') {
            $(document).ready(initMenu);
        } else {
            // DOM is already ready
            setTimeout(initMenu, 100);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        AccountDashboard.init();
        AccountDashboard.initProfileMenu();

        // Global delegated fallback for mobile/tablet
        // Ensures the profile menu toggles even if a previous handler fails
        $(document).off('click.atcProfile touchend.atcProfile', '#atc-profile-menu-trigger')
                   .on('click.atcProfile touchend.atcProfile', '#atc-profile-menu-trigger', function(e) {
            const $t = $(this);
            // If it's a login link, let it navigate
            if ($t.is('a') && $t.hasClass('atc-profile-menu-login-btn')) {
                return true;
            }
            // Prevent default to avoid double-trigger on touch devices
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const $dropdown = $('#atc-profile-dropdown');
            const $overlay = $('.atc-profile-dropdown-overlay');
            if (!$dropdown.length) return false;

            const isOpen = $dropdown.hasClass('active');
            if (isOpen) {
                // Close
                $dropdown.removeClass('active').attr('style', '');
                $t.removeClass('active').attr('aria-expanded', 'false');
                $('body').removeClass('atc-profile-menu-open');
                if ($overlay.length) {
                    $overlay.removeClass('active').css({
                        display: 'none',
                        visibility: 'hidden',
                        'pointer-events': 'none',
                        opacity: 0,
                        'z-index': -1
                    });
                }
            } else {
                // Open
                $dropdown.addClass('active');
                $t.addClass('active').attr('aria-expanded', 'true');
                $('body').addClass('atc-profile-menu-open');
                if (window.innerWidth <= 768) {
                    $dropdown.css({
                        position: 'fixed',
                        bottom: '20px',
                        left: '10px',
                        right: '10px',
                        'max-width': 'calc(100vw - 20px)',
                        'z-index': 99999,
                        opacity: 1,
                        visibility: 'visible',
                        display: 'block',
                        'pointer-events': 'auto',
                        transform: 'translateY(0) scale(1)'
                    });
                }
                if ($overlay.length) {
                    $overlay.addClass('active').css({
                        display: 'block',
                        visibility: 'visible',
                        'pointer-events': 'auto',
                        opacity: 1,
                        'z-index': 9998
                    });
                }
            }
            return false;
        });
    });
    
    // Single delayed initialization attempt (for slower devices)
    setTimeout(function() {
        AccountDashboard.initProfileMenu();
    }, 300);
    
    
})(jQuery);

/**
 * Modal Styles (injected dynamically)
 */
const modalStyles = `
<style>
.atc-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999999;
    display: none;
}

.atc-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(10, 31, 68, 0.8);
    backdrop-filter: blur(4px);
}

.atc-modal-content {
    position: relative;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    margin: 50px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.atc-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 2px solid #e2e8f0;
    background: linear-gradient(135deg, #0A1F44 0%, #1E3A5F 100%);
    color: #fff;
    border-radius: 16px 16px 0 0;
}

.atc-modal-header h2 {
    margin: 0;
    color: #fff;
    font-size: 24px;
}

.atc-modal-close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid #D4AF37;
    color: #fff;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.atc-modal-close:hover {
    background: #D4AF37;
    transform: rotate(90deg);
}

.atc-modal-body {
    padding: 30px;
}

.atc-booking-details {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.atc-detail-row {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.atc-detail-label {
    font-weight: 600;
    color: #64748b;
    font-size: 14px;
}

.atc-detail-value {
    color: #0A1F44;
    font-size: 15px;
}

.atc-modal-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .atc-detail-row {
        grid-template-columns: 1fr;
        gap: 5px;
    }
    
    .atc-modal-content {
        width: 95%;
        margin: 20px auto;
    }
    
    .atc-modal-actions {
        flex-direction: column;
    }
}
</style>
`;

// Inject styles
if (!document.getElementById('atc-modal-styles')) {
    const styleEl = document.createElement('div');
    styleEl.id = 'atc-modal-styles';
    styleEl.innerHTML = modalStyles;
    document.head.appendChild(styleEl);
}