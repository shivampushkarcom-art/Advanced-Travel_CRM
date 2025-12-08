/**
 * ATC Floating WhatsApp Button JavaScript
 * Shows button after delay with smooth animation
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        var $button = $('#atc-whatsapp-button');
        
        if ($button.length === 0) {
            return;
        }
        
        var settings = window.atcWhatsApp || {};
        var delay = settings.delay || 3000;
        var mobileEnabled = settings.mobileEnabled !== false;
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        // Hide on mobile if disabled
        if (isMobile && !mobileEnabled) {
            $button.addClass('atc-whatsapp-mobile-disabled');
            return;
        }
        
        // Show button after delay
        setTimeout(function() {
            $button.fadeIn(500);
        }, delay);
        
        // Track click
        $button.find('.atc-whatsapp-link').on('click', function() {
            // Analytics tracking (if available)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'whatsapp_click', {
                    'event_category': 'Contact',
                    'event_label': 'WhatsApp Button'
                });
            }
        });
    });
    
})(jQuery);

