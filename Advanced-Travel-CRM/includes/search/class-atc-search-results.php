<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Search Results Display
 * Location: includes/search/class-atc-search-results.php
 * MakeMyTrip-style results with grid/list view
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Search_Results {
    
    public static function init() {
        add_shortcode('atc_search_results', [__CLASS__, 'results_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    public static function enqueue_assets() {
        wp_enqueue_style('atc-results-grid', ATC_ASSETS_URL . 'css/atc-results-grid.css', [], ATC_VERSION);
        wp_enqueue_script('atc-results', ATC_ASSETS_URL . 'js/atc-results.js', ['jquery'], ATC_VERSION, true);
        
        wp_localize_script('atc-results', 'atcResults', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('atc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => get_option('atc_currency_symbol', '₹'),
        ]);
    }
    
    /**
     * Search Results Shortcode
     * Usage: [atc_search_results service="tours"]
     */
    public static function results_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => '',
            'view' => 'grid', // grid or list
            'columns' => 3,
            'per_page' => 12,
        ], $atts);
        
        ob_start();
        ?>
        <div class="atc-search-results-wrapper" data-service="<?php echo esc_attr($atts['service']); ?>">
            
            <!-- Filters Sidebar -->
            <aside class="atc-filters-sidebar">
                <div class="atc-filters-header">
                    <h3>🔍 Filters</h3>
                    <button class="atc-filters-reset">Reset All</button>
                </div>
                
                <!-- Price Range -->
                <div class="atc-filter-group">
                    <h4>💰 Price Range</h4>
                    <div class="atc-price-range">
                        <input type="number" id="price-min" placeholder="Min" value="0">
                        <span>-</span>
                        <input type="number" id="price-max" placeholder="Max">
                    </div>
                </div>
                
                <!-- Duration (for tours) -->
                <div class="atc-filter-group" data-service-types="tours">
                    <h4>📅 Duration</h4>
                    <label><input type="checkbox" name="duration" value="1-3"> 1-3 Days</label>
                    <label><input type="checkbox" name="duration" value="4-7"> 4-7 Days</label>
                    <label><input type="checkbox" name="duration" value="8+"> 8+ Days</label>
                </div>
                
                <!-- Star Rating (for hotels) -->
                <div class="atc-filter-group" data-service-types="hotels">
                    <h4>⭐ Star Rating</h4>
                    <label><input type="checkbox" name="stars" value="5"> 5 Star</label>
                    <label><input type="checkbox" name="stars" value="4"> 4 Star</label>
                    <label><input type="checkbox" name="stars" value="3"> 3 Star</label>
                </div>
                
                <!-- Package Type -->
                <div class="atc-filter-group">
                    <h4>🎯 Package Type</h4>
                    <label><input type="checkbox" name="package_type" value="luxury"> Luxury</label>
                    <label><input type="checkbox" name="package_type" value="standard"> Standard</label>
                    <label><input type="checkbox" name="package_type" value="budget"> Budget</label>
                </div>
                
                <button class="atc-apply-filters atc-btn atc-btn-primary atc-btn-block">
                    Apply Filters
                </button>
            </aside>
            
            <!-- Results Area -->
            <main class="atc-results-main">
                
                <!-- Results Header -->
                <div class="atc-results-header">
                    <div class="atc-results-count">
                        <span class="atc-count">0</span> packages found
                    </div>
                    
                    <div class="atc-results-controls">
                        <!-- View Toggle -->
                        <div class="atc-view-toggle">
                            <button class="atc-view-btn active" data-view="grid" title="Grid View">
                                <svg width="20" height="20" viewBox="0 0 20 20">
                                    <rect x="2" y="2" width="7" height="7" fill="currentColor"/>
                                    <rect x="11" y="2" width="7" height="7" fill="currentColor"/>
                                    <rect x="2" y="11" width="7" height="7" fill="currentColor"/>
                                    <rect x="11" y="11" width="7" height="7" fill="currentColor"/>
                                </svg>
                            </button>
                            <button class="atc-view-btn" data-view="list" title="List View">
                                <svg width="20" height="20" viewBox="0 0 20 20">
                                    <rect x="2" y="3" width="16" height="3" fill="currentColor"/>
                                    <rect x="2" y="8.5" width="16" height="3" fill="currentColor"/>
                                    <rect x="2" y="14" width="16" height="3" fill="currentColor"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Sort Dropdown -->
                        <select class="atc-sort-select">
                            <option value="relevance">Sort: Relevance</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="rating">Highest Rated</option>
                            <option value="popular">Most Popular</option>
                        </select>
                    </div>
                </div>
                
                <!-- Results Grid/List -->
                <div class="atc-results-grid" data-view="grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                    <!-- Loading State -->
                    <div class="atc-loading-state">
                        <div class="atc-spinner"></div>
                        <p>Searching for best packages...</p>
                    </div>
                    
                    <!-- Results will be injected here by JS -->
                </div>
                
                <!-- Pagination -->
                <div class="atc-pagination">
                    <!-- Pagination will be injected here -->
                </div>
                
                <!-- Empty State -->
                <div class="atc-empty-state" style="display: none;">
                    <div class="atc-empty-icon">🔍</div>
                    <h3>No packages found</h3>
                    <p>Try adjusting your filters or search criteria</p>
                    <button class="atc-btn atc-btn-primary atc-filters-reset">Reset Filters</button>
                </div>
            </main>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Package Card
     */
    public static function render_package_card($package, $view = 'grid') {
        $card_class = ($view === 'list') ? 'atc-package-card-list' : 'atc-package-card-grid';
        
        // Use numeric package_id (should be numeric 1, 2, 3, etc.)
        $package_identifier = (!empty($package['package_id']) && is_numeric($package['package_id'])) 
            ? intval($package['package_id']) 
            : intval($package['id']);
        
        ob_start();
        ?>
        <div class="atc-package-card <?php echo esc_attr($card_class); ?>" data-package-id="<?php echo esc_attr($package_identifier); ?>">
            
            <!-- Image -->
            <div class="atc-package-image">
                <?php if (!empty($package['image_url'])): ?>
                    <img src="<?php echo esc_url($package['image_url']); ?>" alt="<?php echo esc_attr($package['name']); ?>" loading="lazy">
                <?php else: ?>
                    <div class="atc-package-placeholder">📷</div>
                <?php endif; ?>
                
                <?php if (!empty($package['featured']) && $package['featured']): ?>
                    <span class="atc-badge atc-badge-featured">⭐ Featured</span>
                <?php endif; ?>
                
                <?php if (!empty($package['discount'])): ?>
                    <span class="atc-badge atc-badge-discount"><?php echo esc_html($package['discount']); ?>% OFF</span>
                <?php endif; ?>
            </div>
            
            <!-- Content -->
            <div class="atc-package-content">
                
                <!-- Title & Location -->
                <div class="atc-package-header">
                    <h3 class="atc-package-title"><?php echo esc_html($package['name']); ?></h3>
                    <?php if (!empty($package['destination'])): ?>
                        <p class="atc-package-location">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <?php echo esc_html($package['destination']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Meta Info -->
                <div class="atc-package-meta">
                    <?php if (!empty($package['duration_days'])): ?>
                        <span class="atc-meta-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html($package['duration_days']); ?> Days
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($package['rating'])): ?>
                        <span class="atc-meta-item">
                            <span class="atc-rating">⭐ <?php echo esc_html($package['rating']); ?></span>
                            <?php if (!empty($package['reviews_count'])): ?>
                                (<?php echo esc_html($package['reviews_count']); ?> reviews)
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Description -->
                <?php if (!empty($package['description'])): ?>
                    <p class="atc-package-description">
                        <?php echo esc_html(wp_trim_words($package['description'], 20)); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Features -->
                <?php if (!empty($package['features'])): ?>
                    <ul class="atc-package-features">
                        <?php 
                        $features = is_array($package['features']) ? $package['features'] : explode(',', $package['features']);
                        $features = array_slice($features, 0, 3);
                        foreach ($features as $feature): 
                        ?>
                            <li>✓ <?php echo esc_html(trim($feature)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
            </div>
            
            <!-- Footer -->
            <div class="atc-package-footer">
                <div class="atc-package-price">
                    <?php if (!empty($package['original_price']) && $package['original_price'] > $package['price']): ?>
                        <span class="atc-price-original"><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['original_price']); ?></span>
                    <?php endif; ?>
                    <span class="atc-price-current"><?php echo get_option('atc_currency_symbol', '₹'); ?><?php echo number_format($package['price']); ?></span>
                    <span class="atc-price-label">per person</span>
                </div>
                
                <div class="atc-package-actions">
                    <button class="atc-btn atc-btn-outline atc-btn-small atc-view-details" data-package-id="<?php echo esc_attr($package_identifier); ?>">
                        View Details
                    </button>
                    <button class="atc-btn atc-btn-primary atc-btn-small atc-book-now" data-package-id="<?php echo esc_attr($package_identifier); ?>">
                        Book Now
                    </button>
                </div>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
}