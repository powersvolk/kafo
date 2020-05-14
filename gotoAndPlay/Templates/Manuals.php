<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Context as Context;

class Manuals extends Template {

    protected $view = '@view-manuals';

    private $context;

    private static $ID;

    public static function getId() {
        return self::$ID;
    }

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $tabs    = get_field('manual_tabs');
                $results = [];
                if ($tabs) {
                    foreach ($tabs as $key => $tab) {
                        $result = [
                            'accordionSimple' => [
                                'id' => 'modal-accordion' . $key,
                                'modifier' => 'accordion--simple',
                                'accordionItems' => [],
                            ],
                            'accordionManuals' => [
                                'id' => 'manuals-accordion' . $key,
                                'modifier' => 'accordion--light',
                                'triggerIcons' => [
                                    [
                                        'icon' => 'ic_24_dropdown',
                                    ],
                                ],
                                'accordionItems' => [],
                            ],
                        ];
                        $count  = 0;
                        foreach ($tab['manuals'] as $manual) {
                            $accordion = [
                                'title' => $manual['title'],
                                'manuals' => [],
                            ];
                            foreach ($manual['files'] as $file) {
                                $accordion['manuals'][] = [
                                    'url' => $file['file']['url'],
                                    'name' => ($file['filename'] ? $file['filename'] : $file['file']['filename']),
                                ];
                            }

                            $result['accordionManuals']['accordionItems'][] = $accordion;
                            $count++;
                        }

                        $result['accordionSimple']['accordionItems'][] = [
                            'title' => $tab['title'] . sprintf(' (<span class="js-accordion-title-count">%s</span>)', $count),
                        ];

                        $results[] = $result;
                    }
                }

                $this->context = [
                    'intro' => [
                        'modifier' => 'intro--manuals',
                        'bg' => Helpers::getHeroBackground(self::getId()),
                        'title' => get_field('manual_title'),
                        'text' => get_field('manual_text'),
                    ],
                    'searchForm' => [
                        'groups' => [],
                    ],
                    'results' => $results,
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
