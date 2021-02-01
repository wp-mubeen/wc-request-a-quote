<?php
/**
 * Quote details in my Account.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/addify/rfq/front/quote-list-table.php.
 *
 * @package addify-request-a-quote
 * @version 1.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! empty( $customer_quotes ) ) {
	?>
	<table class="shop_table shop_table_responsive my_account_orders my_account_quotes">
		<thead>
			<tr>
				<th><?php echo esc_html__( 'Quote', 'addify_rfq' ); ?></th>
				<th><?php echo esc_html__( 'Status', 'addify_rfq' ); ?></th>
				<th><?php echo esc_html__( 'Date', 'addify_rfq' ); ?></th>
				<th><?php echo esc_html__( 'Action', 'addify_rfq' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $customer_quotes as $quote ) {
				$quote_status = get_post_meta( $quote->ID, 'quote_status', true );
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( wc_get_endpoint_url( 'request-quote', $quote->ID ) ); ?>">
							<?php echo esc_html__( '#', 'addify_rfq' ) . intval( $quote->ID ); ?>
						</a>
					</td>
					<td>
						<?php echo isset( $statuses[ $quote_status ] ) ? esc_html( $statuses[ $quote_status ] ) : 'Pending'; ?>
					</td>
					<td>
						<time datetime="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( $quote->post_date ) ) ); ?>" title="<?php echo esc_attr( strtotime( $quote->post_date ) ); ?>"><?php echo esc_attr( date_i18n( get_option( 'date_format' ), strtotime( $quote->post_date ) ) ); ?></time>
					</td>							
					<td>
						<a href="<?php echo esc_url( wc_get_endpoint_url( 'request-quote', $quote->ID ) ); ?>" class="woocommerce-button button view">
							<?php echo esc_html__( 'View', 'addify_rfq' ); ?>
						</a>
						<!-- <?php if ( 'yes' === get_option( 'enable_pdf_download' ) ) : ?>
							<a class="woocommerce-button button download" data-quote_id="<?php echo esc_attr( $quote->ID ); ?>">
								<?php echo esc_html__( 'Download', 'addify_rfq' ); ?>
							</a>
						<?php endif; ?> -->
					</td>
				</tr>
			<?php } ?>
		</tbody>
	</table>

<?php } else { ?>

	<div class="woocommerce-MyAccount-content">
		<div class="woocommerce-notices-wrapper"></div>
		<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">
			<a class="woocommerce-Button button" href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"><?php echo esc_html__( 'Go to shop', 'addify_rfq' ); ?></a><?php echo esc_html__( 'No quote has been made yet.', 'addify_rfq' ); ?></div>
	</div>
	<?php
}
