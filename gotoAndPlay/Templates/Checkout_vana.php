<?php

namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\User;
use gotoAndPlay\Template;
use gotoAndPlay\Plugins\Woocommerce;

class Checkout extends Template {

    protected $view = '@view-checkout';

    private $context;
    private $currency;

    public function __construct() {
        $this->currency = get_woocommerce_currency_symbol();
        $this->context = [
            'action'               => wc_get_checkout_url(),
            'nonce'                => wp_nonce_field('woocommerce-process_checkout', '_wpnonce', true, false),
            'shop'                 => $this->getShopInfo(),
            'shopTitle'            => get_the_title(),
            'orderSummary'         => self::getOrderSummary(),
            'billingFields'        => $this->getFormFields('billing', 'user'),
            'billingValues'        => json_encode($this->getOtherAddresses()),
            'companyBillingFields' => $this->getFormFields('billing', 'business'),
            'displayShipping'      => [
                'display' => WC()->cart->needs_shipping_address(),
                'input'   => [
                    'id'            => 'ship-to-different-address-checkbox',
                    'name'          => 'ship_to_different_address',
                    'value'         => 1,
                    'modifier'      => '',
                    'isChecked'     => checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1, false),
                    'label'         => __('Saada tooted teisele aadressile', 'kafo'),
                    'hiddenContent' => true,
                ],
            ],
            'shippingFields'       => $this->getFormFields('shipping'),
            'displayRegister'      => [
                'display' => !is_user_logged_in() && WC()->checkout()->is_registration_enabled(),
                'input'   => [
                    'id'            => 'createaccount',
                    'name'          => 'createaccount',
                    'value'         => 1,
                    'modifier'      => 'form-check--top-spacing',
                    'isChecked'     => false,
                    'label'         => __('Loo uus kasutaja'),
                    'hiddenContent' => true,
                ],
            ],
            'registerFields'       => $this->getFormFields('account'),
            'orderFields'          => $this->getFormFields('order'),
        ];

        if (isset($_SESSION['lhvPayment']) && $_SESSION['lhvPayment'] == 'failed') {
            $this->context['lhvModal'] = [
                'id'       => 'lhv-modal',
                'modifier' => 'modaal--lhv',
                'title'    => get_field('order_lease_title_failed'),
                'content'  => get_field('order_lease_content_failed')
            ];
            unset($_SESSION['lhvPayment']);
        }

    }

    private function getShopInfo() {
        $shop = [
            'title'     => get_the_title(),
            //'action' => wc_get_checkout_url(),
            //'nonce' => wp_nonce_field('woocommerce-process_checkout', '_wpnonce', true, false),
            'wcNotices' => wc_get_notices(),
            'tip'       => [
                'icon'    => get_field('hint_icon'),
                'content' => is_user_logged_in() && get_field('hint_content_logged') ? get_field('hint_content_logged') : get_field('hint_content'),
            ],
            'back'      => [
                'text' => __('Tagasi', 'kafo'),
                'link' => wc_get_cart_url(),
            ],
        ];

        return $shop;
    }

    public static function getFormFields($fieldType = 'billing', $userType = 'user') {
        $fieldList = [];
        $fields = WC()->checkout->get_checkout_fields($fieldType);

        // current shipping for custom fields
        $simpleShipping = self::getSimpleShipping();

        //globally remove fields
        $remove = [
            'billing_state',
            'billing_address_2'
        ];

        //remove user's business fields
        $userRemove = [
            'billing_company',
            'billing_reg_code',
            'billing_vat_number',
        ];

        // remove unneccesary user fields
        $hideAddrFields = [
            //'billing_country',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            //'billing_state',
            'billing_postcode'
        ];

        // generate fields
        foreach ($fields as $key => $field) {
            $value = WC()->checkout->get_value($key);

            // skip fields
            if (in_array($key, $remove)) {
                continue ;
            }

            if (isset($field['country_field'], $fields[$field['country_field']])) {
                $field['country'] = WC()->checkout->get_value($field['country_field']);
            }


            // custom user fields display
            if($fieldType == 'billing' && in_array($key, $hideAddrFields)) {
                $field['class'][] = 'js-custom-shipping-display h-hidden';
                $field['custom_attributes']['data-shipping-hide'] = htmlspecialchars(json_encode($simpleShipping));
            }

            // custom fields
            switch ($userType) {
                case 'user':

                    if (in_array($key, $userRemove)) {
                        continue 2;
                    }


                    //add address select field
                    if ($fieldType == 'billing' && $key == 'billing_country') {
                        $addrList = self::getOtherAddresses();
                        if ($addrList) {
                            $addressField = [
                                'input'    => [
                                    'id'       => 'user_address',
                                    'name'     => 'user_address',
                                    'modifier' => '',
                                    'class' => 'js-custom-shipping-display h-hidden',
                                    'label'    => __('Kasuta salvestatud aadressi'),
                                    'data' => 'data-shipping-hide="' . htmlspecialchars(json_encode($simpleShipping)) . '"',
                                    'options'  => [],
                                ],
                                'template' => '@select',
                            ];
                            foreach ($addrList as $value => $addr) {
                                $addressField['input']['options'][] = [
                                    'value'      => $value,
                                    'name'       => sprintf('%s %s, %s', $addr['billing_address_1'], $addr['billing_address_2'], $addr['billing_city']),
                                    'isSelected' => $addr['default'],
                                ];
                            }

                            $fieldList[] = $addressField;
                        }
                    }
                    break;

                case 'business':
                    $company = User::getCompanyDetails();
                    if (isset($company[$key])) {
                        $value = $company[$key];
                    }
                    break;
            }

            // remove unused fields
            if ($key == 'shipping_company') {
                continue;
            }
            if ($key == 'billing_address_2') {
                continue;
            }
            if ($key == 'shipping_address_2') {
                continue;
            }

            $fieldContext = Woocommerce::getFieldContenxt($key, $field, $value);
            if (isset($field['type']) && $field['type'] == 'country' && !isset($fieldContext['input']['options'])) {
                $fieldContext['class'] = (isset($fieldContext['class'])) ? $fieldContext['class'] . ' h-hidden' : 'h-hidden';
            }

            // disable business fields by default
            if ($userType == 'business') {
                $fieldContext['input']['name'] .= '-disabled';
            }

            if ($fieldType == 'billing' || $fieldType == 'shipping') {
                if ($key == 'shipping_address_1' || $key == 'billing_address_1') {
                    $fieldContext['input']['placeholder'] = __('Tänav, Korter / Maja', 'kafo');
                }
            }

            // shift user's phone and email positions
            if ($fieldType == 'billing' && ($key == 'billing_email' || $key == 'billing_phone')) {
                array_splice($fieldList, 2, 0, [$fieldContext]);
            } else {
                $fieldList[] = $fieldContext;
            }
        }

        remove_filter('woocommerce_checkout_fields', ['gotoAndPlay\Plugins\Woocommerce', 'editUserFields'], 10);
        remove_filter('woocommerce_checkout_fields', ['gotoAndPlay\Plugins\Woocommerce', 'editBusinessFields'], 10);

        return $fieldList;
    }

    private static function getOtherAddresses() {
        $user = new User();
        if (!$user->isLoggedIn()) {
            return [];
        }

        $address = $user->getAddresses();

        return $address;
    }

    public static function getPaymentmethods($gateways) {
        $cart = WC()->cart;
        if (!$cart->needs_payment() || empty($gateways)) {
            return ['paymentMethods' => false];
        }

        $cartTotal = $cart->subtotal;
        $skipLhv = false;
        if (get_field('lhv_lease_min', get_the_ID()) && $cartTotal < get_field('lhv_lease_min', get_the_ID()))
            $skipLhv = true;
        if (get_field('lhv_lease_max', get_the_ID()) && $cartTotal > get_field('lhv_lease_max', get_the_ID()))
            $skipLhv = true;

        $methods = [];
        foreach ($gateways as $gateway) {
            if ($gateway->id == 'lhv_hire_purchase' && $skipLhv)
                continue;

            $method = [
                'modifier'  => "payment_method_{$gateway->id}",
                'id'        => "payment_method_{$gateway->id}",
                'name'      => 'payment_method',
                'value'     => $gateway->id,
                'isChecked' => checked($gateway->chosen, true, false),
                'label'     => $gateway->get_title() . ' ' . $gateway->get_icon(),
                'data'      => 'data-order_button_text="' . $gateway->order_button_text . '"',
            ];

            $methods[] = $method;
        }

        return ['paymentMethods' => $methods];
    }

    public static function getShippingMethods() {
        $packages = WC()->shipping->get_packages();
        $shipping = [];
        ob_start();
        do_action('woocommerce_review_order_before_shipping');
        do_action('woocommerce_review_order_after_shipping');
        $additionalContent = ob_get_clean();

        $checkoutPageId = get_option('woocommerce_checkout_page_id');

        if ($packages) {
            foreach ($packages as $i => $package) {
                $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
                $product_names = [];

                if (sizeof($packages) > 1 && isset($package['contents']) && $package['contents']) {
                    foreach ($package['contents'] as $item_id => $values) {
                        $product_names[$item_id] = $values['data']->get_name() . ' &times;' . $values['quantity'];
                    }
                }

                if (isset($package['rates']) && $package['rates']) {
                    foreach ($package['rates'] as $method) {
                        $checked = $method->id == $chosen_method;

                        if ($method->method_id == 'local_pickup' && $checked) {
                            $options = get_field('local_pickup_offices', $checkoutPageId);
                            if ($options) {
                                foreach ($options as $key => $option) {
                                    $options[$key]['value'] = $option['name'];
                                }

                                // Add default options value
                                array_unshift($options, ['name' => __('Vali kontor', 'kafo'), 'value' => 0]);

                                $select = Template::compileComponent('@select', [
                                    'name'    => 'shipping_local_pickup_office',
                                    'options' => $options,
                                ]);
                                $additionalContent = $additionalContent . $select;
                            }
                        }

                        $shippingMethod = [
                            'label'     => wc_cart_totals_shipping_method_label($method),
                            'name'      => sprintf('shipping_method[%1$d]', $i),
                            'id'        => sprintf('shipping_method_%1$d_%2$s', $i, sanitize_title($method->id)),
                            'value'     => $method->id,
                            'isChecked' => $checked,
                            'data'      => sprintf('data-index="%1$d"', $i),
                            'content'   => $checked ? $additionalContent : '',
                        ];

                        $shipping[] = $shippingMethod;
                    }
                }
            }
        }

        return $shipping;
    }

    public static function getOrderSummary() {
        WC()->cart->calculate_totals();
        $items = WC()->cart->get_cart();
        $itemCount = WC()->cart->get_cart_contents_count();
        $activeCodes = WC()->cart->get_coupons();
        $termsPage = wc_get_page_id('terms');

        $products = [];
        foreach ($items as $key => $item) {
            $product = apply_filters('woocommerce_cart_item_product', $item['data'], $item, $key);

            $productExists = ($product && $product->exists() && $item['quantity'] > 0 && apply_filters('woocommerce_cart_item_visible', true, $item, $key));
            if (!$productExists) {
                continue;
            }

            $qnt = $item['quantity'];
            $price = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($product), $item, $key);
            $total = apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($product, $item['quantity']), $item, $key);

            if ($qnt > 1) {
                $priceLabel = sprintf(__('%1$s tk / %2$s', 'kafo'), $qnt, $total);
            } else {
                $priceLabel = sprintf(__('%1$s tk x %2$s / %3$s', 'kafo'), $qnt, $price, $total);
            }

            $products[] = [
                'image' => sprintf('%s 85w, %s 170w', Helpers::getImage(Helpers::getImageId($product->get_id()), '85w'), Helpers::getImage(Helpers::getImageId($product->get_id()), '170w')),
                'title' => apply_filters('woocommerce_cart_item_name', $product->get_name(), $item, $key),
                'price' => $priceLabel,
            ];
        }

        // subtotal
        $summary[] = [
            'label' => sprintf(__('Summa kokku (%s %s)', 'kafo'), $itemCount, $itemCount == 1 ? __('toode', 'kafo') : __('toodet', 'kafo')),
            'value' => WC()->cart->get_cart_subtotal(),
        ];

        // transport
        $summary[] = [
            'label' => __('Transport', 'kafo'),
            'value' => WC()->cart->get_cart_shipping_total(),
        ];
        // coupons
        foreach ($activeCodes as $id => $code) {
            $summary[] = [
                'label'  => sprintf(__('Kinkekaart(%s)', 'kafo'), $id),
                'value'  => '-' . $code->get_amount() . ($code->get_discount_type() == 'percent' ? '%' : ' ' . get_woocommerce_currency_symbol()),
                'remove' => [
                    'link' => add_query_arg(['remove_coupon' => $id], wc_get_checkout_url()),
                    'data' => sprintf('data-coupon="%s"', $id),
                ],
                'gift'   => true,
            ];
        }

        // tax
        $summary[] = [
            'label' => __('Käibemaks', 'kafo'),
            'value' => WC()->cart->get_cart_tax(),
        ];
        // total
        $summary[] = [
            'label' => __('Kokku', 'kafo'),
            'value' => WC()->cart->get_total(),
            'sum'   => true,
        ];

        $terms = [];
        if ($termsPage > 0 && apply_filters('woocommerce_checkout_show_terms', true)) {
            $terms = [
                'input' => [
                    'id'        => 'terms',
                    'name'      => 'terms',
                    'label'     => __('Nõustun KAFO e-poe', 'kafo'),
                    'value'     => 1,
                    'validate'  => true,
                    'subLabel'  => [
                        'element' => 'a',
                        'link'    => '#terms-modal',
                        'text'    => __('tingimustega', 'kafo'),
                        'class'   => 'js-modaal',
                    ],
                    'isChecked' => checked(apply_filters('woocommerce_terms_is_checked_default', isset($_POST['terms'])), true, false),
                ],
                'modal' => [
                    'id'       => 'terms-modal',
                    'modifier' => 'modaal--terms',
                    'title'    => get_the_title($termsPage),
                    'content'  => sprintf('<div class="text">%s</div>', Helpers::getFormattedContent(get_post($termsPage)->post_content)),
                ],
            ];
        }

        return [
            'modifier'     => 'order-summary--checkout',
            'isCheckout'   => true,
            'products'     => $products,
            'summaryTable' => $summary,
            'terms'        => $terms,
            'button'       => [
                'isInput' => true,
                'text'    => __('Kinnita tellimus', 'kafo'),
            ],
        ];
    }

    /**
     * Shipping methods with simple billing fields
     *
     * @return array
     */
    public static function getSimpleShipping() {
        return [
            'local_pickup',
            'eabi_omniva_parcelterminal',
            'eabi_itella_smartpost'
        ];
    }

    public function getContextFields() {
        return $this->context;
    }

}
