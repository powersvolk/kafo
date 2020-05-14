<?php

namespace gotoAndPlay\Models;

use gotoAndPlay\Helpers;

class Popup {

    public static function getContext() {
        $context = [];

        if( have_rows('popups', 'option') ):
            $popupIndex = 0;

            while ( have_rows('popups', 'option') ) : the_row();
                $popupImage = get_sub_field('popup_image');

                $context = [
                    'settings' => [
                        'id' => 'popup-' . $popupIndex,
                        'is_active' => ( get_sub_field('popup_is_active') && !isset($_COOKIE['hide-popup-' . $popupIndex]) ) ? 1 : 0,
                        'listid' => get_sub_field('popup_list_id'),
                        'timeout' => get_sub_field('popup_trigger_time')
                    ],
                    'image' => [
                        'srcset' => Helpers::getImageSrcSet($popupImage['ID'], '992x458'),
                        'alt' => $popupImage['alt']
                    ],
                    'content' => [
                        'title' => get_sub_field('popup_title')
                    ],
                    'form' => [
                        'title' => get_sub_field('popup_form_title'),
                        'text' => get_sub_field('popup_form_text'),
                        'field' => [
                            'id' => 'newsletter-popup',
                            'label' => get_sub_field('popup_form_field_label')
                        ],
                        'button' => [
                            'text' => get_sub_field('popup_form_button_text')
                        ],
                        'success' => [
                            'title' => get_sub_field('popup_thankyou_title'),
                            'content' => get_sub_field('popup_thankyou_content')
                        ]
                    ]
                ];

                $popupIndex++;
            endwhile;
        endif;

        return $context;
    }
}
