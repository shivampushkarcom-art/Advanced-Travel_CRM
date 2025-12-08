<?php
/**
 * ATC Statistics Widget for Elementor
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('ATC_Elementor_Base_Widget')) {
    return;
}

class ATC_Elementor_Statistics_Widget extends ATC_Elementor_Base_Widget {
    
    public function get_name() {
        return 'atc_statistics';
    }
    
    public function get_title() {
        return esc_html__('ATC Statistics', 'advanced-travel-crm');
    }
    
    public function get_icon() {
        return 'eicon-counter';
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
            'customers',
            [
                'label' => esc_html__('Customers', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '10000+',
            ]
        );
        
        $this->add_control(
            'packages',
            [
                'label' => esc_html__('Packages', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '500+',
            ]
        );
        
        $this->add_control(
            'destinations',
            [
                'label' => esc_html__('Destinations', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '50+',
            ]
        );
        
        $this->add_control(
            'experience',
            [
                'label' => esc_html__('Years Experience', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '10+',
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render_widget($settings) {
        $shortcode = '[atc_homepage_statistics';
        $shortcode .= ' customers="' . esc_attr($settings['customers']) . '"';
        $shortcode .= ' packages="' . esc_attr($settings['packages']) . '"';
        $shortcode .= ' destinations="' . esc_attr($settings['destinations']) . '"';
        $shortcode .= ' experience="' . esc_attr($settings['experience']) . '"';
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}

