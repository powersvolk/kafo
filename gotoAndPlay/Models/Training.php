<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use gotoAndPlay\Templates\Blog;
use WP_Query;
use WP_Post;
use RGFormsModel;

class Training {

    private $ID;
    private $title;
    private $content;
    private $permalink;
    private $tags;
    private $author;

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
        $this->ID        = get_the_ID();
        $this->title     = get_the_title();
        $this->content   = Helpers::getFormattedContent(get_the_content());
        $this->permalink = get_permalink();
        $this->tags      = wp_get_post_tags(get_the_ID());
        $this->author    = get_the_author_meta('ID');
    }

    public function getTitle() {
        return $this->title;
    }

    public function getContent() {
        return $this->content;
    }

    public function getPermalink() {
        return $this->permalink;
    }

    public function getPrice() {
        return Helpers::parseFloat(get_field('training_price', $this->ID));
    }

    public function getPriceDisplay() {
        return $this->getPrice() . __('€ / 1 inimene', 'kafo');
    }

    public function getDuration() {
        return get_field('training_duration', $this->ID);
    }

    public function getLocation() {
        return get_field('training_location', $this->ID);
    }

    public function getTime() {
        return get_field('training_time', $this->ID);
    }

    public function getAuthor() {
        $avatarId = get_the_author_meta('author_avatar', $this->author);

        return [
            'image' => Helpers::getImageSrcSet($avatarId, '85w'),
            'name' => get_the_author_meta('display_name', $this->author),
            'tagline' => get_the_author_meta('author_tagline', $this->author),
            'count' => get_the_author_meta('author_training_count', $this->author),
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

    public function getMinPeople() {
        return intval(get_field('traning_min_people', $this->ID));
    }

    public function getMaxPeople() {
        return intval(get_field('traning_max_people', $this->ID));
    }

    public function getPeopleOptions() {
        $options = [];
        if ($this->getMinPeople() != $this->getMaxPeople()) {
            for ($i = $this->getMinPeople(); $i <= $this->getMaxPeople(); $i++) {
                $options[] = [
                    'name' => $i,
                    'text' => $i,
                    'value' => $i,
                    'isSelected' => false,
                ];
            }
        } else {
            $options[] = [
                'name' => $this->getMaxPeople(),
                'text' => $this->getMaxPeople(),
                'value' => $this->getMaxPeople(),
                'isSelected' => false,
            ];
        }

        return $options;
    }

    public function getSocial() {
        return Helpers::getSocialMedia($this->getPermalink());
    }

    public function getNotes() {
        return sprintf('<i>%s</i>', get_field('training_notes', $this->ID));
    }

    public function getDetails() {
        $details[] = sprintf('<b>%s:</b> %s<br>', __('Hind', 'kafo'), $this->getPriceDisplay());
        $details[] = sprintf('<b>%s:</b> %s<br>', __('Kestvus', 'kafo'), $this->getDuration());
        $min       = $this->getMinPeople();
        $max       = $this->getMaxPeople();
        if ($min != $max) {
            $details[] = sprintf('<b>%s:</b> %s - %s %s<br>', __('Inimeste arv', 'kafo'), $min, $max, __('inimest', 'kafo'));
        } else {
            $details[] = sprintf('<b>%s:</b> %s %s<br>', __('Inimeste arv', 'kafo'), $min, ($min == 1 ? __('inimene', 'kafo') : __('inimest', 'kafo')));
        }

        return sprintf('<p>%s</p>', implode($details));
    }

    public function getCardTitle() {
        return get_field('training_card_title', $this->ID);
    }

    public function getCardContent() {
        return get_field('training_card_content', $this->ID);
    }

    public function getCardIds() {
        return get_field('training_cards', $this->ID);
    }

    public function getModal() {
        $form = RGFormsModel::get_form(get_field('training_form_id', $this->ID));

        return [
            'image' => Helpers::getImageSrcSet(get_field('training_image', $this->ID), '496x229'),
            'title' => get_field('training_text_on_image', $this->ID),
            'info' => [
                [
                    'infoItem' => __('Asukoht', 'kafo'),
                    'infoItem2' => $this->getLocation(),
                ],
                [
                    'infoItem' => __('Koolitaja', 'kafo'),
                    'infoItem2' => get_the_author_meta('display_name', $this->author),
                ],
                [
                    'infoItem' => __('Hind', 'kafo'),
                    'infoItem2' => get_field('training_price', $this->ID) . __('€ / 1 inimene', 'kafo'),
                ],
            ],
            'button' => __('registreeri', 'kafo'),
            'modal' => [
                'id' => 'register',
                'modifier' => 'modaal--split',
                'title' => __('Registreeri koolitusele', 'kafo'),
                'gravityForm' => do_shortcode('[gravityform id="' . $form->id . '" title="false" description="false" ajax="true"]'),
                'trainingDetails' => [
                    'title' => $this->getTitle(),
                    'priceLabel' => __('Hind', 'kafo'),
                    'price' => $this->getPriceDisplay(),
                    'timeLabel' => __('Eeldatav aeg', 'kafo'),
                    'time' => $this->getTime(),
                    'locationLabel' => __('Asukoht', 'kafo'),
                    'location' => $this->getLocation(),
                    'priceTotalLabel' => __('Hind kokku', 'kafo'),
                    'priceTotal' => sprintf('<span class="js-training-price" data-price="%s">%s</span>%s', $this->getPrice(), Helpers::getFormattedPrice(($this->getPrice() * $this->getMinPeople()), false), Helpers::getCurrencySign()),
                    'nextLabel' => __('Mis edasi?', 'kafo'),
                    'next' => __('Saadame sinu emailile kinnituse koos arvega ja täpsustame ajagraafikud.', 'kafo'),
                ],
            ],
        ];
    }

    public static function getCards($ids) {
        $trainings = self::getTrainings($ids);
        $cards     = [];
        foreach ($trainings as $training) {
            $cards[] = [
                'modifier' => 'card--training',
                'background' => Helpers::getImageSrcSet(get_field('training_image', $training->ID), '360x586'),
                'title' => $training->getTitle(),
                'description' => $training->getDetails(),
                'element' => 'a',
                'link' => $training->getPermalink(),
                'cta' => [
                    'text' => __('Vaata koolitust', 'kafo'),
                ],
            ];
        }

        return $cards;
    }

    public static function getTrainings($ids) {
        $articles = [];
        if ($ids) {
            $args  = [
                'post_status' => 'publish',
                'post_type' => 'training',
                'orderby' => 'post__in',
                'post__in' => $ids,
                'posts_per_page' => -1,
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $articles[] = new Training();
                }
            }

            wp_reset_query();
        }

        return $articles;
    }

}
