<?php
/**
 * Mini-cart
 *
 * Contains the drop down items of mini quote basket.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/addify/rfq/quote/mini-quote.php.
 *
 */

defined( 'ABSPATH' ) || exit;

$quotes           = (array) WC()->session->get( 'quotes' );
$pageurl          = get_page_link( get_option( 'addify_atq_page_id', true ) );
$quote_item_count = 0;

foreach ( $quotes as $qoute_item ) {

	$quote_item_count += isset( $qoute_item['quantity'] ) ? $qoute_item['quantity'] : 0;
}

if ( 'icon' === get_option( 'afrfq_basket_option' ) ) : ?>
	<li id="quote-li-icon" class="quote-li">
		<a href="<?php echo esc_url( $pageurl ); ?>" title="<?php echo esc_html__( 'View Quote', 'addify_rfq' ); ?>">
			<span class="dashicons dashicons-cart dashiconsc"></span>
			<span id="total-items-count" class="totalitems"> <?php echo esc_attr( $quote_item_count ); ?> </span>
		</a>
	</li>
<?php endif; ?>

<?php if ( 'dropdown' === get_option( 'afrfq_basket_option' ) ) : ?>

	<li id="quote-li" class="quote-li">
		<a href="<?php echo esc_url( $pageurl ); ?>" title="<?php echo esc_html__( 'View Quote', 'addify_rfq' ); ?>">
			<span class="dashicons dashicons-cart dashiconsc"></span>
			<span id="total-items" class="totalitems">
				<?php echo esc_attr( $quote_item_count ) . esc_html__( ' items in quote', 'addify_rfq' ); ?>
			</span>
		</a>
		<?php
		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote-dropdown.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote-dropdown.php';

		} else {

			include AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote-dropdown.php';
		}
		?>
	<li>
<?php endif; ?>
