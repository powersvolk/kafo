<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Context as Context;

class Terms extends Template {

    protected $view = '@view-terms-and-conditions';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $this->context = [
                    'title' => get_the_title(),
                    'paragraph' => Helpers::getFormattedContent(get_the_content()),
                    'brands' => Context::getBrands(),
                    'quote' => Context::getQuote(),
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
