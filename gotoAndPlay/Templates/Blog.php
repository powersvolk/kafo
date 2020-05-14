<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Context;
use gotoAndPlay\Models\Article;
use gotoAndPlay\Template as Template;

class Blog extends Template {

    protected $view = '@view-blog';

    private $title;
    private $count;
    private $articles;
    private $hasWide     = false;
    private $hasFeatured = true;

    public function __construct() {
        if (is_archive()) {
            $this->title = single_cat_title('', false);
            $isMain      = true;
        } else {
            $isMain = false;
        }

        if (!$this->title) {
            $this->title = get_the_title(get_option('page_for_posts'));
        }

        $this->hasWide = true;

        $articles = [];
        if (get_queried_object() && get_field('blog_featured_post_id', get_queried_object())) {
            $featured   = get_field('blog_featured_post_id', get_queried_object());
            $article    = new Article($featured, true);
            $articles[] = $article->getContext(['articleData'], false, true);
            $grid       = 0;
        } else {
            if ($this->checkWikiCategory()) {
                $this->view        = '@view-wiki-category';
                $this->hasFeatured = false;
                $this->hasWide     = false;
                $featured          = false;
            } else {
                $featured = get_field('blog_featured_post_id', 'option');
                if ($featured) {
                    $article    = new Article($featured, true);
                    $articles[] = $article->getContext(['articleData'], false, true);
                    $grid       = 0;
                } else {
                    $grid = -1;
                }
            }
        }

        if (have_posts()) {
            while (have_posts()) {
                the_post();
                if (get_the_ID() != $featured) {
                    $isWide = false;
                    if ($this->hasWide) {
                        $grid++;
                        if (!$isMain) {
                            if ($grid > 5) {
                                $grid = 0;
                            } else if ($grid > 3) {
                                $isWide = true;
                            }
                        } else {
                            $isWide = true;
                            if ($grid > 5) {
                                $grid = 1;
                            } else if ($grid > 2) {
                                $isWide = false;
                            }
                        }
                    }

                    $article    = new Article();
                    $articles[] = $article->getContext(['articleData'], $isWide, true);
                }
            }
        }

        $this->count    = count($articles);
        $this->articles = $articles;
    }

    public static function getId() {
        return get_option('page_for_posts');
    }

    public static function getUrl() {
        return get_permalink(self::getId());
    }

    public function getBottomBar() {
        $id = get_option('page_for_posts');
        if (!is_archive() && get_field('bottom_bar_form_id', $id)) {
            return [
                'icon' => 'ic_24_notification',
                'title' => get_field('bottom_bar_title', $id),
                'button' => get_field('bottom_bar_button_text', $id),
                'icon2' => 'ic_24_close',
                'signupModal' => [
                    'id' => 'newsletter',
                    'modifier' => 'modaal--small',
                    'title' => get_field('bottom_bar_modal_title', $id),
                    'form' => true,
                    'gravityForm' => true,
                    'content' => do_shortcode('[gravityform id="' . get_field('bottom_bar_form_id', $id) . '" title="false" description="false" ajax="true"]'),
                ],
            ];
        } else {
            return false;
        }
    }

    public function getContextFields() {
        $context = [
            'hasFeatured' => $this->hasFeatured,
            'name' => $this->title,
            'posts' => $this->articles,
            'pagination' => Context::getPagination(),
            'cardsTitle' => FrontPage::getCardsTitle(),
            'cardsContent' => FrontPage::getCardsContent(),
            'cardsLink' => FrontPage::getCardsLinks(),
            'cards' => FrontPage::getTestimonials(),
            'bottomBar' => $this->getBottomBar(),
        ];

        return $context;
    }

    public function checkWikiCategory() {
        $wikiCat = get_field('wiki_category', 'options');
        if (get_queried_object()) {
            $currentCat       = get_queried_object()->term_id;
            $currentCatParent = get_queried_object()->parent;

            if ($wikiCat == $currentCat || $wikiCat == $currentCatParent) {
                return true;
            }
        }

        return false;
    }

}
