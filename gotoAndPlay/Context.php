<?php

namespace gotoAndPlay;

use gotoAndPlay\Plugins\AdvancedCustomFields as AdvancedCustomFields;
use gotoAndPlay\Templates\Cart;
use gotoAndPlay\Templates\Index as Index;
use gotoAndPlay\Plugins\WPML as WPML;
use gotoAndPlay\Models\Menu as Menu;
use gotoAndPlay\Models\Popup as Popup;
use gotoAndPlay\Helpers as Helpers;
use Timber\Helper;
use TimberMenu;
use WP_Query;

class Context {

    public static function globalContext( $context = [] ) {
        $context['version'] = Helpers::getVersion();
        if ( isset( $context['site'] ) ) {
            $context['language'] = $context['site']->language;
        } else {
            $context['language'] = WPML::getCurrentLanguage();
        }

        $context['strings'] = Strings::getAll();

        if( !is_admin() ) {
            $context['header']  = [];
            if ( is_cart() || is_checkout() ) {
                $context['header'] = [
                    'modifier'       => 'header--shop',
                    'isShop'         => true,
                    'help'           => [
                        'label' => get_field( 'checkout_header_info_a', 'options' ),
                        'phone' => get_field( 'checkout_header_info_b', 'options' ),
                    ],
                    'steps'          => [
                        [
                            'title'  => get_field( 'checkout_menu_cart', 'options' ),
                            'icon'   => get_field( 'checkout_menu_cart_icon', 'options' ),
                            'active' => is_cart(),
                        ],
                        [
                            'title'  => get_field( 'checkout_menu_main', 'options' ),
                            'icon'   => get_field( 'checkout_menu_main_icon', 'options' ),
                            'active' => is_checkout() && ! is_wc_endpoint_url( 'order-received' ),
                        ],
                        [
                            'title'  => get_field( 'checkout_menu_end', 'options' ),
                            'icon'   => get_field( 'checkout_menu_end_icon', 'options' ),
                            'active' => is_wc_endpoint_url( 'order-received' ),
                        ],
                    ],
                    'profileBoxData' => Menu::getProfileMenu(),
                    'closeBtn'       => get_site_url(),
                ];
            } else {
                $bragBar = get_field('brag_bar', get_the_ID());
                $brags = get_field('brags', get_the_ID());
                if ( is_archive() ) {
                    $bragBar = get_field('brag_bar', 'options');
                    $brags = get_field('brags', 'options');
                }
                $context['header'] = [
                    'bragBar' => $bragBar,
                    'brags'   => $brags,
                    'navigation'     => [
                        'logo'  => [
                            'link'  => get_site_url(),
                            'title' => get_bloginfo( 'name' ),
                        ],
                        'items' => Menu::getMegaMenu(),
                    ],
                    'mobileNavigation'     => [
                        'logo'  => false,
                        'items' => Menu::getMobileMenu(),
                    ],
                    'languages'      => self::getLanguages(),
                    'profileBoxData' => Menu::getProfileMenu(),
                    'minicart'       => Menu::getMinicart(),
                    'cartCount'      => sprintf( '<span class="js-minicart-count">%s</span>', WC()->cart->get_cart_contents_count() ? WC()->cart->get_cart_contents_count() : '' )
                ];

                if ( get_field( 'location_link', 'options' ) ) {
                    $context['header']['location_link'] = get_field( 'location_link', 'options' );
                }

                $freeShipping= Cart::getRequiredForFreeShipping();
                if($freeShipping && (is_front_page() || Helpers::isShopPage())) {
                    $context['notification'] = sprintf(__('Lisa %s eest tooteid ja saa tasuta kohaletoomine', 'kafo'), Helpers::getFormattedPrice($freeShipping));
                } else if($freeShipping === 0) {
                    $context['notification'] = __('Selle tootega transport tasuta', 'kafo');
                } else {
                    // default price display, as WC doesnt return shipping methods when there are no products to ship
                    $context['notification'] = sprintf(__('Lisa %s eest tooteid ja saa tasuta kohaletoomine', 'kafo'), Helpers::getFormattedPrice(50));
                }
            }

            $context['homeUrl']        = home_url( '/' );
            $context['cartLink']       = wc_get_cart_url();
            $context['header']['logo'] = [
                'link'  => get_site_url(),
                'title' => get_bloginfo( 'name' ),
            ];
            $context['footerBrands']   = self::getBrands();
            $context['footerQuote']    = self::getQuote();
            $context['subMenu']        = Menu::getSubMenu( 'blog' );
            $context['footer']         = Menu::getFooterMenu();
            $context['bottomItems']    = Menu::getFooterQuickMenu();
            $context['gtmNoscript']    = '<!-- Google Tag Manager (noscript) --><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-P938N2H" height="0" width="0" style="display:none; :hidden"></iframe></noscript><!-- End Google Tag Manager (noscript) -->';
            $context['popupData']   = Popup::getContext();
			
			$context['copmpare_counter']   = do_shortcode('[yith_woocompare_counter]');
        }

        return $context;
    }

    public static function getQuote() {
        return [
            'text'   => get_field( 'footer_quote', 'options' ),
            'button' => [
                'text' => get_field( 'footer_quote_label', 'options' ),
                'link' => get_field( 'footer_quote_link', 'options' ),
            ],
        ];
    }

    public static function getBrands() {
        return [
            'modifier'   => 'brands-list--clean',
            'link'       => get_field( 'footer_partners_link', 'options' ),
            'brandLogos' => Menu::getFooterPartners(),
        ];
    }

    public static function getContext( $templateName, $defaultData = [] ) {
        $templateClassName = 'gotoAndPlay\\Templates\\' . $templateName;
        if ( class_exists( $templateClassName ) ) {
            if ( $defaultData ) {
                $templateClass = new $templateClassName( $defaultData );
            } else {
                $templateClass = new $templateClassName();
            }
        } else {
            $templateClass = new Index();
        }

        return $templateClass->getContext();
    }

    public static function getPagination( WP_Query $query = null, $nearbyRange = 1, $extraRange = 2 ) {
        global $paged;
        if ( ! $query ) {
            global $wp_query;
            $pages = $wp_query->max_num_pages;
            if ( isset( $wp_query->query_vars['paged'] ) ) {
                $paged = $wp_query->query_vars['paged'];
            }
        } else {
            $pages = $query->max_num_pages;
            if ( isset( $query->query_vars['paged'] ) ) {
                $paged = $query->query_vars['paged'];
            }
        }

        if ( ! $paged ) {
            $paged = 1;
        }

        // replace server request url to avoid ajax links in pagination
        $requestUri = false;
        if(wp_doing_ajax()) {
            $pageUri = str_replace(home_url(), '', get_term_link($query->get_queried_object()));
            if ($pageUri) {
                $requestUri = $_SERVER['REQUEST_URI'];
                $_SERVER['REQUEST_URI'] = $pageUri;
            }
        }

        $pagination = [
            'next'             => [
                'text'  => Template::compileComponent( '@icon', [
                    'modifier' => 'ic_24_arrow',
                    'class'    => 'pagination__item-icon'
                ] ),
                'paged' => ( $paged + 1 ),
                'url'   => get_pagenum_link( $paged + 1 ),
            ],
            'previous'         => [
                'text'  => Template::compileComponent( '@icon', [
                    'modifier' => 'ic_24_arrow',
                    'class'    => 'pagination__item-icon'
                ] ),
                'paged' => ( $paged - 1 ),
                'url'   => get_pagenum_link( $paged - 1 ),
            ],
            'current'          => [
                'text' => $paged,
                'url'  => false,
            ],
            'select'           => [
                'text'  => Template::compileComponent( '@icon', [ 'modifier' => 'chevron' ] ),
                'url'   => "#",
                'type'  => "select",
                'paged' => $paged,
            ],
            'page'             => $paged,
            'pages'            => $pages,
            'nearbyPagesLimit' => $nearbyRange,
            'extraPagesLimit'  => $extraRange,
            'items'            => [],
            'showAll'          => [
                'link' => add_query_arg( 'all', 1, get_pagenum_link( 1 ) ),
                'text' => __( 'Vaata kõiki', 'kafo' ),
            ],
        ];
        for ( $i = 1; $i <= $pages; $i ++ ) {
            $pagination['items'][ $i ] = [
                'current' => $paged == $i ? true : false,
                'text'    => $i,
                'paged'   => $i,
                'url'     => get_pagenum_link( $i ),
            ];
        }
        if( isset($_POST['action']) && $_POST['action'] == 'filter-products' ) {
            $pagination['modifier'] = 'is-filter';
        }

        //restore server request url
        if(wp_doing_ajax() && $requestUri) {
            $_SERVER['REQUEST_URI'] = $requestUri;
        }

        return $pagination;
    }

    public static function getLanguages() {
        $languages = WPML::getLanguages();
        if ( $languages ) {
            $arr = [
                'visible' => false,
                'items'   => [],
            ];
            foreach ( $languages as $language ) {
                $obj = [
                    'name'       => Helpers::parseLangCode( $language['language_code'] ),
                    'link'       => $language['url'],
                    'isSelected' => $language['active'],
                    'icon'       => $language['active'],
                    'value'      => $language['url'],
                ];
                array_push( $arr['items'], $obj );
            }

            if ( count( $arr['items'] ) > 1 ) {
                $arr['visible'] = true;
            }

            return $arr;
        } else {
            return false;
        }
    }

}
