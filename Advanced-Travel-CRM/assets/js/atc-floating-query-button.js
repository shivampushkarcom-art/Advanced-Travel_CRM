/**
 * ATC Floating Query Form Button
 * Simple blue pill button with close functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        const $button = $('#atc-floating-query-button');
        const $closeBtn = $button.find('.atc-floating-query-close');
        const $triggerBtn = $button.find('.atc-floating-query-btn');
        const SWIPE_THRESHOLD = 12; // px
        let touchStartX = 0;
        let touchStartY = 0;
        let touchMoved = false;
        
        // Ensure close button is always visible on page load (defensive)
        if ($button.length && $closeBtn.length) {
            $closeBtn.show();
        }
        
        // Always show button after page load (buttons reappear on each page)
        setTimeout(function() {
            $button.fadeIn(300);
            // Ensure close button is always visible
            $closeBtn.show();
        }, 1000); // 1 second delay
        
        const shieldEvent = function(e) {
            if (!e) return;
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }
        };
        
        const handleCloseButton = function() {
            // Close any open query modals
            $('.atc-query-modal.active, .atc-package-query-modal.active').each(function() {
                $(this).removeClass('active').fadeOut(300);
            });
            // Hide the button for this page only (will reappear on next page/reload)
            $button.fadeOut(300, function() {
                $button.addClass('atc-query-button-hidden');
            });
        };
        
        // Close button functionality - hides button for this page only (will reappear on reload/navigation)
        $closeBtn.on('touchstart', function(e) {
            shieldEvent(e);
            });
        
        $closeBtn.on('touchend', function(e) {
            shieldEvent(e);
            handleCloseButton();
        });
        
        $closeBtn.on('click', function(e) {
            shieldEvent(e);
            handleCloseButton();
        });
        
        // Handle query form trigger
        // Touch gesture handling to ignore swipes
        $triggerBtn.on('touchstart', function(e) {
            if (!e.originalEvent.touches || !e.originalEvent.touches.length) return;
            const touch = e.originalEvent.touches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            touchMoved = false;
        });

        $triggerBtn.on('touchmove', function(e) {
            if (!e.originalEvent.touches || !e.originalEvent.touches.length) return;
            const touch = e.originalEvent.touches[0];
            const diffX = Math.abs(touch.clientX - touchStartX);
            const diffY = Math.abs(touch.clientY - touchStartY);
            if (diffX > SWIPE_THRESHOLD || diffY > SWIPE_THRESHOLD) {
                touchMoved = true;
            }
        });

        $triggerBtn.on('touchend touchcancel', function() {
            if (touchMoved) {
                $(this).data('ignore-next-click', true);
            }
        });

        $triggerBtn.on('click', function(e) {
            if ($(this).data('ignore-next-click')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $(this).removeData('ignore-next-click');
                return;
            }

            // Ensure close button remains visible
            $closeBtn.show();
        });
    });
    
})(jQuery);

