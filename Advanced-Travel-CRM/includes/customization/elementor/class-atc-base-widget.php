<?php
/**
 * ATC Elementor Base Widget
 * Base class for all ATC Elementor widgets
 */

if (!defined('ABSPATH')) exit;

// Only define if Elementor is loaded
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

abstract class ATC_Elementor_Base_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['atc-travel'];
    }
    
    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['atc', 'travel', 'crm', 'booking'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Common controls can be added here
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $this->render_widget($settings);
    }
    
    /**
     * Render widget content (to be implemented by child classes)
     */
    abstract protected function render_widget($settings);
    
    /**
     * Get shortcode attributes from settings
     */
    protected function get_shortcode_attrs($settings) {
        $attrs = [];
        foreach ($settings as $key => $value) {
            if (!empty($value) && $key !== '_id' && $key !== '_element_id') {
                $attrs[] = esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        return implode(' ', $attrs);
    }
}

