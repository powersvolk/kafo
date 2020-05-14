<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Template as Template;
use gotoAndPlay\Helpers as Helpers;
use gotoAndPlay\Context as Context;

class Index extends Template {

    protected $view = '@view-index';

    private $posts = [];

    public function __construct() {
        $this->posts = [];
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $this->posts[] = [
                    'title' => get_the_title(),
                    'content' => Helpers::getFormattedContent(get_the_content()),
                ];
            }
        }
    }

    public function getContextFields() {
        return [
            'posts' => $this->posts,
            'pagination' => Context::getPagination(),
        ];
    }

}
