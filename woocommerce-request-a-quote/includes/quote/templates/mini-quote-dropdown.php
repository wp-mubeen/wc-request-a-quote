<?php
/**
 * Mini-cart
 *
 * Contains the drop down items of mini quote basket.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/addify/rfq/quote/mini-quote-dropdown.php.
 *
 */

defined( 'ABSPATH' ) || exit;

$af_quote     = new AF_R_F_Q_Quote();
$quote_totals = $af_quote->get_calculated_totals( WC()->session->get( 'quotes' ) );

$price_display    = 'yes' === get_option( 'afrfq_enable_pro_price' ) ? true : false;
$of_price_display = 'yes' === get_option( 'afrfq_enable_off_price' ) ? true : false;
$tax_display      = 'yes' === get_option( 'afrfq_enable_tax' ) ? true : false;

$subtotal = $quote_totals['_subtotal'];

do_action( 'addify_rfq_before_mini_quote' ); ?>

<div class="mini-quote-dropdown">
<?php if ( ! empty( WC()->session->get( 'quotes' ) ) ) : ?>

	<ul class="addify-rfq-mini-cart quote_list product_list_widget">
		<?php
		do_action( 'addify_rfq_before_mini_quote_contents' );

		foreach ( WC()->session->get( 'quotes' ) as $quote_item_key => $quote_item ) {
			$_product   = apply_filters( 'addify_rfq_cart_item_product', $quote_item['data'], $quote_item, $quote_item_key );
			$product_id = apply_filters( 'addify_rfq_cart_item_product_id', $quote_item['product_id'], $quote_item, $quote_item_key );

			if ( $_product && $_product->exists() && $quote_item['quantity'] > 0 && apply_filters( 'addify_rfq_widget_cart_item_visible', true, $quote_item, $quote_item_key ) ) {
				$product_name      = apply_filters( 'addify_rfq_cart_item_name', $_product->get_name(), $quote_item, $quote_item_key );
				$thumbnail         = apply_filters( 'addify_rfq_cart_item_thumbnail', $_product->get_image(), $quote_item, $quote_item_key );
				$product_price     = apply_filters( 'addify_rfq_cart_item_price', WC()->cart->get_product_price( $_product ), $quote_item, $quote_item_key );
				$product_permalink = apply_filters( 'addify_rfq_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $quote_item ) : '', $quote_item, $quote_item_key );
				?>
				<li class="addify-rfq-mini-cart-item <?php echo esc_attr( apply_filters( 'addify_rfq_mini_quote_item_class', 'mini_quote_item', $quote_item, $quote_item_key ) ); ?>">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo wp_kses_post( apply_filters( 
						'addify_rfq_cart_item_remove_link',
						sprintf(
							'<a href="%s" class="quote-remove remove_from_quote_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
							esc_url( '' ),
							esc_attr__( 'Remove this item', 'addify_rfq' ),
							esc_attr( $product_id ),
							esc_attr( $quote_item_key ),
							esc_attr( $_product->get_sku() )
						),
						$quote_item_key
					) );
					?>
					<?php if ( empty( $product_permalink ) ) : ?>
						<?php echo wp_kses_post( $thumbnail . $product_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else : ?>
						<a href="<?php echo esc_url( $product_permalink ); ?>">
							<?php echo  wp_kses_post( $thumbnail . $product_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>
					<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $quote_item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					if ( $price_display ) :

						echo wp_kses_post( apply_filters( 'addify_rfq_widget_cart_item_quantity', '<span class="quantity">' . sprintf( '%s &times; %s', $quote_item['quantity'], $product_price ) . '</span>', $quote_item, $quote_item_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					endif;
					?>
				</li>
				<?php
			}
		}

		do_action( 'addify_rfq_mini_quote_contents' );
		?>
	</ul>
	<?php if ( $price_display ) : ?>
		<p class="addify-rfq-mini-cart__total total">
			<strong><?php echo esc_html__( 'Subtotal', 'addify_rfq' ); ?>:</strong>
			<?php echo wp_kses_post( wc_price( $subtotal ) ); ?>
		</p>
	<?php endif; ?>
	<?php do_action( 'addify_rfq_widget_shopping_quote_before_buttons' ); ?>

	<p class="addify-rfq-mini-cart__buttons buttons"><?php do_action( 'addify_rfq_widget_shopping_quote_buttons' ); ?>
		<a href="<?php echo esc_url( $pageurl ); ?>" class="btn wc-forward" id="view-quote">
			<?php echo esc_html__( ' View Quote', 'addify_rfq' ); ?>
		</a>                                           
	</p>
	<?php do_action( 'addify_rfq_widget_shopping_quote_after_buttons' ); ?>

<?php else : ?>

	<p class="addify-rfq-mini-cart__empty-message"><?php esc_html_e( 'No products in the Quote Basket.', 'addify_rfq' ); ?></p>

<?php endif; ?>
</div>

<?php do_action( 'addify_rfq_after_mini_quote' ); ?>
