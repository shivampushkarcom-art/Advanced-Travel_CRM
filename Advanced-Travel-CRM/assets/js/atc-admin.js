/**
 * ========================================
 * FILE: atc-admin.js
 * Admin dashboard interactions
 * ========================================
 */
(function($) {
    'use strict';
    
    const Admin = {
        init() {
            this.bindEvents();
        },
        
        bindEvents() {
            // View customer details modal
            $(document).on('click', '.atc-view-customer', function(e) {
                e.preventDefault();
                const $btn = $(this);
                Admin.showCustomerModal({
                    bookingId: $btn.data('booking-id'),
                    customerName: $btn.data('customer-name'),
                    customerEmail: $btn.data('customer-email'),
                    customerPhone: $btn.data('customer-phone'),
                    service: $btn.data('service'),
                    price: $btn.data('price'),
                    currency: $btn.data('currency'),
                    status: $btn.data('status'),
                    paymentStatus: $btn.data('payment-status'),
                    created: $btn.data('created')
                });
            });
            
            // Send notification button
            $(document).on('click', '.atc-send-notification', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('booking-id');
                Admin.sendNotification(bookingId);
            });
            
            // Resend email
            $('.atc-resend-email').on('click', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('booking-id');
                Admin.resendEmail(bookingId);
            });
            
            // Mark lead as contacted
            $('.atc-mark-contacted').on('click', function(e) {
                e.preventDefault();
                const leadId = $(this).data('lead-id');
                Admin.markLeadContacted(leadId);
            });
            
            // Update booking status
            $('.atc-status-select').on('change', function() {
                if (confirm('Update booking status?')) {
                    $(this).closest('form').submit();
                }
            });
            
            // Close modal on overlay click
            $(document).on('click', '.atc-modal-overlay', function(e) {
                if ($(e.target).hasClass('atc-modal-overlay')) {
                    Admin.closeModal();
                }
            });
            
            // Close modal on close button
            $(document).on('click', '.atc-modal-close', function() {
                Admin.closeModal();
            });
        },
        
        showCustomerModal(data) {
            const modalHtml = `
                <div class="atc-modal-overlay" style="display: flex;">
                    <div class="atc-modal" style="max-width: 600px; width: 90%;">
                        <div class="atc-modal-header">
                            <h2>Booking Details</h2>
                            <button class="atc-modal-close" type="button">&times;</button>
                        </div>
                        <div class="atc-modal-body">
                            <div class="atc-detail-row">
                                <strong>Booking ID:</strong> #${data.bookingId}
                            </div>
                            <div class="atc-detail-row">
                                <strong>Customer Name:</strong> ${data.customerName || 'N/A'}
                            </div>
                            <div class="atc-detail-row">
                                <strong>Email:</strong> <a href="mailto:${data.customerEmail}">${data.customerEmail || 'N/A'}</a>
                            </div>
                            <div class="atc-detail-row">
                                <strong>Phone:</strong> <a href="tel:${data.customerPhone}">${data.customerPhone || 'N/A'}</a>
                            </div>
                            <div class="atc-detail-row">
                                <strong>Service:</strong> ${data.service ? data.service.charAt(0).toUpperCase() + data.service.slice(1) : 'N/A'}
                            </div>
                            <div class="atc-detail-row">
                                <strong>Price:</strong> ${data.currency || 'INR'} ${parseFloat(data.price || 0).toFixed(2)}
                            </div>
                            <div class="atc-detail-row">
                                <strong>Status:</strong> <span class="atc-status-badge atc-status-${data.status}">${data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A'}</span>
                            </div>
                            <div class="atc-detail-row">
                                <strong>Payment Status:</strong> <span class="atc-status-badge atc-status-${data.paymentStatus}">${data.paymentStatus ? data.paymentStatus.charAt(0).toUpperCase() + data.paymentStatus.slice(1) : 'N/A'}</span>
                            </div>
                            <div class="atc-detail-row">
                                <strong>Created:</strong> ${data.created ? new Date(data.created).toLocaleString() : 'N/A'}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },
        
        closeModal() {
            $('.atc-modal-overlay').fadeOut(200, function() {
                $(this).remove();
            });
        },
        
        async sendNotification(bookingId) {
            if (!bookingId) {
                alert('Booking ID is required');
                return;
            }
            
            const $btn = $('.atc-send-notification[data-booking-id="' + bookingId + '"]');
            const originalText = $btn.html();
            $btn.prop('disabled', true).html('📧 Sending...');
            
            try {
                const response = await fetch(atcAdmin.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'atc_send_booking_notification',
                        _wpnonce: atcAdmin.nonce,
                        booking_id: bookingId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Notification sent successfully to all admin recipients!');
                } else {
                    alert('❌ Failed to send notification: ' + (result.data?.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Failed to send notification. Please try again.');
            } finally {
                $btn.prop('disabled', false).html(originalText);
            }
        },
        
        async resendEmail(bookingId) {
            try {
                const response = await fetch(atcAdmin.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'atc_resend_email',
                        nonce: atcAdmin.nonce,
                        booking_id: bookingId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Email sent successfully!');
                } else {
                    alert('Failed to send email: ' + result.data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to send email.');
            }
        },
        
        async markLeadContacted(leadId) {
            try {
                const response = await fetch(atcAdmin.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'atc_mark_lead_contacted',
                        nonce: atcAdmin.nonce,
                        lead_id: leadId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Lead marked as contacted!');
                    location.reload();
                } else {
                    alert('Failed to update lead.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update lead.');
            }
        }
    };
    
    $(document).ready(() => Admin.init());
    
})(jQuery);