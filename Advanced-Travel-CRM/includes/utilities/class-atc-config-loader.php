<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATC_Config_Loader {
    public static function load_service_config($service_key) {
        $file = ATC_PLUGIN_DIR . 'services/' . $service_key . '.json';
        
        if ( file_exists($file) ) {
            $json = file_get_contents($file);
            $config = json_decode($json, true);
            
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $config;
            }
        }
        
        return [];
    }

    public static function get_all_configs() {
        $services = ATC_Services::get_services();
        $configs = [];
        
        foreach ($services as $key => $val) {
            $configs[$key] = self::load_service_config($key);
        }
        
        return $configs;
    }
}