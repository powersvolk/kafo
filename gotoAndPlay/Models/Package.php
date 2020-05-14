<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use WP_Query;
use WP_Post;
use RGFormsModel;

class Package {

    private $ID;
    private $title;
    private $content;

    public function __construct($idOrPostData = false) {
        if ($idOrPostData) {
            if ($idOrPostData instanceof WP_Post) {
                global $post;
                $post = $idOrPostData;
                setup_postdata($post);
            } else {
                $args  = [
                    'post_status' => 'publish',
                    'post_type' => 'post',
                    'p' => $idOrPostData,
                    'posts_per_page' => 1,
                ];
                $query = new WP_Query($args);
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                    }
                }
            }
        }

        $this->setup();
        wp_reset_query();
    }

    private function setup() {
        $this->ID      = get_the_ID();
        $this->title   = get_the_title();
        $this->content = Helpers::getFormattedContent(get_the_content());
    }

    public function getId() {
        return $this->ID;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getContent() {
        return $this->content;
    }

    public function getImage() {
        return Helpers::getImageSrcSet(get_post_thumbnail_id($this->ID), '374x374');
    }

    public function getItems() {
        $items = get_field('package_contents', $this->getId());
        foreach ($items as $key => $item) {
            $item['image'] = Helpers::getImageSrcSet($item['image_id'], '85w');
            if (!$item['amount'] && !$item['price']) {
                $item['extra'] = true;
            }

            $items[$key] = $item;
        }
		
        return $items;
    }




    public function getHeroData($nr) {
		
		return [
            'nr' => $nr,
            'title' => $this->getTitle(),
            'description' => get_field('package_description', $this->getId()),
            'image' => [
                'srcset' => $this->getImage(),
                'alt' => $this->getTitle(),
            ],
            'monthlyPricing' => get_field('package_price_info2', $this->getId()),
            'cuplyPricing' => get_field('package_price_info', $this->getId()),
            'buyNow' => get_field('package_price_info3', $this->getId()),
            'button' => __('Telli pakett', 'kafo'),
            'details' => __('Näita detaile', 'kafo'),
            'detailsModaal' => [
                'modifier' => '',
                'id' => 'package-' . $this->getId(),
                'content' => '',
                'detailsContent' => get_field('package_description', $this->getId()),
                'contents' => [
                    'title' => __('Pakett sisaldab', 'kafo'),
                    'labels' => [
                        'name' => __('Toode', 'kafo'),
                        'amount' => __('Kogus', 'kafo'),
                        'price' => __('Hind üksikuna välja ostes', 'kafo'),
                    ],
                    'list' => get_field('package_content', $this->getId()),
                ],
                'additions' => [
                    'title' => get_field('additional_title', $this->getId()),
                    'content' => get_field('additional_content', $this->getId()),
                ],
                'terms' => [
                    'title' => get_field('terms_title', $this->getId()),
                    'content' => get_field('terms_content', $this->getId()),
                ],
            ],
            'orderModal' => [
                'modifier' => 'modaal--small',
                'content' => get_field('package_form_description', $this->getId()),
                'id' => 'package-form-' . $this->getId(),
				'formid' => get_field('package_form_id', $this->getId()),
                'gravityForm' => (get_field('package_form_id', $this->getId()) ? do_shortcode('[gravityform id="' . get_field('package_form_id', $this->getId()) . '" title="false" description="false" ajax="true"]') : ''),
            ],
        ];
    }

    public function getProductData() {
        return [
            'image' => [
                'srcset' => $this->getImage(),
                'alt' => $this->getTitle(),
            ],
            'title' => $this->getTitle(),
            'description' => get_field('package_description', $this->getId()),
            'priceInfo' => get_field('package_price_info', $this->getId()),
            'orderButton' => [
                'text' => __('Telli pakett', 'kafo'),
                'link' => 'package-form-' . $this->getId(),
            ],
            'detailsButton' => [
                'text' => __('Vaata detaile', 'kafo'),
                'link' => 'package-' . $this->getId(),
            ],
            'detailsModal' => [
                'title' => get_field('package_title', $this->getId()),
                'description' => get_field('package_description', $this->getId()),
                'image' => [
                    'srcset' => $this->getImage(),
                    'alt' => $this->getTitle(),
                ],
                'monthlyPricing' => get_field('package_price_info2', $this->getId()),
                'cuplyPricing' => get_field('package_price_info', $this->getId()),
                'buyNow' => get_field('package_price_info3', $this->getId()),
                'button' => __('Telli pakett', 'kafo'),
                'details' => __('Näita detaile', 'kafo'),
                'modifier' => '',
                'id' => 'package-' . $this->getId(),
                'detailsContent' => $this->getContent(),
                'contents' => [
                    'title' => __('Pakett sisaldab', 'kafo'),
                    'labels' => [
                        'name' => __('Toode', 'kafo'),
                        'amount' => __('Kogus', 'kafo'),
                        'price' => __('Hind üksikuna välja ostes', 'kafo'),
                    ],
                    'list' => $this->getItems(),
                ],
                'additions' => [
                    'title' => get_field('additional_title', $this->getId()),
                    'content' => get_field('additional_content', $this->getId()),
                ],
                'terms' => [
                    'title' => get_field('terms_title', $this->getId()),
                    'content' => get_field('terms_content', $this->getId()),
                ],
            ],
            'orderModal' => [
                'modifier' => 'modaal--small',
                'content' => get_field('package_form_description', $this->getId()),
                'id' => 'package-form-' . $this->getId(),
                'formid' => get_field('package_form_id', $this->getId()),
				'gravityForm' => (get_field('package_form_id', $this->getId()) ? do_shortcode('[gravityform id="' . get_field('package_form_id', $this->getId()) . '" title="false" description="false" ajax="true"]') : ''),
            ],
        ];
    }

    public static function getPackagesByIds($ids) {
        $packages = [];
        if ($ids) {
            $args  = [
                'post_status' => 'publish',
                'post_type' => 'package',
                'orderby' => 'post__in',
                'post__in' => $ids,
                'posts_per_page' => -1,
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $packages[] = new Package();
                }
            }

            wp_reset_query();
        }

        return $packages;
    }

}
