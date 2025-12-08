/**
 * ATC Gallery Lightbox
 * MakeMyTrip-Style Image Gallery with Lightbox
 */

(function($) {
    'use strict';
    
    const GalleryLightbox = {
        currentIndex: 0,
        images: [],
        isOpen: false,
        
        init() {
            this.bindEvents();
            this.createLightboxHTML();
        },
        
        createLightboxHTML() {
            if ($('#atc-gallery-lightbox').length) return;
            
            const lightboxHTML = `
                <div id="atc-gallery-lightbox" class="atc-lightbox" style="display: none;">
                    <div class="atc-lightbox-overlay"></div>
                    <div class="atc-lightbox-container">
                        <button class="atc-lightbox-close" aria-label="Close lightbox">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M18 6L6 18M6 6L18 18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button class="atc-lightbox-prev" aria-label="Previous image">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M15 18L9 12L15 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <button class="atc-lightbox-next" aria-label="Next image">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M9 18L15 12L9 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="atc-lightbox-image-container">
                            <img id="atc-lightbox-image" src="" alt="Gallery Image">
                            <div class="atc-lightbox-loader">
                                <div class="atc-lightbox-spinner"></div>
                            </div>
                        </div>
                        <div class="atc-lightbox-info">
                            <span class="atc-lightbox-counter">
                                <span id="atc-lightbox-current">1</span> / <span id="atc-lightbox-total">1</span>
                            </span>
                        </div>
                        <div class="atc-lightbox-thumbnails"></div>
                    </div>
                </div>
            `;
            
            $('body').append(lightboxHTML);
        },
        
        bindEvents() {
            // Open lightbox (service-agnostic)
            $(document).on('click', '[data-action="open-lightbox"]', (e) => {
                e.preventDefault();
                // Try to find gallery container (service-agnostic)
                const gallery = $(e.currentTarget).closest('[class*="gallery"]');
                if (gallery.length) {
                    const images = JSON.parse(gallery.attr('data-gallery-images') || '[]');
                    if (images.length > 0) {
                        this.open(images, 0);
                    }
                }
            });
            
            // Change main image
            $(document).on('click', '[data-action="change-main"]', (e) => {
                e.preventDefault();
                const $img = $(e.currentTarget);
                const index = parseInt($img.attr('data-index') || 0);
                const src = $img.attr('src');
                
                $('#atc-gallery-main').attr('src', src);
                
                // Update active thumbnail
                $('.atc-gallery-image').removeClass('atc-gallery-active');
                $img.addClass('atc-gallery-active');
            });
            
            // Lightbox controls
            $(document).on('click', '.atc-lightbox-close, .atc-lightbox-overlay', () => {
                this.close();
            });
            
            $(document).on('click', '.atc-lightbox-prev', () => {
                this.prev();
            });
            
            $(document).on('click', '.atc-lightbox-next', () => {
                this.next();
            });
            
            // Keyboard navigation
            $(document).on('keydown', (e) => {
                if (!this.isOpen) return;
                
                if (e.key === 'Escape') {
                    this.close();
                } else if (e.key === 'ArrowLeft') {
                    this.prev();
                } else if (e.key === 'ArrowRight') {
                    this.next();
                }
            });
            
            // Thumbnail click
            $(document).on('click', '.atc-lightbox-thumb', (e) => {
                const index = parseInt($(e.currentTarget).attr('data-index') || 0);
                this.goTo(index);
            });
        },
        
        open(images, startIndex = 0) {
            if (!images || images.length === 0) return;
            
            this.images = images;
            this.currentIndex = startIndex;
            this.isOpen = true;
            
            const $lightbox = $('#atc-gallery-lightbox');
            $lightbox.fadeIn(300);
            $('body').addClass('atc-lightbox-open');
            
            this.updateImage();
            this.updateThumbnails();
        },
        
        close() {
            this.isOpen = false;
            const $lightbox = $('#atc-gallery-lightbox');
            $lightbox.fadeOut(300);
            $('body').removeClass('atc-lightbox-open');
        },
        
        prev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
            } else {
                this.currentIndex = this.images.length - 1;
            }
            this.updateImage();
        },
        
        next() {
            if (this.currentIndex < this.images.length - 1) {
                this.currentIndex++;
            } else {
                this.currentIndex = 0;
            }
            this.updateImage();
        },
        
        goTo(index) {
            if (index >= 0 && index < this.images.length) {
                this.currentIndex = index;
                this.updateImage();
            }
        },
        
        updateImage() {
            if (this.images.length === 0) return;
            
            const $img = $('#atc-lightbox-image');
            const $loader = $('.atc-lightbox-loader');
            const $container = $('.atc-lightbox-image-container');
            
            $loader.show();
            $container.addClass('atc-loading');
            
            const imageUrl = this.images[this.currentIndex];
            const img = new Image();
            
            img.onload = () => {
                $img.attr('src', imageUrl);
                $loader.hide();
                $container.removeClass('atc-loading');
            };
            
            img.onerror = () => {
                $loader.hide();
                $container.removeClass('atc-loading');
                $img.attr('src', '');
                $container.html('<div class="atc-lightbox-error">Failed to load image</div>');
            };
            
            img.src = imageUrl;
            
            // Update counter
            $('#atc-lightbox-current').text(this.currentIndex + 1);
            $('#atc-lightbox-total').text(this.images.length);
            
            // Update active thumbnail
            $('.atc-lightbox-thumb').removeClass('atc-lightbox-thumb-active');
            $(`.atc-lightbox-thumb[data-index="${this.currentIndex}"]`).addClass('atc-lightbox-thumb-active');
        },
        
        updateThumbnails() {
            const $thumbnails = $('.atc-lightbox-thumbnails');
            $thumbnails.empty();
            
            this.images.forEach((image, index) => {
                const $thumb = $('<div>')
                    .addClass('atc-lightbox-thumb')
                    .attr('data-index', index)
                    .append($('<img>').attr('src', image).attr('alt', `Thumbnail ${index + 1}`));
                
                if (index === this.currentIndex) {
                    $thumb.addClass('atc-lightbox-thumb-active');
                }
                
                $thumbnails.append($thumb);
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        GalleryLightbox.init();
    });
    
    // Make it globally available
    window.atcGalleryLightbox = GalleryLightbox;
    
})(jQuery);

