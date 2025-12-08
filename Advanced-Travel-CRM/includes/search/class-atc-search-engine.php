<?php
/**
 * ATC Search Engine
 * Smart search with filters, sorting, and result optimization
 */

if (!defined('ABSPATH')) exit;

class ATC_Search_Engine {
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    public static function register_routes() {
        register_rest_route('atc/v1', '/search', [
            'methods' => ['GET', 'POST'],
            'callback' => [__CLASS__, 'search'],
            'permission_callback' => '__return_true',
        ]);
    }
    
    /**
     * Search packages - supports global and service-specific search
     */
    public static function search($request) {
        try {
            // Get request parameters
            if ($request->get_method() === 'GET') {
                $params = $request->get_params();
            } else {
                $params = $request->get_json_params() ?: $request->get_body_params();
            }
            
            // Sanitize parameters
            $service = sanitize_text_field($params['service'] ?? '');
            $search = sanitize_text_field($params['search'] ?? $params['destination'] ?? '');
            $package_type = sanitize_text_field($params['package_type'] ?? '');
            $activities = sanitize_text_field($params['activities'] ?? '');
            $budget_min = floatval($params['budget_min'] ?? 0);
            $budget_max = floatval($params['budget_max'] ?? 0);
            $rating_min = floatval($params['rating_min'] ?? 0);
            $sort_by = sanitize_text_field($params['sort_by'] ?? 'relevance');
            $page = max(1, intval($params['page'] ?? 1));
            $per_page = max(1, intval($params['per_page'] ?? 12));
            $offset = ($page - 1) * $per_page;
            
            // Handle service filters for global search
            $service_filters = [];
            if (isset($params['service_filter'])) {
                if (is_array($params['service_filter'])) {
                    $service_filters = array_map('sanitize_text_field', $params['service_filter']);
                } else {
                    $service_filters = [sanitize_text_field($params['service_filter'])];
                }
            }
            
            // Build query
            global $wpdb;
            $where = [];
            $query_params = [];
            
            // Service filter (empty = global search, value = service-specific, array = filter by multiple services)
            if (!empty($service_filters) && is_array($service_filters) && count($service_filters) > 0) {
                // Filter by selected services (global search with service filter)
                $placeholders = implode(',', array_fill(0, count($service_filters), '%s'));
                $where[] = "service_key IN ($placeholders)";
                $query_params = array_merge($query_params, $service_filters);
            } elseif (!empty($service)) {
                // Single service search
                $where[] = "service_key = %s";
                $query_params[] = $service;
            }
            // If both are empty, search all services (global search without filter)
            
            // Search query - searches across multiple fields
            if (!empty($search)) {
                $search_like = '%' . $wpdb->esc_like($search) . '%';
                $where[] = "(
                    destination LIKE %s OR 
                    name LIKE %s OR 
                    short_description LIKE %s OR
                    description LIKE %s OR
                    activities LIKE %s OR 
                    package_type LIKE %s OR 
                    tags LIKE %s OR
                    category LIKE %s
                )";
                for ($i = 0; $i < 8; $i++) {
                    $query_params[] = $search_like;
                }
            }
            
            // Filters
            if (!empty($package_type)) {
                $where[] = "package_type = %s";
                $query_params[] = $package_type;
            }
            
            if (!empty($activities)) {
                $where[] = "activities LIKE %s";
                $query_params[] = '%' . $wpdb->esc_like($activities) . '%';
            }
            
            if ($budget_min > 0) {
                $where[] = "price >= %f";
                $query_params[] = $budget_min;
            }
            
            if ($budget_max > 0) {
                $where[] = "price <= %f";
                $query_params[] = $budget_max;
            }
            
            if ($rating_min > 0) {
                $where[] = "rating >= %f";
                $query_params[] = $rating_min;
            }
            
            // Status filter
            $where[] = "status = %s";
            $query_params[] = 'active';
            
            // Build WHERE clause
            $where_clause = !empty($where) ? implode(' AND ', $where) : '1=1';
            
            // Ordering
            $order_by = 'featured DESC, rating DESC, view_count DESC, id DESC';
            if (!empty($search)) {
                $order_by = 'featured DESC, rating DESC, view_count DESC, id DESC';
            } else {
                switch ($sort_by) {
                    case 'price_low':
                        $order_by = 'price ASC, featured DESC';
                        break;
                    case 'price_high':
                        $order_by = 'price DESC, featured DESC';
                        break;
                    case 'rating':
                        $order_by = 'rating DESC, reviews_count DESC, featured DESC';
                        break;
                    case 'popular':
                        $order_by = 'booking_count DESC, view_count DESC, featured DESC';
                        break;
                }
            }
            
            // Execute queries
            $query_params_count = $query_params;
            $query_params[] = $per_page;
            $query_params[] = $offset;
            
            // Build SQL with proper preparation
            // For IN() clauses, we need to properly prepare the query
            $sql = "SELECT * FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                    WHERE {$where_clause} 
                    ORDER BY {$order_by}
                    LIMIT %d OFFSET %d";
            
            $count_sql = "SELECT COUNT(*) FROM " . ATC_TABLE_CUSTOM_PACKAGES . " 
                          WHERE {$where_clause}";
            
            // Prepare queries
            if (!empty($query_params_count)) {
                // Prepare count query
                if (count($query_params_count) > 0) {
                    $count_sql = $wpdb->prepare($count_sql, $query_params_count);
                }
                // Prepare main query
                $sql = $wpdb->prepare($sql, $query_params);
            } else {
                // No parameters, just prepare pagination
                $sql = $wpdb->prepare($sql, $per_page, $offset);
            }
            
            $packages = $wpdb->get_results($sql, ARRAY_A) ?: [];
            $total_count = intval($wpdb->get_var($count_sql) ?: 0);
            
            // Format results
            $results = [];
            foreach ($packages as $pkg) {
                $image_url = $pkg['image_url'] ?? '';
                if (empty($image_url) && !empty($pkg['gallery_images'])) {
                    $gallery = is_string($pkg['gallery_images']) ? json_decode($pkg['gallery_images'], true) : $pkg['gallery_images'];
                    if (is_array($gallery) && !empty($gallery[0])) {
                        $image_url = $gallery[0];
                    }
                }
                
                // Use numeric package_id (should match id after migration)
                $package_id = (!empty($pkg['package_id']) && is_numeric($pkg['package_id'])) 
                    ? intval($pkg['package_id']) 
                    : intval($pkg['id']);
                
                // Get service_key from package - it should always be set in the database
                $package_service_key = !empty($pkg['service_key']) ? $pkg['service_key'] : '';
                
                // If service_key is empty, try to get it from the service filter or search service
                if (empty($package_service_key)) {
                    if (!empty($service_filters) && is_array($service_filters) && count($service_filters) === 1) {
                        $package_service_key = $service_filters[0];
                    } elseif (!empty($service)) {
                        $package_service_key = $service;
                    }
                }
                
                $results[] = [
                    'id' => intval($pkg['id']),
                    'package_id' => $package_id, // Numeric package_id
                    'name' => $pkg['name'] ?? '',
                    'destination' => $pkg['destination'] ?? '',
                    'price' => floatval($pkg['price'] ?? 0),
                    'original_price' => floatval($pkg['original_price'] ?? 0),
                    'duration_days' => intval($pkg['duration_days'] ?? 0),
                    'duration_nights' => intval($pkg['duration_nights'] ?? 0),
                    'rating' => floatval($pkg['rating'] ?? 0),
                    'reviews_count' => intval($pkg['reviews_count'] ?? 0),
                    'discount' => intval($pkg['discount'] ?? 0),
                    'featured' => !empty($pkg['featured']),
                    'image_url' => $image_url,
                    'description' => $pkg['short_description'] ?? $pkg['description'] ?? '',
                    'package_type' => $pkg['package_type'] ?? '',
                    'category' => $pkg['category'] ?? '',
                    'activities' => $pkg['activities'] ?? '',
                    'tags' => $pkg['tags'] ?? '',
                    'highlights' => $pkg['highlights'] ?? '',
                    'service_key' => $package_service_key, // Always use package's service_key
                ];
            }
            
            // Log search (non-blocking)
            self::log_search($params, count($results));
            
            // Capture lead (non-blocking)
            if (class_exists('ATC_Lead_Capture') && method_exists('ATC_Lead_Capture', 'capture_lead')) {
                try {
                    $lead_request = new WP_REST_Request('POST', '/atc/v1/capture-lead');
                    $lead_request->set_body_params($params);
                    ATC_Lead_Capture::capture_lead($lead_request);
                } catch (Exception $e) {
                    // Silent fail
                }
            }
            
            // Fire action hook
            do_action('atc_search_performed', $params, $results);
            
            return rest_ensure_response([
                'success' => true,
                'results' => $results,
                'count' => count($results),
                'total' => $total_count,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total_count / $per_page),
            ]);
            
        } catch (Exception $e) {
            return new WP_Error(
                'search_error',
                'An error occurred while searching. Please try again.',
                ['status' => 500]
            );
        }
    }
    
    /**
     * Log search activity
     */
    private static function log_search($params, $results_count) {
        if (!defined('ATC_TABLE_SEARCH_LOGS')) {
            return;
        }
        
        global $wpdb;
        
        try {
            $session_id = function_exists('session_id') && session_id() ? session_id() : uniqid('atc_', true);
            
            $wpdb->insert(ATC_TABLE_SEARCH_LOGS, [
                'user_id' => get_current_user_id() ?: null,
                'session_id' => $session_id,
                'service' => is_array($params['service'] ?? null) ? implode(', ', $params['service']) : ($params['service'] ?? ''),
                'query_data' => json_encode($params),
                'results_count' => $results_count,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'device_type' => self::get_device_type(),
                'browser' => self::get_browser(),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'created_at' => current_time('mysql'),
            ]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    private static function get_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
            return 'tablet';
        } elseif (preg_match('/(mobile|phone)/i', $user_agent)) {
            return 'mobile';
        }
        return 'desktop';
    }
    
    private static function get_browser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        return 'Other';
    }
}
