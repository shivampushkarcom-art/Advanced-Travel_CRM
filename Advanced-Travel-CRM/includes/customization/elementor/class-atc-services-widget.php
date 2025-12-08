<?php
/**
 * ATC Services Widget for Elementor
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('ATC_Elementor_Base_Widget')) {
    return;
}

class ATC_Elementor_Services_Widget extends ATC_Elementor_Base_Widget {
    
    public function get_name() {
        return 'atc_services';
    }
    
    public function get_title() {
        return esc_html__('ATC Services Grid', 'advanced-travel-crm');
    }
    
    public function get_icon() {
        return 'eicon-grid';
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
            'title',
            [
                'label' => esc_html__('Title', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Our Services',
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'max' => 6,
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render_widget($settings) {
        $shortcode = '[atc_homepage_services';
        $shortcode .= ' title="' . esc_attr($settings['title']) . '"';
        $shortcode .= ' columns="' . esc_attr($settings['columns']) . '"';
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}

