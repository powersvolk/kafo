<?php

namespace gotoAndPlay\Models;

class Review {

    private $review;
    private $post;

    public function __construct($idOrPostData = false) {
        if ($idOrPostData) {
            if ($idOrPostData instanceof \WP_Comment) {
                $this->review = $idOrPostData;
            } else if (intval($idOrPostData)) {
                $this->review = get_comment($idOrPostData);
                $status = get_post_status($this->review->comment_post_ID);
                if($status == 'publish') {
                    if (get_post_type($this->review->comment_post_ID) == 'product') {
                        $this->post = new Product($this->review->comment_post_ID);
                    } else {
                        $this->post = new Article($this->review->comment_post_ID);
                    }
                } else {
                    return false;
                }
            }
        }
    }

    public function getContext($fields = []) {
        $context = [];
        foreach ($fields as $key) {
            $value = false;
            switch ($key) {
                case 'id':
                    $value = $this->review->comment_ID;
                break;

                case 'author':
                    $value = (filter_var($this->review->comment_author, FILTER_VALIDATE_EMAIL)) ? explode('@', $this->review->comment_author)[0] : $this->review->comment_author;
                break;

                case 'date':
                    $value = date('d.m.Y', strtotime($this->review->comment_date));
                break;

                case 'text':
                    $value = $this->review->comment_content;
                break;

                case 'reviewRating':
                    $value = get_comment_meta($this->review->comment_ID, 'rating', true);
                break;

                case 'productImage':
                    $postVal = $this->post->getContext(['image']);
                    $value   = $postVal['image'];
                break;

                case 'productName':
                    $postVal = $this->post->getContext(['title']);
                    $value   = $postVal['title'];
                break;

                case 'productPrice':
                    $postVal = $this->post->getContext(['price']);
                    $value   = $postVal['price'];
                break;

                case 'productPermalink':
                    $postVal = $this->post->getContext(['link']);
                    $value   = $postVal['link'];
                break;
            }

            $context[$key] = $value;
        }

        return $context;
    }

    public static function getProductReviews($id, $context = ['id', 'author', 'date', 'text', 'reviewRating']) {
        $reviews  = [];
        $args     = [
            'post_type' => 'product',
            'post_id'   => $id,
            'status'    => 'approve',
        ];
        $comments = get_comments($args);
        if ($comments) {
            foreach ($comments as $comment) {
                $review    = new Review($comment);
                $reviews[] = $review->getContext($context);
            }
        }

        return $reviews;
    }

    public static function getSlider($id) {
        if (!get_field('review_module_show', $id)) {
            return false;
        }

        $reviewListData = get_field('review_module_list', $id);
        if (!$reviewListData) {
            return false;
        }

        $context    = [
            'title'   => get_field('review_module_title', $id),
            'reviews' => [],
        ];
        $reviewList = explode(',', $reviewListData);
        foreach ($reviewList as $commentId) {
            $review               = new Review(trim($commentId));
            $status = get_post_status($review->review->comment_post_ID);
            if($status == 'publish') {
                $reviewContext = $review->getContext(['id', 'author', 'date', 'reviewRating', 'text', 'productImage', 'productName', 'productPrice', 'productPermalink']);
                $reviewContext['withProduct'] = true;
                $context['reviews'][] = $reviewContext;
            }

        }

        return $context;
    }

}
