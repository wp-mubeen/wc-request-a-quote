<?php
/**
 * Settings for cart restrictions
 *
 * @package  woocommerce-request-a-quote
 * @version  1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$_nonce = isset( $_POST['afrfq_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['afrfq_nonce_field'] ) ) : 0;

if ( isset( $_POST['afrfq_nonce_field'] ) && ! wp_verify_nonce( $_nonce, 'afrfq_nonce_action' ) ) {
	die( 'Failed Security Check' );
}

if ( isset( $_GET['tab'] ) ) {
	$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
} else {
	$active_tab = 'general';
}

?>
<div class="addify-rfq-settings">
	<div class="wrap woocommerce">
		<h2><?php echo esc_html__( 'Request a Quote Settings', 'addify_rfq' ); ?></h2>
		<?php settings_errors(); ?> 
		<h2 class="nav-tab-wrapper">
			<a href="?post_type=addify_quote&page=af-rfq-settings&tab=general" class="nav-tab <?php echo esc_attr( $active_tab ) === 'general' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'General', 'addify_rfq' ); ?>
			</a>
			<a href="?post_type=addify_quote&page=af-rfq-settings&tab=messages" class="nav-tab <?php echo esc_attr( $active_tab ) === 'messages' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Custom Messages', 'addify_rfq' ); ?>
			</a>
			<a href="?post_type=addify_quote&page=af-rfq-settings&tab=emails" class="nav-tab <?php echo esc_attr( $active_tab ) === 'emails' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Emails', 'addify_rfq' ); ?>
			</a>
			<a href="?post_type=addify_quote&page=af-rfq-settings&tab=captcha" class="nav-tab <?php echo esc_attr( $active_tab ) === 'captcha' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Google Captcha', 'addify_rfq' ); ?>
			</a>
			<a href="?post_type=addify_quote&page=af-rfq-settings&tab=editors" class="nav-tab <?php echo esc_attr( $active_tab ) === 'editors' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Page builders', 'addify_rfq' ); ?>
			</a>
			<a href="?post_type=addify_quote&page=af-rfq-settings&tab=attributes" class="nav-tab <?php echo esc_attr( $active_tab ) === 'attributes' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__( 'Quote Attributes', 'addify_rfq' ); ?>
			</a>
		</h2>
	</div>
	<form method="post" action="options.php" class="afrfq_options_form"> 	
	<?php

	if ( 'general' === $active_tab ) {

		settings_fields( 'afrfq_general_setting_fields' );
		do_settings_sections( 'afrfq_general_setting_section' );

	} elseif ( 'messages' === $active_tab ) {

		settings_fields( 'afrfq_messages_fields' );
		do_settings_sections( 'afrfq_messages_section' );

	} elseif ( 'emails' === $active_tab ) {

		settings_fields( 'afrfq_emails_fields' );
		do_settings_sections( 'afrfq_emails_section' );

	} elseif ( 'captcha' === $active_tab ) {

		settings_fields( 'afrfq_captcha_fields' );
		do_settings_sections( 'afrfq_captcha_section' );

	} elseif ( 'editors' === $active_tab ) {

		settings_fields( 'afrfq_editors_fields' );
		do_settings_sections( 'afrfq_editors_section' );

	} elseif ( 'attributes' === $active_tab ) {

		settings_fields( 'afrfq_attributes_fields' );
		do_settings_sections( 'afrfq_attributes_section' );

	}
		submit_button();
	?>
	</form>	
</div>
