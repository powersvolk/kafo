<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;
use gotoAndPlay\Theme;
use RGFormsModel;

class Meetings extends Template {

    protected $view = '@view-meetings';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $form     = RGFormsModel::get_form(get_field('meetings_form_id'));
                $packages = get_field('packages');
                foreach ($packages as $key => $package) {
                    $package['modifier'] = '';
                    $package['icon']     = 'check';
                    $package['button']   = [
                        'link' => '#reservation',
                        'text' => $package['button_text'],
                        'class' => 'js-modaal js-meetings-package-button',
                        'customAttributes' => 'data-custom-class="modaal--split" data-package-id="' . $package['title'] . '"',
                    ];
                    $packages[$key]      = $package;
                }

                Theme::addDataForFrontend('packages', $packages);

                $this->context = [
                    'hero' => [
                        'bg' => Helpers::getHeroBackground(),
                        'title' => get_field('hero_title'),
                        'description' => get_field('hero_subtitle'),
                        'ctaButton' => [
                            'class' => 'js-meetings-button',
                            'text' => get_field('hero_button_text'),
                            'customAttributes' => 'data-custom-class="modaal--split"',
                            'modal' => [
                                'id' => 'reservation',
                                'title' => $form->title,
                                'gravityForm' => do_shortcode('[gravityform id="' . $form->id . '" title="false" description="false" ajax="true"]'),
                                'meetingDetails' => [
                                    'title' => '<span class="js-meetings-package-title"></span>',
                                    'priceLabel' => __('Hind', 'kafo'),
                                    'price' => '<span class="js-meetings-package-price"></span>',
                                    'lengthLabel' => __('Kestvus', 'kafo'),
                                    'length' => '<span class="js-meetings-package-length"></span>',
                                    'locationLabel' => __('Asukoht', 'kafo'),
                                    'location' => '<span class="js-meetings-package-location"></span>',
                                    'nextLabel' => __('Mis edasi?', 'kafo'),
                                    'next' => __('Saadame sinu emailile kinnituse koos arvega ja täpsustame ajagraafikud.', 'kafo'),
                                ],
                            ],
                        ],
                    ],
                    'features' => get_field('features'),
                    'properties' => [
                        'modifier' => 'properties--meeting',
                        'propertiesTitle' => get_field('content_title'),
                        'propertiesContent' => get_field('content_text'),
                        'propertiesListTitle' => get_field('content_list_title'),
                        'propertiesList' => get_field('content_list'),
                        'contactPerson' => [
                            'image' => Helpers::getImage(get_field('contact_image'), '85w'),
                            'image2x' => Helpers::getImage(get_field('contact_image'), '170w'),
                            'title' => get_field('contact_title'),
                            'email' => get_field('contact_email'),
                            'telephone' => get_field('contact_phone'),
                        ],
                        'propertiesImage' => [
                            'srcset' => Helpers::getImageSrcSet(get_field('content_image'), '1170x800'),
                            'alt' => get_field('content_title'),
                        ],
                    ],
                    'meetingPackages' => [
                        'title' => get_field('packages_accordion_title'),
                        'packages' => [
                            'meeting' => [
                                'title' => get_field('packages_title'),
                                'subtitle' => get_field('packages_content'),
                                'cards' => $packages,
                            ],
                        ],
                    ],
                    'reviewSlider'      => Review::getSlider(get_the_ID()),
                    'emo1' => true,
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
