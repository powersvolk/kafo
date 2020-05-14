<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Models\Brand;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Template as Template;

class Brands extends Template {

    protected $view = '@view-brands';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $emo     = new Module('emo');
                $columns = [];
                foreach (Brand::getAllBrands() as $brand) {
                    $columns[] = [
                        'articleData' => [
                            'columnClass' => 'grid__col--sm-4',
                            'modifier' => '',
                            'image' => [
                                'srcset' => $brand->getImage(),
                                'alt' => $brand->getTitle(),
                            ],
                            'title' => $brand->getTitle(),
                            'description' => $brand->getExcerpt(),
                            'link' => $brand->getPermalink(),
                        ],
                    ];
                }

                $this->context = [
                    'brandsTitle' => get_the_title(),
                    'emoModule' => $emo->getContext(),
                    'reviewSlider' => Review::getSlider(get_the_ID()),
                    'brands' => [
                        'columns' => $columns,
                    ],
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
