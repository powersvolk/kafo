<?php
/*
 * Template Name: Checkout
 */
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        if ( is_order_received_page() ) {
            global $wp;
            gotoAndPlay\Template::render( 'ConfirmationPage', $wp->query_vars['order-received'] );
        } else if ( is_checkout_pay_page() ) {
            gotoAndPlay\Template::render( 'CheckoutPay' );
        } else {
            gotoAndPlay\Template::render( 'Checkout' );
        }
    }
}
