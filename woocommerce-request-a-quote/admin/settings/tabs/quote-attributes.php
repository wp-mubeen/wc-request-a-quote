<?php
/**
 * Quote attributes tab fields
 *
 * @package  woocommerce-request-a-quote
 * @version  1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_settings_section(
	'afrfq-attributes-sec',         // ID used to identify this section and with which to register options.
	esc_html__( 'Quote Attributes Settings', 'addify_acr' ),   // Title to be displayed on the administration page.
	'', // Callback used to render the description of the section.
	'afrfq_attributes_section'                           // Page on which to add this section of options.
);

add_settings_field(
	'afrfq_enable_pro_price',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Enable product price', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_enable_pro_price_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_attributes_section',                          // The page on which this option will be displayed.
	'afrfq-attributes-sec',         // The name of the section to which this field belongs.
	array( esc_html__( 'Enable product price, subtotal and total of quote basket.', 'addify_acr' ) )
);

register_setting(
	'afrfq_attributes_fields',
	'afrfq_enable_pro_price'
);

add_settings_field(
	'afrfq_enable_off_price',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Enable offered price', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_enable_off_price_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_attributes_section',                          // The page on which this option will be displayed.
	'afrfq-attributes-sec',         // The name of the section to which this field belongs.
	array( 
		esc_html__( 'Enable offered price and subtotal(offered price) of quote basket.', 'addify_acr' ),
		esc_html__( 'Note: offered price will be excluding tax if products prices are excluding tax and including tax if prices are including tax.', 'addify_acr' ),
	 )
);

register_setting(
	'afrfq_attributes_fields',
	'afrfq_enable_off_price'
);

add_settings_field(
	'afrfq_enable_tax',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Enable tax Display', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_enable_tax_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_attributes_section',                          // The page on which this option will be displayed.
	'afrfq-attributes-sec',         // The name of the section to which this field belongs.
	array( esc_html__( 'Enable tax calculation of quote basket items.', 'addify_acr' ) )
);

register_setting(
	'afrfq_attributes_fields',
	'afrfq_enable_tax'
);

add_settings_field(
	'afrfq_enable_convert_order',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Enable convert to order', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_enable_convert_order_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_attributes_section',                          // The page on which this option will be displayed.
	'afrfq-attributes-sec',         // The name of the section to which this field belongs.
	array( esc_html__( 'Enable convert to order for customers(Quote Status: In Process, Accepted.', 'addify_acr' ) )
);

register_setting(
	'afrfq_attributes_fields',
	'afrfq_enable_convert_order'
);

add_settings_field(
	'afrfq_enable_converted_by',                      // ID used to identify the field throughout the theme.
	esc_html__( 'Enable quote converter display', 'addify_acr' ),    // The label to the left of the option interface element.
	'afrfq_enable_converted_by_callback',   // The name of the function responsible for rendering the option interface.
	'afrfq_attributes_section',                          // The page on which this option will be displayed.
	'afrfq-attributes-sec',         // The name of the section to which this field belongs.
	array( esc_html__( 'Enable display of quote converted (User/Admin) in my-account quote details.', 'addify_acr' ) )
);

register_setting(
	'afrfq_attributes_fields',
	'afrfq_enable_converted_by'
);

if ( ! function_exists( 'afrfq_enable_converted_by_callback' ) ) {
	function afrfq_enable_converted_by_callback( $args = array() ) {
		?>
		<input type="checkbox" name="afrfq_enable_converted_by" id="afrfq_enable_converted_by" value="yes" <?php echo checked( 'yes', esc_attr( get_option( 'afrfq_enable_converted_by' ) ) ); ?> />
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<?php
	}
}

if ( ! function_exists( 'afrfq_enable_convert_order_callback' ) ) {
	function afrfq_enable_convert_order_callback( $args = array() ) {
		?>
		<input type="checkbox" name="afrfq_enable_convert_order" id="afrfq_enable_convert_order" value="yes" <?php echo checked( 'yes', esc_attr( get_option( 'afrfq_enable_convert_order' ) ) ); ?> />
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<?php
	}
}

if ( ! function_exists( 'afrfq_enable_tax_callback' ) ) {
	function afrfq_enable_tax_callback( $args = array() ) {
		?>
		<input type="checkbox" name="afrfq_enable_tax" id="afrfq_enable_tax" value="yes" <?php echo checked( 'yes', esc_attr( get_option( 'afrfq_enable_tax' ) ) ); ?> />
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<?php
	}
}

if ( ! function_exists( 'afrfq_enable_off_price_callback' ) ) {
	function afrfq_enable_off_price_callback( $args = array() ) {
		?>
		<input type="checkbox" name="afrfq_enable_off_price" id="afrfq_enable_off_price" value="yes" <?php echo checked( 'yes', esc_attr( get_option( 'afrfq_enable_off_price' ) ) ); ?> />
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[1] ); ?> </p>
		<?php
	}
}

if ( ! function_exists( 'afrfq_enable_pro_price_callback' ) ) {
	function afrfq_enable_pro_price_callback( $args = array() ) {
		?>
		<input type="checkbox" name="afrfq_enable_pro_price" id="afrfq_enable_pro_price" value="yes" <?php echo checked( 'yes', esc_attr( get_option( 'afrfq_enable_pro_price' ) ) ); ?> />
		<p class="description afreg_additional_fields_section_title"> <?php echo wp_kses_post( $args[0] ); ?> </p>
		<?php
	}
}
