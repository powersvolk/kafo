<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Review;
use gotoAndPlay\Models\Training;
use gotoAndPlay\Template as Template;

class Trainings extends Template {

    protected $view = '@view-trainings';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $cta           = get_field('cta_button');
                $emo           = new Module('emo', get_the_ID());
                $this->context = [
                    'hero' => [
                        'modifier' => 'hero',
                        'bg' => Helpers::getHeroBackground(),
                        'title' => get_field('hero_title'),
                        'description' => get_field('hero_description'),
                        'ctaButton' => [
                            'text' => $cta['text'],
                            'link' => $cta['link'],
                            'class' => 'h-scrollto',
                        ],
                    ],
                    'cardsTitle' => get_field('cards_title'),
                    'cardsContent' => get_field('cards_content'),
                    'cards' => Training::getCards(get_field('trainings')),
                    'emoModule' => $emo->getContext(),
                    'reviewSlider' => Review::getSlider(get_the_ID()),
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
