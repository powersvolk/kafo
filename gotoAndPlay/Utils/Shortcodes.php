<?php
namespace gotoAndPlay\Utils;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template;

class Shortcodes {

    public function __construct() {
        $shortcodes = [
            'currentyear' => 'currentYear',
            'icon' => 'icon',
            'video' => 'video',
            'gallery' => 'gallery',
            'reviews' => 'reviews'
        ];

        foreach ($shortcodes as $shortcode => $callableFunction) {
            add_shortcode($shortcode, [$this, $callableFunction]);
        }
    }

    public function icon($atts) {
        return Template::compileComponent('@icon', shortcode_atts([
            'modifier' => 'check',
        ], $atts));
    }

    public function currentyear() {
        return date('Y');
    }

    public function gallery($atts) {
        $atts   = shortcode_atts([
            'ids' => '',
        ], $atts);
        $ids    = explode(',', $atts['ids']);
        $slides = [];
        if ($ids) {
            foreach ($ids as $id) {
                $slides[] = [
                    'image' => Helpers::getImage($id, '2560x1440'),
                    'thumbnail' => Helpers::getImage($id, '68w'),
                ];
            }
        }

        return Template::compileComponent('@slider', [
            'modifier' => 'slider--in-content',
            'thumbnailNavigation' => true,
            'slides' => $slides,
            'sliderMod' => 'js-shortcode-slider'
        ]);
    }

    public function video($atts) {
        global $wpdb;
        $data = shortcode_atts([
            'poster' => '',
            'src' => '',
        ], $atts);

        if ($data['poster']) {
            $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $data['poster']));
            if ($attachment) {
                $image_id = $attachment[0];
            } else {
                $image_id = false;
            }
        } else {
            $image_id = false;
        }

        $video = [
            'video' => $data['src'],
        ];

        if ($image_id) {
            $video['image'] = ['srcset' => ($image_id ? Helpers::getImageSrcSet($image_id, '640x360') : '')];
        } else {
            $video['class'] = 'is-open';
        }

        return Template::compileComponent('@video', $video);
    }

    public function reviews($atts) {
        $data = shortcode_atts([
            'title' => '',
            'show' => '',
        ], $atts);

        $context = [
            'title' => $data['title'],
            'reviews' => []
        ];

        if($data['show']) {
            $reviewList = explode(',', $data['show']);
            foreach ($reviewList as $commentId) {
                $review               = new Review(trim($commentId));
                $reviewContext = $review->getContext(['id', 'author', 'date', 'reviewRating', 'text', 'productImage', 'productName', 'productPrice']);
                $reviewContext['withProduct'] = true;
                $context['reviews'][] = $reviewContext;
            }
        }

        return Template::compileComponent('@review-slider', $context);
    }

}
