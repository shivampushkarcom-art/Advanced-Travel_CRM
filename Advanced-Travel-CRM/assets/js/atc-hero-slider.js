/**
 * ATC Hero Slider JavaScript
 * Flipkart-style hero slider functionality
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        $('.atc-hero-slider-wrapper').each(function() {
            var $slider = $(this);
            var $slides = $slider.find('.atc-hero-slide');
            var $dots = $slider.find('.atc-dot');
            var $prev = $slider.find('.atc-slider-prev');
            var $next = $slider.find('.atc-slider-next');
            
            var currentSlide = 0;
            var totalSlides = $slides.length;
            var autoplay = $slider.data('autoplay') === 'true' || $slider.data('autoplay') === true;
            var interval = parseInt($slider.data('interval')) || 5000;
            var autoplayTimer = null;
            
            if (totalSlides <= 1) {
                return; // No need for slider functionality with one slide
            }
            
            // Go to specific slide
            function goToSlide(index) {
                if (index < 0) {
                    index = totalSlides - 1;
                } else if (index >= totalSlides) {
                    index = 0;
                }
                
                // Pause all videos first
                $slides.each(function() {
                    var $slide = $(this);
                    if ($slide.hasClass('active')) {
                        var $video = $slide.find('.atc-html5-video');
                        if ($video.length > 0) {
                            $video[0].pause();
                        }
                        $slide.trigger('slideInactive');
                    }
                });
                
                $slides.removeClass('active');
                $dots.removeClass('active');
                
                $slides.eq(index).addClass('active');
                $dots.eq(index).addClass('active');
                
                currentSlide = index;
                
                // Play video on active slide
                var $activeSlide = $slides.eq(index);
                $activeSlide.trigger('slideActive');
                
                var $video = $activeSlide.find('.atc-html5-video');
                if ($video.length > 0) {
                    var video = $video[0];
                    video.play().catch(function(e) {
                        console.log('Video autoplay prevented:', e);
                    });
                }
            }
            
            // Next slide
            function nextSlide() {
                goToSlide(currentSlide + 1);
            }
            
            // Previous slide
            function prevSlide() {
                goToSlide(currentSlide - 1);
            }
            
            // Start autoplay
            function startAutoplay() {
                if (autoplay) {
                    autoplayTimer = setInterval(nextSlide, interval);
                }
            }
            
            // Stop autoplay
            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }
            
            // Dot navigation
            $dots.on('click', function() {
                var index = $(this).data('slide');
                goToSlide(index);
                stopAutoplay();
                startAutoplay();
            });
            
            // Arrow navigation
            $next.on('click', function(e) {
                e.preventDefault();
                nextSlide();
                stopAutoplay();
                startAutoplay();
            });
            
            $prev.on('click', function(e) {
                e.preventDefault();
                prevSlide();
                stopAutoplay();
                startAutoplay();
            });
            
            // Pause on hover
            $slider.on('mouseenter', function() {
                stopAutoplay();
            }).on('mouseleave', function() {
                startAutoplay();
            });
            
            // Touch/swipe support for mobile
            var touchStartX = 0;
            var touchEndX = 0;
            
            $slider.on('touchstart', function(e) {
                touchStartX = e.originalEvent.touches[0].clientX;
                stopAutoplay();
            });
            
            $slider.on('touchend', function(e) {
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                handleSwipe();
                startAutoplay();
            });
            
            function handleSwipe() {
                var swipeThreshold = 50;
                var diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        // Swipe left - next slide
                        nextSlide();
                    } else {
                        // Swipe right - previous slide
                        prevSlide();
                    }
                }
            }
            
            // Keyboard navigation
            $slider.on('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    prevSlide();
                    stopAutoplay();
                    startAutoplay();
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    nextSlide();
                    stopAutoplay();
                    startAutoplay();
                }
            });
            
            // Auto-detect text color based on background
            function detectTextColor($slide) {
                var $img = $slide.find('.atc-slide-image img');
                if ($img.length === 0) return;
                
                // Create a canvas to analyze image colors
                var img = $img[0];
                if (!img.complete) {
                    img.onload = function() {
                        analyzeImageColor(img, $slide);
                    };
                    return;
                }
                
                analyzeImageColor(img, $slide);
            }
            
            function analyzeImageColor(img, $slide) {
                try {
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');
                    
                    // Sample from bottom center (where text overlay is)
                    canvas.width = 100;
                    canvas.height = 50;
                    
                    // Draw image to canvas
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    
                    // Get pixel data from bottom center area
                    var imageData = ctx.getImageData(0, 30, 100, 20);
                    var pixels = imageData.data;
                    
                    // Calculate average brightness
                    var totalBrightness = 0;
                    var pixelCount = 0;
                    
                    for (var i = 0; i < pixels.length; i += 4) {
                        var r = pixels[i];
                        var g = pixels[i + 1];
                        var b = pixels[i + 2];
                        
                        // Calculate luminance (perceived brightness)
                        var brightness = (r * 0.299 + g * 0.587 + b * 0.114);
                        totalBrightness += brightness;
                        pixelCount++;
                    }
                    
                    var avgBrightness = totalBrightness / pixelCount;
                    
                    // If background is light (brightness > 128), use dark text, else use white
                    // Priority: white text (default), only switch if background is very light
                    var textColor = '#ffffff'; // Default white
                    if (avgBrightness > 200) { // Very light background
                        textColor = '#000000'; // Use black text
                    } else if (avgBrightness > 180) { // Light background
                        textColor = '#1a1a1a'; // Dark gray
                    }
                    
                    // Apply color to slide content
                    $slide.find('.atc-slide-content').css('--text-color', textColor);
                    
                    // Adjust text shadow for better contrast
                    if (avgBrightness > 180) {
                        $slide.find('.atc-slide-title').css('text-shadow', 'none');
                        $slide.find('.atc-slide-description').css('text-shadow', 'none');
                    }
                } catch (e) {
                    // Fallback to white if analysis fails
                    console.log('Color detection failed, using default white text');
                }
            }
            
            // Detect color for each slide
            $slides.each(function() {
                detectTextColor($(this));
            });
            
            // Re-detect when slide changes
            var originalGoToSlide = goToSlide;
            goToSlide = function(index) {
                originalGoToSlide(index);
                var $activeSlide = $slides.eq(index);
                detectTextColor($activeSlide);
            };
            
            // Video playback handling
            $slides.each(function() {
                var $slide = $(this);
                var mediaType = $slide.data('media-type') || 'image';
                
                if (mediaType === 'video') {
                    var $video = $slide.find('.atc-html5-video');
                    var $iframe = $slide.find('iframe');
                    
                    // Handle HTML5 video
                    if ($video.length > 0) {
                        var video = $video[0];
                        
                        // Ensure video plays when slide becomes active
                        $slide.on('slideActive', function() {
                            if (video.paused) {
                                video.play().catch(function(e) {
                                    console.log('Video autoplay prevented:', e);
                                });
                            }
                        });
                        
                        // Pause video when slide becomes inactive
                        $slide.on('slideInactive', function() {
                            if (!video.paused) {
                                video.pause();
                            }
                        });
                    }
                    
                    // Handle YouTube/Vimeo iframes
                    if ($iframe.length > 0) {
                        // YouTube/Vimeo videos autoplay via URL parameters
                        // No additional handling needed as they're configured for autoplay
                    }
                }
            });
            
            
            // Initialize
            startAutoplay();
            
            // Trigger initial video play for first slide
            var $firstSlide = $slides.first();
            if ($firstSlide.length > 0 && $firstSlide.data('media-type') === 'video') {
                $firstSlide.trigger('slideActive');
                var $firstVideo = $firstSlide.find('.atc-html5-video');
                if ($firstVideo.length > 0) {
                    $firstVideo[0].play().catch(function(e) {
                        console.log('Video autoplay prevented:', e);
                    });
                }
            }
        });
    });
    
})(jQuery);

