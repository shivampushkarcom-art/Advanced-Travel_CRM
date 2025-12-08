<?php
/**
 * ATC Error Handler
 * Prevents fatal errors and provides graceful fallbacks
 */

if (!defined('ABSPATH')) exit;

class ATC_Error_Handler {
    
    public static function init() {
        // Suppress errors for missing classes
        add_action('plugins_loaded', [__CLASS__, 'check_dependencies'], 5);
    }
    
    /**
     * Check Dependencies
     */
    public static function check_dependencies() {
        // Check if required constants exist
        if (!defined('ATC_VERSION')) {
            define('ATC_VERSION', '2.4.0');
        }
        
        if (!defined('ATC_ASSETS_URL')) {
            define('ATC_ASSETS_URL', plugin_dir_url(dirname(__FILE__)) . 'assets/');
        }
        
        if (!defined('ATC_INCLUDES_DIR')) {
            define('ATC_INCLUDES_DIR', plugin_dir_path(dirname(__FILE__)) . 'includes/');
        }
        
        if (!defined('ATC_SERVICES_DIR')) {
            define('ATC_SERVICES_DIR', plugin_dir_path(dirname(__FILE__)) . 'services/');
        }
    }
    
    /**
     * Safe Class Loader
     */
    public static function safe_load_class($class_name, $file_path) {
        if (class_exists($class_name)) {
            return true;
        }
        
        if (file_exists($file_path)) {
            try {
                require_once $file_path;
                return class_exists($class_name);
            } catch (Exception $e) {
                error_log('ATC Error: Failed to load ' . $class_name . ' - ' . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
}

ATC_Error_Handler::init();

