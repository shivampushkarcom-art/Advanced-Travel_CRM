<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATC_Services {
    private static $services = [];

    public static function register_default_services() {
        if (class_exists('ATC_Service_Manager')) {
            self::$services = ATC_Service_Manager::get_registered_services();
        }

        if (empty(self::$services)) {
            self::$services = [
                'tours'   => ['label' => 'Tour Packages', 'page_slug' => 'tours', 'icon' => 'palmtree'],
                'hotels'  => ['label' => 'Hotels',        'page_slug' => 'hotels', 'icon' => 'building'],
                'flights' => ['label' => 'Flights',       'page_slug' => 'flights', 'icon' => 'airplane'],
                'trains'  => ['label' => 'Trains',        'page_slug' => 'trains', 'icon' => 'train'],
                'cars'    => ['label' => 'Car Rentals',   'page_slug' => 'cars', 'icon' => 'car'],
                'forex'   => ['label' => 'Forex',         'page_slug' => 'forex', 'icon' => 'currency'],
                'visa'    => ['label' => 'Visa Services', 'page_slug' => 'visa', 'icon' => 'passport'],
                'celebrity' => ['label' => 'Celebrity Management', 'page_slug' => 'celebrity-management', 'icon' => 'star'],
            ];
        }
    }

    public static function get_services() {
        if (empty(self::$services)) {
            self::register_default_services();
        }

        $services = self::$services;

        // Respect Service Manager statuses
        if (class_exists('ATC_Service_Manager')) {
            $statuses = ATC_Service_Manager::get_services_status();
            $services = array_filter($services, function($key) use ($statuses) {
                return !isset($statuses[$key]) || $statuses[$key];
            }, ARRAY_FILTER_USE_KEY);
        }

        return apply_filters('atc_services_list', $services);
    }

    public static function detect_service_context() {
        $detected = null;
        global $post;

        // Check post content for data-service attribute
        if (is_singular() && $post) {
            if (preg_match('/data-service="([a-z0-9\-]+)"/i', $post->post_content, $m)) {
                $detected = $m[1];
            }
        }

        // Fallback: check URL slug
        if (!$detected) {
            $current_url = home_url(add_query_arg(NULL, NULL));
            foreach (self::get_services() as $key => $service) {
                if (false !== strpos($current_url, $service['page_slug'])) {
                    $detected = $key;
                    break;
                }
            }
        }

        // Default fallback
        $detected = $detected ?: 'tours';

        return apply_filters('atc_detect_service_context', $detected);
    }

    public static function get_service_config($service_key = null) {
        if (!$service_key) {
            $service_key = self::detect_service_context();
        }
        return ATC_Config_Loader::load_service_config($service_key);
    }
}

// Initialize defaults
ATC_Services::register_default_services();