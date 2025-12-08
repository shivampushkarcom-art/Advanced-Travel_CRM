/**
 * Enhanced Package Details JavaScript
 * MakeMyTrip-Style Package Details Page
 */

(function($) {
    'use strict';
    
    const PackageDetailsEnhanced = {
        init() {
            this.loadPackageDetails();
            this.bindEvents();
            this.initInteractions();
        },
        
        loadPackageDetails() {
            // Service-agnostic wrapper detection
            const wrapper = $('[class*="atc-package-details"][class*="wrapper"]').filter(function() {
                return $(this).data('package-id');
            });
            
            if (wrapper.length === 0) return;
            
            const packageId = wrapper.data('package-id');
            if (!packageId) {
                this.showError('Package ID not found');
                return;
            }
            
            // Load package data
            $.ajax({
                url: atcPackageDetails.restUrl + 'package/' + packageId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', atcPackageDetails.nonce);
                },
                success: (response) => {
                    if (response.success && response.package) {
                        this.renderPackage(response.package, wrapper);
                    } else {
                        this.showError('Package not found');
                    }
                },
                error: (xhr) => {
                    this.showError('Failed to load package details');
                    console.error('Package details error:', xhr);
                }
            });
        },
        
        renderPackage(packageData, wrapper) {
            // Get service type
            const service = packageData.service_key || 'tours';
            
            // Service-agnostic selectors
            const servicePrefix = this.getServicePrefix(service);
            const loading = wrapper.find(`.${servicePrefix}-loading, .atc-loading-state`);
            const content = wrapper.find(`.${servicePrefix}-content, .atc-package-details-${service}-content`);
            
            // Hide loading
            loading.hide();
            
            // Render based on service
            if (service === 'tours') {
                this.renderToursPackage(packageData, content);
            } else if (service === 'forex') {
                // Forex will be server-rendered, but we can add client-side fallback if needed
                this.renderForexPackage(packageData, content);
            } else if (service === 'visa') {
                // Visa will be server-rendered, but we can add client-side fallback if needed
                this.renderVisaPackage(packageData, content);
            } else if (service === 'hotels') {
                // Hotels will be server-rendered, but we can add client-side fallback if needed
                this.renderHotelsPackage(packageData, content);
            } else if (service === 'flights') {
                // Flights will be server-rendered, but we can add client-side fallback if needed
                this.renderFlightsPackage(packageData, content);
            } else if (service === 'trains') {
                // Trains will be server-rendered, but we can add client-side fallback if needed
                this.renderTrainsPackage(packageData, content);
            } else if (service === 'cars') {
                // Cars will be server-rendered, but we can add client-side fallback if needed
                this.renderCarsPackage(packageData, content);
            } else {
                this.renderDefaultPackage(packageData, content);
            }
            
            // Show content
            content.fadeIn();
        },
        
        getServicePrefix(service) {
            // Map service keys to CSS class prefixes
            const servicePrefixes = {
                'tours': 'atc-tours',
                'forex': 'atc-forex',
                'visa': 'atc-visa',
                'hotels': 'atc-hotels',
                'flights': 'atc-flights',
                'trains': 'atc-trains',
                'cars': 'atc-cars'
            };
            return servicePrefixes[service] || 'atc-default';
        },
        
        renderFlightsPackage(packageData, container) {
            // Flights packages are primarily server-rendered
            // If we need client-side fallback, it would go here
            // For now, just ensure interactions are initialized
            this.initInteractions('flights');
        },
        
        renderTrainsPackage(packageData, container) {
            // Trains packages are primarily server-rendered
            // If we need client-side fallback, it would go here
            // For now, just ensure interactions are initialized
            this.initInteractions('trains');
        },
        
        renderCarsPackage(packageData, container) {
            // Cars packages are primarily server-rendered
            // If we need client-side fallback, it would go here
            // For now, just ensure interactions are initialized
            this.initInteractions('cars');
            
            // Initialize gallery thumbnail switching
            this.initCarsGallery();
        },
        
        initCarsGallery() {
            // Gallery thumbnail switching for cars
            $(document).on('click', '.atc-cars-thumb', function() {
                const $thumb = $(this);
                const imageUrl = $thumb.data('image');
                
                if (imageUrl) {
                    // Update main image
                    $('#atc-cars-main-img').attr('src', imageUrl);
                    
                    // Update active state
                    $('.atc-cars-thumb').removeClass('active');
                    $thumb.addClass('active');
                }
            });
        },
        
        renderHotelsPackage(packageData, container) {
            // Hotels packages are primarily server-rendered
            // If we need client-side fallback, it would go here
            // For now, just ensure interactions are initialized
            this.initInteractions('hotels');
            
            // Initialize gallery thumbnail switching
            this.initHotelsGallery();
        },
        
        initHotelsGallery() {
            // Gallery thumbnail switching for hotels
            $(document).on('click', '.atc-hotels-thumb', function() {
                const $thumb = $(this);
                const imageUrl = $thumb.data('image');
                
                if (imageUrl) {
                    // Update main image
                    $('#atc-hotels-main-img').attr('src', imageUrl);
                    
                    // Update active state
                    $('.atc-hotels-thumb').removeClass('active');
                    $thumb.addClass('active');
                }
            });
        },
        
        renderVisaPackage(packageData, container) {
            // Visa packages are primarily server-rendered
            // If we need client-side fallback, it would go here
            // For now, just ensure interactions are initialized
            this.initInteractions('visa');
        },
        
        renderToursPackage(packageData, container) {
            // This will be rendered server-side via PHP
            // For now, we'll make an AJAX call to get rendered HTML
            $.ajax({
                url: atcPackageDetails.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'atc_render_tours_package',
                    package_id: packageData.id,
                    nonce: atcPackageDetails.nonce
                },
                success: (response) => {
                    if (response.success) {
                        container.html(response.data.html);
                        this.initGallery('tours');
                        this.initInteractions('tours');
                    }
                },
                error: () => {
                    // Fallback: render client-side
                    container.html(this.renderToursPackageHTML(packageData));
                    this.initGallery('tours');
                    this.initInteractions('tours');
                }
            });
        },
        
        renderForexPackage(packageData, container) {
            // Forex packages are primarily server-rendered
            // If we need client-side fallback, it would go here
            // For now, just ensure interactions are initialized
            this.initInteractions('forex');
        },
        
        renderToursPackageHTML(packageData) {
            // Client-side rendering fallback
            const currency = atcPackageDetails.currency;
            let html = '';
            
            // Hero section
            html += `<div class="atc-tours-hero">`;
            if (packageData.image_url) {
                html += `<img src="${packageData.image_url}" alt="${packageData.name}" class="atc-tours-hero-image">`;
            }
            html += `<div class="atc-tours-hero-overlay"></div>`;
            html += `<div class="atc-tours-hero-content">`;
            html += `<h1 class="atc-tours-hero-title">${packageData.name}</h1>`;
            if (packageData.short_description) {
                html += `<p class="atc-tours-hero-subtitle">${packageData.short_description}</p>`;
            }
            html += `<div class="atc-tours-hero-meta">`;
            if (packageData.destination) {
                html += `<div class="atc-tours-hero-meta-item"><span>📍</span><span>${packageData.destination}</span></div>`;
            }
            if (packageData.duration_days) {
                html += `<div class="atc-tours-hero-meta-item"><span>📅</span><span>${packageData.duration_days} Days</span></div>`;
            }
            if (packageData.rating) {
                html += `<div class="atc-tours-hero-meta-item"><span>⭐</span><span>${packageData.rating}/5</span></div>`;
            }
            html += `</div></div></div>`;
            
            // Gallery
            if (packageData.gallery_images && packageData.gallery_images.length > 0) {
                html += this.renderGallery(packageData.gallery_images);
            }
            
            // Content wrapper
            html += `<div class="atc-tours-content-wrapper">`;
            html += `<div class="atc-tours-main-content">`;
            
            // Highlights
            if (packageData.highlights && packageData.highlights.length > 0) {
                html += this.renderHighlights(packageData.highlights);
            }
            
            // Description
            if (packageData.description) {
                html += this.renderDescription(packageData.description);
            }
            
            // Itinerary
            if (packageData.day_wise_itinerary && packageData.day_wise_itinerary.length > 0) {
                html += this.renderItinerary(packageData.day_wise_itinerary);
            }
            
            // Inclusions & Exclusions
            html += this.renderInclusionsExclusions(packageData);
            
            // Tags
            if (packageData.category || packageData.package_type || (packageData.tags && packageData.tags.length > 0)) {
                html += this.renderTags(packageData);
            }
            
            html += `</div>`; // Close main-content
            
            // Pricing Card
            html += this.renderPricingCard(packageData, currency);
            
            html += `</div>`; // Close content-wrapper
            
            return html;
        },
        
        renderGallery(images) {
            if (!images || images.length === 0) return '';
            
            let html = `<div class="atc-tours-gallery">`;
            html += `<div class="atc-tours-gallery-main">`;
            html += `<div class="atc-tours-gallery-primary">`;
            html += `<img src="${images[0]}" alt="Gallery Image 1" id="atc-gallery-main">`;
            html += `<div class="atc-tours-gallery-view-all" onclick="atcOpenGallery()">View All Photos</div>`;
            html += `</div>`;
            
            if (images.length > 1) {
                html += `<div class="atc-tours-gallery-secondary">`;
                if (images[1]) {
                    html += `<div class="atc-tours-gallery-secondary-item"><img src="${images[1]}" onclick="atcChangeMainImage(this.src)"></div>`;
                }
                if (images[2]) {
                    html += `<div class="atc-tours-gallery-secondary-item"><img src="${images[2]}" onclick="atcChangeMainImage(this.src)"></div>`;
                }
                html += `</div>`;
            }
            html += `</div>`;
            
            if (images.length > 3) {
                html += `<div class="atc-tours-gallery-thumbnails">`;
                images.slice(3, 9).forEach((img, idx) => {
                    html += `<div class="atc-tours-gallery-thumb"><img src="${img}" onclick="atcChangeMainImage(this.src)"></div>`;
                });
                html += `</div>`;
            }
            
            html += `</div>`;
            return html;
        },
        
        renderHighlights(highlights) {
            let html = `<div class="atc-tours-section">`;
            html += `<h2 class="atc-tours-section-title">✨ Package Highlights</h2>`;
            html += `<div class="atc-tours-highlights">`;
            highlights.forEach(highlight => {
                html += `<div class="atc-tours-highlight-item">`;
                html += `<span class="atc-tours-highlight-icon">✓</span>`;
                html += `<span class="atc-tours-highlight-text">${highlight}</span>`;
                html += `</div>`;
            });
            html += `</div></div>`;
            return html;
        },
        
        renderDescription(description) {
            return `<div class="atc-tours-section">
                <h2 class="atc-tours-section-title">📖 About This Package</h2>
                <div class="atc-tours-description">${description.replace(/\n/g, '<br>')}</div>
            </div>`;
        },
        
        renderItinerary(itinerary) {
            let html = `<div class="atc-tours-section">`;
            html += `<h2 class="atc-tours-section-title">🗓️ Day-wise Itinerary</h2>`;
            html += `<div class="atc-tours-itinerary">`;
            
            itinerary.forEach((day, index) => {
                const dayNum = index + 1;
                const dayTitle = day.title || `Day ${dayNum}`;
                const dayContent = day.content || day;
                const activities = day.activities || [];
                
                html += `<div class="atc-tours-itinerary-day">`;
                html += `<h3 class="atc-tours-itinerary-day-title">${dayTitle}</h3>`;
                html += `<div class="atc-tours-itinerary-day-content">${dayContent.replace(/\n/g, '<br>')}</div>`;
                if (activities.length > 0) {
                    html += `<div class="atc-tours-itinerary-day-activities">`;
                    activities.forEach(activity => {
                        html += `<span class="atc-tours-activity-tag">${activity}</span>`;
                    });
                    html += `</div>`;
                }
                html += `</div>`;
            });
            
            html += `</div></div>`;
            return html;
        },
        
        renderInclusionsExclusions(packageData) {
            let html = `<div class="atc-tours-section">`;
            html += `<h2 class="atc-tours-section-title">📋 What's Included</h2>`;
            html += `<div class="atc-tours-inclusions-exclusions">`;
            
            // Inclusions
            html += `<div class="atc-tours-inclusions">`;
            html += `<h3>✅ Inclusions</h3>`;
            if (packageData.inclusions && packageData.inclusions.length > 0) {
                html += `<ul>`;
                packageData.inclusions.forEach(inc => {
                    html += `<li>${inc}</li>`;
                });
                html += `</ul>`;
            } else {
                html += `<p>Contact us for details</p>`;
            }
            html += `</div>`;
            
            // Exclusions
            html += `<div class="atc-tours-exclusions">`;
            html += `<h3>❌ Exclusions</h3>`;
            if (packageData.exclusions && packageData.exclusions.length > 0) {
                html += `<ul>`;
                packageData.exclusions.forEach(exc => {
                    html += `<li>${exc}</li>`;
                });
                html += `</ul>`;
            } else {
                html += `<p>Contact us for details</p>`;
            }
            html += `</div>`;
            
            html += `</div></div>`;
            return html;
        },
        
        renderTags(packageData) {
            let html = `<div class="atc-tours-section">`;
            html += `<h2 class="atc-tours-section-title">🏷️ Categories & Tags</h2>`;
            html += `<div class="atc-tours-tags">`;
            
            if (packageData.category) {
                html += `<span class="atc-tours-category-badge">${packageData.category}</span>`;
            }
            if (packageData.package_type) {
                html += `<span class="atc-tours-tag">${packageData.package_type}</span>`;
            }
            if (packageData.tags && packageData.tags.length > 0) {
                packageData.tags.forEach(tag => {
                    html += `<span class="atc-tours-tag">${tag}</span>`;
                });
            }
            
            html += `</div></div>`;
            return html;
        },
        
        renderPricingCard(packageData, currency) {
            const discount = packageData.original_price && packageData.original_price > packageData.price
                ? Math.round(((packageData.original_price - packageData.price) / packageData.original_price) * 100)
                : 0;
            
            let html = `<div class="atc-tours-pricing-card">`;
            html += `<div class="atc-tours-price-header">`;
            html += `<div class="atc-tours-price-label">Starting from</div>`;
            html += `<div class="atc-tours-price-main">`;
            if (packageData.original_price && packageData.original_price > packageData.price) {
                html += `<span class="atc-tours-price-original">${currency}${this.formatPrice(packageData.original_price)}</span>`;
            }
            html += `<span class="atc-tours-price-current">${currency}${this.formatPrice(packageData.price)}</span>`;
            html += `</div>`;
            html += `<div class="atc-tours-price-per">per person</div>`;
            if (discount > 0) {
                html += `<span class="atc-tours-discount-badge">${discount}% OFF</span>`;
            }
            html += `</div>`;
            
            html += `<button class="atc-tours-booking-btn" onclick="atcOpenBookingModal(${packageData.id})">Book Now</button>`;
            html += `<button class="atc-tours-query-btn" style="width: 100%; padding: 12px; margin-top: 10px; background: white; border: 2px solid #ff6b35; color: #ff6b35; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;" onclick="atcOpenQueryModal(${packageData.id})">Ask for Custom Package</button>`;
            html += `</div>`;
            
            return html;
        },
        
        renderDefaultPackage(packageData, container) {
            // Fallback to default rendering
            container.html('<p>Default package rendering coming soon</p>');
        },
        
        initGallery(service = 'tours') {
            // Gallery interactions are handled by atc-gallery-lightbox.js
            // Service-agnostic - lightbox works for all services
            // This function is kept for backward compatibility
        },
        
        initInteractions(service = 'tours') {
            // Service-agnostic interactions
            const servicePrefix = this.getServicePrefix(service);
            // Booking button
            $(document).on('click', '[data-action="book-now"]', (e) => {
                e.preventDefault();
                const packageId = $(e.currentTarget).data('package-id');
                if (packageId) {
                    this.openBookingModal(packageId);
                }
            });
            
            // Query button (service-specific handling)
            $(document).on('click', '[data-action="open-query"]', (e) => {
                e.preventDefault();
                // Service-specific tab handling (only for tours)
                if (service === 'tours') {
                    const $tabs = $(`.${servicePrefix}-tab-btn[data-tab="query"]`);
                    if ($tabs.length) {
                        $tabs.trigger('click');
                        $('html, body').animate({
                            scrollTop: $(`.${servicePrefix}-tabs`).offset().top - 100
                        }, 500);
                    }
                }
            });
            
            // Expandable itinerary - service-specific (tours only)
            if (service === 'tours') {
                $(`.${servicePrefix}-itinerary-day`).each(function() {
                    const $day = $(this);
                    const $toggle = $day.find('.atc-itinerary-toggle');
                    const $content = $day.find(`.${servicePrefix}-itinerary-day-content`);
                    
                    // Start expanded
                    $toggle.attr('aria-expanded', 'true');
                    $day.addClass('atc-itinerary-expanded');
                    $content.show();
                });
                
                // Toggle itinerary days (tours-specific)
                $(document).on('click', `.${servicePrefix}-itinerary-day .atc-itinerary-toggle`, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const $toggle = $(e.currentTarget);
                    const $day = $toggle.closest(`.${servicePrefix}-itinerary-day`);
                    const $content = $day.find(`.${servicePrefix}-itinerary-day-content`);
                    const isExpanded = $toggle.attr('aria-expanded') === 'true';
                    
                    if (isExpanded) {
                        $content.slideUp(300);
                        $toggle.attr('aria-expanded', 'false');
                        $day.removeClass('atc-itinerary-expanded');
                        $day.attr('aria-expanded', 'false');
                    } else {
                        $content.slideDown(300);
                        $toggle.attr('aria-expanded', 'true');
                        $day.addClass('atc-itinerary-expanded');
                        $day.attr('aria-expanded', 'true');
                    }
                });
                
                // Also toggle on header click (tours-specific)
                $(document).on('click', `.${servicePrefix}-itinerary-day .atc-itinerary-day-header`, function(e) {
                    if (!$(e.target).closest('.atc-itinerary-toggle').length) {
                        $(this).find('.atc-itinerary-toggle').trigger('click');
                    }
                });
            }
            
            // FAQ accordion (service-agnostic)
            $(document).on('click', '.atc-faq-toggle', (e) => {
                e.preventDefault();
                const $toggle = $(e.currentTarget);
                const $item = $toggle.closest('.atc-faq-item');
                const $answer = $item.find('.atc-faq-answer');
                const isExpanded = $item.hasClass('atc-faq-expanded');
                
                if (isExpanded) {
                    $answer.slideUp(300);
                    $item.removeClass('atc-faq-expanded');
                    $toggle.text('+');
                } else {
                    // Close other items in same service context
                    $item.closest(`.${servicePrefix}-section, .atc-package-details-${service}`).find('.atc-faq-item').removeClass('atc-faq-expanded');
                    $item.closest(`.${servicePrefix}-section, .atc-package-details-${service}`).find('.atc-faq-answer').slideUp(300);
                    $item.closest(`.${servicePrefix}-section, .atc-package-details-${service}`).find('.atc-faq-toggle').text('+');
                    
                    // Open this item
                    $answer.slideDown(300);
                    $item.addClass('atc-faq-expanded');
                    $toggle.text('−');
                }
            });
            
            // Load similar packages
            this.loadSimilarPackages();
            
            // Scroll animations
            this.initScrollAnimations();
            
            // Mobile booking bar visibility
            this.initMobileBookingBar();
        },
        
        initMobileBookingBar() {
            // Service-agnostic mobile booking bar detection
            const $mobileBar = $('.atc-tours-mobile-booking-bar, .atc-forex-mobile-booking-bar, .atc-visa-mobile-booking-bar, .atc-hotels-mobile-booking-bar, .atc-flights-mobile-booking-bar, .atc-trains-mobile-booking-bar, .atc-cars-mobile-booking-bar');
            if (!$mobileBar.length) return;
            
            const $pricingCard = $('.atc-tours-pricing-card, .atc-forex-pricing-card, .atc-visa-pricing-card, .atc-hotels-booking-card, .atc-flights-booking-card, .atc-trains-booking-card, .atc-cars-booking-card');
            if (!$pricingCard.length) return;
            
            // Show/hide mobile bar based on pricing card visibility
            const checkVisibility = () => {
                if (window.innerWidth <= 768) {
                    const cardTop = $pricingCard.offset().top;
                    const cardBottom = cardTop + $pricingCard.outerHeight();
                    const windowBottom = $(window).scrollTop() + $(window).height();
                    
                    // Show mobile bar if pricing card is not visible
                    if (cardBottom < $(window).scrollTop() || cardTop > windowBottom) {
                        $mobileBar.fadeIn(300);
                    } else {
                        $mobileBar.fadeOut(300);
                    }
                } else {
                    $mobileBar.hide();
                }
            };
            
            $(window).on('scroll', checkVisibility);
            $(window).on('resize', checkVisibility);
            
            // Initial check
            checkVisibility();
        },
        
        openBookingModal(packageId) {
            // Wait for premium booking to be available
            if (typeof window.atcPremiumBooking !== 'undefined' && window.atcPremiumBooking.open) {
                window.atcPremiumBooking.open(packageId);
            } else {
                // Wait a bit for scripts to load
                let attempts = 0;
                const checkBooking = setInterval(() => {
                    attempts++;
                    if (typeof window.atcPremiumBooking !== 'undefined' && window.atcPremiumBooking.open) {
                        clearInterval(checkBooking);
                        window.atcPremiumBooking.open(packageId);
                    } else if (attempts >= 10) {
                        clearInterval(checkBooking);
                        console.error('Premium booking system not available after waiting');
                        // Fallback: try to trigger the booking modal directly
                        if ($('#atc-premium-booking-modal').length) {
                            // Modal exists, try to open it manually
                            const $modal = $('#atc-premium-booking-modal');
                            $modal.addClass('active');
                            $('body').addClass('atc-modal-open');
                            // Try to load package details
                            if (window.atcPremiumBooking && window.atcPremiumBooking.loadPackageDetails) {
                                window.atcPremiumBooking.loadPackageDetails(packageId);
                            }
                        } else {
                            alert('Booking system is loading. Please try again in a moment.');
                        }
                    }
                }, 100);
            }
        },
        
        openQueryModal(packageId) {
            // Use PackageQueryEnhanced to open modal
            if (typeof PackageQueryEnhanced !== 'undefined' && PackageQueryEnhanced.loadPackageQueryForm) {
                const modalId = '#atc-package-query-modal-' + packageId;
                const $modal = $(modalId);
                
                if ($modal.length) {
                    // Prevent multiple clicks
                    if ($modal.hasClass('active') || $modal.hasClass('opening')) {
                        return;
                    }
                    
                    // Mark as opening
                    $modal.addClass('opening');
                    
                    // Stop any ongoing animations
                    $modal.stop(true, true);
                    
                    // Remove inline styles
                    $modal.css({
                        'display': '',
                        'opacity': '',
                        'visibility': ''
                    });
                    
                    // Set display: flex first
                    $modal.css('display', 'flex');
                    
                    // Force reflow
                    $modal[0].offsetHeight;
                    
                    // Use requestAnimationFrame for smooth transition
                    requestAnimationFrame(() => {
                        $modal.addClass('active').removeClass('opening');
                    });
                    
                    $('body').css('overflow', 'hidden');
                    PackageQueryEnhanced.loadPackageQueryForm(packageId);
                } else {
                    console.warn('Query modal not found for package:', packageId);
                }
            } else {
                console.error('PackageQueryEnhanced not available');
            }
        },
        
        loadSimilarPackages() {
            const $grid = $('.atc-similar-packages-grid');
            if (!$grid.length) return;
            
            const packageId = $grid.data('package-id');
            const service = $grid.data('service') || 'tours';
            
            if (!packageId) return;
            
            const self = this;
            
            // Get similar packages from same service
            $.ajax({
                url: atcPackageDetails.restUrl + 'search',
                method: 'GET',
                data: {
                    service: service,
                    per_page: 4
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', atcPackageDetails.nonce);
                },
                success: function(response) {
                    if (response.success && response.packages && response.packages.length > 0) {
                        // Filter out current package
                        const similarPackages = response.packages.filter(pkg => pkg.id != packageId).slice(0, 4);
                        if (similarPackages.length > 0) {
                            self.renderSimilarPackages(similarPackages, $grid);
                        } else {
                            $grid.html('<p class="atc-no-similar">No similar packages found.</p>');
                        }
                    } else {
                        $grid.html('<p class="atc-no-similar">No similar packages found.</p>');
                    }
                },
                error: function() {
                    $grid.html('<p class="atc-no-similar">Failed to load similar packages.</p>');
                }
            });
        },
        
        renderSimilarPackages(packages, container) {
            let html = '<div class="atc-similar-grid">';
            
            packages.forEach(pkg => {
                const imageUrl = pkg.image_url || pkg.images?.[0] || '';
                const price = parseFloat(pkg.price || 0);
                const currency = atcPackageDetails.currency;
                
                html += `
                    <div class="atc-similar-card">
                        <div class="atc-similar-image">
                            ${imageUrl ? `<img src="${imageUrl}" alt="${pkg.name}">` : '<div class="atc-similar-placeholder">📷</div>'}
                        </div>
                        <div class="atc-similar-content">
                            <h3 class="atc-similar-title">${this.escapeHtml(pkg.name)}</h3>
                            <div class="atc-similar-meta">
                                ${pkg.destination ? `<span>📍 ${this.escapeHtml(pkg.destination)}</span>` : ''}
                                ${pkg.duration_days ? `<span>📅 ${pkg.duration_days} Days</span>` : ''}
                            </div>
                            <div class="atc-similar-price">
                                <span class="atc-similar-price-value">${currency}${this.formatPrice(price)}</span>
                                <span class="atc-similar-price-label">per person</span>
                            </div>
                            <a href="?package_id=${pkg.id}" class="atc-similar-view-btn">View Details</a>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        },
        
        initScrollAnimations() {
            // Fade in sections on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('atc-fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observe all sections (service-agnostic)
            $('.atc-tours-section, .atc-forex-section, .atc-visa-section').each(function() {
                observer.observe(this);
            });
        },
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        formatPrice(price) {
            return parseFloat(price).toLocaleString('en-IN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },
        
        showError(message) {
            // Service-agnostic error display
            const wrapper = $('[class*="atc-package-details"][class*="wrapper"]').filter(function() {
                return $(this).data('package-id');
            });
            if (wrapper.length) {
                const wrapperClass = wrapper.attr('class') || '';
                const serviceMatch = wrapperClass.match(/atc-package-details-(\w+)/);
                const detectedService = serviceMatch ? serviceMatch[1] : 'tours';
                const servicePrefix = this.getServicePrefix(detectedService);
                const $loading = wrapper.find(`.${servicePrefix}-loading, .atc-loading-state`);
                if ($loading.length) {
                    $loading.html(`
                        <div class="${servicePrefix}-error">
                            <div class="${servicePrefix}-error-icon">⚠️</div>
                            <div class="${servicePrefix}-error-message">${this.escapeHtml(message)}</div>
                        </div>
                    `);
                }
            }
        },
        
        formatPrice(price) {
            return parseFloat(price).toLocaleString('en-IN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        },
        
        bindEvents() {
            // Add any event bindings here
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PackageDetailsEnhanced.init();
    });
    
})(jQuery);

