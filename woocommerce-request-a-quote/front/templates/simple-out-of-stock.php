<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

if ( ! isset( $afrfq_custom_button_text ) || empty( $afrfq_custom_button_text ) ) {
	$afrfq_custom_button_text = __( 'Add to Quote', 'addify_rfq' );
}

$pageurl = get_page_link( get_option( 'addify_atq_page_id', true ) );

if ( $product->is_in_stock() || 'yes' === get_option( 'enable_o_o_s_products' ) ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
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

		echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . intval( $product->get_ID() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="afrfqbt_single_page button alt single_add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a><div class="added_quote_pro" id="added_quote' . intval( $product->get_id() ) . '">' . esc_html( get_option( 'afrfq_pro_success_message' ) ) . '<br /><a href="' . esc_url( $pageurl ) . '">' . esc_html( get_option( 'afrfq_view_button_message' ) ) . '</a></div>';
		?>
		
	</form>

	<?php 
	do_action( 'addify_after_add_to_quote_button' );
	do_action( 'woocommerce_after_add_to_cart_form' ); 
	?>

<?php endif; ?>
