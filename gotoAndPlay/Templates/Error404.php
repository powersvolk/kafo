<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Context;
use gotoAndPlay\Template as Template;

class Error404 extends Template {

    protected $view = '@view-404';

    public function getContextFields() {
        return [
            'title' => get_field('404_title', 'option'),
            'titleXs' => get_field('404_mobile_title', 'option'),
            'titleXs2' => get_field('404_mobile_subtitle', 'option'),
            'subtitle' => get_field('404_subtitle', 'option'),
            'button' => get_field('404_button', 'option'),
            'brands' => Context::getBrands(),
            'cards' => FrontPage::getTestimonials(),
            'cardsTitle' => FrontPage::getCardsTitle(),
            'cardsContent' => FrontPage::getCardsContent(),
            'cardsLink' => FrontPage::getCardsLinks(),
            'quote' => Context::getQuote(),
        ];
    }

}
