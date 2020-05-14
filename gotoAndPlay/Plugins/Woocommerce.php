<?php

namespace gotoAndPlay\Plugins;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\User;
use gotoAndPlay\Template;
use gotoAndPlay\Templates\Cart;
use gotoAndPlay\Templates\Checkout;
use WC_Order;

class Woocommerce {

    public function __construct() {
        // remove grouped products
        add_filter('product_type_selector', function ($groups) {
            if (isset($groups['grouped'])) {
                unset($groups['grouped']);
            }

            if (isset($groups['external'])) {
                unset($groups['external']);
            }

            return $groups;
        });
        // add new company fields
        add_filter('woocommerce_checkout_fields', [$this, 'editBillingFields'], 10);
        // display fields in admin
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'displayBillingFields'], 10, 1);
        // fragments
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'cartFragements']);
        add_filter('woocommerce_update_order_review_fragments', [$this, 'ajaxFragments']);
        // add order filters
        add_action('woocommerce_default_address_fields', [$this, 'optionalFields']);
        add_action('woocommerce_checkout_fields', [$this, 'optionalFields']);
        add_action('woocommerce_default_address_fields', [$this, 'optionalShippingFields']);
        add_action('woocommerce_checkout_fields', [$this, 'optionalShippingFields']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'add_order_meta']);
        add_filter('woocommerce_order_shipping_to_display', function ($shipping, WC_Order $order) {
            $office = get_post_meta($order->get_id(), 'shipping_local_pickup_office', true);
            if ($office) {
                $shipping .= ' (' . $office . ')';
            }

            return $shipping;
        }, 10, 2);
        // send product review notification
        add_action('comment_post', [$this, 'reviewResponse'], 10, 3);

        // add LHV hire-pay status to session
        add_action('lhv/hire-purchase/order-confirmed', [$this, 'lhvPaymentSuccessful']);
        add_action('lhv/hire-purchase/order-pending', [$this, 'lhvPaymentSuccessful']);
        add_action('lhv/hire-purchase/order-rejected-bank', [$this, 'lhvPaymentFailed']);
        // add LHV status change action
        add_filter('woocommerce_email_actions', [$this, 'lhvPaymentStatusChange']);
        // add custom email template class
        add_filter('woocommerce_email_classes', [$this, 'lhvPaymentEmailClasses']);

        if (is_admin()) {
            add_action('woocommerce_admin_order_data_after_shipping_address', function () {
                $office = get_post_meta(get_the_ID(), 'shipping_local_pickup_office', true);
                if ($office) {
                    printf('<br>Valitud kontor: %s', $office);
                }
            });
        }

        add_action('woocommerce_checkout_process', [$this, 'addLocalPickupValidationRule']);

        add_filter('woocommerce_payment_complete_order_status', [$this, 'autoCompletePaidEstoOrder'], 10, 2);
    }

    public function addLocalPickupValidationRule() {
        if (!isset(WC()->cart)) {
            return;
        }

        if (isset($_POST['shipping_local_pickup_office']) && !$_POST['shipping_local_pickup_office']) {
            wc_add_notice(__('Palun vali, kuidas soovid pakki kätte saada', 'kafo'), 'error');
        }
    }

    public function add_order_meta($order_id) {
        if (isset($_POST['shipping_local_pickup_office'])) {
            update_post_meta($order_id, 'shipping_local_pickup_office', $_POST['shipping_local_pickup_office']);
        }
    }

    public function cartFragements($fragements) {
        $freeShippingFrom = Cart::getRequiredForFreeShipping();
        $fragement = '<span class="shop__title-content woocommerce-needed-for-free-shipping">';
        if ($freeShippingFrom) {
            // main cart notification
            $fragement .= sprintf(__('Lisa ostukorvi veel %s eest tooteid ja transport on %s! %s', 'kafo'),
                Helpers::getFormattedPrice($freeShippingFrom),
                '<strong>' . __('tasuta', 'kafo') . '</strong>',
                '<a href="#modal-free-shipping" class="js-modaal" data-custom-class="modaal--products">' . __('Napilt puudu?', 'kafo') . '</a>'
            );
        }
        $fragement .= '</span>';
        $fragements['.woocommerce-needed-for-free-shipping'] = $fragement;

        return $fragements;
    }

    public function ajaxFragments($fragments) {
        $fragments['.woocommerce-checkout-shipping'] = Template::compileComponent('@view-checkout--shipping', ['transport' => Checkout::getShippingMethods()]);

        return $fragments;
    }

    public function editBillingFields($fields) {
        $key = 'billing_country';
        $offset = array_search($key, array_keys($fields['billing']));

        /*
        $vatCode           = [
            'type'        => 'text',
            'label'       => __('Käibemaksu number', 'kafo'),
            'placeholder' => '',
            'class'       => ['billing_vat_number_field'],
            'required'    => false,
            'clear'       => true,
            'label_class' => [],
        ];
        $fields['billing'] = array_merge(
            array_slice($fields['billing'], 0, $offset),
            ['billing_vat_number' => $vatCode],
            array_slice($fields['billing'], $offset, null)
        );
        */

        $regCode = [
            'type'        => 'text',
            'label'       => __('Registrikood', 'kafo'),
            'placeholder' => '',
            'class'       => ['billing_reg_code_field'],
            'required'    => false, // BF-924 - make required in checkout.php (conflicts with "eraklient / account" requirement checks)
            'clear'       => true,
            'label_class' => [],
        ];
        $fields['billing'] = array_merge(
            array_slice($fields['billing'], 0, $offset),
            ['billing_reg_code' => $regCode],
            array_slice($fields['billing'], $offset, null)
        );

        $companyName = [
            'type'        => 'text',
            'label'       => __('Ettevõtte nimi', 'kafo'),
            'placeholder' => '',
            'class'       => ['billing_company_field'],
            'required'    => false, // BF-924 - make required in checkout.php (conflicts with "eraklient / account" requirement checks)
            'clear'       => true,
            'label_class' => [],
        ];
        $fields['billing'] = array_merge(
            array_slice($fields['billing'], 0, $offset),
            ['billing_company' => $companyName],
            array_slice($fields['billing'], $offset, null)
        );

        return $fields;
    }

    public function displayBillingFields($order) {
        if (get_post_meta($order->get_id(), '_billing_vat_number', true)) {
            echo '<p><strong>' . __('Käibemaksu number', 'kafo') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_vat_number', true) . '</p>';
        }

        if (get_post_meta($order->get_id(), '_billing_reg_code', true)) {
            echo '<p><strong>' . __('Registrikood', 'kafo') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_reg_code', true) . '</p>';
        }
    }

    public function optionalFields($fields) {
        $optionalFields = [
            // 'address_1',
            'phone',
            // 'city',
            // 'postcode'
        ];

        foreach($optionalFields as $fieldKey) {
            if(isset($fields['billing'])) {
                if (isset($fields['billing']['billing_' . $fieldKey])) {
                    $fields['billing']['billing_' . $fieldKey]['required'] = false;
                }
            } else if(isset($fields['billing'][$fieldKey])) {
                $fields[$fieldKey]['required'] = false;
            }
        }

        return $fields;
    }

    public function optionalShippingFields($fields) {
        $simpleShipping = Checkout::getSimpleShipping();
        $hideAddrFields = [
            //'country',
            'address_1',
            'address_2',
            'city',
            'state',
            'postcode'
        ];

        WC()->cart->calculate_shipping();
        $packages = WC()->shipping->get_packages();
        $shipping = false;
        if ($packages) {
            foreach ($packages as $i => $package) {
                $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';

                if (isset($package['rates']) && $package['rates']) {
                    foreach ($package['rates'] as $method) {
                        if ($method->id == $chosen_method) {
                            $shipping = $method;
                        }
                    }
                }
            }
        }
        if ($shipping && in_array($shipping->method_id, $simpleShipping)) {
            foreach($hideAddrFields as $fieldKey) {
                if(isset($fields['billing'])) {
                    if (isset($fields['billing']['billing_' . $fieldKey])) {
                        $fields['billing']['billing_' . $fieldKey]['required'] = false;
                    }
                } else if(isset($fields['billing'][$fieldKey])) {
                    $fields[$fieldKey]['required'] = false;
                }
            }
        }

        return $fields;
    }

    public static function getFieldContenxt($key, $args, $value = null) {
        $defaults = [
            'type'              => 'text',
            'label'             => '',
            'description'       => '',
            'placeholder'       => '',
            'maxlength'         => false,
            'required'          => false,
            'autocomplete'      => false,
            'id'                => $key,
            'class'             => [],
            'label_class'       => [],
            'input_class'       => [],
            'return'            => false,
            'options'           => [],
            'custom_attributes' => [],
            'validate'          => [],
            'default'           => '',
            'autofocus'         => '',
            'priority'          => '',
        ];

        $args = wp_parse_args($args, $defaults);
        $args = apply_filters('woocommerce_form_field_args', $args, $key, $value);

        if ($args['required']) {
            $args['class'][] = 'validate-required';
        }

        if (is_string($args['label_class'])) {
            $args['label_class'] = [$args['label_class']];
        }

        if (is_null($value)) {
            $value = $args['default'];
        }

        // Custom attribute handling
        $custom_attributes = [];
        $args['custom_attributes'] = array_filter((array) $args['custom_attributes']);

        if (!empty($args['autocomplete'])) {
            $args['custom_attributes']['autocomplete'] = $args['autocomplete'];
        }

        if (true === $args['autofocus']) {
            $args['custom_attributes']['autofocus'] = 'autofocus';
        }

        if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
            foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }

        if (!empty($args['validate'])) {
            foreach ($args['validate'] as $validate) {
                $args['class'][] = 'validate-' . $validate;
            }
        }

        switch ($args['id']) {
            case 'shipping_address_2':
            case 'billing_address_2':
                $args['label'] = __('Korter / Maja', 'kafo');
                break;
        }

        $fieldContext = [
            'input' => [
                'id'       => $args['id'],
                'name'     => $key,
                'modifier' => '',
                'validate' => $args['required'],
            ],
        ];

        switch ($args['type']) {
            case 'country':
                $countries = 'shipping_country' === $key ? WC()->countries->get_shipping_countries() : WC()->countries->get_allowed_countries();
                if (1 === sizeof($countries)) {
                    $rawField = '<strong>' . current(array_values($countries)) . '</strong>';
                    $rawField .= '<input type="hidden" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" value="' . current(array_keys($countries)) . '" ' . implode(' ', $custom_attributes) . ' class="country_to_state" />';

                    $fieldContext['template'] = false;
                    $fieldContext['raw'] = $rawField;
                } else {
                    $options = [];
                    foreach ($countries as $ckey => $cvalue) {
                        $options[] = [
                            'value'      => $ckey,
                            'name'       => $cvalue,
                            'isSelected' => selected($value, $ckey, false),
                        ];
                    }

                    $fieldContext['input']['data'] = implode(' ', $custom_attributes);
                    $fieldContext['input']['options'] = $options;
                    $fieldContext['template'] = '@select';
                    $fieldContext['raw'] = '<noscript><input type="submit" name="woocommerce_checkout_update_totals" value="' . esc_attr__('Update country', 'woocommerce') . '" /></noscript>';
                }
                break;

            case 'textarea':
                $fieldContext['input']['data'] = implode(' ', $custom_attributes);
                $fieldContext['input']['value'] = esc_textarea($value);
                $fieldContext['template'] = '@textarea';
                break;

            case 'checkbox':
                $fieldContext['input']['data'] = implode(' ', $custom_attributes);
                $fieldContext['input']['value'] = 1;
                $fieldContext['input']['isChecked'] = checked($value, 1, false);
                $fieldContext['template'] = '@check';
                break;

            case 'state':
            case 'password':
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
                $fieldContext['input']['value'] = $value;
                $fieldContext['input']['data'] = implode(' ', $custom_attributes);
                $fieldContext['input']['type'] = $args['type'];
                $fieldContext['template'] = '@textfield';
                break;

            case 'select':
                $options = [];
                if (!empty($args['options'])) {
                    foreach ($args['options'] as $option_key => $option_text) {
                        if ('' === $option_key) {
                            if (empty($args['placeholder'])) {
                                $args['placeholder'] = $option_text ? $option_text : __('Choose an option', 'woocommerce');
                            }

                            $custom_attributes[] = 'data-allow_clear="true"';
                        }

                        $options[] = [
                            'value'      => $option_key,
                            'name'       => $option_text,
                            'isSelected' => selected($value, $option_key, false),
                        ];
                    }

                    $fieldContext['input']['options'] = $options;
                    $fieldContext['input']['data'] = implode(' ', $custom_attributes) . ' data-placeholder="' . $args['placeholder'] . '"';
                    $fieldContext['template'] = '@select';
                }
                break;

            case 'radio':
                $fieldContext['input'] = [];
                if (!empty($args['options'])) {
                    foreach ($args['options'] as $option_key => $option_text) {
                        $checkField = [
                            'id'        => $args['id'] . '_' . $option_key,
                            'name'      => $key,
                            'value'     => $option_key,
                            'isChecked' => checked($value, $option_key, false),
                            'label'     => $option_text,
                        ];
                        $fieldContext['input'][] = $checkField;
                    }

                    $fieldContext['template'] = '@radio';
                }
                break;
        }

        if (!empty($fieldContext['template'])) {
            if ($args['label'] && 'radio' != $args['type']) {
                $fieldContext['input']['label'] = $args['label'];
            }

            $fieldContext['class'] = implode(' ', $args['class']);
            $fieldContext['id'] = $args['id'] . '_field';
        }

        return $fieldContext;
    }

    public function reviewResponse($id, $approved, $comment) {
        if (!$approved || empty($comment['comment_author_email']))
            return false;

        $headers = [
            'Content-type: text/html',
            'From:' . sprintf('Kafo <%s>', get_bloginfo('admin_email')),
        ];
        $mailTitle = get_field('rating_mail_title', 'options');
        $mailContent = get_field('rating_mail_content', 'options');
        if ($mailTitle && $mailContent) {
            wp_mail(
                $comment['comment_author_email'],
                $mailTitle,
                $mailContent,
                $headers
            );
        }
    }

    public function lhvPaymentSuccessful($order) {
        session_start();
        $_SESSION['lhvPayment'] = 'success';
    }

    public function lhvPaymentFailed($order) {
        session_start();
        $_SESSION['lhvPayment'] = 'failed';
    }

    public function lhvPaymentStatusChange($actions) {
        $actions[] = 'woocommerce_order_status_pending_to_lhv-pending';
        return $actions;
    }

    public function lhvPaymentEmailClasses($emails) {
        $emails['LHV_Email_New_Order'] = include('woocommerce_emails/class-lhv-email-new-order.php');
        $emails['LHV_Email_Customer_Pending_Order'] = include('woocommerce_emails/class-lhv-email-customer-processing-order.php');
        return $emails;
    }

    public function autoCompletePaidEstoOrder($orderStatus, $orderId) {
        if (!$orderId) {
            return;
        }

        // Get an instance of the WC_Order object
        $order = wc_get_order($orderId);

        // Updated status for orders delivered with Esto payment methods.
        if ($order->payment_method === 'esto' && $orderStatus == 'processing' && ($order->status == 'on-hold' || $order->status == 'pending' || $order->status == 'failed')) {
            return 'completed';
        }

        return $orderStatus;
    }
}
