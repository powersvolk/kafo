<?php
namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;
use gotoAndPlay\Templates\Blog;
use WP_Query;
use WP_Post;

class Article {

    private $ID;
    private $title;
    private $author;
    private $authorUrl;
    private $categories;
    private $tags;
    private $content;
    private $timestamp;
    private $permalink;
    private $imageId;
    private $featuredId;
    private $excerpt;
    private $featured;
    private $authorPostCount;

    public function __construct($idOrPostData = false, $featured = false) {
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

        $this->featured = $featured;
        $this->setup();
        wp_reset_query();
    }

    private function setup() {
        $this->ID              = get_the_ID();
        $this->permalink       = get_permalink();
        $this->title           = get_the_title();
        $this->content         = Helpers::getFormattedContent(get_the_content());
        $this->excerpt         = get_the_excerpt();
        $this->imageId         = get_field('large_post_img_xl', $this->ID);
        $this->featuredId      = get_post_thumbnail_id($this->ID);
        $this->timestamp       = get_the_time('U');
        $this->author          = get_the_author_meta('display_name');
        $this->authorPostCount = count_user_posts(get_the_author_meta('ID'), 'post');
        $this->authorUrl       = get_author_posts_url(get_the_author_meta('ID'), get_the_author_meta('user_nicename'));
        $this->categories      = wp_get_post_categories(get_the_ID(), ['fields' => 'all']);
        $this->tags            = wp_get_post_tags(get_the_ID());
    }

    public function getContext($fields = [], $isWide = false, $useFeaturedImage = false) {
        $context = [];
        foreach ($fields as $key) {
            $value = false;
            switch ($key) {
                case 'id':
                    $value = $this->getId();
                break;

                case 'title':
                    $value = $this->getTitle();
                break;

                case 'link':
                case 'url':
                    $value = $this->getPermalink();
                break;

                case 'content':
                    $value = $this->getContent();
                break;

                case 'image':
                    $value = $this->getImageSrcSet('large');
                break;

                case 'articleData':
                    $value = [
                        'isWide' => $isWide,
                        'columnClass' => 'grid__col--sm-4',
                        'link' => $this->getPermalink(),
                        'image' => [
                            'srcset' => $this->featured ? $this->getImageSrcSet('773w2x', $useFeaturedImage) : $isWide ? $this->getImageSrcSet('570w2x', $useFeaturedImage) : $this->getImageSrcSet('760x760', $useFeaturedImage),
                            'alt' => $this->getTitle(),
                        ],
                        'title' => $this->getTitle(),
                        'date' => $this->getDate(),
                        'description' => $this->getExcerpt(),
                    ];
                break;
            }

            $context[$key] = $value;
        }

        return $context;
    }

    public function getId() {
        return $this->ID;
    }

    public function getContent() {
        return $this->content;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getExcerpt() {
        return $this->excerpt;
    }

    public function getPermalink() {
        return $this->permalink;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function getAuthorPostCount() {
        return sprintf(_n('%s postitus', '%s postitust', $this->authorPostCount, 'kafo'), $this->authorPostCount);
    }

    public function getAvatar($size) {
        $avatarId  = get_the_author_meta('author_avatar');
        $avatarSrc = Helpers::getImageSrcSet($avatarId, $size);

        return $avatarSrc;
    }

    public function getDate() {
        return date('d.m.Y', $this->timestamp);
    }

    public function getCategories() {
        return $this->categories;
    }

    public function hasCategory($id) {
        foreach ($this->getCategories() as $category) {
            if ($category->term_id == $id) {
                return true;
            }
        }

        return false;
    }

    public function hasRecipeCategory() {
        $cats    = $this->categories;
        $kitties = [];
        $recipe  = get_field('recipe_category', 'options');

        foreach ($cats as $cat) {
            $kitties[] = $cat->term_id;
        }

        if (in_array($recipe, $kitties)) {
            return true;
        } else {
            return false;
        }
    }

    public function getTags() {
        return $this->tags;
    }

    public function getFeatured() {
        return false;
    }

    public function getCoverSize() {
        if ($this->getFeatured()) {
            $size = '760x760';
        } else {
            $size = '380x380';
        }

        return $size;
    }

    public function getImageSrcSet($size, $useFeatured = false) {
        if (!$size) {
            $size = $this->getCoverSize();
        }

        if ($this->imageId || ( $useFeatured && $this->featuredId )) {
            return Helpers::getImageSrcSet($useFeatured && $this->featuredId ? $this->featuredId : $this->imageId, $size);
        } else {
            return false;
        }
    }

    public function categoriesList() {
        $catsList = get_the_category_list(',');

        return $catsList;
    }

    public function getTimeDiff() {
        return Helpers::timeElapsedString($this->timestamp);
    }

    public function getBlogPoster() {
        $poster = [
            'bg' => [
                'xl' => Helpers::getImage(get_field('large_post_img_xl', $this->getId()), '2560x1440'),
                'lg' => Helpers::getImage(get_field('large_post_img_lg', $this->getId()), '1920x660'),
                'lg2x' => Helpers::getImage(get_field('large_post_img_lg', $this->getId()), '3840x1320'),
                'md' => Helpers::getImage(get_field('large_post_img_md', $this->getId()), '1440x660'),
                'md2x' => Helpers::getImage(get_field('large_post_img_md', $this->getId()), '2880x1320'),
                'sm' => Helpers::getImage(get_field('large_post_img_sm', $this->getId()), '768x560'),
                'sm2x' => Helpers::getImage(get_field('large_post_img_sm', $this->getId()), '1536x1120'),
                'xs' => Helpers::getImage(get_field('large_post_img_xs', $this->getId()), '320x350'),
                'xs2x' => Helpers::getImage(get_field('large_post_img_xs', $this->getId()), '640x700'),
            ],
            'title' => $this->getTitle(),
            'back' => __('Tagasi', 'kafo'),
            'backLink' => Blog::getUrl(),
            'metaLabel' => __('Postitatud', 'kafo') . '<br>',
            'time' => $this->getTimeDiff() . '<br>',
            'category' => $this->categoriesList() . '<br>',
            'photoAuthor' => get_field('poster_image_caption', $this->getId()),
        ];

        $poster['bg'] = $this->getFullPoster($poster['bg']);
        return $poster;
    }

    private function getFullPoster($bgSet = []) {
        $sizeSets = ['xl', 'lg', 'md', 'sm', 'xs'];
        $current = false;
        $current2x = false;

        foreach($bgSet as $size => $image) {
            // skip retina images
            if(in_array($size, $sizeSets)) {
                if($image) {
                    // define current largest image
                    $current = $image;
                    if(isset($bgSet[$size.'2x']))
                        $current2x = $bgSet[$size.'2x'];
                } else {
                    // set last large image to current size
                    $bgSet[$size] = $current;
                    $bgSet[$size.'2x'] = $current2x ? $current2x : $current;
                }
            }
        }

        return $bgSet;
    }

    public function getSocial() {
        return Helpers::getSocialMedia(get_permalink($this->getId()));
    }

    public function getPostTags() {
        $tags    = $this->getTags();
        $theTags = [
            'prepend' => __('Tagid', 'kafo'),
            'tags' => [],
        ];

        if ($tags) {
            foreach ($tags as $tag) {
                $element = [
                    'title' => $tag->name,
                    'link' => get_tag_link($tag->term_id),
                ];

                $theTags['tags'][] = $element;
            }
        }

        return $theTags;
    }

    public function getGallery() {
        $contentGallery = get_field('content_gallery', $this->getId());
        $slides         = [
            'slides' => [],
        ];

        if ($contentGallery) {
            foreach ($contentGallery as $slide) {
                $element = [
                    'image' => Helpers::getImage($slide['id'], '763x352'),
                    'thumbnail' => Helpers::getImage($slide['id'], '68w'),
                    'alt' => $slide['alt'],
                ];

                $slides['slides'][] = $element;
            }
        }

        return $slides;
    }

    public function recipeContent() {
        $ingridients = get_field('ingridients', $this->getId());
        $tools       = get_field('tools', $this->getId());
        $recipe      = [
            'title' => get_field('recipe_title', $this->getId()),
            'subtitle' => get_field('ingridients_title', $this->getId()),
            'subtitle2' => get_field('tools_title', $this->getId()),
            'progressBarText' => '<span class="recipe-list__percentage">0</span>% ' . __('tehtud', 'kafo'),
            'list' => [],
            'list2' => [],
        ];

        if ($ingridients) {
            foreach ($ingridients as $ingridient) {
                $element = [
                    'id' => sanitize_title($ingridient['ingridient_name']),
                    'label' => $ingridient['ingridient_name'],
                ];

                $recipe['list'][] = $element;
            }
        }

        if ($tools) {
            foreach ($tools as $tool) {
                $element = [
                    'id' => sanitize_title($tool['tool_name']),
                    'label' => $tool['tool_name'],
                ];

                $recipe['list2'][] = $element;
            }
        }

        return $recipe;
    }

    public static function getArticlesForSlider($ids) {
        $articles = [];
        foreach (self::getArticles($ids) as $article) {
            $articles[] = [
                'link' => $article->getPermalink(),
                'image' => [
                    'srcset' => $article->getImageSrcSet('380x380-top', true),
                    'alt' => $article->getTitle(),
                ],
                'title' => $article->getTitle(),
                'date' => $article->getTimeDiff(),
            ];
        }

        return $articles;
    }

    public static function getArticles($ids) {
        $articles = [];
        if ($ids) {
            $args  = [
                'post_status' => 'publish',
                'post_type' => 'post',
                'orderby' => 'post__in',
                'post__in' => $ids,
                'posts_per_page' => -1,
            ];
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $articles[] = new Article();
                }
            }
        }

        wp_reset_query();

        return $articles;
    }

}
