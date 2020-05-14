<?php
/**
 * Created by PhpStorm.
 * User: GAP developer jr
 * Date: 20/08/2017
 * Time: 17:37
 */

namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Template;
use WC_Order;

class ConfirmationPage extends Template {

    protected $view = '@view-confirmation-page';

    private $context;
    private $order;
    private $orderStatus;

    public function __construct($order_id = 0) {
        $order     = false;
        $order_id  = apply_filters('woocommerce_thankyou_order_id', absint($order_id));
        $order_key = apply_filters('woocommerce_thankyou_order_key', empty($_GET['key']) ? '' : wc_clean($_GET['key']));

        if ($order_id > 0) {
            $order = wc_get_order($order_id);
            if (!$order || $order->get_order_key() !== $order_key) {
                $order = false;
            }
        }

        unset(WC()->session->order_awaiting_payment);
        wc_empty_cart();

        $this->order       = new WC_Order($order);
        $this->orderStatus = $this->order->get_status();
        $gateway = wc_get_payment_gateway_by_order($order);

        $this->context = [
            'shop' => $this->getShopInfo(),
            'shopTitle' => get_field('order_title_' . $this->orderStatus),
            'subtitle' => get_field('order_content_' . $this->orderStatus),
            'cover' => [],
            'sidebar' => [
                'video' => [
                    'image' => [
                        'srcset' => Helpers::getImageSrcSet(get_field('order_video_thumb'), '570w2x'),
                        'alt' => get_the_title(get_field('order_video_thumb')),
                    ],
                    'video' => get_field('order_video_url'),
                ],
                'label' => get_field('order_video_desc'),
                'email' => get_field('order_email'),
                'phone' => get_field('order_phone'),
            ],
            'related' => $this->getRelatedProducts(),
        ];

        if (is_object($gateway) && $gateway->id == 'lhv_hire_purchase') {
            $this->context['shopTitle'] = get_field('order_title_lease');
            $this->context['subtitle'] = get_field('order_content_lease');
        }

        if ($this->orderStatus == 'cancelled') {
            $this->context['cover']['image'] = Helpers::getImageSrcSet(get_field('order_cover'), '1524x2156');
        } else {
            if(is_user_logged_in()) {
                $this->context['cover']['pdf'] = wp_nonce_url( admin_url( 'admin-ajax.php?action=generate_wpo_wcpdf&template_type=invoice&order_ids=' . $this->order->get_id() . '&my-account' ), 'generate_wpo_wcpdf' );
            } else {
                //pending
                $orderType = $this->orderStatus != 'on-hold' ? 'processing' : $this->orderStatus;
                $this->context['cover']['content'] = [
                    'title' => get_field('order_notice_title_' . $orderType),
                    'list' => [],
                    'image' => sprintf('%s 1x, %s 2x', Helpers::getImage(get_field('order_notice_cover_' . $orderType), '762x1078'), Helpers::getImage(get_field('order_notice_cover_' . $orderType), '1524x2156'))
                ];
                $list = [];
                if(have_rows('order_notice_content_' . $orderType)) {
                    while(have_rows('order_notice_content_' . $orderType)) {
                        the_row();
                        $list[] = [
                            'label' => get_sub_field('label'),
                            'text' => get_sub_field('text')
                        ];
                    }
                }

                switch($this->orderStatus) {
                    case 'on-hold':
                        $list[] = [
                            'label' => __('Makseselgitus', 'kafo'),
                            'text' => $this->order->get_order_number(),
                        ];
                        $list[] = [
                            'label' => __('Kokku tasuda', 'kafo'),
                            'text' => $this->order->get_total(),
                        ];
                        break;
                    default:
                        $list[] = [
                            'label' => __('Makseviis', 'kafo'),
                            'text' => ( (is_object($gateway)) ? $gateway->get_title() : __('Free', 'kafo') ),
                        ];
                        $list[] = [
                            'label' => __('Tellimus', 'kafo'),
                            'text' => $this->order->get_order_number(),
                        ];
                        $list[] = [
                            'label' => __('Kokku', 'kafo'),
                            'text' => $this->order->get_formatted_order_total(),
                        ];
                        break;
                }
                $this->context['cover']['content']['list'] = $list;
            }
        }

        if(isset($_SESSION['lhvPayment']) && $_SESSION['lhvPayment'] == 'success') {
            $this->context['lhvModal'] = [
                'id' => 'lhv-modal',
                'modifier' => 'modaal--lhv modaal--centered modaal--small modaal--iconed',
                'headerIcon' => 'ic_24_checkmark',
                'title' => get_field('order_lease_title_success'),
                'content' => sprintf('<div class="text text-centered">%s</div>', get_field('order_lease_content_success')),
            ];
            unset($_SESSION['lhvPayment']);
        }
    }

    private function getShopInfo() {
        $shop = [
            'title' => get_field('order_title_' . $this->orderStatus),
            'headContent' => get_field('order_content_' . $this->orderStatus),
            'tip' => [
                'icon' => get_field('chk_hint_icon'),
                'content' => is_user_logged_in() && get_field('chk_hint_content_logged') ? get_field('chk_hint_content_logged') : get_field('chk_hint_content'),
            ],
        ];
        if ($this->orderStatus == 'cancelled') {
            $shop['backButton'] = [
                'text' => __('Tagsi E-poodi', 'kafo'),
                'link' => get_site_url(),
            ];
        }

        return $shop;
    }

    private function getRelatedProducts() {
        if (!get_field('order_related')) {
            return false;
        }

        $related = [
            'title' => get_field('order_related_title'),
            'button' => [
                'text' => get_field('order_related_label'),
                'link' => get_field('order_related_link'),
            ],
            'grid' => ['products' => []],
        ];

        foreach (get_field('order_related') as $product) {
            $status = get_post_status($product);
            if ($status == 'publish') {
                $product                       = new Product($product);
                $related['grid']['products'][] = $product->getContext([
                    'title',
                    'description',
                    'image',
                    'link',
                    'price',
                ]);
            }
        }

        return $related;
    }

    public function getContextFields() {
        return $this->context;
    }

}
