/**
 * ATC Astra Hamburger Menu JavaScript
 * Red hamburger menu integrated with Astra theme
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        let initAttempts = 0;
        const MAX_ATTEMPTS = 40;
        
        const selectors = '.ast-header-wrapper, .ast-primary-header, header.site-header, .site-header';
        
        function initializeMenu() {
            const $astraHeader = $(selectors).first();
            const $menu = $('.atc-hamburger-menu');
            const $overlay = $('.atc-hamburger-menu-overlay');
            const $closeBtn = $('.atc-hamburger-menu-close');
            const $body = $('body');
            
            if (!$astraHeader.length || !$menu.length || !$overlay.length || !$closeBtn.length) {
                if (initAttempts < MAX_ATTEMPTS) {
                    initAttempts++;
                    setTimeout(initializeMenu, 250);
                } else {
                    console.warn('ATC Menu: Unable to initialize (missing header/menu elements)');
                }
                return;
            }
            
            // Remove any existing toggle buttons to prevent duplicates
            $('.atc-hamburger-menu-toggle').remove();
            
            const $toggle = $(`
                <button type="button" 
                        class="atc-hamburger-menu-toggle" 
                        aria-label="Toggle Menu" 
                        aria-expanded="false" 
                        aria-controls="atc-hamburger-menu">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            `);
            
            $astraHeader.prepend($toggle);
            
            function openMenu() {
                if ($menu.hasClass('active')) return;
                $menu.addClass('active');
                $overlay.addClass('active');
                $body.addClass('atc-menu-open');
                $toggle.attr('aria-expanded', 'true');
                
                const scrollY = window.scrollY || window.pageYOffset || 0;
                $body.css({
                    'position': 'fixed',
                    'top': `-${scrollY}px`,
                    'width': '100%'
                });
                $body.data('scroll-y', scrollY);
            }
            
            function closeMenu() {
                if (!$menu.hasClass('active')) return;
                $menu.removeClass('active');
                $overlay.removeClass('active');
                $body.removeClass('atc-menu-open');
                $toggle.attr('aria-expanded', 'false');
                
                const scrollY = $body.data('scroll-y') || 0;
                $body.css({
                    'position': '',
                    'top': '',
                    'width': ''
                });
                window.scrollTo(0, parseInt(scrollY, 10));
                $body.removeData('scroll-y');
            }
            
            $toggle.on('click.atcMenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if ($menu.hasClass('active')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
            
            $closeBtn.off('.atcMenu').on('click.atcMenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeMenu();
            });
            
            $overlay.off('.atcMenu').on('click.atcMenu', function(e) {
                if (e.target === this) {
                    closeMenu();
                }
            });
            
            $(document).off('keydown.atcMenu').on('keydown.atcMenu', function(e) {
                if (e.key === 'Escape') {
                    closeMenu();
                }
            });
            
            $('.atc-hamburger-menu-items .menu-item-has-children > a')
                .off('.atcMenu')
                .on('click.atcMenu', function(e) {
                    const $parent = $(this).parent('.menu-item-has-children');
                    const $submenu = $parent.find('.sub-menu').first();
                    
                    if ($submenu.length) {
                        e.preventDefault();
                        $parent.toggleClass('active');
                        $submenu.stop(true, true).slideToggle(250);
                    }
                });
            
            $('.atc-hamburger-menu-items a')
                .off('.atcMenuNavigate')
                .on('click.atcMenuNavigate', function() {
                    closeMenu();
                });
        }
        
        initializeMenu();
        $(window).on('resize.atcMenu orientationchange.atcMenu', function() {
            initializeMenu();
        });
    });
    
})(jQuery);

