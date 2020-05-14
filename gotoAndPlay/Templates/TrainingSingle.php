<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Models\Training;
use gotoAndPlay\Template as Template;

class TrainingSingle extends Template {

    protected $view = '@view-single-training';

    private $context;

    public function __construct() {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $training      = new Training();
                $this->context = [
                    'socialMedia' => $training->getSocial(),
                    'registering' => $training->getModal(),
                    'trainingTitle' => $training->getTitle(),
                    'content' => $training->getContent(),
                    'details' => $training->getDetails(),
                    'notes' => $training->getNotes(),
                    'author' => $training->getAuthor(),
                    'tags' => $training->getTags(),
                    'cardsTitle' => $training->getCardTitle(),
                    'cardsContent' => $training->getCardContent(),
                    'cards' => Training::getCards($training->getCardIds()),
                ];
            }
        }
    }

    public function getContextFields() {
        return $this->context;
    }

}
