<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Package;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;

class Horecanew extends Template {

    protected $view = '@view-horecanew';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $emo    = new Module('emo', get_the_ID());
                $groups = get_field('packages');
                foreach ($groups as $key => $group) {
                    $productPackages = [];
                    $packages        = Package::getPackagesByIds($group['package_ids']);
                    foreach ($packages as $package) {
                        $productPackages[]['productData'] = $package->getProductData();
                    }
                    $group['products'] = $productPackages;
                    $groups[$key]       = $group;
                }

                $packages     = Package::getPackagesByIds(get_field('hero_packages'));
                $heroPackages = [];
                $nr           = 1;
                foreach ($packages as $package) {
                    $heroPackages[] = $package->getHeroData($nr);
                    $nr++;
                }

                $this->context = [
                    'hero'              => [
                        'modifier'    => 'hero--packages',
                        'bg'          => Helpers::getHeroBackground(),
                        'title'       => get_field('hero_title'),
                        'description' => get_field('hero_description'),
                        'ctaButton'   => [
                            'text'  => get_field('hero_button_text_scroll'),
                            'link'  => get_field('hero_button_link'),
                            'class' => 'h-scrollto',
                        ],
                        'packages'    => $heroPackages,
                        'thankyou'    => [
                            'modifier'   => 'modaal--centered modaal--small',
                            'headerIcon' => 'ic_24_checkmark',
                            'title'      => 'Tänud, et huvitusid meie KAFO Superstar One Paketist',
                            'id'         => 'thankyou',
                            'content'    => '<p><b>Kuidas nüüd edasi tegutseda?</b></p><p>Lihtne! Varsti võtab keegi meie kliendihuriga ühendust ja õige pea saadki enda klientidel lasta mekkida ülihead kohvi.</p>',
                        ],
                    ],
                    'features'          => get_field('features'),
                    'productTitle' => get_field('product_title'),
                    'productContent' => get_field('product_content'),
                    'products' => get_field('product_content_list'),
					'partners_title' => get_field('partners_title'),
					'partners_content' => get_field('partners_content'),
					'partners_image' => get_field('partners_image'),
					'about' => [
                        'title' => get_field('about_title'),
                        'text' => get_field('about_text'),
						'lists' => get_field('about_list'),
						'image' => get_field('about_image'),
                    ],
					'banner' => [
						'image_first' => get_field('banner_image_1'),
						'title_first' => get_field('banner_image_title'),
						'text_first' => get_field('banner_text'),
						'image_second' => get_field('banner_image_2'),
						'title_second' => get_field('banner_image_title_2'),
						'text_second' => get_field('banner_text_2'),
					],
					
					//'categoriesTitle'   => (get_field('categories_title') ? get_field('categories_title') : get_field('home_cat_title', FrontPage::getId())),
					// 'categoriesContent' => (get_field('categories_content') ? get_field('categories_content') : get_field('home_cat_content', FrontPage::getId())),
                    //'categories'        => FrontPage::getCategories(),
                    
                    'packageGroups'     => $groups,
                    'emoModule'         => $emo->getContext(),
                    'reviewSlider'      => Review::getSlider(get_the_ID()),
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
