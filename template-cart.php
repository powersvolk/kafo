<?php
/*
 * Template Name: Cart
 */
if (have_posts()) {
    while (have_posts()) {
        the_post();
        gotoAndPlay\Template::render('Cart');
    }
}
