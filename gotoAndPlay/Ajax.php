<?php

namespace gotoAndPlay;

use gotoAndPlay\Models\Menu;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Models\User;
use gotoAndPlay\Templates\Cart;
use gotoAndPlay\Utils\MailChimp\MailChimp;
use WP_Query;
use WP_Error;
use WC_Order;

class Ajax {

    private $ajaxEvents = [
        'filter-products'       => [ 'callback' => 'filterProducts' ],
        'register-user'         => [ 'callback' => 'registerUser' ],
        'request-reset'         => [ 'callback' => 'requestPassReset' ],
        'reset-password'        => [ 'callback' => 'resetPassword' ],
        'autosuggest'           => [ 'callback' => 'autosuggest' ],
        'update-profile'        => [ 'callback' => 'updateProfile' ],
        'add-location'          => [ 'callback' => 'addLocation' ],
        'add-company'           => [ 'callback' => 'addCompany' ],
        're-order'              => [ 'callback' => 'reOrder' ],
        'newsletter-subscribe'  => [ 'callback' => 'subscribe' ],
        'popup-subscribe'       => [ 'callback' => 'newsletterSubscribe' ],
        'add-to-cart'           => [ 'callback' => 'addToCart' ],
        'remove-cart-item'      => [ 'callback' => 'removeFromCart' ],
        'update-notification'   => [ 'callback' => 'updateNotification' ],
    ];

    public function __construct() {
        foreach ( $this->ajaxEvents as $key => $vars ) {
            add_action( 'wp_ajax_' . $key, [ $this, $vars['callback'] ] );
            add_action( 'wp_ajax_nopriv_' . $key, [ $this, $vars['callback'] ] );
        }
    }

    public function removeFromCart() {
        $urlParts = parse_url( $_POST['data'] );
        parse_str( $urlParts['query'], $queryParts );
        $cart_item_key = sanitize_text_field( $queryParts['remove_item'] );
        if ( $cart_item = WC()->cart->get_cart_item( $cart_item_key ) ) {
            WC()->cart->remove_cart_item( $cart_item_key );
        }
        $result = [
            'success'   => 1,
            'minicart'  => Template::compileComponent( '@cart', Menu::getMinicart() ),
            'cartCount' => WC()->cart->get_cart_contents_count(),
        ];
        wp_send_json( $result );
    }

    public function addToCart() {
        $productId = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $_POST['data'] );
        if ( isset( $_POST['set'] ) ) {
            $cartItemKey = false;
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                if ( $cart_item['product_id'] == $productId ) {
                    $cartItemKey = $cart_item_key;
                }
            }

            if ( $cartItemKey ) {
                WC()->cart->set_quantity( $cartItemKey, intval( $_POST['set'] ) );
            }
        } else {
            $qty = ( isset( $_POST['qty'] ) ? intval( $_POST['qty'] ) : 1 );
            if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $productId, $qty ) ) {
                wc_get_product( $productId );
                WC()->cart->add_to_cart( $productId, $qty );
            }
        }

        $result = [
            'success'   => 1,
            'minicart'  => Template::compileComponent( '@cart', Menu::getMinicart() ),
            'cartCount' => WC()->cart->get_cart_contents_count(),
        ];
        wp_send_json( $result );
    }

    public function reOrder() {
        if ( User::getId() ) {
            $order_id = $_POST['data'];
            $order    = new WC_Order( trim( $order_id ) );
            if ( $order->get_customer_id() == User::getId() ) {
                if ( WC()->cart->get_cart_contents_count() ) {
                    WC()->cart->empty_cart();
                }

                foreach ( $order->get_items() as $productInfo ) {
                    $productId       = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $productInfo['product_id'] );
                    $qty             = (int) $productInfo['qty'];
                    $allVariations   = [];
                    $variationId     = (int) $productInfo['variation_id'];
                    $cartProductData = apply_filters( 'woocommerce_order_again_cart_item_data', [], $productInfo, $order );
                    foreach ( $productInfo['item_meta'] as $productMetaName => $productMetaValue ) {
                        if ( taxonomy_is_product_attribute( $productMetaName ) ) {
                            $allVariations[ $productMetaName ] = $productMetaValue[0];
                        } else {
                            if ( meta_is_product_attribute( $productMetaName, $productMetaValue[0], $productId ) ) {
                                $allVariations[ $productMetaName ] = $productMetaValue[0];
                            }
                        }
                    }

                    if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $productId, $qty, $variationId, $allVariations, $cartProductData ) ) {
                        continue;
                    }

                    wc_get_product( $productId );
                    WC()->cart->add_to_cart( $productId, $qty, $variationId, $allVariations, $cartProductData );
                }
            }
        }

        wp_send_json( [ 'success' => 1, 'redirect' => wc_get_cart_url() ] );
    }

    public function updateProfile() {
        if ( User::getId() ) {
            if ( isset( $_POST['data'] ) ) {
                User::update( $_POST['data'] );
            }
        }

        wp_send_json( [ 'success' => 1 ] );
    }

    public function addLocation() {
        if ( User::getId() ) {
            if ( isset( $_POST['data'] ) ) {
                User::updateAddress( $_POST['data'] );
            }
        }

        wp_send_json( [ 'success' => 1 ] );
    }

    public function addCompany() {
        if ( User::getId() ) {
            if ( isset( $_POST['data'] ) ) {
                User::updateCompanyDetails( $_POST['data'] );
            }
        }

        wp_send_json( [ 'success' => 1 ] );
    }

    public function subscribe() {
        $email = $_POST['data'];
        if ( $email && get_field( 'mailchimp_api_key', 'option' ) ) {
            $mailchimpListId = get_field( 'mailchimp_list_id', 'option' );
            $mailchimp       = new MailChimp( get_field( 'mailchimp_api_key', 'option' ) );
            $mailchimp->post( "lists/$mailchimpListId/members", [
                'email_address' => $email,
                'status'        => 'pending',
            ] );
        }

        wp_send_json( [ 'success' => 1 ] );
    }

    public function newsletterSubscribe() {
        $email = $_POST['email'];
        $mailchimpListId = $_POST['list_id'];
        $mailchimpApiKey = get_field( 'mailchimp_api_key', 'option' );

        if ( $email && $mailchimpListId && $mailchimpApiKey ) {
            $mailchimp       = new MailChimp( $mailchimpApiKey );
            $mailchimpReturn = $mailchimp->post( "lists/$mailchimpListId/members", [
                'email_address' => $email,
                'status'        => 'subscribed',
            ] );
        }

        if( $mailchimpReturn['status'] == 'subscribed' ) {
            wp_send_json( [ 'success' => 1 ] );
        } else {
            wp_send_json( ['success' => 0, 'message' => __($mailchimpReturn['title'], 'kafo') ] );
        }
    }

    public function autosuggest() {
        $response     = [
            'success' => 1,
            'results' => '',
        ];
        $searchString = esc_attr( $_POST['data'] );

        if ( $searchString ) {
            $groups = [];
            $types  = [
                [
                    'type'   => 'product',
                    'title'  => __( 'Tooted', 'kafo' ),
                    'target' => '?type=products',
                ],
                [
                    'type'   => 'post',
                    'title'  => __( 'Blogi', 'kafo' ),
                    'target' => '?type=blog',
                ],
                [
                    'type'   => 'post',
                    'title'  => __( 'Retseptid', 'kafo' ),
                    'cat'    => get_field( 'recipe_category', 'option' ),
                    'target' => '?type=recipes',
                ],
                [
                    'type'   => 'page',
                    'title'  => __( 'Lehed', 'kafo' ),
                    'target' => '?type=pages',
                ],
            ];
            foreach ( $types as $data ) {
                $type = $data['type'];
                $args = [
                    'post_status'    => 'publish',
                    'post_type'      => $type,
                    's'              => $searchString,
                    'posts_per_page' => 2,
                ];
                if ( isset( $data['cat'] ) ) {
                    $args['cat'] = $data['cat'];
                }

                $query = new WP_Query( $args );
                if ( $query->have_posts() ) {
                    $group = [
                        'title'   => $data['title'],
                        'results' => [],
                    ];
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        $group['results'][] = [
                            'title' => Helpers::highlightText( get_the_title(), $searchString ),
                            'link'  => get_permalink(),
                        ];
                    }

                    if ( $query->found_posts > count( $group['results'] ) ) {
                        $group['more']     = sprintf( __( 'Vaata kõiki (%s)', 'kafo' ), $query->found_posts );
                        $group['moreLink'] = get_search_link( $searchString ) . $data['target'];
                    }

                    $groups[] = $group;
                };

                wp_reset_query();
            }

            $categories = get_terms( [
                'taxonomy' => [ 'product_cat' ], // category
                'search'   => $searchString,
            ] );

            if ( $categories ) {
                $group = [
                    'title'   => __( 'Kategooriad', 'kafo' ),
                    'results' => [],
                ];

                foreach ( array_slice( $categories, 0, 2 ) as $category ) {
                    $group['results'][] = [
                        'title' => Helpers::highlightText( $category->name, $searchString ),
                        'link'  => get_category_link( $category ),
                    ];
                }

                if ( count( $categories ) > count( $group['results'] ) ) {
                    $group['more']     = sprintf( __( 'Vaata kõiki (%s)', 'kafo' ), count( $categories ) );
                    $group['moreLink'] = get_search_link( $searchString ) . '?type=categories';
                }

                $groups[] = $group;
            }

            if ( ! $groups ) {
                $groups[] = [
                    'title'   => __( 'Otsing', 'kafo' ),
                    'results' => [
                        [
                            'title' => sprintf( __( 'Päringule %s ei leitud üllatavalt ühtegi vastet', 'kafo' ), sprintf( '<b>%s</b>', $searchString ) ),
                            'link'  => false,
                        ],
                    ],
                ];
            }

            if ( $groups ) {
                $response['results'] = Template::compileComponent( '@search--results', [ 'groups' => $groups ] );
            }
        }

        wp_send_json( $response );
    }

    public function filterProducts() {
        $response = [
            'success' => 1,
            'content' => '',
            'count'   => 0,
        ];

        $taxArgs = [];
        // set base category
        if ( isset( $_POST['product_cat'] ) ) {
            $taxArgs[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $_POST['product_cat'],
            ];
        }

        // include attribute terms
        if ( ! empty( $_POST['tax_query'] ) ) {
            foreach ( $_POST['tax_query'] as $slug => $terms ) {
                $taxArgs[] = [
                    'taxonomy' => $slug,
                    'field'    => 'slug',
                    'terms'    => $terms,
                ];
            }
        }

        // include range terms
        if ( ! empty( $_POST['range'] ) ) {
            foreach ( $_POST['range'] as $term => $range ) {
                $baseTerm   = get_term_by( 'slug', $_POST['product_cat'], 'product_cat' );
                $termFilter = get_field( 'term_filter_item', $baseTerm );
                $minTerms   = [];
                $maxTerms   = [];
                $n          = 0;
                if ( ! $termFilter ) {
                    $termFilter = 0;
                }

                // get category filter
                while ( have_rows( 'filter_list', 'options' ) ) {
                    the_row();
                    if ( $termFilter == $n ) {
                        // get range filter term
                        while ( have_rows( 'product_filter' ) ) {
                            the_row();
                            // get min/max terms for range
                            if ( get_sub_field( 'wc_attr_filter' ) == $term ) {
                                // set range term by term, as taxonomy cant query with 'BETWEEN'
                                // min term args
                                $minArgs  = [];
                                $minSlug  = get_sub_field( 'wc_attr_filter_min' );
                                $minTerms = get_terms( $minSlug, [ 'hide_empty' => false ] );
                                foreach ( $minTerms as $term ) {
                                    if ( is_numeric( $term->slug ) ) {
                                        if ( intval( $term->slug ) <= $range ) {
                                            $minArgs[] = $term->term_id;
                                        }
                                    }
                                }

                                if ( $minArgs ) {
                                    $taxArgs[] = [
                                        'taxonomy' => $minSlug,
                                        'field'    => 'term_id',
                                        'terms'    => $minArgs,
                                    ];
                                }

                                // max term args
                                $maxArgs  = [];
                                $maxSlug  = get_sub_field( 'wc_attr_filter_max' );
                                $maxTerms = get_terms( $maxSlug, [ 'hide_empty' => false ] );
                                foreach ( $maxTerms as $term ) {
                                    if ( is_numeric( $term->slug ) ) {
                                        if ( intval( $term->slug ) >= $range ) {
                                            $maxArgs[] = $term->term_id;
                                        }
                                    }
                                }

                                if ( $maxArgs ) {
                                    $taxArgs[] = [
                                        'taxonomy' => $maxSlug,
                                        'field'    => 'term_id',
                                        'terms'    => $maxArgs,
                                    ];
                                }
                            }
                        }
                    }

                    $n ++;
                }
            }
        }

        if ( count( $taxArgs ) > 1 ) {
            $taxArgs['relation'] = 'AND';
        }

        $args              = [
            'posts_per_page' => get_option( 'posts_per_page' ),
            'paged'          => 1,
            'post_type'      => 'product',
            'tax_query'      => $taxArgs,
            'post_status'    => 'publish'
        ];
        $query             = new WP_Query( $args );
        $products          = [];
        
		$type_tem = $_POST['type_tem'];
		
		$response['count'] = $query->post_count;
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product    = new Product();
                $products[] = $product->getContext(['id','title', 'description', 'image_full', 'imagesrc', 'link', 'price', 'link_name', 'add_button_name', 'vordle', 'lisainfo', 'attributes', 'price_info', 'compare_button']) ;
            }
        }

		if ($type_tem == 'jura') {
			$response['content']    = Template::compileComponent( '@view-product-category--list', [ 'products' => $products ] );
		} else {	
			$response['content']    = Template::compileComponent( '@view-product-category--grid', [ 'products' => $products ] );
		}	
			$response['pagination'] = Template::compileComponent( '@pagination', Context::getPagination( $query ) );
        wp_send_json( $response );
    }

    public function registerUser() {
        check_ajax_referer( 'register-user', 'nonce' );

        if (!Helpers::validateRecaptcha()) {
            wp_send_json([ 'success' => false, 'error' => __( 'Sa oled robot', 'kafo' ) ]);
            die();
        }

        $response = [ 'success' => false ];

        // Honeypot check
        if ( !empty( $_REQUEST['user_phone'] ) ) {
            $response = [ 'success' => true ];
            wp_send_json( $response );
        }

        if ( empty( $_REQUEST['user_login'] ) ) {
            $response['error'] = 'Kasutaja email puudu';
        } else if ( empty( $_REQUEST['password'] ) ) {
            $response['error'] = 'Parool puudu';
        } else {
            $newUser = wp_create_user( $_REQUEST['user_login'], $_REQUEST['password'], $_REQUEST['user_login'] );

            if ( ! $newUser || is_wp_error( $newUser ) ) {
                if ( $newUser && ( $newUser->get_error_code() === 'existing_user_login' || $newUser->get_error_code() === 'existing_user_login' ) ) {
                    $response['error'] = __( 'Kasutaja juba eksisteerib', 'kafo' );
                } else {
                    $response['error'] = __( 'Viga kasutaja loomisel', 'kafo' );
                }
            }
        }

        $headers     = [
            'Content-type: text/html',
            'From:' . sprintf( 'Kafo <%s>', get_bloginfo( 'admin_email' ) ),
        ];
        $mailTitle   = get_field( 'account_register_mail_title', 'options' );
        $mailContent = get_field( 'account_register_mail_content', 'options' );

        if ( ! isset( $response['error'] ) ) {
            if ( $mailTitle && $mailContent ) {
                wp_mail(
                    $_REQUEST['user_login'],
                    $mailTitle,
                    $mailContent,
                    $headers
                );
            }

            wp_signon( [
                'user_login'    => $_REQUEST['user_login'],
                'user_password' => $_REQUEST['password'],
            ] );
        }
        $response['success']  = ! isset( $response['error'] );
        $response['redirect'] = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
        wp_send_json( $response );
    }

    public function requestPassReset() {
        check_ajax_referer( 'request-reset', 'nonce' );

        $response  = [ 'success' => false ];
        $user_data = get_user_by( 'email', trim( wp_unslash( $_REQUEST['user_login'] ) ) );
        if ( empty( $user_data ) ) {
            $response['error'] = __( 'Kasutajat ei ole olemas', 'kafo' );
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key        = get_password_reset_key( $user_data );

        if ( is_wp_error( $key ) ) {
            $response['error'] = __( 'Viga võtme genereerimisel', 'kafo' );
        }

        //$result = sendNotifcation('reset', $emailArgs);
        if ( ! isset( $response['error'] ) ) {
            $headers   = [
                'Content-type: text/html',
                'From:' . sprintf( 'Kafo <%s>', get_bloginfo( 'admin_email' ) ),
            ];
            $message   = get_field( 'account_request_email', 'options' );
            $resetLink = add_query_arg( [ 'reset_key' => $key, 'user_login' => $user_login ], get_site_url() );
            $result    = wp_mail(
                $user_email,
                __( 'Parooli taastamine', 'kafo' ),
                sprintf( $message, $resetLink ),
                $headers
            );
        }
        $response['success'] = ! isset( $response['error'] );
        wp_send_json( $response );
    }

    public function resetPassword() {
        check_ajax_referer( 'reset-password', 'nonce' );

        $response = [
            'success'    => false,
            'user_login' => esc_attr( $_REQUEST['user_login'] ),
        ];
        $user     = check_password_reset_key( $_REQUEST['reset_key'], $_REQUEST['user_login'] );

        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                $response['error'] = __( 'Parooli vahetus aegunud', 'kafo' );
            } else {
                $response['error'] = __( 'Viga parooli vahetamisel', 'kafo' );
            }
        }

        if ( ! isset( $_REQUEST['password'] ) || ! $_REQUEST['password'] ) {
            $response['error'] = __( 'Parool puudu', 'kafo' );
        }

        if ( $_REQUEST['password'] !== $_REQUEST['password2'] ) {
            $response['error'] = __( 'Paroolid ei kattu', 'kafo' );
        }

        reset_password( $user, $_REQUEST['password'] );

        $headers     = [
            'Content-type: text/html',
            'From:' . sprintf( 'Kafo <%s>', get_bloginfo( 'admin_email' ) ),
        ];
        $mailTitle   = get_field( 'account_reset_mail_title', 'options' );
        $mailContent = get_field( 'account_reset_mail_content', 'options' );

        if ( $mailTitle && $mailContent && ! isset( $response['error'] ) ) {
            wp_mail(
                $_REQUEST['user_login'],
                $mailTitle,
                $mailContent,
                $headers
            );
        }

        $response['success'] = ! isset( $response['error'] );
        wp_send_json( $response );
    }

    public function updateNotification() {
        $noteType = $_POST['data']['type'];
        $response = [];
        switch($noteType) {
            case 'freeShipping':
                $freeShippingFrom = Cart::getRequiredForFreeShipping();
                $content = '';
                if($freeShippingFrom) {
                    $content = sprintf(__('Lisa %s eest tooteid ja saa tasuta kohaletoomine', 'kafo'), Helpers::getFormattedPrice($freeShippingFrom));
                } else if($freeShippingFrom === 0) {
                    $content = __('Selle tootega transport tasuta', 'kafo');
                }

                $response['notification'] = $content;
            break;
        }

        wp_send_json($response);
    }

    public static function isAjaxRequest() {
        return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
    }

}
