/**
 * ATC Premium Search & Booking System
 * Clean, streamlined search to booking flow
 */

(function ($) {
    'use strict';

    // Get config from localized script
    const getConfig = () => {
        return window.atcPremiumSearchConfig || window.atcConfig || {};
    };

    const PremiumSearch = {
        currentPage: 1,
        perPage: 12,
        sortBy: 'relevance',
        performSearchFromUrl: false, // Flag to prevent URL updates when loading from URL
        isInitialized: false, // Flag to prevent duplicate initialization

        init() {
            if (this.isInitialized) return;
            this.isInitialized = true;

            this.bindEvents();
            this.bindHistoryEvents();

            // Initialize timer variable
            this.popupTimer = null;

            // Load URL parameters after a brief delay to ensure DOM is ready
            setTimeout(() => {
                this.loadUrlParams();
            }, 100);
        },

        bindEvents() {
            $(document).on('submit', '#atc-premium-search-form', (e) => {
                e.preventDefault();
                this.currentPage = 1; // Reset to first page on new search
                this.performSearch();
            });

            $(document).on('click', '.atc-advanced-toggle-btn', (e) => {
                e.preventDefault();
                $('.atc-search-advanced-filters').slideToggle(300);
            });

            $(document).on('change', '.atc-sort-select', (e) => {
                this.sortBy = $(e.currentTarget).val();
                this.currentPage = 1; // Reset to first page when sorting changes
                this.performSearch();
            });

            // Handle View Details button clicks - stop propagation to prevent card click
            $(document).on('click', '.atc-view-details, .atc-btn-premium.atc-view-details', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Stop event from bubbling to card
                const $target = $(e.currentTarget);
                const $card = $target.closest('[data-package-id]');
                const packageId = $target.data('package-id') || $card.data('package-id');
                const serviceKey = $target.data('service-key') || $card.data('service-key') || '';
                if (packageId) {
                    this.viewDetails(packageId, serviceKey);
                }
            });

            // Handle Book Now button clicks - stop propagation
            $(document).on('click', '.atc-book-now, .atc-btn-premium.atc-book-now', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Stop event from bubbling to card
                const packageId = $(e.currentTarget).data('package-id') ||
                    $(e.currentTarget).closest('[data-package-id]').data('package-id');
                if (packageId && window.atcPremiumBooking) {
                    window.atcPremiumBooking.open(packageId);
                }
            });

            // Handle card clicks (only if not clicking on buttons or links)
            $(document).on('click', '.atc-premium-package-card', (e) => {
                // Don't trigger if clicking on buttons, links, or interactive elements
                if ($(e.target).closest('.atc-view-details, .atc-book-now, .atc-btn-premium, a, button').length) {
                    return;
                }
                e.preventDefault();
                const $card = $(e.currentTarget);
                const packageId = $card.data('package-id');
                const serviceKey = $card.data('service-key') || '';
                if (packageId) {
                    this.viewDetails(packageId, serviceKey);
                }
            });

            $(document).on('click', '.atc-page-btn:not(.active):not(:disabled)', (e) => {
                const page = $(e.currentTarget).data('page');
                if (page) this.goToPage(page);
            });

            $(document).on('click', '.atc-filter-reset-btn', () => {
                this.resetFilters();
            });

            // Auto-popup trigger for zero results
            $(document).on('click', '#atc-auto-popup-trigger', () => {
                this.triggerAutoPopup();
            });

            // Special request button click handler
            $(document).on('click', '#atc-special-request-btn', () => {
                this.triggerSpecialRequestPopup();
            });
        },

        loadUrlParams() {
            const urlParams = new URLSearchParams(window.location.search);
            const form = $('#atc-premium-search-form');
            if (!form.length) return;

            // Check if there are any search parameters in URL
            const hasSearchParams = urlParams.has('search') || urlParams.has('search_query') ||
                urlParams.has('destination') || urlParams.has('budget_min') ||
                urlParams.has('budget_max') || urlParams.has('package_type') ||
                urlParams.has('rating_min') || urlParams.has('rating') ||
                urlParams.has('page') || urlParams.has('service_filter');

            if (!hasSearchParams) {
                // No search parameters - don't perform search
                return;
            }

            // Load search query
            if (urlParams.has('search')) {
                $('input[name="search_query"]').val(urlParams.get('search'));
            } else if (urlParams.has('search_query')) {
                $('input[name="search_query"]').val(urlParams.get('search_query'));
            } else if (urlParams.has('destination')) {
                $('input[name="search_query"]').val(urlParams.get('destination'));
            }

            // Load budget range
            if (urlParams.has('budget_min') || urlParams.has('budget_max')) {
                const min = urlParams.get('budget_min') || '0';
                const max = urlParams.get('budget_max') || '';
                if (max) {
                    $('select[name="budget_range"]').val(`${min}-${max}`);
                } else if (min && parseFloat(min) > 0) {
                    $('select[name="budget_range"]').val(`${min}+`);
                }
            }

            // Load package type
            if (urlParams.has('package_type')) {
                $('select[name="package_type_filter"]').val(urlParams.get('package_type'));
            }

            // Load rating
            if (urlParams.has('rating_min') || urlParams.has('rating')) {
                const rating = urlParams.get('rating_min') || urlParams.get('rating');
                $('select[name="rating"]').val(rating);
            }

            // Load service filters (for global search)
            if (urlParams.has('service_filter')) {
                const serviceFilters = urlParams.getAll('service_filter');
                $('input[name="service_filter[]"]').each(function () {
                    const val = $(this).val();
                    $(this).prop('checked', serviceFilters.includes(val));
                });
            }

            // Load pagination
            if (urlParams.has('page')) {
                this.currentPage = parseInt(urlParams.get('page')) || 1;
            }

            // Load sort
            if (urlParams.has('sort_by')) {
                this.sortBy = urlParams.get('sort_by');
                $('.atc-sort-select').val(this.sortBy);
            }

            // Perform search with URL parameters (don't update URL again)
            this.performSearchFromUrl = true;
            this.performSearch();
        },

        bindHistoryEvents() {
            // Handle browser back/forward buttons
            $(window).on('popstate', (e) => {
                // When user clicks back/forward, reload search from URL
                // Prevent infinite loop by checking if we're already loading from URL
                if (this.performSearchFromUrl) return;

                // Mark that we're loading from URL to prevent URL update
                this.performSearchFromUrl = true;

                // Clear any existing results first
                const resultsSection = $('#atc-premium-results');
                if (resultsSection.length) {
                    resultsSection.find('.atc-premium-results-grid').empty();
                }

                // Reload search from URL parameters
                this.loadUrlParams();
            });
        },

        async performSearch() {
            // Clear any pending popup timers
            if (this.popupTimer) {
                clearTimeout(this.popupTimer);
                this.popupTimer = null;
            }

            const form = $('#atc-premium-search-form');
            if (!form.length) return;

            // Build payload from form
            const formData = new FormData(form[0]);
            const payload = {};

            for (const [key, value] of formData.entries()) {
                if (value && value.toString().trim()) {
                    payload[key] = value.toString().trim();
                }
            }

            // Convert search_query to search parameter
            if (payload.search_query) {
                payload.search = payload.search_query;
                delete payload.search_query;
            }

            // Get service from page if not in form
            const pageService = $('.atc-premium-search-page').data('service');
            const isGlobalSearch = $('.atc-global-search-page').length > 0;

            if (pageService && !isGlobalSearch) {
                payload.service = pageService;
            } else {
                // Global search - handle service filters
                const serviceFilters = [];
                $('input[name="service_filter[]"]:checked').each(function () {
                    const serviceValue = $(this).val();
                    if (serviceValue) {
                        serviceFilters.push(serviceValue);
                    }
                });
                if (serviceFilters.length > 0) {
                    payload.service_filter = serviceFilters;
                }
                // Empty service means search all services (if no filters, search all)
                // Don't set service parameter if it's global search - let API handle it
                if (!isGlobalSearch) {
                    payload.service = '';
                }
            }

            // Handle filters
            if (payload.package_type_filter) {
                payload.package_type = payload.package_type_filter;
                delete payload.package_type_filter;
            }

            if (payload.budget_range) {
                const range = payload.budget_range.split('-');
                if (range[0]) payload.budget_min = parseFloat(range[0]);
                if (range[1]) {
                    if (range[1].includes('+')) {
                        payload.budget_min = parseFloat(range[1].replace('+', ''));
                    } else {
                        payload.budget_max = parseFloat(range[1]);
                    }
                }
                delete payload.budget_range;
            }

            if (payload.rating) {
                payload.rating_min = parseFloat(payload.rating);
                delete payload.rating;
            }

            // Add pagination
            payload.page = this.currentPage;
            payload.per_page = this.perPage;
            payload.sort_by = this.sortBy;

            // Clean empty values
            Object.keys(payload).forEach(key => {
                if (!payload[key] && payload[key] !== 0) delete payload[key];
            });

            this.showLoading();

            const config = getConfig();
            if (!config.restUrl) {
                this.showError('Configuration error. Please refresh the page.');
                return;
            }

            const searchUrl = config.restUrl.endsWith('/') ? config.restUrl + 'search' : config.restUrl + '/search';

            // Debug logging (remove in production)
            if (window.console && console.log) {
                console.log('ATC Global Search - Payload:', payload);
                console.log('ATC Global Search - URL:', searchUrl);
            }

            try {
                const response = await fetch(searchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.nonce || ''
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    this.showError(errorData.message || `Error: ${response.status}`);
                    return;
                }

                const data = await response.json();

                // Debug logging (remove in production)
                if (window.console && console.log) {
                    console.log('ATC Global Search - Response:', data);
                }

                // Handle WP_Error responses
                if (data.code && data.message) {
                    this.showError(data.message);
                    return;
                }

                // Handle successful response
                // Check for success flag or presence of results array
                if (data.success !== false && data.success !== null || (data.results !== undefined || data.data !== undefined)) {
                    const results = data.results || data.data || [];
                    const responseData = {
                        total: data.total || data.count || results.length,
                        total_pages: data.total_pages || Math.ceil((data.total || data.count || results.length) / this.perPage),
                        page: data.page || this.currentPage,
                        per_page: data.per_page || this.perPage
                    };

                    // Debug logging
                    if (window.console && console.log) {
                        console.log('ATC Global Search - Results:', results.length, 'packages found');
                        console.log('ATC Global Search - Response Data:', responseData);
                    }

                    this.renderResults(results, responseData);

                    // Update URL only if not loading from URL (prevents adding duplicate history entries)
                    if (this.performSearchFromUrl) {
                        // If loading from URL (page load or back button), replace state to avoid duplicate entries
                        this.updateUrl(payload, true);
                        // Reset flag after update
                        this.performSearchFromUrl = false;
                    } else {
                        // User-initiated search - push new state
                        this.updateUrl(payload, false);
                    }
                } else {
                    this.showError(data.message || 'Search failed. Please try again.');
                }
            } catch (error) {
                console.error('ATC Search Error:', error);
                this.showError('Failed to load results. Please try again.');
            } finally {
                this.hideLoading();
            }
        },

        renderResults(results, responseData = {}) {
            // Check if this is global search
            const isGlobalSearch = $('.atc-global-search-page').length > 0;

            let resultsSection = $('#atc-premium-results');
            if (!resultsSection.length) {
                resultsSection = $('<div class="atc-premium-results-wrapper" id="atc-premium-results"></div>');
                $('.atc-premium-search-page').append(resultsSection);
            }

            // For global search, use different structure
            if (isGlobalSearch) {
                let globalResultsContainer = resultsSection.find('.atc-global-search-results');
                if (!globalResultsContainer.length) {
                    globalResultsContainer = $('<div class="atc-global-search-results"></div>');
                    resultsSection.append(globalResultsContainer);
                }

                // Create results structure if it doesn't exist
                let resultsMain = globalResultsContainer.find('.atc-premium-results-main');
                if (!resultsMain.length) {
                    resultsMain = $('<main class="atc-premium-results-main"></main>');
                    globalResultsContainer.append(resultsMain);
                    resultsMain.html(`
                        <div class="atc-premium-results-header">
                            <div class="atc-results-count"><strong>0</strong> packages found</div>
                            <div class="atc-results-controls">
                                <select class="atc-sort-select">
                                    <option value="relevance">Sort: Relevance</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="rating">Highest Rated</option>
                                    <option value="popular">Most Popular</option>
                                </select>
                            </div>
                        </div>
                        <div class="atc-premium-results-grid"></div>
                        <div class="atc-pagination"></div>
                    `);
                }

                const container = resultsMain.find('.atc-premium-results-grid');
                const total = responseData.total || results.length;

                resultsMain.find('.atc-results-count strong').text(total);
                resultsMain.find('.atc-results-count').html(`<strong>${total}</strong> packages found`);

                if (results.length === 0) {
                    container.html(this.getEmptyState());

                    // Trigger the auto-popup after 1 second delay when results are zero
                    if (this.popupTimer) clearTimeout(this.popupTimer);
                    this.popupTimer = setTimeout(() => {
                        this.openCustomRequestPopup();
                    }, 1000);

                    // Show the special requests section even when no results
                    $('#atc-special-requests-section').show();
                } else {
                    container.empty();
                    results.forEach(pkg => {
                        container.append(this.renderPackageCard(pkg));
                    });

                    // Pagination
                    if (responseData.total_pages > 1) {
                        this.renderPagination(responseData.page || 1, responseData.total_pages, resultsMain.find('.atc-pagination'));
                    } else {
                        resultsMain.find('.atc-pagination').empty();
                    }

                    // Add clickable CTA line below results
                    this.addCustomRequestCTA(resultsMain);

                    // Hide the large special requests section section when results are found (using the line instead)
                    $('#atc-special-requests-section').hide();

                    // Hide contact us section initially (will be shown when special request button is clicked)
                    $('#atc-contact-us-section').hide();
                }

                // Remove inline display:none and show results section
                resultsSection.removeAttr('style').show();
            } else {
                // Regular service-specific search
                let resultsMain = resultsSection.find('.atc-premium-results-main');
                if (!resultsMain.length) {
                    resultsMain = $('<main class="atc-premium-results-main"></main>');
                    resultsSection.find('.atc-premium-results-layout').append(resultsMain);
                    if (!resultsSection.find('.atc-premium-results-layout').length) {
                        resultsSection.append($('<div class="atc-premium-results-layout"></div>'));
                        resultsSection.find('.atc-premium-results-layout').append(resultsMain);
                    }
                    resultsMain.html(`
                        <div class="atc-premium-results-header">
                            <div class="atc-results-count"><strong>0</strong> packages found</div>
                            <div class="atc-results-controls">
                                <select class="atc-sort-select">
                                    <option value="relevance">Sort: Relevance</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="rating">Highest Rated</option>
                                    <option value="popular">Most Popular</option>
                                </select>
                            </div>
                        </div>
                        <div class="atc-premium-results-grid"></div>
                        <div class="atc-pagination"></div>
                    `);
                }

                const container = resultsMain.find('.atc-premium-results-grid');
                const total = responseData.total || results.length;

                resultsMain.find('.atc-results-count strong').text(total);
                resultsMain.find('.atc-results-count').html(`<strong>${total}</strong> packages found`);

                if (results.length === 0) {
                    container.html(this.getEmptyState());

                    // Trigger the auto-popup after 1 second delay when results are zero
                    if (this.popupTimer) clearTimeout(this.popupTimer);
                    this.popupTimer = setTimeout(() => {
                        this.openCustomRequestPopup();
                    }, 1000);

                    // Show the special requests section even when no results
                    $('#atc-special-requests-section').show();
                } else {
                    container.empty();
                    results.forEach(pkg => {
                        container.append(this.renderPackageCard(pkg));
                    });

                    // Pagination
                    if (responseData.total_pages > 1) {
                        this.renderPagination(responseData.page || 1, responseData.total_pages, resultsMain.find('.atc-pagination'));
                    } else {
                        resultsMain.find('.atc-pagination').empty();
                    }

                    // Add clickable CTA line below results
                    this.addCustomRequestCTA(resultsMain);

                    // Hide the large special requests section section when results are found (using the line instead)
                    $('#atc-special-requests-section').hide();
                }

                // Remove inline display:none and show results section for regular search
                resultsSection.removeAttr('style').show();
            }

            // Scroll to results section after a brief delay to ensure it's rendered
            setTimeout(() => {
                if (resultsSection.length && resultsSection.is(':visible')) {
                    const offset = resultsSection.offset();
                    if (offset && offset.top) {
                        $('html, body').animate({
                            scrollTop: offset.top - 100
                        }, 500);
                    }
                }
            }, 200);
        },

        renderPagination(currentPage, totalPages, container) {
            if (!container.length) return;

            let html = '';
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            html += `<button class="atc-page-btn" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>← Prev</button>`;

            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="atc-page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }

            html += `<button class="atc-page-btn" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>Next →</button>`;

            container.html(html);
        },

        renderPackageCard(pkgData) {
            const imageUrl = pkgData.image_url || '';
            const price = parseFloat(pkgData.price || 0);
            const originalPrice = parseFloat(pkgData.original_price || 0);
            const config = getConfig();
            const currency = config.currency || '₹';

            let badgesHTML = '';
            if (pkgData.featured) badgesHTML += '<span class="atc-premium-badge atc-badge-featured">⭐ Featured</span>';
            if (pkgData.discount) badgesHTML += `<span class="atc-premium-badge atc-badge-discount">${pkgData.discount}% OFF</span>`;

            const imageHTML = imageUrl
                ? `<img src="${this.escapeHtml(imageUrl)}" alt="${this.escapeHtml(pkgData.name || 'Package')}" loading="lazy">`
                : '<div class="atc-image-placeholder">📷</div>';

            // Use numeric package_id (should be numeric 1, 2, 3, etc.)
            const packageIdentifier = (pkgData.package_id && isNumeric(pkgData.package_id))
                ? parseInt(pkgData.package_id)
                : parseInt(pkgData.id);

            // Get service key and label for global search
            const isGlobalSearch = $('.atc-global-search-page').length > 0;
            const pageService = $('.atc-premium-search-page').data('service');
            const serviceKey = pkgData.service_key || pageService || '';
            const serviceLabel = (serviceKey && isGlobalSearch) ? this.getServiceLabel(serviceKey) : '';

            return `
                <div class="atc-premium-package-card" data-package-id="${packageIdentifier}" data-service-key="${this.escapeHtml(serviceKey)}">
                    <div class="atc-premium-package-image">
                        ${imageHTML}
                        ${badgesHTML}
                        ${serviceLabel ? `<span class="atc-premium-badge atc-badge-service">${serviceLabel}</span>` : ''}
                    </div>
                    <div class="atc-premium-package-content">
                        <h3 class="atc-premium-package-title">${this.escapeHtml(pkgData.name || 'Package')}</h3>
                        ${pkgData.destination ? `<p class="atc-premium-package-location">📍 ${this.escapeHtml(pkgData.destination)}</p>` : ''}
                        <div class="atc-premium-package-meta">
                            ${pkgData.duration_days ? `<span class="atc-premium-meta-item">⏱️ ${pkgData.duration_days} Days</span>` : ''}
                            ${pkgData.rating ? `<span class="atc-premium-meta-item">⭐ ${pkgData.rating} ${pkgData.reviews_count ? `(${pkgData.reviews_count})` : ''}</span>` : ''}
                        </div>
                        ${pkgData.description ? `<p class="atc-premium-package-description">${this.truncate(pkgData.description, 120)}</p>` : ''}
                        <div class="atc-premium-package-footer">
                            <div class="atc-premium-package-price">
                                ${originalPrice > price ? `<span class="atc-price-original">${currency}${this.formatPrice(originalPrice)}</span>` : ''}
                                <span class="atc-price-current">${currency}${this.formatPrice(price)}</span>
                                <span class="atc-price-label">per person</span>
                            </div>
                            <div class="atc-premium-package-actions">
                                <button class="atc-btn-premium atc-btn-premium-primary atc-view-details" data-package-id="${packageIdentifier}" data-service-key="${this.escapeHtml(serviceKey)}">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        goToPage(page) {
            if (page < 1) return;
            this.currentPage = page;
            this.performSearch();
        },

        resetFilters() {
            $('#atc-premium-search-form')[0].reset();
            this.currentPage = 1;
            this.performSearch();
        },

        viewDetails(packageId, serviceKey = '') {
            if (!packageId) return;

            // Always prioritize package's service_key from data attribute
            // For global search, use the package's service_key (required)
            // For service-specific search, prefer package's service_key over page service (in case of mismatch)
            let service = serviceKey || '';

            // If no service_key provided, try to get from page (service-specific search only)
            if (!service) {
                const pageService = $('.atc-premium-search-page').data('service');
                if (pageService) {
                    service = pageService;
                }
            }

            // Build URL with package_id and service
            // Note: Package details page will verify service matches package's actual service_key
            const url = new URL('/package-details/', window.location.origin);
            url.searchParams.set('package_id', packageId);
            // Always include service parameter if available (helps with routing)
            // Package details page will override if service doesn't match package's service_key
            if (service) {
                url.searchParams.set('service', service);
            }

            // Debug logging
            if (window.console && console.log) {
                console.log('ATC View Details:', {
                    packageId: packageId,
                    serviceKey: serviceKey,
                    service: service || 'auto-detect',
                    isGlobalSearch: $('.atc-global-search-page').length > 0
                });
            }

            // Try modal first
            if (window.atcPackageDetails && typeof window.atcPackageDetails.loadPackage === 'function') {
                window.atcPackageDetails.loadPackage(packageId, service || undefined).catch(() => {
                    // Fallback to page navigation
                    window.location.href = url.toString();
                });
            } else {
                window.location.href = url.toString();
            }
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

        openCustomRequestPopup() {
            // Open popup with custom header "Looking for something special? Share your travel needs!"
            const customHeader = 'Looking for something special? Share your travel needs!';

            // Find any open query modal and update its header
            // Use .first() to prevent multiple triggers if duplicates exist
            const floatingQueryBtn = $('[data-atc-query-form-trigger]').first();

            if (floatingQueryBtn.length) {
                const modalId = floatingQueryBtn.data('modal-id');
                if (modalId) {
                    const $modal = $('#' + modalId);
                    if ($modal.length) {
                        // Update the modal header text
                        $modal.find('.atc-auth-header h2').text(customHeader);
                    }
                }

                // Trigger the popup by clicking the button
                floatingQueryBtn.trigger('click');
            } else {
                // Fallback to lead popup
                this.triggerLeadPopup();
            }
        },

        addCustomRequestCTA(container) {
            // Remove existing CTA if any
            container.find('.atc-custom-request-cta').remove();

            // Add clickable CTA line below results
            const ctaHtml = `
                <div class="atc-custom-request-cta" style="text-align: center; margin-top: 30px; padding: 20px;">
                    <a href="#" class="atc-custom-request-link" style="
                        display: inline-block;
                        color: #667eea;
                        font-size: 18px;
                        font-weight: 600;
                        text-decoration: none;
                        padding: 15px 30px;
                        border: 2px solid #667eea;
                        border-radius: 8px;
                        transition: all 0.3s ease;
                        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
                    ">
                        Looking for something special? Share your travel needs!
                    </a>
                </div>
            `;

            // Insert after pagination or at the end of results
            const pagination = container.find('.atc-pagination');
            if (pagination.length) {
                pagination.after(ctaHtml);
            } else {
                container.append(ctaHtml);
            }

            // Bind click event for the CTA
            // Enable safe re-binding
            container.find('.atc-custom-request-link').off('click').on('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openCustomRequestPopup();
            });
        },

        triggerLeadPopup() {
            // Fallback to trigger the lead popup if query form doesn't exist
            const leadPopup = $('#atc-lead-popup');
            if (leadPopup.length) {
                leadPopup.fadeIn(300);
            }
        },

        updateUrl(params, replace = false) {
            // If loading from URL, use replaceState to avoid duplicate history entries
            // This ensures back button works correctly
            const shouldReplace = replace || this.performSearchFromUrl;

            // Reset flag after checking (but only if we're actually updating)
            const wasLoadingFromUrl = this.performSearchFromUrl;
            if (this.performSearchFromUrl) {
                this.performSearchFromUrl = false;
            }

            try {
                const url = new URL(window.location.href);
                const isGlobalSearch = $('.atc-global-search-page').length > 0;

                // Clear ALL search-related parameters first
                const searchParams = ['search', 'search_query', 'destination', 'budget_min', 'budget_max',
                    'package_type', 'rating_min', 'rating', 'sort_by', 'page', 'service_filter'];
                searchParams.forEach(key => {
                    url.searchParams.delete(key);
                });

                // Only add parameters that have meaningful values
                // Search query
                if (params.search || params.search_query) {
                    url.searchParams.set('search', params.search || params.search_query);
                }

                // Destination (if different from search)
                if (params.destination && params.destination !== (params.search || params.search_query)) {
                    url.searchParams.set('destination', params.destination);
                }

                // Budget range
                if (params.budget_min || params.budget_max) {
                    if (params.budget_min) url.searchParams.set('budget_min', params.budget_min);
                    if (params.budget_max) url.searchParams.set('budget_max', params.budget_max);
                } else if (params.budget_range) {
                    // Parse budget range into min/max
                    const range = params.budget_range.toString().split('-');
                    if (range[0] && parseFloat(range[0]) > 0) {
                        url.searchParams.set('budget_min', range[0]);
                    }
                    if (range[1]) {
                        if (range[1].includes('+')) {
                            url.searchParams.set('budget_min', range[1].replace('+', ''));
                        } else {
                            url.searchParams.set('budget_max', range[1]);
                        }
                    }
                }

                // Package type
                if (params.package_type_filter || params.package_type) {
                    url.searchParams.set('package_type', params.package_type_filter || params.package_type);
                }

                // Rating
                if (params.rating_min || params.rating) {
                    url.searchParams.set('rating_min', params.rating_min || params.rating);
                }

                // Service filters (global search only)
                if (isGlobalSearch && params.service_filter && Array.isArray(params.service_filter) && params.service_filter.length > 0) {
                    params.service_filter.forEach(filter => {
                        url.searchParams.append('service_filter', filter);
                    });
                }

                // Pagination (only if > 1)
                if (this.currentPage > 1) {
                    url.searchParams.set('page', this.currentPage);
                }

                // Sort (only if not default)
                if (this.sortBy && this.sortBy !== 'relevance') {
                    url.searchParams.set('sort_by', this.sortBy);
                }

                // Update URL without reloading page
                // Use replaceState for initial load/back button to avoid duplicate history entries
                if (shouldReplace) {
                    window.history.replaceState({ searchParams: params, timestamp: Date.now(), fromUrl: wasLoadingFromUrl }, '', url);
                } else {
                    // User-initiated search - push new state
                    window.history.pushState({ searchParams: params, timestamp: Date.now(), fromUrl: false }, '', url);
                }
            } catch (e) {
                // Fallback if history API fails (shouldn't happen in modern browsers)
                console.warn('ATC: History API error:', e);
            }
        },

        showLoading() {
            // Check if global search or regular search
            const isGlobalSearch = $('.atc-global-search-page').length > 0;
            let grid = $('.atc-premium-results-grid');

            // If no grid exists yet, ensure results section is visible
            const resultsSection = $('#atc-premium-results');
            if (resultsSection.length) {
                resultsSection.show();

                if (isGlobalSearch) {
                    // For global search, ensure structure exists
                    let globalContainer = resultsSection.find('.atc-global-search-results');
                    if (!globalContainer.length) {
                        globalContainer = $('<div class="atc-global-search-results"></div>');
                        resultsSection.append(globalContainer);
                    }
                    let resultsMain = globalContainer.find('.atc-premium-results-main');
                    if (!resultsMain.length) {
                        resultsMain = $('<main class="atc-premium-results-main"></main>');
                        globalContainer.append(resultsMain);
                        resultsMain.html('<div class="atc-premium-results-grid"></div>');
                    }
                    grid = resultsMain.find('.atc-premium-results-grid');
                } else {
                    // For regular search, ensure structure exists
                    let resultsMain = resultsSection.find('.atc-premium-results-main');
                    if (!resultsMain.length) {
                        resultsMain = $('<main class="atc-premium-results-main"></main>');
                        resultsSection.append(resultsMain);
                        resultsMain.html('<div class="atc-premium-results-grid"></div>');
                    }
                    grid = resultsMain.find('.atc-premium-results-grid');
                }
            }

            if (grid.length) {
                grid.html('<div class="atc-premium-loading"><div class="atc-premium-spinner"></div><p>Searching...</p></div>');
            }
        },

        hideLoading() {
            $('.atc-premium-loading').remove();
        },

        showError(message) {
            // Check if global search or regular search
            const isGlobalSearch = $('.atc-global-search-page').length > 0;
            let grid = $('.atc-premium-results-grid');

            // If no grid exists, create it
            const resultsSection = $('#atc-premium-results');
            if (resultsSection.length && !grid.length) {
                if (isGlobalSearch) {
                    let globalContainer = resultsSection.find('.atc-global-search-results');
                    if (!globalContainer.length) {
                        globalContainer = $('<div class="atc-global-search-results"></div>');
                        resultsSection.append(globalContainer);
                    }
                    let resultsMain = globalContainer.find('.atc-premium-results-main');
                    if (!resultsMain.length) {
                        resultsMain = $('<main class="atc-premium-results-main"></main>');
                        globalContainer.append(resultsMain);
                        resultsMain.html('<div class="atc-premium-results-grid"></div>');
                    }
                    grid = resultsMain.find('.atc-premium-results-grid');
                } else {
                    let resultsMain = resultsSection.find('.atc-premium-results-main');
                    if (!resultsMain.length) {
                        resultsMain = $('<main class="atc-premium-results-main"></main>');
                        resultsSection.append(resultsMain);
                        resultsMain.html('<div class="atc-premium-results-grid"></div>');
                    }
                    grid = resultsMain.find('.atc-premium-results-grid');
                }
            }

            if (grid.length) {
                grid.html(`
                    <div class="atc-premium-empty">
                        <div class="atc-premium-empty-icon">⚠️</div>
                        <h3>Oops! Something went wrong</h3>
                        <p>${this.escapeHtml(message || 'An error occurred.')}</p>
                        <button class="atc-btn-premium atc-btn-premium-primary" onclick="location.reload()">Try Again</button>
                    </div>
                `);
            }
            resultsSection.slideDown(300);
        },

        getEmptyState() {
            return `
                <div class="atc-premium-empty">
                    <div class="atc-premium-empty-icon">🔍</div>
                    <h3>No packages found</h3>
                    <p>Try adjusting your search criteria</p>
                    <button class="atc-btn-premium atc-btn-premium-primary atc-filter-reset-btn">Reset Filters</button>
                </div>
            `;
        },

        truncate(str, length) {
            if (!str) return '';
            return str.length > length ? str.substring(0, length) + '...' : str;
        },

        formatPrice(price) {
            return parseFloat(price).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        },

        escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        },

        getServiceLabel(serviceKey) {
            const serviceLabels = {
                'tours': 'Tours',
                'hotels': 'Hotels',
                'flights': 'Flights',
                'trains': 'Trains',
                'cars': 'Cars',
                'visa': 'Visa',
                'forex': 'Forex',
                'safari': 'Safari'
            };
            return serviceLabels[serviceKey] || (serviceKey ? serviceKey.charAt(0).toUpperCase() + serviceKey.slice(1) : '');
        }
    };

    // Helper function to check if value is numeric
    function isNumeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value)) && isFinite(value);
    }

    // Initialize
    $(document).ready(() => {
        const checkAndInit = () => {
            const config = getConfig();
            if (config.restUrl && $('.atc-premium-search-page').length) {
                PremiumSearch.init();
            } else if (!config.restUrl) {
                setTimeout(checkAndInit, 100);
            }
        };
        checkAndInit();
    });

    window.atcPremiumSearchInstance = PremiumSearch;

})(jQuery);
