<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Package;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;
use gotoAndPlay\Theme;
use RGFormsModel;

class Events extends Template {

    protected $view = '@view-events';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $emo           = new Module('emo', get_the_ID());
                $packages = Package::getPackagesByIds(get_field('popular_packages'));
                $groups = [];
                foreach($packages as $package) {
                    $groups[]['productData'] = $package->getProductData();
                }
                $this->context = [
                    'hero' => [
                        'modifier' => 'hero--packages',
                        'bg' => Helpers::getHeroBackground(),
                        'title' => get_field('hero_title'),
                        'description' => get_field('hero_description'),
                        'ctaButton' => [
                            'text' => get_field('hero_button_text'),
                            'link' => '#packages',
                            'class' => 'h-scrollto',
                        ],
                    ],
                    'features' => get_field('features'),
                    'popular' => [
                        'title' => get_field('popular_title'),
                        'products' => $groups,
                    ],
                    'packageAccordion' => [
                        'title' => get_field('package_accordion_title'),
                        'content' => [
                            'image' => [
                                'srcset' => Helpers::getImage(get_field('package_content_image'), '470x428'),
                                'alt' => get_field('package_content_title'),
                            ],
                            'title' => get_field('package_content_title'),
                            'content' => get_field('package_content_text'),
                        ],
                    ],
                    'termsAccordion' => [
                        'title' => get_field('terms_accordion_title'),
                        'content' => [
                            'image' => [
                                'srcset' => Helpers::getImageSrcSet(get_field('terms_image'), '1170x800'),
                                'alt' => get_field('terms_accordion_title'),
                            ],
                            'title' => get_field('terms_title'),
                            'content' => get_field('terms_content'),
                        ],
                    ],
                    'extraAccordion' => [
                        'title' => get_field('extra_accordion_title'),
                        'content' => [
                            'image' => [
                                'srcset' => Helpers::getImageSrcSet(get_field('extra_image'), '940x856'),
                                'alt' => get_field('extra_accordion_title'),
                            ],
                            'title' => get_field('extra_title'),
                            'content' => get_field('extra_content'),
                        ],
                    ],
                    'emoModule' => $emo->getContext(),
                    'reviewSlider' => Review::getSlider(get_the_ID()),
                    'cardsTitle' => FrontPage::getCardsTitle(),
                    'cardsContent' => FrontPage::getCardsContent(),
                    'cardsLink' => FrontPage::getCardsLinks(),
                    'cards' => FrontPage::getTestimonials(),
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
