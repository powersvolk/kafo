<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use WP_Query;
use WP_Post;
use RGFormsModel;

class Brand {

    private $ID;
    private $title;
    private $content;
    private $permalink;
    private $tags;
    private $author;
    private $authorPostCount;
    private $excerpt;

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
        $this->ID              = get_the_ID();
        $this->title           = get_the_title();
        $this->excerpt         = get_the_excerpt();
        $this->content         = Helpers::getFormattedContent(get_the_content());
        $this->permalink       = get_permalink($this->ID);
        $this->tags            = wp_get_post_tags(get_the_ID());
        $this->author          = get_the_author_meta('ID');
        $this->authorPostCount = count_user_posts(get_the_author_meta('ID'), 'post');
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

    public function getExcerpt() {
        return $this->excerpt;
    }

    public function getPermalink() {
        return $this->permalink;
    }

    public function getAuthorPostCount() {
        return sprintf(_n('%s postitus', '%s postitust', $this->authorPostCount, 'kafo'), $this->authorPostCount);
    }

    public function getAuthor() {
        $avatarId = get_the_author_meta('author_avatar', $this->author);

        return [
            'image' => Helpers::getImageSrcSet($avatarId, '85w'),
            'name' => get_the_author_meta('display_name', $this->author),
            'tagline' => get_the_author_meta('author_tagline', $this->author),
            'count' => $this->getAuthorPostCount(),
            'description' => get_the_author_meta('author_description', $this->author),
        ];
    }

    public function getTags() {
        $tags = [];
        if ($this->tags) {
            foreach ($this->tags as $tag) {
                $element = [
                    'title' => $tag->name,
                    'link' => get_tag_link($tag->term_id),
                ];

                $tags[] = $element;
            }
        }

        return [
            'prepend' => __('Tagid', 'kafo'),
            'tags' => $tags,
        ];
    }

    public function getSocial() {
        return Helpers::getSocialMedia($this->getPermalink());
    }

    public function getCoverImage() {
        return Helpers::getImageSrcSet(get_post_thumbnail_id($this->ID), '763x352');
    }

    public function getCoverImageCaption() {
        return get_the_post_thumbnail_caption($this->ID);
    }

    public function getImage() {
        return Helpers::getImageSrcSet(get_post_thumbnail_id($this->ID), '380x380');
    }

    public function getCardIds() {
        return get_field('training_cards', $this->ID);
    }

    public static function getCards($brands) {
        $cards = [];
        foreach ($brands as $brand) {
            $cards[] = [
                'image' => [
                    'srcset' => $brand->getImage(),
                    'alt' => $brand->getTitle(),
                ],
                'title' => $brand->getTitle(),
                'description' => $brand->getExcerpt(),
                'link' => $brand->getPermalink(),
            ];
        }

        return $cards;
    }

    public static function getAllBrands() {
        $brands = [];
        $args   = [
            'post_status' => 'publish',
            'post_type' => 'brand',
            'posts_per_page' => -1,
        ];
        $query  = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $brands[] = new Brand();
            }
        }

        return $brands;
    }

    public static function getOtherBrands($id) {
        $brands = [];
        $args   = [
            'post_status' => 'publish',
            'post_type' => 'brand',
            'post__not_in' => (!is_array($id) ? [$id] : $id),
            'posts_per_page' => -1,
        ];
        $query  = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $brands[] = new Brand();
            }
        }

        wp_reset_query();

        return $brands;
    }

}
