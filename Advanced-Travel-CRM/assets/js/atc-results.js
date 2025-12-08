/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Search Results Handler
 * Location: assets/js/atc-results.js
 * ═══════════════════════════════════════════════════════════════════
 */

(function($) {
    'use strict';
    
    const ResultsHandler = {
        currentPage: 1,
        perPage: 12,
        totalResults: 0,
        filters: {},
        sortBy: 'relevance',
        view: 'grid',
        
        init() {
            this.bindEvents();
            this.loadUrlParams();
            this.performSearch();
        },
        
        bindEvents() {
            // View toggle
            $('.atc-view-btn').on('click', (e) => {
                const view = $(e.currentTarget).data('view');
                this.switchView(view);
            });
            
            // Sort change
            $('.atc-sort-select').on('change', (e) => {
                this.sortBy = $(e.currentTarget).val();
                this.performSearch();
            });
            
            // Apply filters
            $('.atc-apply-filters').on('click', () => {
                this.applyFilters();
            });
            
            // Reset filters
            $('.atc-filters-reset').on('click', () => {
                this.resetFilters();
            });
            
            // Package actions - stop propagation to prevent card click
            $(document).on('click', '.atc-view-details', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Stop event from bubbling to card
                const packageId = $(e.currentTarget).data('package-id') || 
                                 $(e.currentTarget).closest('[data-package-id]').data('package-id');
                if (packageId) {
                    this.viewDetails(packageId);
                }
            });
            
            $(document).on('click', '.atc-book-now', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Stop event from bubbling to card
                const packageId = $(e.currentTarget).data('package-id') || 
                                 $(e.currentTarget).closest('[data-package-id]').data('package-id');
                if (packageId) {
                    this.bookNow(packageId);
                }
            });
            
            // Handle card clicks (only if not clicking on buttons or links)
            $(document).on('click', '.atc-package-card', (e) => {
                // Don't trigger if clicking on buttons, links, or interactive elements
                if ($(e.target).closest('.atc-view-details, .atc-book-now, a, button').length) {
                    return;
                }
                e.preventDefault();
                const packageId = $(e.currentTarget).data('package-id');
                if (packageId) {
                    this.viewDetails(packageId);
                }
            });
            
            // Pagination
            $(document).on('click', '.atc-page-btn:not(.active):not(:disabled)', (e) => {
                const page = $(e.currentTarget).data('page');
                this.goToPage(page);
            });
        },
        
        loadUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Load filters from URL
            if (urlParams.has('destination')) {
                this.filters.destination = urlParams.get('destination');
            }
            if (urlParams.has('date_from')) {
                this.filters.date_from = urlParams.get('date_from');
            }
            if (urlParams.has('date_to')) {
                this.filters.date_to = urlParams.get('date_to');
            }
            if (urlParams.has('adults')) {
                this.filters.adults = parseInt(urlParams.get('adults'));
            }
            if (urlParams.has('budget_min')) {
                this.filters.budget_min = parseFloat(urlParams.get('budget_min'));
                $('#price-min').val(this.filters.budget_min);
            }
            if (urlParams.has('budget_max')) {
                this.filters.budget_max = parseFloat(urlParams.get('budget_max'));
                $('#price-max').val(this.filters.budget_max);
            }
        },
        
        async performSearch() {
            const service = $('.atc-search-results-wrapper').data('service');
            
            this.showLoading();
            
            try {
                const response = await fetch(`${atcResults.restUrl}search`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': atcResults.nonce
                    },
                    body: JSON.stringify({
                        service: service,
                        ...this.filters,
                        sort_by: this.sortBy,
                        page: this.currentPage,
                        per_page: this.perPage
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.totalResults = data.count;
                    this.renderResults(data.results);
                    this.renderPagination();
                } else {
                    this.showError(data.message || 'Search failed');
                }
            } catch (error) {
                console.error('Search error:', error);
                this.showError('Failed to load results. Please try again.');
            }
        },
        
        renderResults(results) {
            const container = $('.atc-results-grid');
            container.empty();
            
            $('.atc-results-count .atc-count').text(this.totalResults);
            
            if (results.length === 0) {
                $('.atc-empty-state').show();
                
                // Trigger the auto-popup when results are zero
                $('#atc-auto-popup-trigger').click();
                
                // Show the special requests section even when no results
                $('#atc-special-requests-section').show();
                
                return;
            }
            
            $('.atc-empty-state').hide();
            
            results.forEach(pkg => {
                container.append(this.renderPackageCard(pkg));
            });
            
            // Scroll to top of results
            $('html, body').animate({
                scrollTop: $('.atc-results-main').offset().top - 20
            }, 300);
            
            // Show the special requests section after results are displayed
            $('#atc-special-requests-section').show();
        },
        
        renderPackageCard(pkg) {
            const isListView = this.view === 'list';
            const cardClass = isListView ? 'atc-package-card-list' : 'atc-package-card-grid';
            
            // Use numeric package_id (should be numeric 1, 2, 3, etc.)
            const packageIdentifier = (pkg.package_id && !isNaN(pkg.package_id) && !isNaN(parseFloat(pkg.package_id))) 
                ? parseInt(pkg.package_id) 
                : parseInt(pkg.id);
            
            return `
                <div class="atc-package-card ${cardClass}" data-package-id="${packageIdentifier}">
                    <div class="atc-package-image">
                        ${pkg.image_url 
                            ? `<img src="${pkg.image_url}" alt="${pkg.name}" loading="lazy">` 
                            : '<div class="atc-package-placeholder">📷</div>'
                        }
                        ${pkg.featured ? '<span class="atc-badge atc-badge-featured">⭐ Featured</span>' : ''}
                        ${pkg.discount ? `<span class="atc-badge atc-badge-discount">${pkg.discount}% OFF</span>` : ''}
                    </div>
                    
                    <div class="atc-package-content">
                        <div class="atc-package-header">
                            <h3 class="atc-package-title">${pkg.name}</h3>
                            ${pkg.destination ? `
                                <p class="atc-package-location">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    ${pkg.destination}
                                </p>
                            ` : ''}
                        </div>
                        
                        <div class="atc-package-meta">
                            ${pkg.duration_days ? `
                                <span class="atc-meta-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    ${pkg.duration_days} Days
                                </span>
                            ` : ''}
                            
                            ${pkg.rating ? `
                                <span class="atc-meta-item">
                                    <span class="atc-rating">⭐ ${pkg.rating}</span>
                                    ${pkg.reviews_count ? `(${pkg.reviews_count} reviews)` : ''}
                                </span>
                            ` : ''}
                        </div>
                        
                        ${pkg.description ? `
                            <p class="atc-package-description">${this.truncate(pkg.description, 100)}</p>
                        ` : ''}
                        
                        ${pkg.features ? `
                            <ul class="atc-package-features">
                                ${this.renderFeatures(pkg.features)}
                            </ul>
                        ` : ''}
                    </div>
                    
                    <div class="atc-package-footer">
                        <div class="atc-package-price">
                            ${pkg.original_price && pkg.original_price > pkg.price ? `
                                <span class="atc-price-original">${atcResults.currency}${this.formatPrice(pkg.original_price)}</span>
                            ` : ''}
                            <span class="atc-price-current">${atcResults.currency}${this.formatPrice(pkg.price)}</span>
                            <span class="atc-price-label">per person</span>
                        </div>
                        
                        <div class="atc-package-actions">
                            <button class="atc-btn atc-btn-outline atc-btn-small atc-view-details" data-package-id="${packageIdentifier}">
                                View Details
                            </button>
                            <button class="atc-btn atc-btn-primary atc-btn-small atc-book-now" data-package-id="${packageIdentifier}">
                                Book Now
                            </button>
                        </div>
                    </div>
                </div>
            `;
        },
        
        renderFeatures(features) {
            const featuresList = typeof features === 'string' ? features.split(',') : features;
            return featuresList.slice(0, 3).map(f => `<li>✓ ${f.trim()}</li>`).join('');
        },
        
        renderPagination() {
            const totalPages = Math.ceil(this.totalResults / this.perPage);
            
            if (totalPages <= 1) {
                $('.atc-pagination').hide();
                return;
            }
            
            $('.atc-pagination').show();
            
            let html = '';
            
            // Previous button
            html += `
                <button class="atc-page-btn" data-page="${this.currentPage - 1}" ${this.currentPage === 1 ? 'disabled' : ''}>
                    ‹
                </button>
            `;
            
            // Page numbers
            const startPage = Math.max(1, this.currentPage - 2);
            const endPage = Math.min(totalPages, this.currentPage + 2);
            
            if (startPage > 1) {
                html += `<button class="atc-page-btn" data-page="1">1</button>`;
                if (startPage > 2) {
                    html += `<span class="atc-pagination-dots">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <button class="atc-page-btn ${i === this.currentPage ? 'active' : ''}" data-page="${i}">
                        ${i}
                    </button>
                `;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span class="atc-pagination-dots">...</span>`;
                }
                html += `<button class="atc-page-btn" data-page="${totalPages}">${totalPages}</button>`;
            }
            
            // Next button
            html += `
                <button class="atc-page-btn" data-page="${this.currentPage + 1}" ${this.currentPage === totalPages ? 'disabled' : ''}>
                    ›
                </button>
            `;
            
            $('.atc-pagination').html(html);
        },
        
        switchView(view) {
            this.view = view;
            
            $('.atc-view-btn').removeClass('active');
            $(`.atc-view-btn[data-view="${view}"]`).addClass('active');
            
            $('.atc-results-grid').attr('data-view', view);
            
            // Re-render with new view
            const packages = $('.atc-package-card');
            packages.each(function() {
                const isListView = view === 'list';
                $(this).toggleClass('atc-package-card-grid', !isListView);
                $(this).toggleClass('atc-package-card-list', isListView);
            });
        },
        
        applyFilters() {
            this.filters = {};
            
            // Price range
            const priceMin = parseFloat($('#price-min').val());
            const priceMax = parseFloat($('#price-max').val());
            
            if (priceMin > 0) this.filters.budget_min = priceMin;
            if (priceMax > 0) this.filters.budget_max = priceMax;
            
            // Duration
            const durations = $('input[name="duration"]:checked').map(function() {
                return $(this).val();
            }).get();
            if (durations.length) this.filters.duration = durations;
            
            // Stars
            const stars = $('input[name="stars"]:checked').map(function() {
                return $(this).val();
            }).get();
            if (stars.length) this.filters.stars = stars;
            
            // Package type
            const packageTypes = $('input[name="package_type"]:checked').map(function() {
                return $(this).val();
            }).get();
            if (packageTypes.length) this.filters.package_type = packageTypes;
            
            this.currentPage = 1;
            this.performSearch();
        },
        
        resetFilters() {
            this.filters = {};
            this.currentPage = 1;
            
            $('#price-min, #price-max').val('');
            $('.atc-filter-group input[type="checkbox"]').prop('checked', false);
            
            this.performSearch();
        },
        
        goToPage(page) {
            this.currentPage = page;
            this.performSearch();
        },
        
        triggerAutoPopup() {
            // Trigger the existing floating query form popup
            const floatingQueryBtn = $('[data-atc-query-form-trigger]');
            if (floatingQueryBtn.length) {
                // Simulate click on the floating query button
                floatingQueryBtn.trigger('click');
            } else {
                // If floating button doesn't exist, try to trigger the lead popup
                this.triggerLeadPopup();
            }
        },
        
        triggerSpecialRequestPopup() {
            // Same functionality as auto popup - trigger the existing floating query form
            const floatingQueryBtn = $('[data-atc-query-form-trigger]');
            if (floatingQueryBtn.length) {
                floatingQueryBtn.trigger('click');
            } else {
                // If floating button doesn't exist, try to trigger the lead popup
                this.triggerLeadPopup();
            }
        },
        
        triggerLeadPopup() {
            // Fallback to trigger the lead popup if query form doesn't exist
            const leadPopup = $('#atc-lead-popup');
            if (leadPopup.length) {
                leadPopup.fadeIn(300);
            }
        },
        
        viewDetails(packageId) {
            // Open package details page
            const service = $('.atc-search-results-wrapper').data('service');
            // Get package details page URL (default: /package-details/)
            const packageDetailsPage = '/package-details/';
            const url = new URL(packageDetailsPage, window.location.origin);
            url.searchParams.set('package_id', packageId);
            if (service) {
                url.searchParams.set('service', service);
            }
            window.location.href = url.toString();
        },
        
        bookNow(packageId) {
            // Use premium booking system
            if (typeof window.atcPremiumBooking !== 'undefined' && window.atcPremiumBooking.open) {
                window.atcPremiumBooking.open(packageId);
            } else {
                // Fallback: redirect to booking page
                const service = $('.atc-search-results-wrapper').data('service');
                const url = new URL(window.location.href);
                url.searchParams.set('book', packageId);
                if (service) {
                    url.searchParams.set('service', service);
                }
                window.location.href = url.toString();
            }
        },
        
        showLoading() {
            $('.atc-results-grid').html(`
                <div class="atc-loading-state">
                    <div class="atc-spinner"></div>
                    <p>Searching for best packages...</p>
                </div>
            `);
        },
        
        showError(message) {
            $('.atc-results-grid').html(`
                <div class="atc-empty-state">
                    <div class="atc-empty-icon">⚠️</div>
                    <h3>Oops! Something went wrong</h3>
                    <p>${message}</p>
                    <button class="atc-btn atc-btn-primary" onclick="location.reload()">Try Again</button>
                </div>
            `);
        },
        
        // Utility functions
        truncate(str, length) {
            if (!str) return '';
            return str.length > length ? str.substring(0, length) + '...' : str;
        },
        
        formatPrice(price) {
            return parseFloat(price).toLocaleString('en-IN', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(() => {
        if ($('.atc-search-results-wrapper').length) {
            ResultsHandler.init();
        }
    });
    
    // Expose globally for external access
    window.atcResultsHandler = ResultsHandler;
    
})(jQuery);