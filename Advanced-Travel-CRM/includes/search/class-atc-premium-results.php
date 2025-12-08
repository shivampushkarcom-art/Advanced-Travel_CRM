<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 * ATC Premium Search Results
 * Make My Trip-like premium results display
 * ═══════════════════════════════════════════════════════════════════
 */

if (!defined('ABSPATH')) exit;

class ATC_Premium_Results {
    
    public static function init() {
        add_shortcode('atc_premium_results', [__CLASS__, 'results_shortcode']);
    }
    
    /**
     * Premium Results Shortcode
     * Usage: [atc_premium_results service="tours"]
     */
    public static function results_shortcode($atts) {
        $atts = shortcode_atts([
            'service' => '',
            'view' => 'grid',
            'columns' => 3,
            'per_page' => 12,
        ], $atts);
        
        ob_start();
        ?>
        <div class="atc-premium-results-layout">
            
            <!-- Results Area (No Filters) -->
            <main class="atc-premium-results-main">
                
                <!-- Results Header -->
                <div class="atc-premium-results-header">
                    <div class="atc-results-count">
                        <strong>0</strong> packages found
                    </div>
                    
                    <div class="atc-results-controls">
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
                
                <!-- Results Grid -->
                <div class="atc-premium-results-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                    <!-- Loading State -->
                    <div class="atc-premium-loading">
                        <div class="atc-premium-spinner"></div>
                        <p>Searching for best packages...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div class="atc-empty-state" style="display: none;">
                        <div class="atc-empty-icon">🔍</div>
                        <h3>No packages found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                        <button class="atc-btn atc-btn-primary atc-filters-reset">Reset Filters</button>
                        
                        <!-- Auto-popup for zero results -->
                        <div id="atc-auto-popup-trigger" style="display: none;"></div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="atc-pagination" style="margin-top: 30px; display: flex; justify-content: center; gap: 10px;">
                    <!-- Pagination will be injected here by JS -->
                </div>
                
                <!-- Looking for something special section (shown at bottom of results) -->
                <div id="atc-special-requests-section" class="atc-special-requests-section" style="display: none; margin-top: 40px; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; text-align: center;">
                    <div class="atc-special-requests-content">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.5em;">Looking for something special? Share your travel needs!</h3>
                        <p style="margin: 0 0 20px 0; opacity: 0.9;">We'll create a custom package just for you!</p>
                        <button id="atc-special-request-btn" class="atc-btn atc-btn-secondary" style="background: rgba(255,255,255,0.2); border: 2px solid white; color: white; padding: 12px 24px; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                            Share Your Travel Needs
                        </button>
                    </div>
                </div>
                
                <!-- Contact Us Form Section (loaded via shortcode) -->
                <div id="atc-contact-us-section" class="atc-contact-us-section" style="display: none; margin-top: 40px;">
                    <div class="atc-contact-us-content">
                        <?php echo do_shortcode('[atc_contact_us_form]'); ?>
                    </div>
                </div>
                
            </main>
            
        </div>
        <?php
        return ob_get_clean();
    }
}

