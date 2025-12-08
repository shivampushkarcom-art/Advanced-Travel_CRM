/**
 * ========================================
 * FILE: atc-booking-window.js
 * Dynamic booking modal functionality
 * ========================================
 */
(function($) {
    'use strict';
    
    const BookingWindow = {
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            $(document).on('click', '[data-atc-booking-trigger]', (e) => {
                e.preventDefault();
                const service = $(e.currentTarget).data('service');
                this.openModal(service);
            });
            
            $('.atc-modal-close, .atc-modal-overlay').on('click', () => {
                this.closeModal();
            });
        },
        
        openModal(service) {
            this.loadBookingForm(service);
            $('#atc-booking-modal').fadeIn(300);
            $('body').addClass('atc-modal-open');
        },
        
        closeModal() {
            $('#atc-booking-modal').fadeOut(300);
            $('body').removeClass('atc-modal-open');
        },
        
        async loadBookingForm(service) {
            const container = $('#atc-booking-form-container');
            container.html('<div class="atc-loading">Loading...</div>');
            
            try {
                const vars = typeof atcVars !== 'undefined' ? atcVars : null;
                if (!vars) {
                    container.html('<div class="atc-error">Configuration error</div>');
                    return;
                }
                const response = await fetch(`${vars.restUrl}service/${service}`, {
                    headers: {
                        'X-WP-Nonce': vars.restNonce
                    }
                });
                
                const config = await response.json();
                this.renderForm(config);
            } catch (error) {
                container.html('<div class="atc-error">Failed to load form</div>');
            }
        },
        
        renderForm(serviceConfig) {
            const fields = serviceConfig.booking_fields || [];
            let html = '<form class="atc-booking-form" data-service="' + serviceConfig.service_key + '">';
            
            fields.forEach(field => {
                html += this.renderField(field);
            });
            
            // Get currency symbol from global vars
            const vars = typeof atcVars !== 'undefined' ? atcVars : { currencySymbol: '₹' };
            html += '<div class="atc-total">Total: ' + vars.currencySymbol + ' 0</div>';
            html += '<button type="submit" class="atc-btn atc-btn-primary">Complete Booking</button>';
            html += '</form>';
            
            $('#atc-booking-form-container').html(html);
            
            // Initialize calculator
            if (typeof AtcCalculator !== 'undefined') {
                new AtcCalculator($('.atc-booking-form')[0], serviceConfig);
            }
        },
        
        renderField(field) {
            let html = '<div class="atc-form-field">';
            html += '<label>' + field.label + (field.required ? ' *' : '') + '</label>';
            
            if (field.type === 'select') {
                html += '<select name="' + field.id + '"' + (field.required ? ' required' : '') + '>';
                field.options.forEach(opt => {
                    html += '<option value="' + opt + '">' + opt + '</option>';
                });
                html += '</select>';
            } else {
                html += '<input type="' + field.type + '" name="' + field.id + '"';
                if (field.placeholder) html += ' placeholder="' + field.placeholder + '"';
                if (field.required) html += ' required';
                html += '>';
            }
            
            html += '</div>';
            return html;
        }
    };
    
    $(document).ready(() => BookingWindow.init());
    
})(jQuery);
