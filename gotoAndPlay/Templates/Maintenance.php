<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Template as Template;

class Maintenance extends Template {

    protected $view = '@view-maintenance';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();

                $maintenanceModal = false;

                $cards = get_field('pricing_cards');
                foreach ($cards as $key => $card) {
                    $card['modifier'] = 'meeting-package--maintenance';
                    $card['icon']     = '';
                    $card['time']     = '';

                    if( $card['button_is_link'] ) {
                        $card['button']   = [
                            'text' => $card['button_text'],
                            'link' => $card['button_url'],
                        ];
                    } else {
                        $card['button']   = [
                            'text' => $card['button_text'],
                            'link' => '#maintenance',
                            'class' => 'js-modaal',
                            'customAttributes' => 'data-custom-class="modaal--small"',
                        ];

                        $maintenanceModal = true;
                    }

                    $cards[$key]      = $card;
                }

                $extras = get_field('extras');
                foreach ($extras as $key => $extra) {
                    $extra['reverse'] = true;
                    if ($extra['image_id']) {
                        $extra['image'] = [
                            'srcset' => Helpers::getImageSrcSet($extra['image_id'], '640x360'),
                            'alt' => $extra['title'],
                        ];
                    } else {
                        $extra['class'] = 'is-playing';
                        $extra['image'] = [];
                    }

                    $extras[$key] = $extra;
                }

                $this->context = [
                    'hero' => [
                        'bg' => Helpers::getHeroBackground(),
                        'title' => get_field('hero_title'),
                        'description' => get_field('hero_subtitle'),
                        'ctaButton2' => [
                            'text' => get_field('hero_button_text_scroll'),
                            'link' => '#events-terms',
                            'class' => 'js-scrollto-accordion',
                        ],
                    ],
                    'maintenanceAccordion' => [
                        'title' => get_field('maintenance_accordion_title'),
                        'content' => [
                            'title' => get_field('maintenance_title'),
                            'description' => get_field('maintenance_description'),
                            'longDescription' => get_field('maintenance_content'),
                            'image' => [
                                'srcset' => Helpers::getImage(get_field('maintenance_image'), '1170x800'),
                                'alt' => get_field('maintenance_title'),
                            ],
                        ],
                    ],
                    'pricingAccordion' => [
                        'title' => get_field('pricing_accordion_title'),
                        'content' => [
                            'title' => get_field('pricing_title'),
                            'subtitle' => get_field('pricing_subtitle'),
                            'cards' => $cards,
                        ],
                    ],
                    'extraAccordion' => [
                        'title' => get_field('extra_title'),
                        'content' => [
                            'splitRows' => $extras,
                        ],
                    ],
                    'contactPerson' => [
                        'image' => Helpers::getImage(get_field('contact_image'), '85w'),
                        'image2x' => Helpers::getImage(get_field('contact_image'), '170w'),
                        'title' => get_field('contact_title'),
                        'email' => get_field('contact_email'),
                        'telephone' => get_field('contact_phone'),
                    ],
                ];

                if( get_field('hero_button_is_link_modal') ) {
                    $this->context['hero']['ctaButton'] = [
                        'text' => get_field('hero_button_text_modal'),
                        'link' => get_field('hero_button_link_modal'),
                    ];
                } else {
                    $this->context['hero']['ctaButton'] = [
                        'text' => get_field('hero_button_text_modal'),
                        'class' => 'js-modaal',
                        'link' => '#maintenance',
                        'customAttributes' => 'data-custom-class="modaal--small"',
                    ];

                    $maintenanceModal = true;
                }

                if( $maintenanceModal ) {
                    $this->context['maintananceModal'] = [
                        'id' => 'maintenance',
                        'modifier' => 'modaal--small',
                        'title' => get_field('form_title'),
                        'gravityForm' => (get_field('form_id') ? do_shortcode('[gravityform id="' . get_field('form_id') . '" title="false" description="false" ajax="true"]') : ''),
                    ];
                } else {
                    $this->context['maintananceModal'] = false;
                }
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
