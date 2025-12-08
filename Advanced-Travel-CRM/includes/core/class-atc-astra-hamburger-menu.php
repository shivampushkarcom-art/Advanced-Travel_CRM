<?php
/**
 * ATC Astra Hamburger Menu
 * Red hamburger menu integrated with Astra theme
 * Works on all screen sizes (laptop, tablet, mobile)
 * Uses WordPress primary menu
 */

if (!defined('ABSPATH')) exit;

class ATC_Astra_Hamburger_Menu {
    
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'render_menu_panel']);
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'atc-astra-hamburger-menu',
            ATC_ASSETS_URL . 'css/atc-astra-hamburger-menu.css',
            [],
            ATC_VERSION
        );
        
        wp_enqueue_script(
            'atc-astra-hamburger-menu',
            ATC_ASSETS_URL . 'js/atc-astra-hamburger-menu.js',
            ['jquery'],
            ATC_VERSION,
            true
        );
    }
    
    /**
     * Render menu panel HTML
     */
    public static function render_menu_panel() {
        // Check if primary menu exists
        $menu_locations = get_nav_menu_locations();
        $menu_id = isset($menu_locations['primary']) ? $menu_locations['primary'] : 0;
        
        if (!$menu_id) {
            $menus = wp_get_nav_menus();
            if (!empty($menus)) {
                $menu_id = $menus[0]->term_id;
            }
        }
        
        if (!$menu_id) {
            return;
        }
        
        ?>
        <!-- ATC Red Hamburger Menu Overlay -->
        <div class="atc-hamburger-menu-overlay"></div>
        
        <!-- ATC Red Hamburger Menu Panel -->
        <nav class="atc-hamburger-menu" id="atc-hamburger-menu" role="navigation" aria-label="<?php esc_attr_e('Main Menu', 'advanced-travel-crm'); ?>">
            <div class="atc-hamburger-menu-header">
                <h2 class="atc-hamburger-menu-title"><?php esc_html_e('Menu', 'advanced-travel-crm'); ?></h2>
                <button type="button" 
                        class="atc-hamburger-menu-close" 
                        aria-label="<?php esc_attr_e('Close Menu', 'advanced-travel-crm'); ?>">
                    ×
                </button>
            </div>
            
            <div class="atc-hamburger-menu-items">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container' => false,
                    'menu_class' => 'menu',
                    'fallback_cb' => '__return_false',
                    'walker' => new ATC_Hamburger_Menu_Walker(),
                ]);
                ?>
            </div>
        </nav>
        <?php
    }
}

/**
 * Custom Walker for Hamburger Menu
 */
class ATC_Hamburger_Menu_Walker extends Walker_Nav_Menu {
    
    function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }
    
    function end_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }
    
    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
        
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
        
        $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
        
        $output .= $indent . '<li' . $id . $class_names . '>';
        
        $attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
        $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
        $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
        $attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';
        
        $item_output = isset($args->before) ? $args->before : '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
        $item_output .= '</a>';
        $item_output .= isset($args->after) ? $args->after : '';
        
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
    
    function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= "</li>\n";
    }
}

