/**
 * ATC Lead Generation Popup JavaScript
 * Smart trigger system with AI-optimized timing
 */

(function($) {
    'use strict';
    
    const ATCLeadPopup = {
        settings: {},
        shown: false,
        sessionKey: 'atc_popup_shown',
        timer: null,
        scrollTimer: null,
        exitIntentBound: false,
        
        init: function() {
            this.settings = window.atcPopup?.settings || {};
            
            // Check if already shown in this session
            if (this.settings.show_once_per_session && sessionStorage.getItem(this.sessionKey)) {
                return;
            }
            
            // Wait for delay after page load
            setTimeout(() => {
                this.setupTriggers();
            }, (this.settings.delay_after_page_load || 5) * 1000);
        },
        
        setupTriggers: function() {
            // Time-based trigger
            if (this.settings.trigger_time) {
                this.timer = setTimeout(() => {
                    this.showPopup('time');
                }, this.settings.trigger_time * 1000);
            }
            
            // Scroll-based trigger
            if (this.settings.trigger_scroll) {
                $(window).on('scroll', this.debounce(() => {
                    const scrollPercent = this.getScrollPercent();
                    if (scrollPercent >= this.settings.trigger_scroll) {
                        this.showPopup('scroll');
                    }
                }, 100));
            }
            
            // Exit intent trigger
            if (this.settings.exit_intent) {
                this.setupExitIntent();
            }
        },
        
        setupExitIntent: function() {
            if (this.exitIntentBound) return;
            this.exitIntentBound = true;
            
            // Track mouse movement
            $(document).on('mouseleave', (e) => {
                if (e.clientY <= 0) {
                    // Mouse is moving toward top of screen (likely closing tab)
                    this.showPopup('exit_intent');
                }
            });
            
            // Also track on mobile (when user swipes up to close)
            let touchStartY = 0;
            $(document).on('touchstart', (e) => {
                touchStartY = e.originalEvent.touches[0].clientY;
            });
            
            $(document).on('touchend', (e) => {
                const touchEndY = e.originalEvent.changedTouches[0].clientY;
                const swipeDistance = touchStartY - touchEndY;
                
                // If swiping up more than 100px from bottom, likely exiting
                if (swipeDistance > 100 && touchEndY < 50) {
                    this.showPopup('exit_intent');
                }
            });
        },
        
        showPopup: function(trigger) {
            if (this.shown) return;
            
            // Check mobile setting
            if (!this.settings.mobile_enabled && this.isMobile()) {
                return;
            }
            
            this.shown = true;
            
            // Clear timers
            if (this.timer) {
                clearTimeout(this.timer);
            }
            
            // Mark as shown in session
            if (this.settings.show_once_per_session) {
                sessionStorage.setItem(this.sessionKey, '1');
            }
            
            // Lock body scroll
            this.lockBodyScroll();
            
            // Show popup with animation
            const $popup = $('#atc-lead-popup');
            $popup.fadeIn(300);
            
            // Focus first input
            setTimeout(() => {
                $('#atc-popup-name').focus();
            }, 400);
            
            // Track popup shown event
            this.trackEvent('popup_shown', { trigger: trigger });
        },
        
        hidePopup: function() {
            // Unlock body scroll
            this.unlockBodyScroll();
            
            $('#atc-lead-popup').fadeOut(300);
        },
        
        lockBodyScroll: function() {
            // Save current scroll position
            const scrollY = window.scrollY || window.pageYOffset;
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';
            document.body.classList.add('atc-popup-open');
            
            // Store scroll position for restoration
            document.body.setAttribute('data-scroll-y', scrollY);
        },
        
        unlockBodyScroll: function() {
            // Restore scroll position
            const scrollY = document.body.getAttribute('data-scroll-y') || 0;
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.classList.remove('atc-popup-open');
            
            // Restore scroll position
            window.scrollTo(0, parseInt(scrollY));
            document.body.removeAttribute('data-scroll-y');
        },
        
        getScrollPercent: function() {
            const windowHeight = $(window).height();
            const documentHeight = $(document).height();
            const scrollTop = $(window).scrollTop();
            const scrollPercent = (scrollTop / (documentHeight - windowHeight)) * 100;
            return Math.min(100, Math.max(0, scrollPercent));
        },
        
        isMobile: function() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                   window.innerWidth <= 768;
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        trackEvent: function(event, data) {
            // Track in visitor tracking if available
            if (typeof atcTrackEvent === 'function') {
                atcTrackEvent('popup_' + event, data);
            }
        },
        
        submitForm: function() {
            const $form = $('#atc-popup-form');
            const $submit = $form.find('.atc-popup-submit');
            const $submitText = $submit.find('.atc-submit-text');
            const $submitLoader = $submit.find('.atc-submit-loader');
            
            // Validate
            const name = $('#atc-popup-name').val().trim();
            const phone = $('#atc-popup-phone').val().trim();
            
            if (!name || !phone) {
                this.showError('Please fill in name and phone number.');
                return;
            }
            
            // Validate phone
            const phoneClean = phone.replace(/[^0-9+]/g, '');
            if (phoneClean.length < 10) {
                this.showError('Please enter a valid phone number.');
                return;
            }
            
            // Show loading state
            $submit.prop('disabled', true);
            $submitText.hide();
            $submitLoader.show();
            
            // Submit via AJAX
            $.ajax({
                url: window.atcPopup.ajax_url,
                type: 'POST',
                data: {
                    action: 'atc_submit_popup_lead',
                    nonce: window.atcPopup.nonce,
                    name: name,
                    phone: phone,
                    email: $('#atc-popup-email').val().trim(),
                    service: $('#atc-popup-service').val(),
                    destination: $('#atc-popup-destination').val().trim(),
                    travel_date: $('#atc-popup-travel-date').val(),
                    trip_type: $('#atc-popup-trip-type').val(),
                    adults: $('#atc-popup-adults').val() || '2',
                    children: $('#atc-popup-children').val() || '0',
                    child_ages: $('input[name="child_age[]"]').map(function() { return $(this).val(); }).get().join(','),
                    budget: $('#atc-popup-budget').val(),
                    custom_budget: $('#atc-popup-custom-budget-input').val(),
                    hotel_type: $('#atc-popup-hotel-type-select').val(),
                    message: $('#atc-popup-message').val().trim()
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess();
                        this.trackEvent('lead_submitted', { lead_id: response.data.lead_id });
                    } else {
                        this.showError(response.data?.message || 'Something went wrong. Please try again.');
                        $submit.prop('disabled', false);
                        $submitText.show();
                        $submitLoader.hide();
                    }
                },
                error: () => {
                    this.showError('Network error. Please try again.');
                    $submit.prop('disabled', false);
                    $submitText.show();
                    $submitLoader.hide();
                }
            });
        },
        
        showSuccess: function() {
            $('.atc-popup-form').fadeOut(300, function() {
                $('.atc-popup-success').fadeIn(300);
            });
            
            // Auto-close after 3 seconds
            setTimeout(() => {
                ATCLeadPopup.hidePopup();
            }, 3000);
        },
        
        showError: function(message) {
            // Simple error display (can be enhanced)
            alert(message);
        }
    };
    
    // Initialize conditional fields for lead popup
    function initLeadPopupConditionalFields() {
        // Children count change - show/hide child ages
        $(document).on('input change', '.atc-popup-children-count', function() {
            const childrenCount = parseInt($(this).val()) || 0;
            const $childAgesGroup = $('#atc-popup-child-ages');
            const $childAgesContainer = $('#atc-popup-child-ages-container');
            
            if (childrenCount > 0) {
                $childAgesGroup.slideDown(300);
                $childAgesContainer.empty();
                
                // Create age input for each child
                for (let i = 1; i <= childrenCount; i++) {
                    const ageInput = $('<div style="display: flex; align-items: center; gap: 10px;">' +
                        '<label style="min-width: 100px; font-size: 13px; color: #666;">Child ' + i + ':</label>' +
                        '<input type="number" name="child_age[]" min="0" max="17" placeholder="Age" ' +
                        'class="atc-form-input" style="flex: 1;" required>' +
                        '</div>');
                    $childAgesContainer.append(ageInput);
                }
            } else {
                $childAgesGroup.slideUp(300);
                $childAgesContainer.empty();
            }
        });
        
        // Budget change - show/hide custom budget
        $(document).on('change', '#atc-popup-budget', function() {
            const budgetValue = $(this).val();
            const $customBudgetGroup = $('#atc-popup-custom-budget');
            
            if (budgetValue === 'custom') {
                $customBudgetGroup.slideDown(300);
            } else {
                $customBudgetGroup.slideUp(300);
            }
        });
        
        // Service change - show/hide hotel type field
        $(document).on('change', '.atc-popup-service-select', function() {
            const service = $(this).val();
            const $hotelTypeGroup = $('#atc-popup-hotel-type');
            
            // Services that need hotel type: tours, hotels
            const hotelServices = ['tours', 'hotels'];
            
            if (hotelServices.includes(service)) {
                $hotelTypeGroup.slideDown(300);
            } else {
                $hotelTypeGroup.slideUp(300);
                $hotelTypeGroup.find('select').val('');
            }
        });
    }
    
    // Initialize on DOM ready
    $(document).ready(function() {
        ATCLeadPopup.init();
        initLeadPopupConditionalFields();
        
        // Close button
        $(document).on('click', '.atc-popup-close, .atc-popup-overlay', function(e) {
            if (e.target === this) {
                ATCLeadPopup.hidePopup();
            }
        });
        
        // Prevent scroll propagation from popup container to body
        $(document).on('wheel touchmove', '.atc-popup-container', function(e) {
            const container = this;
            const scrollTop = container.scrollTop;
            const scrollHeight = container.scrollHeight;
            const height = container.clientHeight;
            const wheelDelta = e.originalEvent.deltaY || 0;
            
            // Check if scrolling up at top or down at bottom
            const isAtTop = scrollTop === 0 && wheelDelta < 0;
            const isAtBottom = scrollTop + height >= scrollHeight - 1 && wheelDelta > 0;
            
            // Prevent body scroll if not at boundaries
            if (!isAtTop && !isAtBottom) {
                e.stopPropagation();
            }
        });
        
        // Form submission
        $(document).on('submit', '#atc-popup-form', function(e) {
            e.preventDefault();
            ATCLeadPopup.submitForm();
        });
        
        // Escape key to close
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#atc-lead-popup').is(':visible')) {
                ATCLeadPopup.hidePopup();
            }
        });
    });
    
})(jQuery);

