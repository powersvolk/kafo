<?php

namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Templates\Cart;

class Menu {

    public static function getId() {
        global $post;
        return $post->ID;
    }

    public static function accountPageId() {
        return get_option( 'woocommerce_myaccount_page_id' );
    }

    public static function getFooterMenu() {
        $footer = [
            "disclaimer" => get_field( 'footer_copyright', 'option' ),
            "column1"    => [
                "title"   => get_field( 'footer_address_title', 'options' ),
                "content" => get_field( 'footer_aadress', 'options' ),
                "social"  => [
                    [
                        "link" => get_field( 'social_facebook_url', 'options' ),
                        "icon" => 'facebook',
                    ],
                    [
                        "link" => get_field( 'social_youtube_url', 'options' ),
                        "icon" => 'youtube',
                    ],
                    [
                        "link" => get_field( 'social_instagram_url', 'options' ),
                        "icon" => "instagram",
                    ],
                ],
            ],
            "bottomNav"  => [
                "nav" => [
                    [
                        "name" => __( "Privacy Policy", 'kafo' ),
                        'link' => get_field( 'footer_privacy_url', 'options' ),
                    ],
                    [
                        "name" => __( "Terms of Use", 'kafo' ),
                        'link' => get_field( 'footer_terms_url', 'options' ),
                    ],
                    [
                        "name" => __( "Site Map", 'kafo' ),
                        'link' => get_field( 'footer_sitemap', 'options' ),
                    ],
                ],
            ],
        ];

        if ( is_cart() || is_checkout() ) {
            $footer['modifier']   = 'footer--checkout';
            $footer['isCheckout'] = true;

            $colBase = 2;
            if ( have_rows( 'footer_checkout_content', 'options' ) ) {
                while ( have_rows( 'footer_checkout_content', 'options' ) ) {
                    the_row();
                    $footer[ 'column' . $colBase ] = [
                        'title'   => get_sub_field( 'title' ),
                        'content' => get_sub_field( 'content' ),
                    ];

                    $colBase ++;
                }
            }
        } else {
            $footer["column4"] = [
                "title"   => get_field( 'footer_content_title', 'options' ),
                "content" => get_field( 'footer_content', 'options' ),
            ];
            $footer["column5"] = [
                "title"       => get_field( 'newsletter_title', 'options' ),
                "content"     => get_field( 'newsletter_description', 'options' ),
                'placeholder' => get_field( 'newsletter_placeholder', 'options' ),
            ];
            $tree              = self::getMenuTree( self::getNavMenuItems( 'footer' ) );
            $colBase           = 2;
            foreach ( $tree as $branch ) {
                $menuList          = [];
                $menuList['title'] = $branch['item']->title;
                if ( $branch['submenu'] ) {
                    foreach ( $branch['submenu'] as $subitem ) {
                        $menuList['nav'][] = [
                            "link" => $subitem['item']->url,
                            "name" => $subitem['item']->title,
                        ];
                    }
                }

                $footer[ "column" . $colBase ] = $menuList;
                $colBase ++;
            }
        }

        return $footer;
    }

    public static function getFooterPartners() {
        $footer_partners = get_field( 'footer_partners', 'options' );
        $logos           = [];
        if ( is_array( $footer_partners ) ) {
            foreach ( $footer_partners as $partner ) {
                $logos[] = [
                    'image' => Helpers::getImage( $partner['logo'] ),
                ];
            }
        }

        return $logos;
    }

    public static function getFooterQuickMenu() {
        $mobileNav = [];
        if ( have_rows( 'footer_mobile_nav', 'options' ) ) {
            while ( have_rows( 'footer_mobile_nav', 'options' ) ) {
                the_row();
                $mobileNav[] = [
                    'icon'  => get_sub_field( 'icon' ),
                    'link'  => get_sub_field( 'link' ),
                    'title' => get_sub_field( 'label' ),
                    'isActive' => get_the_ID() == url_to_postid(get_sub_field('link')),
                ];
            }
        }

        return $mobileNav;
    }

    public static function getMegaMenu() {
        $megaMenu = [];
        $tree     = self::getMenuTree( self::getNavMenuItems( 'header' ) );
        foreach ( $tree as $item ) {
            $menuItem = self::getMenuItem( $item );

            // add megamenu specific context
            if ( $item['item']->object == 'megamenu' ) {
                if ( have_rows( 'extra_list', $item['item']->object_id ) ) {
                    while ( have_rows( 'extra_list', $item['item']->object_id ) ) {
                        the_row();
                        $extraCol = [
                            'menuClass' => 'navigation__list--links',
                            'children'  => [],
                        ];
                        if ( have_rows( 'links' ) ) {
                            $extraRows = [];
                            while ( have_rows( 'links' ) ) {
                                the_row();
                                $extraRows[] = [
                                    'link'  => get_sub_field( 'target' ),
                                    'title' => get_sub_field( 'label' ),
                                ];
                            }

                            $extraCol['children'] = $extraRows;
                        }

                        $menuItem['children'][] = $extraCol;
                    }
                }

                if ( get_field( 'cta_label', $item['item']->object_id ) && get_field( 'cta_link', $item['item']->object_id ) ) {
                    $menuItem['cta'] = [
                        'class' => 'navigation__cta',
                        'text' => get_field( 'cta_label', $item['item']->object_id ),
                        'link' => get_field( 'cta_link', $item['item']->object_id ),
                    ];
                    if ( get_field( 'cta_side', $item['item']->object_id ) ) {
                        $menuItem['cta']['class'] .= ' navigation__cta--left';
                    }
                }
            }

            $megaMenu[] = $menuItem;
        }

        return $megaMenu;
    }

    public static function getMobileMenu() {
        $items     = self::getMenuTree( self::getNavMenuItems( 'mobile' ) );
        $menu = [];

        foreach ( $items as $item ) {

            $menuItem = self::getMenuItem( $item );

            $menu[] = $menuItem;

        }

        // var_dump($menu);

        return $menu;
    }

    public static function getCategoryMenu( $menuName ) {
        $items = self::getMenuTree( self::getNavMenuItems( $menuName ) );

        return self::getCategoryMenuItems( $items );
    }

    private static function getCategoryMenuItems( $items ) {
        $menu = [];
        foreach ( $items as $item ) {
            $count   = '';
            $element = [
                'name'  => $item['item']->title,
                'href'  => $item['item']->url,
                'id'    => $item['item']->object_id,
                'count' => $count,
            ];
            if ( $item['submenu'] ) {
                $element['suboptions'] = self::getCategoryMenuItems( $item['submenu'] );
            }

            $menu[] = $element;
        }

        return $menu;
    }

    private static function getNavMenuItems( $menu_name = 'main' ) {
        $locations = get_nav_menu_locations();
        $menu      = [];
        if ( isset( $locations[ $menu_name ] ) ) {
            $menu_id = $locations[ $menu_name ];
            foreach ( wp_get_nav_menu_items( $menu_id ) as $item ) {
                array_push( $menu, $item );
            }
        }

        return $menu;
    }

    private static function getMenuTree( $menuItems ) {
        $tree = [];
        foreach ( $menuItems as $item ) {
            $tree = self::addMenuBranch( $tree, $item );
        }

        return $tree;
    }

    private static function addMenuBranch( $tree, $item ) {
        if ( $item->menu_item_parent ) {
            if ( isset( $tree[ $item->menu_item_parent ] ) ) {
                $tree[ $item->menu_item_parent ]['submenu'][ $item->ID ] = [
                    'item'    => $item,
                    'submenu' => [],
                ];
            } else {
                foreach ( $tree as $key => $value ) {
                    if ( $value['submenu'] ) {
                        $tree[ $key ]['submenu'] = self::addMenuBranch( $tree[ $key ]['submenu'], $item );
                    }
                }
            }
        } else {
            $tree[ $item->ID ] = [
                'item'    => $item,
                'submenu' => [],
            ];
        }

        return $tree;
    }

    private static function getMenuItem( $menu ) {
        $element  = $menu['item'];
        $modifier = '';
        $menuClasses = [];
        $imgId = get_field('category_image', $element->ID);

        if ( $element->object == 'megamenu' && get_field( 'menu_position', $element->object_id ) ) {
            $modifier = 'navigation__item--special';
        }

        if ( ( isset( $menu['submenu'] ) && count( $menu['submenu'] ) ) ) {
            $children    = [];

            if ( $element->object == 'megamenu' && get_field( 'menu_size', $element->object_id ) ) {
                $menuClasses[] = 'navigation__list--full';
            }

            if ( ! $element->menu_item_parent ) {
                $menuClasses[] = 'navigation__list--megamenu';
            }

            if ( $element->menu_item_parent ) {
                $menuClasses[] = 'navigation__list--childmenu';
            }

            if ( count( $menu['submenu'] ) ) {
                $subnav = [];
                foreach ( $menu['submenu'] as $subelement ) {
                    $subnav[] = self::getMenuItem( $subelement );
                }

                $children = $subnav;
            }

            if ($imgId) {
                $image = [
                    'srcset' => Helpers::getImageSrcSet($imgId, '100x80'),
                    'alt'    => ''
                ];
            }
            // var_dump($imgId);

            return [
                'title'     => $element->title,
                'link'      => $element->object == 'megamenu' ? '#' : $element->url,
                'modifier'  => $modifier,
                'menuClass' => implode( ' ', self::filterNavMenuClasses( $element, $menuClasses ) ),
                'typeClass' => implode( ' ', self::filterNavMenuClasses( $element ) ),
                'children'  => $children,
                'img'       => $image,
            ];
        } else {
            return [
                'title'     => $element->title,
                'link'      => $element->url == '#logout' ? wp_logout_url(home_url()) : $element->url,
                'modifier'  => $modifier,
                'typeClass' => implode( ' ', self::filterNavMenuClasses( $element ) ),
            ];
        }
    }

    private static function filterNavMenuClasses( $item, $additionalClasses = [] ) {
        $classes        = [];
        $custom_classes = [];
        $wp_classes     = [];
        $is_custom      = true;
        $itemClasses    = [];
        if ( is_array( $item->classes ) ) {
            foreach ( $item->classes as $c ) {
                if ( $c ) {
                    if ( $c == 'menu-item' ) {
                        $is_custom = false;
                    }

                    if ( $is_custom ) {
                        $custom_classes[] = $c;
                    } else {
                        $wp_classes[] = $c;
                    }
                }
            }

            $itemClasses = array_intersect( $item->classes, [
                'menu-item',
                'current-menu-item',
                'current-menu-parent',
                'current-menu-ancestor',
                'current-page-ancestor',
                'current-page-parent',
                'menu-item-has-children',
                'h-hidden',
                'h-hidden-xs',
                'h-hidden-sm',
                'h-hidden-md',
                'h-visible-lg',
                'h-visible',
                'h-visible-xs',
                'h-visible-sm',
                'h-visible-md',
                'h-visible-lg'
            ] );
        }

        foreach ( $itemClasses as $class ) {
            switch ( $class ) {
                case 'menu-item':
                    $class = false;
                    break;

                case 'current-menu-item':
                case 'current-menu-parent':
                case 'current-menu-ancestor':
                case 'current-page-ancestor':
                case 'current-page-parent':
                    $class = 'is-current';
                    break;

                case 'menu-item-has-children':
                    $class = 'has-subnav';
                    break;
            }

            if ( $class ) {
                array_push( $classes, $class );
            }
        }

        if (get_field('mobile_1st_level', $item)) {
            array_push( $classes, 'is-mobile-main' );
        }

        if ( is_category() || get_post_type() ) {
            if ( get_post_type_archive_link( get_post_type() ) == $item->url ) {
                array_push( $classes, 'is-current' );
            }
        }

        return array_unique( array_merge( $additionalClasses, $custom_classes, $classes ) );
    }

    private static function getTitleMenu( $items ) {
        $menu = [];
        foreach ( $items as $item ) {
            $element = [
                'name' => $item['item']->title,
                'href' => $item['item']->url,
            ];
            if ( $item['submenu'] ) {
                $element['suboptions'] = self::getTitleMenu( $item['submenu'] );
            }

            $menu[] = $element;
        }

        return $menu;
    }

    public static function getTitleNavigation( $ID ) {
        $menuId = get_field( 'title_navigation', $ID );
        if ( $menuId ) {
            $items = self::getTitleMenu( self::getMenuTree( wp_get_nav_menu_items( $menuId ) ) );
        } else {
            $items = [];
        }

        return $items;
    }

    public static function getProfileMenu() {
        if ( is_user_logged_in() ) {
            return [
                'isLogged' => true,
                'isActive' => self::accountPageId() == self::getId() ? true : false,
                'account'  => get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ),
                'logout'   => wp_logout_url( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ),
            ];
        }

        $resetView      = isset( $_REQUEST['reset_key'] ) && $_REQUEST['reset_key'];
        $loginFailed    = isset( $_REQUEST['login'] ) && $_REQUEST['login'] == 'failed';
        $passwordField  = [
            'id'           => 'register-password',
            'name'         => 'password',
            'label'        => __( 'Salasõna', 'kafo' ),
            'type'         => 'password',
            'autocomplete' => 'off',
        ];
        $passwordField2  = [
            'id'           => 'forgot-password-main',
            'name'         => 'password',
            'label'        => __( 'Salasõna', 'kafo' ),
            'type'         => 'password',
            'autocomplete' => 'off',
        ];
        $passwordField3 = [
            'id'           => 'forgot-password2',
            'name'         => 'password2',
            'label'        => __( 'Salasõna korrata', 'kafo' ),
            'type'         => 'password',
            'autocomplete' => 'off',
        ];
        $emailField     = [
            'id'           => 'request-email',
            'type'         => 'email',
            'name'         => 'user_login',
            'label'        => __( 'Email', 'kafo' ),
            'autocomplete' => 'off',
        ];
        $emailField2     = [
            'id'           => 'register-email',
            'type'         => 'email',
            'name'         => 'user_login',
            'label'        => __( 'Email', 'kafo' ),
            'autocomplete' => 'off',
        ];

        //
        $honeypotField  = [
            'modifier'     => 'h-hidden',
            'id'           => 'phone',
            'type'         => 'text',
            'name'         => 'user_phone',
            'label'        => __( 'Phone', 'kafo' ),
            'autocomplete' => 'off',
        ];

        //login
        //register
        //request reset
        //reset
        //re-login
        $header  = [
            [
                'title'     => get_field( 'account_login_tab', 'options' ),
                'link'      => '#profile-login',
                'isCurrent' => true,
            ]
        ];
        $screens = [
            'login'     => [
                'isActive'     => true,
                'form'         => [
                    'name'     => 'loginform',
                    'action'   => esc_url( site_url( 'wp-login.php', 'login_post' ) ),
                    'redirect' => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                ],
                'fields'       => [
                    [
                        'id'    => 'email',
                        'name'  => 'log',
                        'type'  => 'email',
                        'label' => __( 'Email', 'kafo' ),
                    ],
                    [
                        'id'    => 'password',
                        'name'  => 'pwd',
                        'label' => __( 'Salasõna', 'kafo' ),
                        'type'  => 'password',
                    ],
                ],
                'submitButton' => [
                    'type'  => 'submit',
                    'name'  => 'wp-submit',
                    'value' => 1,
                    'text'  => __( 'Logi sisse', 'kafo' ),
                ],
                'bottomLink'   => [
                    'text'  => __( 'Unustasid parooli?', 'kafo' ),
                    'class' => 'profile-box__link js-profile-box-show',
                    'link'  => '#profile-request',
                ],
                'error' => $loginFailed ? __('Vigane kasutajanimi või parool', 'kafo') : '',
            ],
            'request'   => [
                'form'         => [
                    'nonce'      => wp_create_nonce( 'request-reset' ),
                    'ajaxAction' => 'request-reset',
                ],
                'fields'       => [
                    $emailField,
                ],
                'submitButton' => [
                    'type' => 'submit',
                    'text' => __( 'Edasi', 'kafo' ),
                ],
                'backLink'     => [
                    'text'  => __( 'Tagasi', 'kafo' ),
                    'class' => 'profile-box__link js-profile-box-show',
                    'link'  => '#profile-login',
                ],
            ],
            'requested' => [
                'modifier'     => 'profile-box__content--center',
                'contentIcon'  => get_field( 'account_requested_icon', 'options' ),
                'submitButton' => [
                    'isNav'   => true,
                    'element' => 'a',
                    'link'    => '#profile-login',
                    'text'    => __( 'Saab tehtud', 'kafo' ),
                ],
            ],
        ];
        if ( ! is_cart() && ! is_checkout() ) {
            $header[0]['isCurrent']       = $resetView || $loginFailed;
            $header[]                     = [
                'title'     => get_field( 'account_register_tab', 'options' ),
                'link'      => '#profile-register',
                'isCurrent' => !$resetView && !$loginFailed,
            ];
            $screens['login']['isActive'] = $loginFailed;
            $screens['register']          = [
                'isActive'     => !$loginFailed,
                'form'         => [
                    'nonce'      => wp_create_nonce( 'register-user' ),
                    'ajaxAction' => 'register-user',
                ],
                'fields'       => [
                    $emailField2,
                    $passwordField,
                    $honeypotField,
                ],
                'submitButton' => [
                    'type' => 'submit',
                    'text' => __( 'Loo konto', 'kafo' ),
                ],
                'recaptchaLabel' => get_field('recaptcha_label', 'options'),
            ];
        }

        if ( $resetView ) {
            $screens['reset']                = [
                'isActive'     => true,
                'description'  => sprintf( get_field( 'account_reset_descr', 'options' ), esc_attr( $_REQUEST['user_login'] ) ),
                'form'         => [
                    'nonce'      => wp_create_nonce( 'reset-password' ),
                    'ajaxAction' => 'reset-password',
                    'reset_key'  => esc_attr( $_REQUEST['reset_key'] ),
                    'user_login' => esc_attr( $_REQUEST['user_login'] ),
                ],
                'fields'       => [
                    $passwordField2,
                    $passwordField3,
                ],
                'submitButton' => [
                    'type' => 'submit',
                    'text' => __( 'Edasi', 'kafo' ),
                ],
            ];
            $screens['relogin']              = [
                'form'         => [
                    'name'     => 'loginform',
                    'action'   => esc_url( site_url( 'wp-login.php', 'login_post' ) ),
                    'redirect' => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                ],
                'fields'       => [
                    [
                        'id'    => 'relogin-email',
                        'name'  => 'log',
                        'label' => __( 'Email', 'kafo' ),
                        'value' => esc_attr( $_REQUEST['user_login'] ),
                    ],
                    [
                        'id'    => 'relogin-password',
                        'name'  => 'pwd',
                        'label' => __( 'Salasõna', 'kafo' ),
                        'type'  => 'password',
                    ],
                ],
                'submitButton' => [
                    'type'  => 'submit',
                    'name'  => 'wp-submit',
                    'value' => 1,
                    'text'  => __( 'Logi sisse', 'kafo' ),
                ],
            ];
            $screens['login']['isActive']    = false;
            $screens['register']['isActive'] = false;
        }

        $profile = [
            'isFixed'   => $resetView || $loginFailed,
            'headLinks' => $header,
            'screens'   => $screens
        ];

        //add tab content
        foreach ( $profile['screens'] as $screen => $content ) {
            if ( ! isset( $content['title'] ) ) {
                $profile['screens'][ $screen ]['title'] = get_field( 'account_' . $screen . '_title', 'options' );
            }

            if ( ! isset( $content['description'] ) ) {
                $profile['screens'][ $screen ]['description'] = get_field( 'account_' . $screen . '_descr', 'options' );
            }
        }

        return $profile;
    }

    public static function getMinicart($isInit = false) {

        $cartContentsCount = WC()->cart->get_cart_contents_count();

        if ( $cartContentsCount ) {
            $minicartTitle = ( $cartContentsCount == 1 ) ? sprintf( get_field( 'minicart_title_single', 'options' ), $cartContentsCount ) : sprintf( get_field( 'minicart_title', 'options' ), $cartContentsCount );
        } else {
            $minicartTitle = get_field( 'minicart_title_empty', 'options' );
        }

        $cart = [
            'modifier' => 'cart--popout',
            'isPopout' => true,
            'title'    => $minicartTitle,
            'footer'   => [
                'transport' => get_field( 'minicart_transport', 'options' ),
                'pickup'    => get_field( 'minicart_pickup', 'options' ),
            ],
            'button'   => [
                'text' => get_field( 'minicart_button', 'options' ),
                'link' => get_field( 'minicart_target', 'options' ) == 'cart' ? wc_get_cart_url() : wc_get_checkout_url(),
            ],
            'items'    => [],
        ];
        if($isInit){
            $cart['modifier'] .= ' is-loading';
            $cart['ajaxCart'] = true;
        }

        $products = [];

        $items = WC()->cart->get_cart();
        foreach ( $items as $key => $item ) {
            $productId = apply_filters( 'woocommerce_cart_item_product_id', $item['product_id'], $item, $key );
            $product   = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );

            $productExists = ( $product && $product->exists() && $item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $item, $key ) );
            if ( ! $productExists ) {
                continue;
            }

            $productClass     = apply_filters( 'woocommerce_cart_item_class', 'cart_item', $item, $key );
            $productPermalink = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $item ) : '', $item, $key );
            $productRemove    = [
                'link' => WC()->cart->get_remove_url( $key ),
                'data' => sprintf( 'data-product_id="%s" data-product_sku="%s"', $productId, $product->get_sku() ),
            ];
            $productImage     = sprintf( '%s 85w, %s 170w', Helpers::getImage( Helpers::getImageId( $product->get_id() ), '85w' ), Helpers::getImage( Helpers::getImageId( $product->get_id() ), '170w' ) );
            $productTitle     = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $item, $key );

            // $productPrice     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $item, $key );
            if (isset($item['discounts'])) {
                $productPrice = '<del>' . wc_price($product->get_regular_price()) . '</del><ins> ' . wc_price($product->get_price()) . '</ins>';
            } else {
                $productPrice = '<span class="amount">' . wc_price($product->get_regular_price()) . '</span>';
            }

            $productQnty      = $item['quantity'];
            $productTotal     = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $item['quantity'] ), $item, $key );

            $productItem = [
                'id'           => $productId,
                'title'        => $productTitle,
                'subtitle'     => __( 'Eemalda', 'kafo' ),
                'class'        => $productClass,
                'link'         => $productPermalink,
                'remove'       => $productRemove,
                'quantityName' => "cart[{$key}][qty]",
                'quantity'     => $productQnty,
                'price'        => $productPrice,
                'total'        => $productTotal,
                'image'        => $productImage,
            ];

            $products[] = $productItem;
        }

        $cart['items'] = $products;

        $freeShippingFrom = Cart::getRequiredForFreeShipping();
        if ($cartContentsCount && $freeShippingFrom) {
            $cart['message'] = sprintf(__('Ainult %s tasuta kohaletoimetamiseni.', 'kafo'), '<strong>'. Helpers::getFormattedPrice($freeShippingFrom) .'</strong>');
        }

        return $cart;
    }

    public static function getSubMenu( $location ) {
        $menu = self::getCategoryMenu( $location );

        $current = get_queried_object();

        $title = get_the_title( get_option( 'page_for_posts' ) );

        $mymenu['title']     = $title;
        $mymenu['menuItems'] = [];
        foreach ( $menu as $item ) {
            $element = [
                'title' => $item['name'],
            ];

            if ( is_tax( $current ) ) {
                $item_children = get_term_children( $item['id'], $current->taxonomy );
                if ( is_array( $item_children ) ) {
                    $child_of = ( in_array( $current->term_id, $item_children ) ) ? true : false;
                } else {
                    $child_of = false;
                }
            } else {
                $child_of = false;
            }

            if ( $current && $item['id'] == $current->term_id || $child_of ) {
                $element['active'] = true;
            }

            if ( $current && $current->parent != 0 && $child_of ) {
                $element['active'] = true;
            }

            if ( isset( $item['suboptions'] ) && $item['suboptions'] ) {
                $element['id']        = sanitize_title( $item['name'] );
                $element['label']     = $item['name'];
                $element['isDefault'] = true;

                $subitems   = [];
                $subitems[] = [ 'name' => $item['name'], 'value' => $item['href'], 'href' => $item['href'] ];

                foreach ( $item['suboptions'] as $subitem ) {
                    $subelement = [
                        'name'  => $subitem['name'],
                        'value' => $subitem['href'],
                        'href'  => $subitem['href'],
                    ];
                    if ( $current && $subitem['id'] == $current->term_id ) {
                        $subelement['isSelected'] = true;
                    }

                    $subitems[] = $subelement;
                }

                $element['options'] = $subitems;
            } else {
                $element['link'] = $item['href'];
            }

            $mymenu['menuItems'][] = $element;
        }

        return $mymenu;
    }

}
