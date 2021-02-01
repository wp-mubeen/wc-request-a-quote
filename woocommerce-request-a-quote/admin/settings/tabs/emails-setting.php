<?php
/**
 * Email settings fields
 *
 * @package  woocommerce-request-a-quote
 * @version  1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_settings_section(
	'afrfq-emails-sec',         // ID used to identify this section and with which to register options.
	esc_html__( 'Emails Settings', 'addify_acr' ),   // Title to be displayed on the administration page.
	'', // Callback used to render the description of the section.
	'afrfq_emails_section'                           // Page on which to add this section of options.
);

add_settings_field(
	'afrfq_admin_email',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Admin/Shop manager Email Address(es)', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_admin_email_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_emails_section',                          // The page on which this option will be displayed.
	'afrfq-emails-sec',         // The name of the section to which this field belongs. 
	array( esc_html__( 'All admin emails that are related to our module will be sent to this email address. If this email is empty then default admin email address is used. You can add more than one email addresses separated by comma (,).', 'addify_acr' ) )
);

register_setting(
	'afrfq_emails_fields',
	'afrfq_admin_email'
);

add_settings_field(
	'afrfq_emails',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Request a Quote Emails', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_emails_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_emails_section',                          // The page on which this option will be displayed.
	'afrfq-emails-sec',         // The name of the section to which this field belongs. 
	array( '' )
);

register_setting(
	'afrfq_emails_fields',
	'afrfq_emails'
);

if ( ! function_exists( 'afrfq_emails_callback' ) ) {
	function afrfq_emails_callback( $args = array() ) {
		include_once AFRFQ_PLUGIN_DIR . 'admin/settings/email-settings.php';
		?>
		
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<?php
	}
}

if ( ! function_exists( 'afrfq_admin_email_callback' ) ) {
	function afrfq_admin_email_callback( $args = array() ) {
		$value = get_option( 'afrfq_admin_email' );
		?>
		<input value="<?php echo esc_html( $value ); ?>" class="afrfq_input_class" type="email" multiple name="afrfq_admin_email" id="afrfq_admin_email" />
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<?php
	}
}
