<?php
/**
 * Customer information table for email.
 *
 * The WooCommerce quote class stores quote data and maintain session of quotes.
 * The quote class also has a price calculation function which calls upon other classes to calculate totals.
 *
 * @package addify-request-a-quote
 * @version 1.6.0
 */

defined( 'ABSPATH' ) || exit;

global $post;

$quote_contents = get_post_meta( $post->ID, 'quote_contents', true );
$quotes         = $quote_contents;
$quote_order    = wc_create_order();

foreach ( $quote_contents as $quote_item_key => $quote_item ) {


	if ( isset( $quote_item['data'] ) ) {
		$product = $quote_item['data'];
	} else {
		continue;
	}

	if ( ! is_object( $product ) ) {
		continue;
	}

	$price         = $product->get_price();
	$qty_display   = $quote_item['quantity'];
	$offered_price = isset( $quote_item['offered_price'] ) ? floatval( $quote_item['offered_price'] ) : $price;

	$product->set_price( $offered_price );

	$quote_order->add_product( $product, $qty_display );
}

$customer_name  = $post->afrfq_name_field;
$customer_email = $post->afrfq_email_field;
$customer_id    = get_post_meta( $post->ID, '_customer_user', true );

$quote_user = get_user_by( 'id', $customer_id );

$af_fields_obj = new AF_R_F_Q_Quote_Fields();

$billing_address  = $af_fields_obj->afrfq_get_billing_data( $post->ID );
$shipping_address = $af_fields_obj->afrfq_get_shipping_data( $post->ID );

$quote_order->set_address( $billing_address, 'billing' );
$quote_order->set_address( $shipping_address, 'shipping' );
$quote_order->set_customer_id( intval( $customer_id ) );

$quote_order->calculate_totals(); // updating totals.

$quote_order->save(); // Save the order data.

$current_admin = wp_get_current_user();

$current_admin = isset( $current_admin->ID ) ? (string) $current_admin->user_login : 'Admin';

update_post_meta( $post->ID, 'quote_status', 'af_converted' );
update_post_meta( $post->ID, 'converted_by_user', $current_admin );
update_post_meta( $post->ID, 'converted_by', __( 'Administrator', 'addify_rfq' ) );

do_action( 'addify_rfq_send_quote_email_to_customer', $post->ID );
do_action( 'addify_rfq_send_quote_email_to_admin', $post->ID );

wp_safe_redirect( $quote_order->get_edit_order_url() );
exit;
