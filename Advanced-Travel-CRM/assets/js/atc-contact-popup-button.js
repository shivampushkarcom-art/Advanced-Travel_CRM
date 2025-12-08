/**
 * ATC Contact Popup Button JavaScript
 * Floating contact button with dismissible popup
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        const $button = $('#atc-contact-popup-button');
        const $closeBtn = $button.find('.atc-contact-popup-close');
        const $modal = $('#atc-contact-popup-modal');
        const $modalClose = $modal.find('.atc-contact-popup-modal-close');
        const $trigger = $button.find('.atc-contact-popup-trigger');
        const $overlay = $modal.find('.atc-contact-popup-overlay');
        
        const settings = window.atcContactPopup || {};
        const delay = settings.delay || 3000;
        
        // Always show button after delay (buttons reappear on each page)
            setTimeout(function() {
                $button.fadeIn(300);
            $closeBtn.show(); // Ensure close button is always visible
            }, delay);
        
        // Ensure button and close button are visible on page load (defensive)
        if ($button.length) {
            $closeBtn.show();
        }
        
        // Close button functionality - hides button for this page only (will reappear on reload/navigation)
        $closeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Close modal if open
            if ($modal.is(':visible')) {
                closeModal();
            }
            // Hide the button for this page only (will reappear on next page/reload)
            $button.fadeOut(300, function() {
                $button.addClass('atc-contact-popup-hidden');
            });
        });
        
        // Open modal when trigger is clicked
        $trigger.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
        });
        
        // Close modal
        function closeModal() {
            $modal.fadeOut(300);
            // Unlock body scroll
            unlockBodyScroll();
        }
        
        function openModal() {
            // Lock body scroll
            lockBodyScroll();
            $modal.fadeIn(300);
        }
        
        // Close modal on overlay click
        $overlay.on('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Close modal on close button click
        $modalClose.on('click', function(e) {
            e.preventDefault();
            closeModal();
        });
        
        // Close modal on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                closeModal();
            }
        });
        
        // Lock/unlock body scroll functions
        function lockBodyScroll() {
            const scrollY = window.scrollY || window.pageYOffset;
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';
            document.body.classList.add('atc-popup-open');
            document.body.setAttribute('data-scroll-y', scrollY);
        }
        
        function unlockBodyScroll() {
            const scrollY = document.body.getAttribute('data-scroll-y') || 0;
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            document.body.classList.remove('atc-popup-open');
            window.scrollTo(0, parseInt(scrollY));
            document.body.removeAttribute('data-scroll-y');
        }
        
        // Prevent scroll propagation from modal content to body
        $modal.find('.atc-contact-popup-content').on('wheel touchmove', function(e) {
            const container = this;
            const scrollTop = container.scrollTop;
            const scrollHeight = container.scrollHeight;
            const height = container.clientHeight;
            const wheelDelta = e.originalEvent.deltaY || 0;
            
            const isAtTop = scrollTop === 0 && wheelDelta < 0;
            const isAtBottom = scrollTop + height >= scrollHeight - 1 && wheelDelta > 0;
            
            if (!isAtTop && !isAtBottom) {
                e.stopPropagation();
            }
        });
    });
    
})(jQuery);

