<?php
/**
 * ATC CTA Widget for Elementor
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('ATC_Elementor_Base_Widget')) {
    return;
}

class ATC_Elementor_CTA_Widget extends ATC_Elementor_Base_Widget {
    
    public function get_name() {
        return 'atc_cta';
    }
    
    public function get_title() {
        return esc_html__('ATC Call to Action', 'advanced-travel-crm');
    }
    
    public function get_icon() {
        return 'eicon-call-to-action';
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
                'default' => 'Ready to Plan Your Trip?',
            ]
        );
        
        $this->add_control(
            'subtitle',
            [
                'label' => esc_html__('Subtitle', 'advanced-travel-crm'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'Contact us today for personalized travel solutions',
            ]
        );
        
        $this->add_control(
            'show_form',
            [
                'label' => esc_html__('Show Form', 'advanced-travel-crm'),
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
        $shortcode = '[atc_homepage_cta';
        $shortcode .= ' title="' . esc_attr($settings['title']) . '"';
        $shortcode .= ' subtitle="' . esc_attr($settings['subtitle']) . '"';
        $shortcode .= ' show_form="' . esc_attr($settings['show_form']) . '"';
        $shortcode .= ']';
        
        echo do_shortcode($shortcode);
    }
}

