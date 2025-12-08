<?php
/**
 * ATC Featured Packages Widget for Elementor
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('ATC_Elementor_Base_Widget')) {
    return;
}

class ATC_Elementor_Featured_Packages_Widget extends ATC_Elementor_Base_Widget {
    
    public function get_name() {
        return 'atc_featured_packages';
    }
    
    public function get_title() {
        return esc_html__('ATC Featured Packages', 'advanced-travel-crm');
    }
    
    public function get_icon() {
        return 'eicon-posts-grid';
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
                ],
                'default' => 'tours',
            ]
        );
        
        $this->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Featured Packages',
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 1,
                'max' => 6,
            ]
        );
        
        $this->add_control(
            'per_page',
            [
                'label' => esc_html__('Packages Per Page', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 8,
                'min' => 1,
                'max' => 20,
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render_widget($settings) {
        $shortcode = '[atc_homepage_featured_packages';
        $shortcode .= ' service="' . esc_attr($settings['service']) . '"';
        $shortcode .= ' title="' . esc_attr($settings['title']) . '"';
        $shortcode .= ' columns="' . esc_attr($settings['columns']) . '"';
        $shortcode .= ' per_page="' . esc_attr($settings['per_page']) . '"';
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}

