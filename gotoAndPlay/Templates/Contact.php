<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Context as Context;

class Contact extends Template {

    protected $view = '@view-contacts';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $generalButton             = get_field('general_button');
                $generalButton['element']  = 'a';
                $generalButton['modifier'] = 'button--small';
                $generalButton['class']    = 'contact__navigation-button';
                $generalButton['icon']     = 'location';
                $locations                 = get_field('locations');
                foreach ($locations as $key => $location) {
                    $location['value'] = 'address' . $key;
                    $location['map']   = [
                        'triggerBackground' => Helpers::getImageSrcSet($location['image'], '288x163'),
                        'triggerText' => __('Vaata kaarti', 'kafo'),
                        'link' => $location['google_maps_link'],
                    ];
                    $locations[$key]   = $location;
                }

                $members = get_field('team_members');
                foreach ($members as $key => $member) {
                    $member['image'] = Helpers::getImageSrcSet($member['image_id'], '276x276');
                    $members[$key]   = $member;
                }

                $this->context = [
                    'hero' => [
                        'generalSection' => [
                            'background' => (get_field('general_image') ? Helpers::getImageSrcSet(get_field('general_image'), '748w') : ''),
                            'title' => get_field('general_title'),
                            'contactGroups' => get_field('general_groups'),
                            'navigationButton' => $generalButton,
                            'contactLinks' => [
                                [
                                    'element' => 'a',
                                    'text' => __('Tutvu KAFO põhimõtetega', 'kafo'),
                                    'link' => '#principles',
                                    'modifier' => 'button--naked',
                                    'class' => 'contact__link js-scrollto-accordion',
                                    'icon' => 'arrow-right',
                                    'iconAlign' => 'right',
                                ],
                                [
                                    'element' => 'a',
                                    'text' => __('Tutvu tiimiga', 'kafo'),
                                    'link' => '#team-members',
                                    'modifier' => 'button--naked',
                                    'class' => 'contact__link js-scrollto-accordion',
                                    'icon' => 'arrow-right',
                                    'iconAlign' => 'right',
                                ],
                            ],
                        ],
                        'locationsSection' => [
                            'background' => (get_field('location_image') ? Helpers::getImageSrcSet(get_field('location_image'), '760w') : ''),
                            'title' => get_field('location_title'),
                            'locations' => $locations,
                        ],
                    ],
                    'principles' => [
                        'title' => get_field('princibles_title'),
                        'logo' => [
                            'srcset' => (get_field('princibles_logo') ? Helpers::getImageSrcSet(get_field('princibles_logo'), '245x90') : ''),
                            'alt' => get_field('princibles_title'),
                        ],
                        'content' => get_field('princibles_content'),
                    ],
                    'team' => [
                        'title' => get_field('team_title'),
                        'list' => [
                            'title' => get_field('team_subtitle'),
                            'subtitle' => get_field('team_content'),
                            'cards' => $members,
                        ],
                    ],
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
