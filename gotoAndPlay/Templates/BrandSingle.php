<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Models\Brand;
use gotoAndPlay\Template as Template;

class BrandSingle extends Template {

    protected $view = '@view-single-brand';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $brand         = new Brand();
                $articles      = Brand::getCards(Brand::getOtherBrands($brand->getId()));
                $this->context = [
                    'coverImage' => $brand->getCoverImage(),
                    'coverImageCaption' => $brand->getCoverImageCaption(),
                    'socialMedia' => $brand->getSocial(),
                    'recipeTitle' => $brand->getTitle(),
                    'content' => $brand->getContent(),
                    'author' => $brand->getAuthor(),
                    'tags' => $brand->getTags(),
                    'relatedTitle' => ($articles ? __('Teised kaubamärgid', 'kafo') : ''),
                    'articles' => $articles,
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
