<?php
namespace gotoAndPlay;

use gotoAndPlay\Models\Checkout;
use gotoAndPlay\Models\Pricelist;
use gotoAndPlay\Models\Cart;
use gotoAndPlay\Utils\TavexCrm;

class Strings {

    public static function getAll() {
        return [
            'title' => [
                'menu' => __('Menüü', 'kafo'),
                'myMenu' => __('Minu kasutaja', 'kafo'),
                'rating' => __('Loe arvustusi', 'kafo'),
                'feedback' => __('Klientide tagasiside', 'kafo'),
                'cartSummary' => __('Tellimuse kokkuvõte', 'kafo'),
                'cartProduct' => __('Toode', 'kafo'),
                'cartQnt' => __('Kogus', 'kafo'),
                'cartPrice' => __('Tükihind', 'kafo'),
                'cartTotal' => __('Kokku', 'kafo'),
                'shipping' => __('Transport', 'kafo'),
                'payments' => __('Makseviis', 'kafo'),
            ],
            'button' => [
                'addCart' => __('Lisa ostukorvi', 'kafo'),
                'updateCart' => __('Uuenda ostukorvi', 'kafo'),
                'filterModal' => __('Filter', 'kafo'),
                'viewMore' => __('Vaata veel', 'kafo'),
                'back' => __('Tagasi', 'kafo'),
                'viewAll' => __('Vaata kõiki', 'kafo'),
                'addCode' => __('Lisa kood', 'kafo'),
            ],
            'label' => [
                'priceBuy' => __('Hind ostes', 'kafo'),
                'priceRent' => __('Hind rendtides', 'kafo'),
                'priceLease' => __('Hind järelmaksuga', 'kafo'),
                'cartAmount' => __('Kogus', 'kafo'),
                'cartRemove' => __('Eemalda', 'kafo'),
                'checkoutUser' => __('Sinu andmed', 'kafo'),
                'checkoutBusiness' => __('Äriklient?', 'kafo'),
                'noProducts' => __('Tooteid ei leitud', 'kafo'),
                'addReview' => __('Lisa tagasiside', 'kafo'),
                'print' => __('Prindi', 'kafo'),
                'download' => __('Laadi alla', 'kafo'),
                'emptyCart' => __('Tooteid pole lisatud!', 'kafo'),
                'defaultAddress' => __('Vaikimisi aadress', 'kafo'),
                'transportLabel' => __('Transport', 'kafo'),
                'pickupLabel' => __('Tulen ise järgi', 'kafo'),
                'loader' => __('Laeme tulemusi', 'kafo'),
            ],
        ];
    }

}
