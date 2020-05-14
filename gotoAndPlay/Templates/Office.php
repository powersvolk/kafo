<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Package;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;

class Office extends Template {

    protected $view = '@view-office';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $emo           = new Module('emo', get_the_ID());
                $packages = Package::getPackagesByIds(get_field('packages'));
                $groups = [];
                foreach($packages as $package) {
                    $groups[]['productData'] = $package->getProductData();
                }
                $this->context = [
                    'hero' => [
                        'modifier' => 'hero',
                        'bg' => Helpers::getHeroBackground(),
                        'title' => get_field('hero_title'),
                        'description' => get_field('hero_description'),
                        'ctaButton' => [
                            'text' => get_field('hero_button_text_scroll'),
                            'link' => '#popular',
                            'class' => 'h-scrollto',
                        ],
                        'ctaButton2' => get_field('cta_button'),
                    ],
                    'features' => get_field('features'),
                    'categoriesTitle' => (get_field('categories_title') ? get_field('categories_title') : get_field('home_cat_title', FrontPage::getId())),
                    'categoriesContent' => (get_field('categories_content') ? get_field('categories_content') : get_field('home_cat_content', FrontPage::getId())),
                    'categories' => FrontPage::getCategories(),
                    'accordion' => [
                        'title' => get_field('accordion_title'),
                        'content' => [
                            'image' => [
                                'srcset' => Helpers::getImage(get_field('content_image'), '470x428'),
                                'alt' => get_field('content_title'),
                            ],
                            'title' => get_field('content_title'),
                            'content' => get_field('content_text'),
                        ],
                    ],
                    'packageGroups' => [
                        [
                            'title' => get_field('packages_title'),
                            'products' => $groups,
                        ],
                    ],
                    'emoModule' => $emo->getContext(),
                    'reviewSlider' => Review::getSlider(get_the_ID()),
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
