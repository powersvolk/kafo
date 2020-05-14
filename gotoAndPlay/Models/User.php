<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use gotoAndPlay\Utils\MailChimp\MailChimp;
use WP_Query;

class User {

    private static $ID;
    private static $data;

    public static function __init__() {
        self::$data = wp_get_current_user();
        if (self::$data) {
            self::$ID = self::$data->ID;
        } else {
            self::$ID = false;
        }
    }

    public static function isLoggedIn() {
        return is_user_logged_in();
    }

    public static function getId() {
        return self::$ID;
    }

    public static function getFullName() {
        return self::getData('billing_first_name') . ' ' . self::getData('billing_last_name');
    }

    public static function getFirstName() {
        return get_user_meta(self::getId(), 'billing_first_name', true);
    }

    public static function getLastName() {
        return get_user_meta(self::getId(), 'billing_last_name', true);
    }

    public static function getEmail() {
        $email = get_user_meta(self::getId(), 'billing_email', true);
        if (!$email) {
            $email = self::getData('user_email');
        }

        return $email;
    }

    public static function getPhone() {
        return get_user_meta(self::getId(), 'billing_phone', true);
    }

    public static function getCompanyDetails() {
        $company = get_user_meta(self::getId(), 'wc_company_details', true);
        if (!$company) {
            $company = [];
        }

        return $company;
    }

    public static function updateCompanyDetails($company) {
        update_user_meta(self::getId(), 'wc_company_details', $company);
    }

    public static function getAddresses() {
        $addresses = get_user_meta(self::getId(), 'wc_multiple_shipping_addresses', true);
        if (!$addresses) {
            $addresses = [];
        }

        return $addresses;
    }

    public static function getOrders($type = false) {
        $orders = [];
        $args   = [
            'posts_per_page' => -1,
            'post_type' => 'shop_order',
            'post_status' => 'any',
            'meta_key' => '_customer_user',
            'meta_value' => self::getId(),
        ];
        $query  = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $order    = wc_get_order(get_the_ID());
                $products = [];
                $title    = [];
                foreach ($order->get_items() as $item) {
                    $products[] = [
                        'image' => Helpers::getImageSrcSet(Helpers::getImageId($item->get_product_id()), '85w'),
                        'title' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'itemPrice' => Helpers::getFormattedPrice($item->get_total() / $item->get_quantity()),
                        'sumPrice' => Helpers::getFormattedPrice($item->get_total()),
                    ];
                    $title[]    = sprintf('%s x%s', $item->get_name(), $item->get_quantity());
                }

                $context = [
                    'date' => $order->get_date_created()->format('d.m'),
                    'year' => $order->get_date_created()->format('Y'),
                    'number' => 'KFO-' . $order->get_id(),
                    'title' => implode('<br>', $title),
                    'sum' => Helpers::getFormattedPrice($order->get_total()),
                    'details' => [
                        'title' => __('Tellimuse detailid', 'kafo'),
                        'orderNumber' => 'KFO-' . $order->get_id(),
                        'orderDate' => $order->get_date_created()->format(get_option('date_format')),
                        'strings' => [
                            'nameLabel' => __('Toode', 'kafo'),
                            'amountLabel' => __('Kogus', 'kafo'),
                            'priceLabel' => __('Tükihind', 'kafo'),
                            'totalLabel' => __('Kokku', 'kafo'),
                            'piece' => __('1 tk', 'kafo'),
                            'orderNo' => __('Tellimuse nr.', 'kafo'),
                            'dateLabel' => __('Tellitud', 'kafo'),
                            'toBePaid' => __('Makse kokkuvõte', 'kafo'),
                            'subTotal' => __('Tooted kokku', 'kafo'),
                            'transportLabel' => __('Transport', 'kafo'),
                            'vatLabel' => __('Käibemaks', 'kafo'),
                            'summaryTotal' => __('Tellimus kokku', 'kafo'),
                        ],
                        'reOrder' => [
                            'text' => __('Telli uuesti', 'kafo'),
                            'data' => sprintf('data-order="%s"', $order->get_id()),
                            'link' => '#',
                        ],
                        'downloadPdf' => [
                            'text' => __('Lae arve PDF', 'kafo'),
                            'link' => wp_nonce_url(admin_url('admin-ajax.php?action=generate_wpo_wcpdf&template_type=invoice&order_ids=' . $order->get_id() . '&my-account'), 'generate_wpo_wcpdf'),
                        ],
                        'orderInfo' => [
                            [
                                'title' => __('Tellija', 'kafo'),
                                'content' => $order->get_formatted_billing_address(),
                            ],
                            [
                                'title' => __('Transpordi info', 'kafo'),
                                'content' => $order->get_shipping_to_display(), // . '<br><br>' . $order->get_formatted_shipping_address(),
                            ],
                            [
                                'title' => __('Maksemeetod', 'kafo'),
                                'content' => $order->get_payment_method_title(),
                            ],
                        ],
                        'orderProducts' => $products,
                        'summary' => [
                            'subTotal' => Helpers::getFormattedPrice($order->get_subtotal()),
                            'transport' => Helpers::getFormattedPrice($order->get_shipping_total()),
                            'VAT' => Helpers::getFormattedPrice($order->get_total_tax()),
                            'total' => Helpers::getFormattedPrice($order->get_total()),
                        ],
                    ],
                ];

                switch ($type) {
                    case 'pending':
                        if ($order->get_status() == 'pending' || $order->get_status() == 'on-hold') {
                            $orders[] = $context;
                        }
                    break;

                    case 'complete':
                        if ($order->get_status() == 'completed' || $order->get_status() == 'processing') {
                            $orders[] = $context;
                        }
                    break;

                    default:
                        $orders[] = $context;
                    break;
                }
            }
        }

        wp_reset_query();

        return $orders;
    }

    public static function subscribedToBlog() {
        return get_user_meta(self::getId(), 'list_news', true);
    }

    public static function subscribedToOffers() {
        return get_user_meta(self::getId(), 'list_special_offers', true);
    }

    public static function update($data) {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'billing_address_1':
                case 'billing_address_2':
                case 'billing_city':
                case 'billing_postcode':
                case 'billing_state':
                case 'billing_country':

                case 'billing_first_name':
                case 'billing_last_name':
                case 'billing_email':
                case 'billing_phone':
                    update_user_meta(self::getId(), $key, $value);
                break;

                case 'list_special_offers':
                case 'list_news':
                    update_user_meta(self::getId(), $key, $value);
                    if (get_field('mailchimp_api_key', 'option')) {
                        switch ($key) {
                            case 'list_special_offers':
                                $mailchimpListId = get_field('mailchimp_special_list_id', 'option');
                            break;

                            default:
                            case 'list_news':
                                $mailchimpListId = get_field('mailchimp_list_id', 'option');
                            break;
                        }

                        $mailchimp = new MailChimp(get_field('mailchimp_api_key', 'option'));
                        $mailchimp->post("lists/$mailchimpListId/members", [
                            'email_address' => self::getEmail(),
                            'status' => ($value ? 'subscribed' : 'unsubscribed'),
                        ]);
                    }
                break;

                case 'user_password':
                    wp_set_password($value, self::getId()); // TODO: maybe check old password?
                break;
            }
        }
    }

    public static function updateAddress($address) {
        $addresses = self::getAddresses();
        if ($address['default']) {
            self::update($address);
            foreach ($addresses as $key => $data) {
                $addresses[$key]['default'] = false;
            }
        }

        if (isset($address['index']) && isset($addresses[$address['index']])) {
            $addresses[$address['index']] = $address;
        } else {
            $addresses[] = $address;
        }

        update_user_meta(self::getId(), 'wc_multiple_shipping_addresses', $addresses);
    }

    private static function getData($key) {
        if (isset(self::$data->{$key})) {
            return self::$data->{$key};
        }

        return false;
    }

}
