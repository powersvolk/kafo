<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Models\Article;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Context as Context;
use WP_Query;

class FrontPage extends Template {

    protected $view = '@view-front-page';

    private static $ID;

    public static function __init__() {
        self::$ID = get_option('page_on_front');
    }

    public static function getId() {
        return self::$ID;
    }

    public static function getUrl() {
        return get_permalink(self::getId());
    }

    public static function getHero() {
        $hero = [
            'modifier' => 'hero--front',
            'bg'       => Helpers::getHeroBackground(self::getId()),
        ];

        if (get_field('hero_dark', self::getId())) {
            $hero['modifier'] .= ' hero--dark';
        }

        if (have_rows('hero_links_large', self::getId())) {
            $hero['nav'] = [];
            while (have_rows('hero_links_large', self::getId())) {
                the_row();
                $hero['nav'][] = [
                    'title' => get_sub_field('label'),
                    'link'  => get_sub_field('target'),
                ];
            }
        }

        if (have_rows('hero_links_small', self::getId())) {
            $hero['cats'] = [];
            while (have_rows('hero_links_small', self::getId())) {
                the_row();
                $hero['cats'][] = [
                    'title' => get_sub_field('label'),
                    'link'  => get_sub_field('target'),
                ];
            }
        }

        return $hero;
    }

    public static function getBrandsList() {
        $brandsCtaText = get_field('home_partner_label', self::getId());
        $brandsCtaLink = get_field('home_partners_link', self::getId());

        $brands = [
            'cta'        => $brandsCtaText,
            'link'       => $brandsCtaLink ? $brandsCtaLink : '#product-filter',
        ];

        if (have_rows('home_partners', self::getId())) {
            $brands['logos'] = [];
            while (have_rows('home_partners', self::getId())) {
                the_row();
                $brands['logos'][] = [
                    'image' => Helpers::getImage(get_sub_field('logo', self::getId())),
                ];
            }
        }

        if (!$brandsCtaLink) {
            $brands['modalClass']   = 'data-custom-class="modaal--collapsed modaal--large"';
            $brands['btnClass']     = 'js-modaal';

            if (get_field('home_partner_cat', self::getId())) {
                $filters     = self::getFilter();
                $terms       = [];
                $currentTerm = get_field('home_partner_cat', self::getId());
                foreach ($filters as $key => $filter) {
                    if (isset($filter['rangeslider'])) {
                        $terms[$filter['rangeslider']['termSlug']] = $filter['rangeslider']['minValue'];
                    }
                }
                $taxArgs = [];
                // include range terms
                if (!empty($terms)) {
                    foreach ($terms as $term => $range) {
                        $baseTerm   = get_term_by('slug', $currentTerm->slug, 'product_cat');
                        $termFilter = get_field('term_filter_item', $baseTerm);
                        $minTerms   = [];
                        $maxTerms   = [];
                        $n          = 0;
                        if (!$termFilter) {
                            $termFilter = 0;
                        }

                        // get category filter
                        while (have_rows('filter_list', 'options')) {
                            the_row();
                            if ($termFilter == $n) {
                                // get range filter term
                                while (have_rows('product_filter')) {
                                    the_row();
                                    // get min/max terms for range
                                    if (get_sub_field('wc_attr_filter') == $term) {
                                        // set range term by term, as taxonomy cant query with 'BETWEEN'
                                        // min term args
                                        $minArgs  = [];
                                        $minSlug  = get_sub_field('wc_attr_filter_min');
                                        $minTerms = get_terms($minSlug, ['hide_empty' => false]);
                                        foreach ($minTerms as $term) {
                                            if (is_numeric($term->slug)) {
                                                if (intval($term->slug) <= $range) {
                                                    $minArgs[] = $term->term_id;
                                                }
                                            }
                                        }

                                        if ($minArgs) {
                                            $taxArgs[] = [
                                                'taxonomy' => $minSlug,
                                                'field'    => 'term_id',
                                                'terms'    => $minArgs,
                                            ];
                                        }

                                        // max term args
                                        $maxArgs  = [];
                                        $maxSlug  = get_sub_field('wc_attr_filter_max');
                                        $maxTerms = get_terms($maxSlug, ['hide_empty' => false]);
                                        foreach ($maxTerms as $term) {
                                            if (is_numeric($term->slug)) {
                                                if (intval($term->slug) >= $range) {
                                                    $maxArgs[] = $term->term_id;
                                                }
                                            }
                                        }

                                        if ($maxArgs) {
                                            $taxArgs[] = [
                                                'taxonomy' => $maxSlug,
                                                'field'    => 'term_id',
                                                'terms'    => $maxArgs,
                                            ];
                                        }
                                    }
                                }
                            }

                            $n++;
                        }
                    }
                }

                if (count($taxArgs) > 1) {
                    $taxArgs['relation'] = 'AND';
                }

                $args            = [
                    'posts_per_page' => -1,
                    'post_type'      => 'product',
                    'tax_query'      => $taxArgs,
                    'post_status'    => 'publish',
                ];
                $query           = new WP_Query($args);
                $brands['modal'] = [
                    'id'         => 'product-filter',
                    'modifier'   => '',
                    'collapsed'  => true,
                    'filterData' => [
                        'category' => $currentTerm->slug,
                        'action'   => 'filter-products',
                        'formPost' => get_term_link($currentTerm),
                        'header'   => [
                            'title' => get_field('home_filter_title', self::getId()),
                        ],
                        'filters'  => $filters,
                        'footer'   => [
                            'text'   => __('Sinu valikutele on tulemusi ei rohkem ega vähem kui', 'kafo'),
                            'result' => '',
                            'button' => [
                                'text'      => __('Näita', 'kafo'),
                                'modifiers' => 'button--white button--icon button--block-xs',
                                'icon'      => 'arrow-right',
                                'type'      => 'submit',
                            ],
                        ],
                    ],
                ];
            }
        }

        return $brands;
    }

    public static function getFilter() {
        $filters = [];
        $term    = get_field('home_partner_cat', self::getId());
        if (have_rows('filter_list', 'options')) {
            $n          = 0;
            $termFilter = get_field('term_filter_item', $term);
            if (!$termFilter) {
                $termFilter = 0;
            }

            while (have_rows('filter_list', 'options')) {
                the_row();
                if ($termFilter == $n) {
                    if (have_rows('product_filter')) {
                        while (have_rows('product_filter')) {
                            the_row();

                            $filterAttr         = get_terms(get_sub_field('wc_attr_filter'), ['hide_empty' => false]);
                            $filter             = [
                                'field' => 'checkbox',
                                'label' => get_sub_field('filter_label'),
                            ];
                            $filter['checkbox'] = [];
                            $m                  = 0;

                            foreach ($filterAttr as $attr) {
                                $check = [
                                    'id'    => $attr->taxonomy . '-' . $m,
                                    'label' => $attr->name,
                                    'name'  => 'tax_query[' . $attr->taxonomy . '][]',
                                    'value' => $attr->slug,
                                ];
                                if (get_sub_field('filter_range')) {
                                    $check['class'] = str_replace('_', '-', get_sub_field('wc_attr_filter') . '_control');
                                    $check['data']  = sprintf('data-min="%s" data-max="%s"', get_field('range_min', $attr), get_field('range_max', $attr));
                                }

                                $filter['checkbox'][] = $check;
                                $m++;
                            }

                            $filters[] = $filter;

                            if (get_sub_field('filter_range')) {
                                $range = [
                                    'field' => 'range-slider',
                                    'label' => get_sub_field('filter_range_label'),
                                ];

                                $range['rangeslider'] = [
                                    'minValue'   => get_sub_field('filter_range_min'),
                                    'maxValue'   => get_sub_field('filter_range_max'),
                                    'inputValue' => get_sub_field('filter_range_min'),
                                    'step'       => 1,
                                    'controls'   => str_replace('_', '-', get_sub_field('wc_attr_filter') . '_control'),
                                    'inputName'  => sprintf('range[%s]', get_sub_field('wc_attr_filter')),
                                    'termSlug'   => get_sub_field('wc_attr_filter'),
                                ];

                                $filters[] = $range;
                            }
                        }
                    }
                }

                $n++;
            }
        }

        return $filters;
    }

    public static function getFeatures() {
        $features = [];
        if (have_rows('home_features', self::getId())) {
            while (have_rows('home_features', self::getId())) {
                the_row();
                $features[] = [
                    'title' => get_sub_field('title'),
                    'icon'  => get_sub_field('logo'),
                    'text'  => get_sub_field('content'),
                ];
            }
        }

        return $features;

    }

    public static function getCategories() {
        $cats = [];
        if (get_field('home_cat_list', self::getId())) {
            $i          = 0;
            $imageSizes = [
                1 => ['517x777' => 'desktop', '1142x1554' => 'desktop', '363x777' => 'tablet', '726x1554' => 'tablet', '317x220' => 'mobile', '634x440' => 'mobile'],
                2 => ['519x282' => 'desktop', '1038x564' => 'desktop', '198x282' => 'tablet', '396x564' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
                3 => ['198x178' => 'desktop', '396x356' => 'desktop', '198x282' => 'tablet', '396x564' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
                4 => ['519x282' => 'desktop', '1038x564' => 'desktop', '198x282' => 'tablet', '396x564' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
                5 => ['198x178' => 'desktop', '396x356' => 'desktop', '198x282' => 'tablet', '396x564' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
                6 => ['519x282' => 'desktop', '1038x564' => 'desktop', '198x282' => 'tablet', '396x564' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
                7 => ['198x178' => 'desktop', '396x356' => 'destop', '198x282' => 'tablet', '396x564' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
                8 => ['1269x268' => 'desktop', '2538x536' => 'desktop', '792x268' => 'tablet', '1584x536' => 'tablet', '317x150' => 'mobile', '634x300' => 'mobile'],
            ];
            foreach (get_field('home_cat_list', self::getId()) as $category) {
                $i++;
                $thumb_id         = get_term_meta($category->term_taxonomy_id, 'thumbnail_id', true);
                $cats['cat' . $i] = [
                    'title' => $category->name,
                    'link'  => get_term_link($category),
                ];

                foreach ($imageSizes[$i] as $size => $type) {
                    $imageId = get_field('hero_img_' . $type, $category);
                    if (!$imageId) {
                        if (get_field('hero_img_desktop', $category)) {
                            $imageId = get_field('hero_img_desktop', $category);
                        } else if (get_field('hero_img_tablet', $category)) {
                            $imageId = get_field('hero_img_tablet', $category);
                        } else if (get_field('hero_img_mobile', $category)) {
                            $imageId = get_field('hero_img_mobile', $category);
                        } else {
                            $imageId = $thumb_id;
                        }
                    }

                    $cats['cat' . $i]['img' . $size] = Helpers::getImage($imageId, $size);
                }
            }
        }

        return $cats;
    }

    public static function getTestimonials() {
        $cats     = get_field('home_blog_posts', self::getId());
        $blogList = [];
        if ($cats) {
            foreach ($cats as $cat) {
                $args = [
                    'posts_per_page' => -1,
                    'cat'            => $cat,
                    'post_type'      => 'post',
                    'order'          => 'DESC',
                    'orderby'        => 'date',
                ];

                $query = new WP_Query($args);
                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $article    = new Article();
                        $blogList[] = [
                            'link'        => $article->getPermalink(),
                            'modifier'    => 'card--testimonial',
                            'element'     => 'a',
                            'title'       => $article->getTitle(),
                            'description' => false,
                            'background'  => $article->getImageSrcSet('360x586', true),
                        ];
                    }
                }

                wp_reset_postdata();
            }
        }

        return $blogList;
    }

    public static function getCardsTitle() {
        return get_field('home_blog_title', self::getId());
    }

    public static function getCardsContent() {
        return get_field('home_blog_content', self::getId());
    }

    public static function getCardsLinks() {
        return [
            'text' => get_field('home_blog_more', self::getId()),
            'link' => get_field('home_blog_more_link', self::getId()),
        ];
    }

    public static function getEmo() {
        $emoModule = new Module('emo', self::getId());

        return $emoModule->getContext();
    }

    public function getReviewSlider() {
        return Review::getSlider(self::getId());
    }

    public function getContextFields() {
        return [
            'hero'              => self::getHero(),
            'brands'            => self::getBrandsList(),
            'features'          => self::getFeatures(),
            'cardsTitle'        => self::getCardsTitle(),
            'cardsContent'      => self::getCardsContent(),
            'cardsLink'         => self::getCardsLinks(),
            'cards'             => self::getTestimonials(),
            'categories'        => self::getCategories(),
            'reviewSlider'      => self::getReviewSlider(),
            'emoModule'         => self::getEmo(),
            'categoriesTitle'   => get_field('home_cat_title', self::getId()),
            'categoriesContent' => get_field('home_cat_content', self::getId()),
        ];
    }

}
