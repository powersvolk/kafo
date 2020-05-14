<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Package;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;


class Officenew extends Template {

    protected $view = '@view-officenew';

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
                        'bg' => wp_get_attachment_image_src(get_field('hero_img_xl'),'large')[0],
						'title' => get_field('hero_title'),
                        'description' => get_field('hero_description'),
                        'ctaButton2' => get_field('cta_button'),
                    ],
					
                    'features' => get_field('features'),
                    'productTitle' => get_field('product_title'),
                    'productContent' => get_field('product_content'),
                    'products' => get_field('product_content_list'),
					'partners_title' => get_field('partners_title'),
					'partners_content' => get_field('partners_content'),
					'partners_image' => get_field('partners_image'),
					'news_title' => get_field('news_title'),
					'news' => get_field('news_news'),
					'banner_image_1' => get_field('banner_image_1'),
					'banner_title_1' => get_field('banner_title_1'),
					'banner_description_1' => get_field('banner_description_1'),
					'banner_image_2' => get_field('banner_image_2'),
					'banner_title_2' => get_field('banner_title_2'),
					'banner_description_2' => get_field('banner_description_2'),
					'banner_link' => get_field('banner_link'),
					'banner_button' => get_field('banner__button_name'),
					//'categories' => FrontPage::getCategories(),
                    'about' => [
                        'title' => get_field('about_title'),
                        'text' => get_field('about_text'),
						'lists' => get_field('about_list'),
						'image' => get_field('about_image'),
                    ],
                    'packageGroups' => [
                        [
                            'title' => get_field('packages_title'),
                            'products' => $groups,
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
        // echo '<pre>';
			// print_r($this->context);
		// echo '</pre>';	
		return $this->context;
    }

}
