/**
 * Enhanced Package Query Form JavaScript
 * Service-Specific & Package-Specific Query Forms
 */

(function($) {
    'use strict';
    
    const PackageQueryEnhanced = {
        init() {
            this.bindEvents();
            this.loadPackageQueryForms();
        },
        
        bindEvents() {
            const TAP_THRESHOLD = 14;
            const TAP_STATE_KEY = 'atcTapState';
            const TAP_IGNORE_KEY = 'atcTapIgnoreClick';
            
            const attachTapOnlyHandler = function(selector, handler) {
                $(document).on('touchstart', selector, function(e) {
                    if (!e.originalEvent.touches || !e.originalEvent.touches.length) return;
                    const touch = e.originalEvent.touches[0];
                    $(this).data(TAP_STATE_KEY, {
                        x: touch.clientX,
                        y: touch.clientY,
                        moved: false
                    });
                });
                
                $(document).on('touchmove', selector, function(e) {
                    const state = $(this).data(TAP_STATE_KEY);
                    if (!state || !e.originalEvent.touches || !e.originalEvent.touches.length) return;
                    const touch = e.originalEvent.touches[0];
                    if (Math.abs(touch.clientX - state.x) > TAP_THRESHOLD || Math.abs(touch.clientY - state.y) > TAP_THRESHOLD) {
                        state.moved = true;
                        $(this).data(TAP_STATE_KEY, state);
                    }
                });
                
                $(document).on('touchend', selector, function(e) {
                    const state = $(this).data(TAP_STATE_KEY);
                    $(this).removeData(TAP_STATE_KEY);
                    if (!state || state.moved) {
                        return;
                    }
                    if (typeof e.preventDefault === 'function') e.preventDefault();
                    if (typeof e.stopPropagation === 'function') e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                    $(this).data(TAP_IGNORE_KEY, Date.now());
                    handler.call(this, e);
                });
                
                $(document).on('touchcancel', selector, function() {
                    $(this).removeData(TAP_STATE_KEY);
                });
            };
            
            const shouldSkipSyntheticClick = function($el) {
                const lastHandled = $el.data(TAP_IGNORE_KEY);
                if (!lastHandled) {
                    return false;
                }
                if (Date.now() - lastHandled < 400) {
                    $el.removeData(TAP_IGNORE_KEY);
                    return true;
                }
                $el.removeData(TAP_IGNORE_KEY);
                return false;
            };
            
            // Smart conditional fields logic
            this.initConditionalFields();
            
            // Package query trigger buttons - Support both click and touch events
            const packageTriggerHandler = function(e) {
                if (e.type === 'click' && shouldSkipSyntheticClick($(this))) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                const wrapper = $(this).closest('.atc-package-query-wrapper');
                const packageId = wrapper.data('package-id');
                const modalId = '#atc-package-query-modal-' + packageId;
                const $modal = $(modalId);
                
                // Prevent multiple clicks or rapid successive clicks
                if ($modal.hasClass('active') || $modal.hasClass('opening')) {
                    return;
                }
                
                // Mark as opening to prevent duplicate triggers
                $modal.addClass('opening');
                
                // Stop any ongoing jQuery animations
                $modal.stop(true, true);
                
                // Remove any inline styles that might interfere
                $modal.css({
                    'display': '',
                    'opacity': '',
                    'visibility': ''
                });
                
                // Set display: flex first (without transition)
                $modal.css('display', 'flex');
                
                // Force reflow to ensure display: flex is applied
                if ($modal[0]) {
                    $modal[0].offsetHeight;
                }
                
                // Use requestAnimationFrame to ensure smooth CSS transition
                requestAnimationFrame(function() {
                    // Add active class to trigger CSS transition
                    $modal.addClass('active').removeClass('opening');
                });
                
                $('body').css('overflow', 'hidden'); // Prevent background scroll
                PackageQueryEnhanced.loadPackageQueryForm(packageId);
            };
            
            attachTapOnlyHandler('[data-atc-package-query-trigger]', packageTriggerHandler);
            $(document).on('click', '[data-atc-package-query-trigger]', packageTriggerHandler);
            
            // Service query form trigger buttons (NEW) - Support both click and touch events
            const triggerHandler = function(e) {
                if (e.type === 'click' && shouldSkipSyntheticClick($(this))) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                
                if (typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                const service = $(this).data('service');
                const modalId = '#' + $(this).data('modal-id');
                const $modal = $(modalId);
                
                // Prevent multiple clicks or rapid successive clicks
                if ($modal.hasClass('active') || $modal.hasClass('opening')) {
                    return;
                }
                
                // Mark as opening to prevent duplicate triggers
                $modal.addClass('opening');
                
                // Stop any ongoing jQuery animations
                $modal.stop(true, true);
                
                // Remove any inline styles that might interfere
                $modal.css({
                    'display': '',
                    'opacity': '',
                    'visibility': ''
                });
                
                // Set display: flex first (without transition)
                $modal.css('display', 'flex');
                
                // Force reflow to ensure display: flex is applied
                if ($modal[0]) {
                    $modal[0].offsetHeight;
                }
                
                // Use requestAnimationFrame to ensure smooth CSS transition
                requestAnimationFrame(function() {
                    // Add active class to trigger CSS transition
                    $modal.addClass('active').removeClass('opening');
                });
                
                $('body').css('overflow', 'hidden'); // Prevent background scroll
                PackageQueryEnhanced.loadServiceQueryForm(service, modalId);
            };
            
            attachTapOnlyHandler('[data-atc-query-form-trigger]', triggerHandler);
            $(document).on('click', '[data-atc-query-form-trigger]', triggerHandler);
            
            const closeModal = function(context) {
                const $modal = $(context).closest('.atc-package-query-modal, .atc-query-modal');
                if (!$modal.length) return;
                $modal.removeClass('active');
                setTimeout(function() {
                    if (!$modal.hasClass('active')) {
                        $modal.css('display', 'none');
                    }
                }, 350);
                $('body').css('overflow', '');
            };
            
            $(document).on('touchstart', '.atc-query-modal-close', function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                }
            });
            
            $(document).on('touchend', '.atc-query-modal-close', function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                }
                closeModal(this);
            });
            
            $(document).on('click', '.atc-query-modal-close', function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                }
                closeModal(this);
            });
            
            $(document).on('touchstart', '.atc-query-modal-overlay', function(e) {
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
            });
            
            $(document).on('touchend', '.atc-query-modal-overlay', function(e) {
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                if ($(e.target).hasClass('atc-query-modal-overlay')) {
                    closeModal(this);
                }
            });
            
            $(document).on('click', '.atc-query-modal-overlay', function(e) {
                if (typeof e.stopPropagation === 'function') e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                if ($(e.target).hasClass('atc-query-modal-overlay')) {
                    closeModal(this);
                }
            });
            
            // Close modal on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    const $modals = $('.atc-package-query-modal.active, .atc-query-modal.active');
                    $modals.removeClass('active');
                    
                    // Wait for CSS transition to complete (300ms) before hiding
                    setTimeout(function() {
                        $modals.each(function() {
                            if (!$(this).hasClass('active')) {
                                $(this).css('display', 'none');
                            }
                        });
                    }, 350); // Slightly longer than CSS transition (300ms)
                    
                    $('body').css('overflow', ''); // Restore scroll
                }
            });
            
            // Submit query form (including simple query form)
            $(document).on('submit', '.atc-package-query-form, .atc-query-form, .atc-simple-query-form', function(e) {
                e.preventDefault();
                PackageQueryEnhanced.submitQuery($(this));
            });
        },
        
        loadPackageQueryForms() {
            // Load query forms for all package query wrappers
            $('.atc-package-query-wrapper').each(function() {
                const packageId = $(this).data('package-id');
                if (packageId) {
                    // Pre-load form if not in modal
                    if ($(this).find('.atc-package-query-form').length === 0) {
                        // Form will be loaded when modal opens
                    }
                }
            });
        },
        
        loadServiceQueryForm(service, modalId) {
            // Simple form is already rendered server-side in PHP
            // Just bind form events - no need to load via AJAX
            const $modal = $(modalId);
            const form = $modal.find('.atc-simple-query-form');
            
            if (form.length) {
                // Form is ready, just ensure events are bound
                // Events are already bound via document.on('submit') handler
                
                // Ensure modal is visible on mobile - force reflow
                if ($modal.length && $modal[0]) {
                    // Force reflow for mobile browsers
                    $modal[0].offsetHeight;
                    
                    // Double-check display is set correctly
                    requestAnimationFrame(function() {
                        if ($modal.hasClass('active') && $modal.css('display') === 'none') {
                            $modal.css('display', 'flex');
                        }
                    });
                }
            }
        },
        
        
        renderDefaultServiceForm(container, service) {
            const content = container.find('.atc-query-form-content');
            let html = '<form class="atc-query-form atc-package-query-form" data-service="' + service + '">';
            html += '<div class="atc-message-container"></div>';
            
            // Personal Information Section
            html += '<div class="atc-query-form-section">';
            html += '<div class="atc-query-form-section-title"><span>👤</span>Personal Information</div>';
            html += '<div class="atc-form-row">';
            html += '<div class="atc-form-group"><label for="customer_name">Your Name <span class="required">*</span></label>';
            html += '<input type="text" id="customer_name" name="customer_name" required></div>';
            html += '<div class="atc-form-group"><label for="customer_email">Email Address <span class="required">*</span></label>';
            html += '<input type="email" id="customer_email" name="customer_email" required></div>';
            html += '</div>';
            html += '<div class="atc-form-row">';
            html += '<div class="atc-form-group"><label for="customer_phone">Phone Number <span class="required">*</span></label>';
            html += '<input type="tel" id="customer_phone" name="customer_phone" required></div>';
            html += '</div>';
            html += '</div>';
            
            // Service-Specific Section
            html += '<div class="atc-query-form-section">';
            html += '<div class="atc-query-form-section-title"><span>📋</span>Package Details</div>';
            html += '<div class="atc-form-row">';
            html += '<div class="atc-form-group"><label for="destination">Destination <span class="required">*</span></label>';
            html += '<input type="text" id="destination" name="destination" required placeholder="Where do you want to go?"></div>';
            html += '<div class="atc-form-group"><label for="travel_date">Travel Date</label>';
            html += '<input type="date" id="travel_date" name="travel_date"></div>';
            html += '</div>';
            html += '<div class="atc-form-row">';
            html += '<div class="atc-form-group"><label for="adults">Adults</label>';
            html += '<input type="number" id="adults" name="adults" min="1" value="2"></div>';
            html += '<div class="atc-form-group"><label for="children">Children</label>';
            html += '<input type="number" id="children" name="children" min="0" value="0"></div>';
            html += '</div>';
            html += '</div>';
            
            // Additional Information Section
            html += '<div class="atc-query-form-section">';
            html += '<div class="atc-query-form-section-title"><span>⭐</span>Additional Information</div>';
            html += '<div class="atc-form-group full-width">';
            html += '<label for="special_requirements">Special Requirements & Preferences</label>';
            html += '<textarea id="special_requirements" name="special_requirements" rows="5" placeholder="Tell us about your travel preferences, must-have activities, accommodation preferences, etc."></textarea>';
            html += '</div>';
            html += '<div class="atc-form-group full-width">';
            html += '<label for="message">Message <span class="required">*</span></label>';
            html += '<textarea id="message" name="message" rows="4" required placeholder="Ask any questions or provide additional details..."></textarea>';
            html += '</div>';
            html += '</div>';
            
            html += '<button type="submit" class="atc-btn-premium atc-btn-premium-primary">';
            html += '<span>Submit Query</span>';
            html += '</button>';
            html += '</form>';
            
            content.html(html);
        },
        
        loadPackageQueryForm(packageId) {
            const container = $('.atc-package-query-form-container[data-package-id="' + packageId + '"]');
            const loading = container.find('.atc-query-loading');
            const content = container.find('.atc-query-form-content');
            
            // Show loading
            loading.show();
            content.hide();
            
            // Load query fields from API
            $.ajax({
                url: atcQueryForm.restUrl + 'package/' + packageId + '/query-fields',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', atcQueryForm.nonce);
                },
                success: function(response) {
                    if (response.success && response.query_fields) {
                        // Update modal title/subtitle if provided
                        if (response.query_form_title) {
                            container.closest('.atc-query-modal-content').find('.atc-query-modal-header h2').text(response.query_form_title);
                        }
                        if (response.query_form_subtitle) {
                            const subtitle = container.closest('.atc-query-modal-body').find('.atc-query-modal-subtitle');
                            if (subtitle.length) {
                                subtitle.text(response.query_form_subtitle);
                            } else {
                                container.closest('.atc-query-modal-body').prepend('<p class="atc-query-modal-subtitle">' + response.query_form_subtitle + '</p>');
                            }
                        }
                        if (response.query_custom_message) {
                            const messageContainer = container.closest('.atc-query-modal-body').find('.atc-query-custom-message');
                            if (messageContainer.length) {
                                messageContainer.html(response.query_custom_message);
                            } else {
                                container.closest('.atc-query-modal-body').prepend('<div class="atc-query-custom-message">' + response.query_custom_message + '</div>');
                            }
                        }
                        
                        PackageQueryEnhanced.renderQueryForm(container, response.query_fields, response.service_key, packageId);
                        loading.hide();
                        content.fadeIn();
                    } else {
                        PackageQueryEnhanced.showError(container, 'Failed to load query form');
                    }
                },
                error: function(xhr) {
                    console.error('Query form error:', xhr);
                    PackageQueryEnhanced.showError(container, 'Failed to load query form');
                }
            });
        },
        
        renderQueryForm(container, fields, serviceKey, packageId) {
            const content = container.find('.atc-query-form-content');
            let html = '<form class="atc-package-query-form" data-package-id="' + packageId + '" data-service="' + serviceKey + '">';
            html += '<div class="atc-message-container"></div>';
            
            // Group fields by section
            const sections = {
                personal: [],
                service_specific: [],
                additional: []
            };
            
            fields.forEach(field => {
                const section = field.section || 'service_specific';
                if (!sections[section]) {
                    sections[section] = [];
                }
                sections[section].push(field);
            });
            
            // Render Personal Information Section
            if (sections.personal.length > 0) {
                html += '<div class="atc-query-form-section">';
                html += '<div class="atc-query-form-section-title"><span>👤</span>Personal Information</div>';
                html += '<div class="atc-form-row">';
                sections.personal.forEach(field => {
                    html += PackageQueryEnhanced.renderField(field);
                });
                html += '</div></div>';
            }
            
            // Render Service-Specific Section
            if (sections.service_specific.length > 0) {
                html += '<div class="atc-query-form-section">';
                html += '<div class="atc-query-form-section-title"><span>📋</span>Package Details</div>';
                html += '<div class="atc-form-row">';
                sections.service_specific.forEach(field => {
                    html += PackageQueryEnhanced.renderField(field);
                });
                html += '</div></div>';
            }
            
            // Render Additional Information Section
            if (sections.additional.length > 0) {
                html += '<div class="atc-query-form-section">';
                html += '<div class="atc-query-form-section-title"><span>⭐</span>Additional Information</div>';
                sections.additional.forEach(field => {
                    html += PackageQueryEnhanced.renderField(field, true);
                });
                html += '</div>';
            }
            
            // Add default message field if not present
            if (!fields.find(f => f.id === 'message')) {
                html += '<div class="atc-query-form-section">';
                html += '<div class="atc-query-form-section-title"><span>💬</span>Your Question</div>';
                html += '<div class="atc-form-group full-width">';
                html += '<label for="message">Message <span class="required">*</span></label>';
                html += '<textarea id="message" name="message" rows="5" required placeholder="Ask any questions about this package..."></textarea>';
                html += '</div></div>';
            }
            
            html += '<button type="submit" class="atc-btn-premium atc-btn-premium-primary">';
            html += '<span>Submit Query</span>';
            html += '</button>';
            html += '</form>';
            
            content.html(html);
        },
        
        renderField(field, fullWidth = false) {
            const fieldId = field.id || '';
            const fieldLabel = field.label || '';
            const fieldType = field.type || 'text';
            const fieldRequired = field.required || false;
            const fieldDefault = field.default || '';
            const fieldPlaceholder = field.placeholder || '';
            const fieldOptions = field.options || [];
            
            let html = '<div class="atc-form-group' + (fullWidth ? ' full-width' : '') + '">';
            html += '<label for="query_' + fieldId + '">' + fieldLabel;
            if (fieldRequired) {
                html += ' <span class="required">*</span>';
            }
            html += '</label>';
            
            switch (fieldType) {
                case 'select':
                    html += '<select id="query_' + fieldId + '" name="' + fieldId + '"' + (fieldRequired ? ' required' : '') + '>';
                    html += '<option value="">Select</option>';
                    fieldOptions.forEach(option => {
                        const optionValue = typeof option === 'object' ? option.value : option;
                        const optionLabel = typeof option === 'object' ? option.label : option;
                        const selected = (fieldDefault === optionValue) ? ' selected' : '';
                        html += '<option value="' + optionValue + '"' + selected + '>' + optionLabel + '</option>';
                    });
                    html += '</select>';
                    break;
                    
                case 'textarea':
                    html += '<textarea id="query_' + fieldId + '" name="' + fieldId + '" rows="4"' + (fieldRequired ? ' required' : '') + ' placeholder="' + fieldPlaceholder + '">' + fieldDefault + '</textarea>';
                    break;
                    
                case 'date':
                    html += '<input type="date" id="query_' + fieldId + '" name="' + fieldId + '" value="' + fieldDefault + '"' + (fieldRequired ? ' required' : '') + '>';
                    break;
                    
                case 'number':
                    html += '<input type="number" id="query_' + fieldId + '" name="' + fieldId + '" value="' + fieldDefault + '"' + (fieldRequired ? ' required' : '') + ' placeholder="' + fieldPlaceholder + '">';
                    break;
                    
                default:
                    html += '<input type="' + fieldType + '" id="query_' + fieldId + '" name="' + fieldId + '" value="' + fieldDefault + '"' + (fieldRequired ? ' required' : '') + ' placeholder="' + fieldPlaceholder + '">';
                    break;
            }
            
            html += '</div>';
            return html;
        },
        
        submitQuery($form) {
            const packageId = $form.data('package-id') || 0;
            const serviceKey = $form.data('service') || '';
            const formData = $form.serializeArray();
            const data = {
                package_id: packageId || 0,
                service_key: serviceKey || '',
            };
            
            // Convert form data to object
            formData.forEach(item => {
                // Map simple form field names to query format
                if (item.name === 'name') {
                    data.customer_name = item.value;
                } else if (item.name === 'phone') {
                    data.customer_phone = item.value;
                } else if (item.name === 'email') {
                    data.customer_email = item.value;
                } else if (item.name === 'message') {
                    data.message = item.value;
                } else if (item.name === 'service') {
                    data.service_key = item.value;
                } else if (item.name === 'package_id') {
                    data.package_id = item.value || 0;
                } else if (item.name === 'package_name') {
                    data.package_name = item.value;
                } else if (item.name === 'child_age[]') {
                    // Collect child ages into array
                    if (!data.child_ages) {
                        data.child_ages = [];
                    }
                    if (item.value) {
                        data.child_ages.push(item.value);
                    }
                } else {
                    data[item.name] = item.value;
                }
            });
            
            // Combine child ages into comma-separated string for storage
            if (data.child_ages && data.child_ages.length > 0) {
                data.child_ages_str = data.child_ages.join(',');
            }
            
            // Validate phone is required
            if (!data.customer_phone && !data.phone) {
                PackageQueryEnhanced.showError($form, 'Phone number is required');
                return;
            }
            
            // Show loading
            const submitBtn = $form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<span>Submitting...</span>');
            
            // Submit query
            $.ajax({
                url: atcQueryForm.restUrl + 'query/submit',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', atcQueryForm.nonce);
                },
                success: function(response) {
                    if (response.success) {
                        PackageQueryEnhanced.showSuccess($form, response.message || 'Query submitted successfully!');
                        $form[0].reset();
                        
                        // Close modal after 2 seconds
                        setTimeout(function() {
                            const $modal = $form.closest('.atc-query-modal, .atc-package-query-modal');
                            $modal.removeClass('active');
                            
                            // Wait for CSS transition to complete (300ms) before hiding
                            setTimeout(function() {
                                if (!$modal.hasClass('active')) {
                                    $modal.css('display', 'none');
                                }
                            }, 350); // Slightly longer than CSS transition (300ms)
                            
                            $('body').css('overflow', ''); // Restore scroll
                        }, 2000);
                    } else {
                        PackageQueryEnhanced.showError($form, response.message || 'Failed to submit query');
                    }
                },
                error: function(xhr) {
                    console.error('Query submission error:', xhr);
                    PackageQueryEnhanced.showError($form, 'Failed to submit query. Please try again.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        showSuccess($form, message) {
            const container = $form.find('.atc-message-container');
            container.html('<div class="atc-alert atc-alert-success">' + message + '</div>');
            container.fadeIn();
            
            setTimeout(function() {
                container.fadeOut();
            }, 5000);
        },
        
        showError($form, message) {
            const container = $form.find('.atc-message-container');
            container.html('<div class="atc-alert atc-alert-error">' + message + '</div>');
            container.fadeIn();
            
            setTimeout(function() {
                container.fadeOut();
            }, 5000);
        },
        
        initConditionalFields() {
            const self = this;
            
            // Children count change - show/hide child ages
            $(document).on('input change', '.atc-children-count', function() {
                const childrenCount = parseInt($(this).val()) || 0;
                const formId = $(this).data('form-id') || '';
                const $childAgesGroup = $('#child_ages_' + formId);
                const $childAgesContainer = $('#child_ages_container_' + formId);
                
                if (childrenCount > 0) {
                    $childAgesGroup.slideDown(300);
                    $childAgesContainer.empty();
                    
                    // Create age input for each child
                    for (let i = 1; i <= childrenCount; i++) {
                        const ageInput = $('<div style="display: flex; align-items: center; gap: 10px;">' +
                            '<label style="min-width: 120px; font-size: 14px;">Child ' + i + ' Age:</label>' +
                            '<input type="number" name="child_age[]" min="0" max="17" placeholder="Age" ' +
                            'class="atc-query-field" style="flex: 1;" required>' +
                            '</div>');
                        $childAgesContainer.append(ageInput);
                    }
                } else {
                    $childAgesGroup.slideUp(300);
                    $childAgesContainer.empty();
                }
            });
            
            // Budget change - show/hide custom budget
            $(document).on('change', '[id^="query_budget_"]', function() {
                const budgetValue = $(this).val();
                const formId = $(this).attr('id').replace('query_budget_', '');
                const $customBudgetGroup = $('#custom_budget_' + formId);
                
                if (budgetValue === 'custom') {
                    $customBudgetGroup.slideDown(300);
                } else {
                    $customBudgetGroup.slideUp(300);
                }
            });
            
            // Service change - show/hide hotel type field
            $(document).on('change', '.atc-service-select', function() {
                const service = $(this).val();
                const formId = $(this).data('form-id') || '';
                const $form = $(this).closest('form');
                const $hotelTypeGroup = $form.find('.atc-hotel-type-group');
                
                // Services that need hotel type: tours, hotels
                const hotelServices = ['tours', 'hotels'];
                
                if (hotelServices.includes(service)) {
                    $hotelTypeGroup.slideDown(300);
                } else {
                    $hotelTypeGroup.slideUp(300);
                    $hotelTypeGroup.find('select').val('');
                }
            });
            
            // Initialize on page load - check current service
            $('.atc-simple-query-form').each(function() {
                const $form = $(this);
                const service = $form.data('service') || '';
                const $hotelTypeGroup = $form.find('.atc-hotel-type-group');
                const hotelServices = ['tours', 'hotels'];
                
                if (hotelServices.includes(service)) {
                    $hotelTypeGroup.show();
                }
                
                // Check children count
                const childrenCount = parseInt($form.find('.atc-children-count').val()) || 0;
                if (childrenCount > 0) {
                    $form.find('.atc-children-count').trigger('input');
                }
                
                // Check budget
                const budgetValue = $form.find('[id^="query_budget_"]').val();
                if (budgetValue === 'custom') {
                    $form.find('[id^="custom_budget_"]').closest('.atc-custom-budget-group').show();
                }
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PackageQueryEnhanced.init();
    });
    
})(jQuery);

