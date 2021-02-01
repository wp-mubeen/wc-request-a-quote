<?php
/**
 * Single variation cart button
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input(
		array(
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
			'input_value' => $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
		)
	);

	do_action( 'woocommerce_after_add_to_cart_quantity' );

	$pageurl = get_page_link( get_option( 'addify_atq_page_id', true ) );

	echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . intval( $product->get_ID() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="afrfqbt_single_page button single_add_to_cart_button alt product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a><div class="added_quote_pro" id="added_quote' . intval( $product->get_id() ) . '">' . esc_html( get_option( 'afrfq_pro_success_message' ) ) . '<br /><a href="' . esc_url( $pageurl ) . '">' . esc_html( get_option( 'afrfq_view_button_message' ) ) . '</a></div>';
	do_action( 'addify_after_add_to_quote_button' );
	do_action( 'woocommerce_after_add_to_cart_button' );
	?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
