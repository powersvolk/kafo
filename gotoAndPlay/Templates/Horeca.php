<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Package;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;

class Horeca extends Template {

    protected $view = '@view-horeca';

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
                            'link'  => '#packages',
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
                    'categoriesTitle'   => (get_field('categories_title') ? get_field('categories_title') : get_field('home_cat_title', FrontPage::getId())),
                    'categoriesContent' => (get_field('categories_content') ? get_field('categories_content') : get_field('home_cat_content', FrontPage::getId())),
                    'categories'        => FrontPage::getCategories(),
                    'accordion'         => [
                        'title'   => get_field('accordion_title'),
                        'content' => [
                            'image'   => [
                                'srcset' => Helpers::getImage(get_field('content_image'), '470x428'),
                                'alt'    => get_field('content_title'),
                            ],
                            'title'   => get_field('content_title'),
                            'content' => get_field('content_text'),
                        ],
                    ],
                    'packageGroups'     => $groups,
                    'emoModule'         => $emo->getContext(),
                    'reviewSlider'      => Review::getSlider(get_the_ID()),
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
