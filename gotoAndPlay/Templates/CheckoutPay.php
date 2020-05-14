<?php
namespace gotoAndPlay\Templates;

use gotoAndPlay\Helpers;
use gotoAndPlay\Template;

class CheckoutPay extends Template {

    protected $view = '@view-checkout--pay';

    private $context;

    public function __construct() {
        $this->context  = [
            'content' => Helpers::getFormattedContent(get_the_content()),
        ];
    }

    public function getContextFields() {
        return $this->context;
    }

}
