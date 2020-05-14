<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Article;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Template as Template;
use WP_Query;

class Search extends Template {

    protected $view = '@view-search';

    private $context;

    public function __construct() {
        $emo          = new Module('emo');
        $type         = isset($_GET['type']) ? $_GET['type'] : false;
        $searchString = get_search_query();
        $results      = self::getSearchResults($searchString);
        $totalResults = 0;
        foreach ($results as $arr) {
            $totalResults += count($arr);
        }

        $this->context = [
            'filterSectionData' => [
                'modifier' => 'search',
                'title' => __('Otsingutulemused', 'kafo'),
                'text' => sprintf(__('Leidsid sõnale <b>"%s"</b> ei rohkem ega vähem kui %s tulemust.', 'kafo'), $searchString, sprintf('<strong class="text-red">%s</strong>', $totalResults)),
                'filterData' => [
                    'header' => [
                        'title' => __('Täpsusta otsingut', 'kafo'),
                    ],
                    'filters' => [
                        [
                            'field' => 'checkbox',
                            'label' => __('Kategooriad', 'kafo'),
                            'hideLabel' => true,
                            'checkbox' => [
                                [
                                    'id' => 'products',
                                    'name' => 'products',
                                    'value' => 'products',
                                    'label' => __('Tooted', 'kafo'),
                                    'class' => 'js-search-filter',
                                    'isChecked' => $type == 'products',
                                ],
                                [
                                    'id' => 'categories',
                                    'name' => 'categories',
                                    'value' => 'categories',
                                    'label' => __('Kategooriad', 'kafo'),
                                    'class' => 'js-search-filter',
                                    'isChecked' => $type == 'categories',
                                ],
                                [
                                    'id' => 'blog',
                                    'name' => 'blog',
                                    'value' => 'blog',
                                    'label' => __('Blogi', 'kafo'),
                                    'class' => 'js-search-filter',
                                    'isChecked' => $type == 'blog',
                                ],
                                [
                                    'id' => 'recipes',
                                    'name' => 'recipes',
                                    'value' => 'recipes',
                                    'label' => __('Retseptid', 'kafo'),
                                    'class' => 'js-search-filter',
                                    'isChecked' => $type == 'recipes',
                                ],
                                [
                                    'id' => 'pages',
                                    'name' => 'pages',
                                    'value' => 'pages',
                                    'label' => __('Lehed', 'kafo'),
                                    'class' => 'js-search-filter',
                                    'isChecked' => $type == 'pages',
                                ],
                            ],
                        ],
                        [
                            'field' => 'select',
                            'label' => __('Sorteeri', 'kafo'),
                            'hideLabel' => true,
                            'select' => [
                                [
                                    'id' => 'sort',
                                    'label' => __('Sorteeri', 'kafo'),
                                    'options' => [
                                        [
                                            'name' => __('Uuemand', 'kafo'),
                                            'href' => '#new',
                                            'class' => 'js-search-sort',
                                        ],
                                        [
                                            'name' => __('Vanemad', 'kafo'),
                                            'href' => '#old',
                                            'class' => 'js-search-sort',
                                        ],
                                        [
                                            'name' => __('Kasvav', 'kafo'),
                                            'href' => '#up',
                                            'class' => 'js-search-sort',
                                        ],
                                        [
                                            'name' => __('Kahanev', 'kafo'),
                                            'href' => '#down',
                                            'class' => 'js-search-sort',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'footer' => [
                        'isMobile' => true,
                        'button' => [
                            'text' => __('Filtreeri', 'kafo'),
                            'modifiers' => 'button--white button--block-xs',
                        ],
                    ],
                ],
            ],
            'results' => [
                'productsTitle' => sprintf('%s <span class="section__title-count"> (%s)</span>', __('Tooted', 'kafo'), count($results['products'])),
                'products' => $results['products'],
                'productsCount' => count($results['products']),
                'categoriesTitle' => sprintf('%s <span class="section__title-count"> (%s)</span>', __('Kategooriad', 'kafo'), count($results['categories'])),
                'categories' => $results['categories'],
                'categoriesCount' => count($results['categories']),
                'articlesTitle' => sprintf('%s <span class="section__title-count"> (%s)</span>', __('Artiklid', 'kafo'), count($results['articles'])),
                'articles' => $results['articles'],
                'articlesCount' => count($results['articles']),
                'recipes' => $results['recipes'],
                'recipesTitle' => sprintf('%s <span class="section__title-count"> (%s)</span>', __('Retseptid', 'kafo'), count($results['recipes'])),
                'recipesCount' => count($results['recipes']),
                'pagesTitle' => sprintf('%s <span class="section__title-count"> (%s)</span>', __('Lehed', 'kafo'), count($results['pages'])),
                'pages' => $results['pages'],
                'pagesCount' => count($results['pages']),
            ],
            'emoModule' => $emo->getContext(),
        ];
    }

    public function getContextFields() {
        return $this->context;
    }

    public static function getSearchResults($searchString) {
        $results = [
            'products' => [],
            'categories' => [],
            'articles' => [],
            'recipes' => [],
            'pages' => [],
        ];

        $args  = [
            'post_status' => 'publish',
            'post_type' => ['post', 'product', 'page'],
            's' => $searchString,
            'posts_per_page' => -1,
        ];
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                switch (get_post_type()) {
                    case 'post':
                        $article               = new Article();
                        $results['articles'][] = [
                            'articleData' => [
                                'columnClass' => 'grid__col--sm-6 grid__col--md-4',
                                'link' => $article->getPermalink(),
                                'image' => [
                                    'srcset' => $article->getImageSrcSet('760x760', true),
                                    'alt' => $article->getTitle(),
                                ],
                                'title' => $article->getTitle(),
                                'date' => $article->getDate(),
                                'timestamp' => get_the_time('U', $article->getId()),
                            ],
                        ];
                    break;

                    case 'product':
                        $product               = new Product();
                        $results['products'][] = $product->getContext(['title', 'description', 'image', 'link', 'price', 'timestamp', 'priceRaw']);
                    break;

                    case 'page':
                        $results['pages'][] = [
                            'articleData' => [
                                'modifier' => 'article--page',
                                'columnClass' => 'grid__col--sm-6 grid__col--md-4',
                                'title' => get_the_title(),
                                'link' => get_permalink(),
                                'date' => __('Vaata lehete', 'kafo'),
                                'timestamp' => get_the_time('U'),
                            ],
                        ];
                    break;
                }
            }
        }

        wp_reset_query();
        $args['post_type'] = 'post';
        $args['cat']       = get_field('recipe_category', 'option');
        $query             = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $article              = new Article();
                $results['recipes'][] = [
                    'articleData' => [
                        'columnClass' => 'grid__col--sm-6 grid__col--md-4',
                        'link' => $article->getPermalink(),
                        'image' => [
                            'srcset' => $article->getImageSrcSet('380x380'),
                            'alt' => $article->getTitle(),
                        ],
                        'title' => $article->getTitle(),
                        'date' => $article->getDate(),
                        'timestamp' => get_the_time('U', $article->getId()),
                    ],
                ];
            }
        }

        wp_reset_query();

        $categories = get_terms([
            'taxonomy' => ['product_cat'], // category
            'search' => $searchString,
        ]);

        if ($categories) {
            foreach ($categories as $category) {
                $results['categories'][] = [
                    'modifier' => 'card--category',
                    'element' => 'a',
                    'link' => get_term_link($category),
                    'background' => Helpers::getImageSrcSet(get_term_meta($category->term_id, 'thumbnail_id', true), '360x586'),
                    'title' => $category->name,
                    'description' => $category->description,
                ];
            }
        }

        return $results;
    }

}
