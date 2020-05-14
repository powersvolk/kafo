<?php

namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Template;

class Cart extends Template {

    protected $view = '@view-cart';

    private $cart;
    private $context;
    private $currency;

    public function __construct() {
        $this->cart = WC()->cart;
        $this->currency = get_woocommerce_currency_symbol();

        $this->context = [
            'action'    => wc_get_cart_url(),
            'nonce'     => wp_nonce_field('woocommerce-cart', '_wpnonce', true, false),
            'shop'      => $this->getShopInfo(),
            'shopTitle' => get_the_title(),
            'cart'      => $this->getCartItems(),
            'summary'   => $this->getCartSummary(),
            'related'   => $this->getRelatedProducts(),
            'cartPage'  => do_action('woocommerce_before_cart'),
        ];

    }

    private function getShopInfo() {
        $shop = [
            'title'     => get_the_title(),
            //'action' => wc_get_cart_url(),
            //'nonce' => wp_nonce_field('woocommerce-cart', '_wpnonce', true, false),
            'wcNotices' => wc_get_notices(),
            'back'      => [
                'text' => __('Jätka shoppamist', 'kafo'),
                'link' => wp_get_referer() && wp_get_referer() != wc_get_checkout_url() ? wp_get_referer() : get_site_url(),
            ],
            'tip'       => [
                'icon'    => get_field('hint_icon'),
                'content' => is_user_logged_in() ? get_field('hint_content_logged') : get_field('hint_content'),
            ],
        ];
        if (wc_coupons_enabled()) {
            $gift = [];
            $gift['field'] = [
                'label' => __('Sisesta kinkekaardi kood', 'kafo'),
                'name'  => 'coupon_code',
                'id'    => 'coupon_code',
            ];
            $activeCodes = $this->cart->get_applied_coupons();
            if ($activeCodes) {
                $gift['hasCard'] = true;
                $gift['code'] = $activeCodes[0];
                $gift['label'] = sprintf(__('Kinkekaart <strong>%s</strong> lisatud', 'kafo'), $activeCodes[0]);
                $gift['field']['class'] = 'is-done';
                $gift['field']['value'] = $activeCodes[0];
            } else {
                $gift['label'] = __('On sul kinkekaart?', 'kafo');
            }

            $shop['giftcard'] = $gift;
        }

        if (get_field('cart_free_shipping')) {
            $shop['headContent'] = '<span class="shop__title-content woocommerce-needed-for-free-shipping">';
            $freeShippingFrom = $this->getRequiredForFreeShipping();
            if ($freeShippingFrom) {
                $shop['headContent'] .= sprintf(__('Lisa ostukorvi veel %s eest tooteid ja transport on %s! %s', 'kafo'),
                    Helpers::getFormattedPrice($freeShippingFrom),
                    '<strong>' . __('tasuta', 'kafo') . '</strong>',
                    '<a href="modal-free-shipping" class="js-modaal" data-custom-class="modaal--products">' . __('Napilt puudu?', 'kafo') . '</a>'
                );
            }
            $shop['headContent'] .= '</span>';
            $products = get_field('cart_free_shipping_products');
            $shop['modal'] = [
                'id' => 'modal-free-shipping',
                'title' => sprintf(__('Napilt puudu %s ostukorvist?'), Helpers::getFormattedPrice($this->getRequiredForFreeShipping(true))),
                'products' => [],
            ];
            foreach($products as $productId) {
                if (get_post_status($productId) == 'publish') {
                    $product = new Product($productId);
                    $shop['modal']['products'][] = $product->getContext(['title', 'image', 'description', 'link', 'price']);
                }
            }
        }

        return $shop;
    }

    private function getCartItems() {
        $products = [];

        $items = $this->cart->get_cart();
        foreach ($items as $key => $item) {
            $productId = apply_filters('woocommerce_cart_item_product_id', $item['product_id'], $item, $key);
            $product = apply_filters('woocommerce_cart_item_product', $item['data'], $item, $key);

            $productExists = ($product && $product->exists() && $item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $item, $key));
            if (!$productExists) {
                continue;
            }

            $productClass = apply_filters('woocommerce_cart_item_class', 'cart_item', $item, $key);
            $productPermalink = apply_filters('woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink($item) : '', $item, $key);
            $productRemove = [
                'link' => $this->cart->get_remove_url($key),
                'data' => sprintf('data-product_id="%s" data-product_sku="%s"', $productId, $product->get_sku()),
            ];
            $productImage = sprintf('%s 85w, %s 170w', Helpers::getImage(Helpers::getImageId($product->get_id()), '85w'), Helpers::getImage(Helpers::getImageId($product->get_id()), '170w'));
            $productTitle = apply_filters('woocommerce_cart_item_name', $product->get_name(), $item, $key);
            $wooProductPrice = $this->cart->get_product_price($product);
            $originalPrice = str_replace(".",",",get_post_meta($product->get_id(), '_regular_price', true));
            $productTotal = apply_filters('woocommerce_cart_item_subtotal', $this->cart->get_product_subtotal($product, $item['quantity']), $item, $key);

			if (floatval($originalPrice) != floatval(strip_tags($wooProductPrice))) {
                $price = $wooProductPrice . '<br /><strike>' . number_format((float)$originalPrice, 2, ',', '') . '</strike> ';
                $total = $this->cart->get_product_subtotal($product, $item['quantity']) . '<br /><strike>' . number_format((float)($item['quantity'] * get_post_meta($product->get_id(), '_regular_price', true)), 2, ',', '') . '</strike> ';
                $productTotal = apply_filters('woocommerce_cart_item_subtotal', $total, $item, $key);
			} else {
				$price = $wooProductPrice;
			}

            $productPrice = apply_filters('woocommerce_cart_item_price', $price, $item, $key);
            $productQnty = $item['quantity'];

            $productItem = [
                'title'        => $productTitle,
                'subtitle'     => __('Eemalda', 'kafo'),
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

        return [
            'hasColumns' => true,
            'items'      => $products,
        ];
    }

    private function getCartSummary() {
        $this->cart->calculate_totals();
        $itemCount = $this->cart->get_cart_contents_count();
        $activeCodes = $this->cart->get_coupons();

        // subtotal
        $summary[] = [
            'label' => sprintf(__('Summa kokku (%s %s)', 'kafo'), $itemCount, $itemCount == 1 ? __('toode', 'kafo') : __('toodet', 'kafo')),
            'value' => $this->cart->get_cart_subtotal(),
        ];
        // transport
        /* excluded from cart
        $summary[] = [
            'label' => __('Transport', 'kafo'),
            'value' => $this->cart->get_cart_shipping_total(),
            'select' => true,
        ];
        */
        // coupons
        foreach ($activeCodes as $id => $code) {
            $summary[] = [
                'label'  => sprintf(__('Kinkekaart(%s)', 'kafo'), $id),
                'value'  => '-' . $code->get_amount() . ($code->get_discount_type() == 'percent' ? '%' : ' ' . get_woocommerce_currency_symbol()),
                'remove' => [
                    'link' => add_query_arg(['remove_coupon' => $id], wc_get_cart_url()),
                    'data' => sprintf('data-coupon="%s"', $id),
                ],
                'gift'   => true,
            ];
        }

        // tax
        $summary[] = [
            'label' => __('Käibemaks', 'kafo'),
            'value' => $this->cart->get_cart_tax(),
        ];
        $summary[] = [
            'label' => __('Transport', 'kafo'),
            'value' => WC()->cart->get_cart_shipping_total(),
        ];
        // total
        $summary[] = [
            'label' => __('Kokku', 'kafo'),
            'value' => $this->cart->get_total(),
            'sum'   => true,
        ];

        $transport = [
            [
                'name'  => 'Omniva kodu ukseni - 10€',
                'value' => 0,
            ],
            [
                'name'  => 'Tulen ise järgi - 0€',
                'value' => 1,
            ],
        ];

        return [
            'modifier'     => '',
            'hasItems'     => !$this->cart->is_empty(),
            'summaryTable' => $this->cart->is_empty() ? [] : $summary,
            //'transportOptions' => $transport,
            'button'       => [
                'link' => $this->cart->get_checkout_url(),
                'text' => __('Checkouti', 'kafo'),
            ],
        ];
    }

    private function getRelatedProducts() {
        if ($this->cart->is_empty || !intval(get_field('cart_related_count'))) {
            return [];
        }

        $relatedProducts = [
            'title'    => get_field('cart_related_title'),
            'products' => [],
        ];
        $related = [];
        $items = $this->cart->get_cart();
        foreach ($items as $key => $product) {
            $productId = $product['product_id'];
            if (have_rows('products_related', $productId)) {
                while (have_rows('products_related', $productId)) {
                    the_row();
                    $related = array_merge($related, get_sub_field('products_list'));
                }
            }
        }

        $related = array_unique($related);
        shuffle($related);
        $relatedList = array_slice($related, 0, intval(get_field('cart_related_count')));

        if ($relatedList) {
            foreach ($relatedList as $related) {
                $productObj = new Product($related);
                $relatedProducts['products'][] = $productObj->getContext(['title', 'description', 'image', 'link', 'price']);
            }
        }

        return $relatedProducts;
    }

    /**
     * Get addiional cart price needed for free shipping
     *
     * @return float|int|mixed
     */
    public static function getRequiredForFreeShipping($getTotal = false) {
        if (!get_field('cart_free_shipping', get_option( 'woocommerce_cart_page_id' ))) {
            return false;
        }

        $required = false;
        $freeFrom = [];
        $cartCost = WC()->cart->subtotal;
        WC()->cart->calculate_shipping();
        $packages = WC()->shipping()->get_packages();
        if ($packages) {
            foreach ($packages as $i => $package) {
                $methods = WC()->shipping()->load_shipping_methods($package);
                foreach ($methods as $method) {
                    $priceData = false;
                    if (method_exists($method, 'getDefinitionsByCountry') && isset($package['destination']) && isset($package['destination']['country'])) {
                        $priceData = $method->getDefinitionsByCountry($method->get_option('price_per_country'), $package['destination']['country']);
                    }

                    if (!empty($method->get_option('enable_free'))) {
                        //old fashioned way
                        if ($method->get_option('enable_free') == 'yes' && $method->get_option('free_from') > 0) {
                            $freeFrom[] = $method->get_option('free_from');
                        }
                    } else if($priceData) {
                        if (isset($priceData['free_from']) && $priceData['free_from'] !== '' && $priceData['free_from'] > 0) {
                            $freeFrom[] = $priceData['free_from'];
                        }
                    }
                }
            }
        }

        if ($freeFrom) {
            $minFree = min($freeFrom);
            if($getTotal) {
                $required = $minFree;
            } else  {
                if($cartCost < $minFree) {
                    $required = $minFree - $cartCost;
                } else {
                    $required = 0;
                }
            }
        }

        return $required;
    }

    public function getContextFields() {
        return $this->context;
    }

}
