<?php
/**
 * Plugin Name:       Request a Quote for WooCommerce
 * Plugin URI:        https://woocommerce.com/products/request-a-quote-plugin/
 * Description:       Allow customers to add product(s) to quote basket and ask for a quote by submitting a simple quote form.
 * Version:           2.1.2
 * Author:            Addify
 * Developed By:      Addify
 * Author URI:        http://www.addifypro.com
 * Support:           http://www.addifypro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * Text Domain:       addify_rfq
 * Woo: 4872510:f687f573919bd78647d0bcacb5277b76
 * WC requires at least: 3.0.9
 * WC tested up to: 4.*.*
 *
 * @package woocommerce-request-a-quote
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check the installation of WooCommerce module if it is not a multi site.
if ( ! is_multisite() ) {

	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

		/**
		 * Constructor of class.
		 */
		function afrfq_admin_notice() {

			// Deactivate the plugin.
			deactivate_plugins( __FILE__ );

			$afpvu_woo_check = '<div id="message" class="error">
				<p><strong>' . __( 'WooCommerce Request a Quote plugin is inactive.', 'addify_acr' ) . '</strong> The <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce plugin</a> ' . __( 'must be active for this plugin to work. Please install &amp; activate WooCommerce.', 'addify_acr' ) . ' »</p></div>';
			echo wp_kses_post( $afpvu_woo_check );

		}

		add_action( 'admin_notices', 'afrfq_admin_notice' );
	}
}

if ( ! class_exists( 'Addify_Request_For_Quote' ) ) {

	class Addify_Request_For_Quote {

		/**
		 * Contains an array of quote items.
		 *
		 * @var array
		 */
		public $quote_fields_obj = array();

		public function __construct() {

			$this->afrfq_global_constents_vars();

			register_activation_hook( __FILE__, array( $this, 'afrfq_register_settings' ) );

			add_action( 'wp_loaded', array( $this, 'afrfq_init' ) );

			add_action( 'init', array( $this, 'afrfq_custom_post_type' ) );

			include_once AFRFQ_PLUGIN_DIR . '/includes/class-af-r-f-q-quote.php';
			include_once AFRFQ_PLUGIN_DIR . '/includes/class-af-r-f-q-quote-fields.php';
			include_once AFRFQ_PLUGIN_DIR . '/includes/class-af-r-f-q-email-controller.php';
			include_once AFRFQ_PLUGIN_DIR . '/includes/class-af-r-f-q-ajax-controller.php';
			
			if ( is_admin() ) {
				include_once AFRFQ_PLUGIN_DIR . 'admin/class-af-r-f-q-admin.php';
			} else {
				include_once AFRFQ_PLUGIN_DIR . 'front/class-af-r-f-q-front.php';
			}

			
		}

		public function afrfq_global_constents_vars() {

			if ( ! defined( 'AFRFQ_URL' ) ) {
				define( 'AFRFQ_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'AFRFQ_BASENAME' ) ) {
				define( 'AFRFQ_BASENAME', plugin_basename( __FILE__ ) );
			}

			if ( ! defined( 'AFRFQ_PLUGIN_DIR' ) ) {
				define( 'AFRFQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}
		}

		public function afrfq_init() {
			if ( function_exists( 'load_plugin_textdomain' ) ) {
				load_plugin_textdomain( 'addify_rfq', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			}
		}

		public function afrfq_custom_post_type() {

			$labels = array(
				'name'                => esc_html__( 'Request for Quote Rules', 'addify_rfq' ),
				'singular_name'       => esc_html__( 'Request for Quote Rule', 'addify_rfq' ),
				'add_new'             => esc_html__( 'Add New Rule', 'addify_rfq' ),
				'add_new_item'        => esc_html__( 'Add New Rule', 'addify_rfq' ),
				'edit_item'           => esc_html__( 'Edit Rule', 'addify_rfq' ),
				'new_item'            => esc_html__( 'New Rule', 'addify_rfq' ),
				'view_item'           => esc_html__( 'View Rule', 'addify_rfq' ),
				'search_items'        => esc_html__( 'Search Rule', 'addify_rfq' ),
				'exclude_from_search' => true,
				'not_found'           => esc_html__( 'No rule found', 'addify_rfq' ),
				'not_found_in_trash'  => esc_html__( 'No rule found in trash', 'addify_rfq' ),
				'parent_item_colon'   => '',
				'all_items'           => esc_html__( 'All Rules', 'addify_rfq' ),
				'menu_name'           => esc_html__( 'Request for Quote', 'addify_rfq' ),
				'attributes'          => esc_html__( 'Rule Priority', 'addify_rfq' ),
				'item_published'      => esc_html__( 'Quote rule published', 'addify_rfq' ),
				'item_updated'        => esc_html__( 'Quote rule updated', 'addify_rfq' ),
			);

			$args = array(
				'labels'             => $labels,
				'menu_icon'          => plugin_dir_url( __FILE__ ) . 'assets/images/small_logo_white.png',
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'rewrite'            => array(
					'slug'       => 'addify_rfq',
					'with_front' => false,
				),
				'supports'           => array( 'title', 'page-attributes' ),
			);

			register_post_type( 'addify_rfq', $args );

			$labels = array(
				'name'                => esc_html__( 'Fields for Request a Quote', 'addify_rfq' ),
				'singular_name'       => esc_html__( 'Field for Quote Rule', 'addify_rfq' ),
				'add_new'             => esc_html__( 'Add New Field', 'addify_rfq' ),
				'add_new_item'        => esc_html__( 'Add New Field', 'addify_rfq' ),
				'edit_item'           => esc_html__( 'Edit Field', 'addify_rfq' ),
				'new_item'            => esc_html__( 'New Field', 'addify_rfq' ),
				'view_item'           => esc_html__( 'View Field', 'addify_rfq' ),
				'search_items'        => esc_html__( 'Search Field', 'addify_rfq' ),
				'exclude_from_search' => true,
				'not_found'           => esc_html__( 'No Field found', 'addify_rfq' ),
				'not_found_in_trash'  => esc_html__( 'No Field found in trash', 'addify_rfq' ),
				'parent_item_colon'   => '',
				'all_items'           => esc_html__( 'All Fields', 'addify_rfq' ),
				'menu_name'           => esc_html__( 'Quote Fields', 'addify_rfq' ),
				'attributes'          => esc_html__( 'Field Attributes', 'addify_rfq' ),
				'item_published'      => esc_html__( 'Quote field published', 'addify_rfq' ),
				'item_updated'        => esc_html__( 'Quote field updated', 'addify_rfq' ),
			);

			$args = array(
				'labels'             => $labels,
				'menu_icon'          => plugin_dir_url( __FILE__ ) . 'assets/images/small_logo_white.png',
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'rewrite'            => array(
					'slug'       => 'addify_rfq_fields',
					'with_front' => false,
				),
				'supports'           => array( 'title', 'page-attributes' ),
			);

			register_post_type( 'addify_rfq_fields', $args );

			$labels = array(
				'name'                => esc_html__( 'Quotes', 'addify_rfq' ),
				'singular_name'       => esc_html__( 'Quote', 'addify_rfq' ),
				'add_new'             => esc_html__( 'New Quote', 'addify_rfq' ),
				'add_new_item'        => esc_html__( 'New Quote', 'addify_rfq' ),
				'edit_item'           => esc_html__( 'Edit Quote', 'addify_rfq' ),
				'new_item'            => esc_html__( 'New Quote', 'addify_rfq' ),
				'view_item'           => esc_html__( 'View Quote', 'addify_rfq' ),
				'search_items'        => esc_html__( 'Search Quote', 'addify_rfq' ),
				'exclude_from_search' => true,
				'not_found'           => esc_html__( 'No Quote found', 'addify_rfq' ),
				'not_found_in_trash'  => esc_html__( 'No quote found in trash', 'addify_rfq' ),
				'parent_item_colon'   => '',
				'all_items'           => esc_html__( 'All Quotes', 'addify_rfq' ),
				'menu_name'           => esc_html__( 'Quotes', 'addify_rfq' ),
				'item_published'      => esc_html__( 'Quote published', 'addify_rfq' ),
				'item_updated'        => esc_html__( 'Quote updated', 'addify_rfq' ),
			);

			$args = array(
				'labels'             => $labels,
				'menu_icon'          => plugin_dir_url( __FILE__ ) . 'assets/images/small_logo_white.png',
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => true,
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 30,
				'rewrite'            => array(
					'slug'       => 'addify_quote',
					'with_front' => false,
				),
				'supports'           => array( 'title' ),
			);

			register_post_type( 'addify_quote', $args );

		}

		public function afrfq_register_settings() {

			if ( null === get_page_by_path( 'request-a-quote' ) ) {

				$new_page = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => 1,
					'post_name'      => esc_html__( 'request-a-quote', 'addify_rfq' ),
					'post_title'     => esc_html__( 'Request a Quote', 'addify_rfq' ),
					'post_content'   => '[addify-quote-request-page]',
					'post_parent'    => 0,
					'comment_status' => 'closed',
				);

				$page_id = wp_insert_post( $new_page );

				update_option( 'addify_atq_page_id', $page_id );
			} else {
				$page_id = get_page_by_path( 'request-a-quote' );
				update_option( 'addify_atq_page_id', $page_id );
			}

			$quote_emails = 'a:8:{s:8:"af_admin";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:30:"You have received a new Quote.";s:7:"heading";s:30:"You have received a new Quote.";s:7:"message";s:78:"<p>Hi,</p>
			<p>You have received a new quote# {quote_id} form {user_name}.</p>";}s:10:"af_pending";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:29:"Your Quote has been received.";s:7:"heading";s:29:"Your Quote has been received.";s:7:"message";s:71:"<p>Hi {user_name},</p>
			<p>Your Quote#{quote_id} has been received.</p>";}s:13:"af_in_process";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:29:"Your Quote Is Now In Process.";s:7:"heading";s:29:"Your Quote Is Now In Process.";s:7:"message";s:89:"<p>Hi {username},<br />Good news, your quote is now in process. Here are the details,</p>";}s:11:"af_accepted";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:28:"Your Quote has been Accepted";s:7:"heading";s:31:"Your Quote request is accepted.";s:7:"message";s:101:"<p>Hi {username},</p>
			<p>Good News!!</p>
			<p>Your quote has been accepted. Here are the details,</p>";}s:13:"af_admin_conv";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:34:"Quote has been converted to order.";s:7:"heading";s:34:"Quote has been converted to order.";s:7:"message";s:101:"<p>Hi,</p>
			<p>Quote#{quote_id} by {user_name} has been converted to order. Here are the details.</p>";}s:12:"af_converted";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:39:"Your Quote has been converted to order.";s:7:"heading";s:39:"Your Quote has been converted to order.";s:7:"message";s:103:"<p>Hi {user_name},</p>
			<p>Your Quote#{quote_id} has been converted to order. Here are the details.</p>";}s:11:"af_declined";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:28:"Your quote has been declined";s:7:"heading";s:28:"Your quote has been declined";s:7:"message";s:180:"<p>Hi {username},</p>
			<p>Thank you for submitting the quote. Unfortunately, we cannot accept this quote at the moment. Thank you for understanding. Here are the quote details,</p>";}s:12:"af_cancelled";a:4:{s:6:"enable";s:3:"yes";s:7:"subject";s:29:"Your Quote has been rejected.";s:7:"heading";s:29:"Your Quote has been rejected.";s:7:"message";s:71:"<p>Hi {user_name},</p>
			<p>Your Quote#{quote_id} has been rejected.</p>";}}';

			$quote_fields_obj = new AF_R_F_Q_Quote_Fields();
			$quote_fields_obj->afrfq_migrate_fields_enabled_to_rules();

			if ( empty( get_option( 'afrfq_admin_email' ) ) ) {
				update_option( 'afrfq_admin_email', get_option( 'admin_email' ), true );
			}

			if ( empty( get_option( 'afrfq_emails' ) ) ) {
				update_option( 'afrfq_emails', $quote_emails, true );
			}

			if ( empty( get_option( 'afrfq_success_message' ) ) ) {
				update_option( 'afrfq_success_message', 'Your Quote Submitted Successfully.', true );
			}

			if ( empty( get_option( 'afrfq_pro_success_message' ) ) ) {
				update_option( 'afrfq_pro_success_message', 'Product Added to Quote Successfully.', true );
			}

			if ( empty( get_option( 'afrfq_view_button_message' ) ) ) {
				update_option( 'afrfq_view_button_message', 'View Quote', true );
			}

			if ( empty( get_option( 'afrfq_basket_option' ) ) ) {
				update_option( 'afrfq_basket_option', 'dropdown', true );
			}
		}
	}

	new Addify_Request_For_Quote();

}

