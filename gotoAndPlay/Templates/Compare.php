<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Context;
use gotoAndPlay\Helpers;
use gotoAndPlay\Models\Module;
use gotoAndPlay\Models\Product;
use gotoAndPlay\Template;

class Index extends Template {

    protected $view = '@view-compare';

    private $context;

    public function __construct() {
        $this->posts = [];
        if (have_posts()) {
            while (have_posts()) {
                the_post();
				$emo    = new Module('emo', get_the_ID());
                $this->context = [
                    'title' => get_the_title(),
                    'content' => Helpers::getFormattedContent(get_the_content()),
					'emoModule'  => $emo->getContext(),
                ];
				
				
            }
        }
    }

   public function getContextFields() {
        // echo '<pre>';
				// print_r($this->context);
				// echo '</pre>';
		return $this->context;
    }

}