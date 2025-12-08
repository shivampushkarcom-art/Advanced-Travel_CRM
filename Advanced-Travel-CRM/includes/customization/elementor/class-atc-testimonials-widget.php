<?php
/**
 * ATC Testimonials Widget for Elementor
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('ATC_Elementor_Base_Widget')) {
    return;
}

class ATC_Elementor_Testimonials_Widget extends ATC_Elementor_Base_Widget {
    
    public function get_name() {
        return 'atc_testimonials';
    }
    
    public function get_title() {
        return esc_html__('ATC Testimonials', 'advanced-travel-crm');
    }
    
    public function get_icon() {
        return 'eicon-testimonial';
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
                'default' => 'What Our Customers Say',
            ]
        );
        
        $this->add_control(
            'per_page',
            [
                'label' => esc_html__('Testimonials Per Page', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 12,
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render_widget($settings) {
        $shortcode = '[atc_homepage_testimonials';
        $shortcode .= ' title="' . esc_attr($settings['title']) . '"';
        $shortcode .= ' per_page="' . esc_attr($settings['per_page']) . '"';
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}

