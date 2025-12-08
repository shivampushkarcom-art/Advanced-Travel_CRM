<?php
/**
 * ATC Service Landing Widget for Elementor
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('ATC_Elementor_Base_Widget')) {
    return;
}

class ATC_Elementor_Service_Landing_Widget extends ATC_Elementor_Base_Widget {
    
    public function get_name() {
        return 'atc_service_landing';
    }
    
    public function get_title() {
        return esc_html__('ATC Service Landing', 'advanced-travel-crm');
    }
    
    public function get_icon() {
        return 'eicon-post-content';
    }
    
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'advanced-travel-crm'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'service',
            [
                'label' => esc_html__('Service', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'tours' => 'Tours',
                    'hotels' => 'Hotels',
                    'flights' => 'Flights',
                    'trains' => 'Trains',
                    'cars' => 'Cars',
                    'forex' => 'Forex',
                    'visa' => 'Visa',
                    'celebrity' => 'Celebrity',
                ],
                'default' => 'tours',
            ]
        );
        
        $this->add_control(
            'show_search',
            [
                'label' => esc_html__('Show Search', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'advanced-travel-crm'),
                'label_off' => esc_html__('No', 'advanced-travel-crm'),
                'return_value' => 'true',
                'default' => 'true',
            ]
        );
        
        $this->add_control(
            'show_packages',
            [
                'label' => esc_html__('Show Packages', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'advanced-travel-crm'),
                'label_off' => esc_html__('No', 'advanced-travel-crm'),
                'return_value' => 'true',
                'default' => 'true',
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render_widget($settings) {
        $service = $settings['service'];
        $shortcode_map = [
            'tours' => 'atc_tours_landing',
            'hotels' => 'atc_hotels_landing',
            'flights' => 'atc_flights_landing',
            'trains' => 'atc_trains_landing',
            'cars' => 'atc_cars_landing',
            'forex' => 'atc_forex_landing',
            'visa' => 'atc_visa_landing',
            'celebrity' => 'atc_celebrity_landing',
        ];
        
        $shortcode_key = $shortcode_map[$service] ?? 'atc_tours_landing';
        
        $shortcode = '[' . $shortcode_key;
        $shortcode .= ' show_search="' . esc_attr($settings['show_search']) . '"';
        $shortcode .= ' show_packages="' . esc_attr($settings['show_packages']) . '"';
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}

