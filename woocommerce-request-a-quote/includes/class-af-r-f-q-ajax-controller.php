<?php
/**
 * Addify Add to Quote
 *
 * The WooCommerce quote class stores quote data and maintain session of quotes.
 * The quote class also has a price calculation function which calls upon other classes to calculate totals.
 *
 * @package addify-request-a-quote
 * @version 1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * AF_R_F_Q_Quote class.
 */
class AF_R_F_Q_Ajax_Controller {
	/**
	 * Contains an array of quote items.
	 *
	 * @var array
	 */
	public $quote_contents = array();

	/**
	 * Constructor for the AF_R_F_Q_Ajax_Controller class. Loads quote contents.
	 */
	public function __construct() {

		add_action( 'wp_ajax_add_to_quote', array( $this, 'afrfq_add_to_quote_callback_function' ) );
		add_action( 'wp_ajax_nopriv_add_to_quote', array( $this, 'afrfq_add_to_quote_callback_function' ) );

		add_action( 'wp_ajax_add_to_quote_single', array( $this, 'afrfq_add_to_quote_single_callback_function' ) );
		add_action( 'wp_ajax_nopriv_add_to_quote_single', array( $this, 'afrfq_add_to_quote_single_callback_function' ) );

		add_action( 'wp_ajax_add_to_quote_single_vari', array( $this, 'afrfq_add_to_quote_single_vari_callback_function' ) );
		add_action( 'wp_ajax_nopriv_add_to_quote_single_vari', array( $this, 'afrfq_add_to_quote_single_vari_callback_function' ) );

		add_action( 'wp_ajax_remove_quote_item', array( $this, 'afrfq_remove_quote_item_callback_function' ) );
		add_action( 'wp_ajax_nopriv_remove_quote_item', array( $this, 'afrfq_remove_quote_item_callback_function' ) );

		add_action( 'wp_ajax_update_quote_items', array( $this, 'afrfq_update_quote_items' ) );
		add_action( 'wp_ajax_nopriv_update_quote_items', array( $this, 'afrfq_update_quote_items' ) );

		add_action( 'wp_ajax_download_quote_in_pdf', array( $this, 'afrfq_download_quote_in_pdf' ) );
		add_action( 'wp_ajax_nopriv_download_quote_in_pdf', array( $this, 'afrfq_download_quote_in_pdf' ) );

		add_action( 'wp_ajax_check_availability_of_quote', array( $this, 'check_availability_of_quote' ) );
		add_action( 'wp_ajax_nopriv_check_availability_of_quote', array( $this, 'check_availability_of_quote' ) );

		add_action( 'wp_ajax_cache_quote_fields', array( $this, 'cache_quote_fields' ) );
		add_action( 'wp_ajax_nopriv_cache_quote_fields', array( $this, 'cache_quote_fields' ) );

		// Admin Ajax Hooks.
		add_action( 'wp_ajax_af_r_f_q_search_products', array( $this, 'af_r_f_q_search_products' ) );
		add_action( 'wp_ajax_afrfqsearchProduct_and_variation', array( $this, 'afrfqsearchProduct_and_variation' ) );
		add_action( 'wp_ajax_afrfq_insert_product_row', array( $this, 'afrfq_insert_product_row' ) );
		add_action( 'wp_ajax_afrfq_delete_quote_item', array( $this, 'afrfq_delete_quote_item' ) );
		add_action( 'wp_ajax_afrfq_search_users', array( $this, 'afrfq_search_users' ) );
	}

	public function cache_quote_fields() {

		$nonce = isset( $_POST['nonce'] ) && '' !== $_POST['nonce'] ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : 0;

		if ( !empty( $_POST['nonce'] ) && ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {
			die( 'Failed ajax security check!' );
		}

		if ( isset( $_POST['form_data'] ) ) {
			parse_str( sanitize_meta('', $_POST['form_data'], ''), $form_data );
		}

		$quote_fields_obj = new AF_R_F_Q_Quote_Fields();
		$quote_fields     = (array) $quote_fields_obj->quote_fields;
		$fields_data      = array();

		foreach ( $quote_fields as $key => $value ) {

			$field_id         = $value->ID;
			$afrfq_field_name = get_post_meta( $field_id, 'afrfq_field_name', true );

			if ( isset( $form_data[ $afrfq_field_name ] ) ) {
				$fields_data[$afrfq_field_name] = $form_data[ $afrfq_field_name ];
			}
		}

		wc()->session->set('quote_fields_data', $fields_data);
	}

	/**
	 * Search users by Ajax.
	 */
	public function check_availability_of_quote() {

		$nonce = isset( $_POST['nonce'] ) && '' !== $_POST['nonce'] ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : 0;

		if ( !empty( $_POST['nonce'] ) && ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {
			die( 'Failed ajax security check!' );
		}

		$variation_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) : '';

		if ( empty( $variation_id ) ) {
			die();
		}

		$variation_avaiable = get_post_meta( $variation_id, 'disable_rfq', true );

		if ( 'yes' === $variation_avaiable ) {
			die( 'false' );
		}

		$variation = wc_get_product( $variation_id );

		if ( ! $variation->is_in_stock() && 'yes' !== get_option( 'enable_o_o_s_products' ) ) {
			die( 'false' );
		} 

		die( 'true' );

	}


	/**
	 * Search users by Ajax.
	 */
	public function afrfq_search_users() {

		$nonce = isset( $_POST['nonce'] ) && '' !== $_POST['nonce'] ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : 0;

		if ( isset( $_POST['q'] ) && '' !== $_POST['q'] ) {
			if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {
				die( 'Failed ajax security check!' );
			}
			$pro = sanitize_text_field( wp_unslash( $_POST['q'] ) );
		} else {
			$pro = '';
		}

		$data_array  = array();
		$users       = new WP_User_Query(
			array(
				'search'         => '*' . esc_attr( $pro ) . '*',
				'search_columns' => array(
					'user_login',
					'user_nicename',
					'user_email',
					'user_url',
				),
			)
		);
		$users_found = $users->get_results();

		if ( ! empty( $users_found ) ) {
			foreach ( $users_found as $user ) {
				$title        = $user->display_name . '(' . $user->user_email . ')';
				$data_array[] = array( $user->ID, $title ); // array( User ID, User name and email ).
			}
		}

		wp_send_json( $data_array );
		die();
	}

	public function afrfq_delete_quote_item() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		$quote_item_key = isset( $_POST['quote_key'] ) ? sanitize_text_field( wp_unslash( $_POST['quote_key'] ) ) : '';
		$post_id        = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;

		$post = get_post( intval( $post_id ) );

		if ( ! $post ) {
			die( 'Quote Item not found' );
		}

		$quote_contents = get_post_meta( $post->ID, 'quote_contents', true );

		if ( isset( $quote_contents[ $quote_item_key ] ) ) {
			unset( $quote_contents[ $quote_item_key ] );
		}

		update_post_meta( $post->ID, 'quote_contents', $quote_contents );

		$af_quote       = new AF_R_F_Q_Quote( $quote_contents );
		$quote_contents = get_post_meta( $post->ID, 'quote_contents', true );
		$quote_totals   = $af_quote->get_calculated_totals( $quote_contents );

		ob_start();
		include AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/quote-details-table.php';
		$quote_table = ob_get_clean();

		wp_send_json(
			array(
				'quote-details-table' => $quote_table,
			)
		);

		die();

	}

	public function afrfq_insert_product_row() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? intval( wp_unslash( $_POST['quantity'] ) ) : 0;
		$post_id    = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;

		$post = get_post( intval( $post_id ) );

		if ( ! $post ) {
			die( 'post not found' );
		}

		$product = wc_get_product( $product_id );

		$quote_contents = (array) get_post_meta( $post->ID, 'quote_contents', true );
		$af_quote       = new AF_R_F_Q_Quote( $quote_contents );

		$quote_contents = $af_quote->add_to_quote( $product_id, $quantity, 0, array(), array(), true );

		if ( is_array( $quote_contents ) ) {

			$quote_totals = $af_quote->get_calculated_totals( $quote_contents );

			update_post_meta( $post_id, 'quote_totals', wp_json_encode( $quote_totals ) );
			update_post_meta( $post->ID, 'quote_contents', $quote_contents );

			ob_start();
			include AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/quote-details-table.php';
			$quote_table = ob_get_clean();

			wp_send_json(
				array(
					'success'             => true,
					'quote-details-table' => $quote_table,
				)
			);

			die();

		} else {

			wp_send_json(
				array(
					'success'  => false,
					/* translators: %s: Product name */
					'message'  => sprintf( __('Quote is not permitted for “%s”.', 'addify_rfq'), $product->get_name() ),
				)
			);
			die();
		}
	}

	public function afrfqsearchProduct_and_variation() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( isset( $_POST['q'] ) && ! empty( $_POST['q'] ) ) {

			if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

				die( 'Failed ajax security check!' );
			}

			$pro = sanitize_text_field( wp_unslash( $_POST['q'] ) );

		} else {

			$pro = '';

		}

		$data_array = array();
		$args       = array(
			'post_type'   => array( 'product', 'product_variation' ),
			'post_status' => 'publish',
			'numberposts' => -1,
			's'           => $pro,
		);
		$pros       = get_posts( $args );

		if ( ! empty( $pros ) ) {

			foreach ( $pros as $proo ) {

				$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
				$data_array[] = array( $proo->ID, $title ); // array( Post ID, Post Title )
			}
		}

		wp_send_json( $data_array );

		die();
	}

	public function af_r_f_q_search_products() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( isset( $_POST['q'] ) && ! empty( $_POST['q'] ) ) {

			if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

				die( 'Failed ajax security check!' );
			}

			$pro = sanitize_text_field( wp_unslash( $_POST['q'] ) );

		} else {

			$pro = '';

		}

		$data_array = array();
		$args       = array(
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => -1,
			's'           => $pro,
		);
		$pros       = get_posts( $args );

		if ( ! empty( $pros ) ) {

			foreach ( $pros as $proo ) {

				$title        = ( mb_strlen( $proo->post_title ) > 50 ) ? mb_substr( $proo->post_title, 0, 49 ) . '...' : $proo->post_title;
				$data_array[] = array( $proo->ID, $title ); // array( Post ID, Post Title )
			}
		}

		wp_send_json( $data_array );

		die();
	}

	/**
	 * Ajax add to quote controller.
	 */
	public function afrfq_download_quote_in_pdf() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		$quote_id = isset( $_POST['quote_id'] ) ? sanitize_text_field( wp_unslash( $_POST['quote_id'] ) ) : 0;

		$quote = get_post( $quote_id );

		$email_controller = new AF_R_F_Q_PDF_Controller();

		echo esc_url( $email_controller->process_pdf_print( intval( $quote_id ) ) );

		die();

	}

	/**
	 * Ajax add to quote controller.
	 */
	public function afrfq_update_quote_items() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		if ( isset( $_POST['form_data'] ) ) {
			parse_str( sanitize_meta('', wp_unslash( $_POST['form_data'] ), '' ), $form_data );
		} else {
			$form_data = '';
		}

		$quotes = WC()->session->get( 'quotes' );

		foreach ( WC()->session->get( 'quotes' ) as $quote_item_key => $quote_item ) {

			if ( isset( $form_data['quote_qty'][ $quote_item_key ] ) ) {
				$quotes[ $quote_item_key ]['quantity'] = intval( $form_data['quote_qty'][ $quote_item_key ] );
			}

			if ( isset( $form_data['offered_price'][ $quote_item_key ] ) ) {
				$quotes[ $quote_item_key ]['offered_price'] = floatval( $form_data['offered_price'][ $quote_item_key ] );
			}
		}

		WC()->session->set( 'quotes', $quotes );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'addify_quote', WC()->session->get( 'quotes' ) );
		}

		$af_quote = new AF_R_F_Q_Quote();

		ob_start();

		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/front/quote-table.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/front/quote-table.php';

		} else {

			include AFRFQ_PLUGIN_DIR . 'front/templates/quote-table.php';
		}
		$quote_table = ob_get_clean();

		ob_start();
		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php';

		} else {

			include_once AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote.php';
		}
		$mini_quote = ob_get_clean();

		$message = wp_kses_post( '<div class="woocommerce-message" role="alert">' . esc_html__( 'Quote updated', 'addify_rfq' ) . '</div>' );

		ob_start();

		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/front/quote-totals-table.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/front/quote-totals-table.php';

		} else {

			include AFRFQ_PLUGIN_DIR . 'front/templates/quote-totals-table.php';
		}

		$quote_totals = ob_get_clean();

		if ( empty( $quote_totals ) ) {
			$quote_totals = '';
		}

		wp_send_json(
			array(
				'quote-table'  => $quote_table,
				'message'      => $message,
				'mini-quote'   => $mini_quote,
				'quote-totals' => $quote_totals,
			)
		);
	}

	/**
	 * Ajax add to quote controller.
	 */
	public function afrfq_add_to_quote_callback_function() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		$product_id       = isset( $_REQUEST['product_id'] ) ? intval( wp_unslash( $_REQUEST['product_id'] ) ) : 0;
		$product_quantity = isset( $_REQUEST['quantity'] ) ? intval( wp_unslash( $_REQUEST['quantity'] ) ) : 0;
		$variation_id     = isset( $_REQUEST['variation_id'] ) ? intval( wp_unslash( $_REQUEST['variation_id'] ) ) : 0;

		$key = -1;

		$ajax_add_to_quote = new AF_R_F_Q_Quote();

		$quote_item_key = $ajax_add_to_quote->add_to_quote( $product_id, $product_quantity, $variation_id );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'addify_quote', WC()->session->get( 'quotes' ) );
		}

		$quote_contents = wc()->session->get( 'quotes' );
		$product        = '';
		$product_name   = 'Product';

		if ( isset( $quote_contents[ $quote_item_key ] ) ) {
			$product = $quote_contents[ $quote_item_key ]['data'];
		}

		if ( is_object( $product ) ) {
			$product_name = $product->get_name();
		}

		if ( 'yes' === get_option( 'enable_ajax_shop' ) && false !== $quote_item_key  ) {

			ob_start();

			if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php' ) ) {

				include get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php';

			} else {

				include_once AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote.php';
			}

			$mini_quote = ob_get_clean();

			ob_start();
			?>
				<a href="<?php echo esc_url( get_page_link( get_option( 'addify_atq_page_id' ) ) ); ?>" class="added_to_cart added_to_quote wc-forward" title="View Quote"><?php echo esc_html( get_option( 'afrfq_view_button_message' ) ); ?></a>
				<?php
				$view_quote_btn = ob_get_clean();

				wp_send_json(
				array(
					'mini-quote'   => $mini_quote,
					'view_button'  => $view_quote_btn,
				)
				);
		} else {

			if ( false === $quote_item_key ) {
				/* translators: %s: Product name */
				wc_add_notice( sprintf( __( '“%s” has been added to your quote.', 'addify_rfq' ), $product_name ), 'success' );
				echo 'success';
			} else {
				/* translators: %s: Product name */
				wc_add_notice( sprintf( __( '“%s” has been added to your quote.', 'addify_rfq' ), $product_name ), 'success' );
				echo 'success';
			}
			
		}

		die();

	}

	/**
	 * Ajax add to quote controller for variable.
	 */
	public function afrfq_add_to_quote_single_vari_callback_function() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		if ( isset( $_POST['form_data'] ) ) {
			parse_str( sanitize_meta('', wp_unslash( $_POST['form_data'] ), '' ), $form_data );
		} else {
			$form_data = '';
		}

		$product_id       = isset( $form_data['add-to-cart'] ) ? intval( $form_data['add-to-cart'] ) : '';
		$product_quantity = isset( $form_data['quantity'] ) ? intval( $form_data['quantity'] ) : 1;
		$variation_id     = isset( $form_data['variation_id'] ) ? intval( $form_data['variation_id'] ) : '';
		$variation        = array();

		foreach ( $form_data as $key => $value ) {

			if ( ! in_array( $key, array( 'add-to-cart', 'quantity', 'variation_id', 'product_id' ), true ) ) {

				$variation[ $key ] = $value;
			}
		}

		$ajax_add_to_quote = new AF_R_F_Q_Quote();

		$quote_item_key = $ajax_add_to_quote->add_to_quote( $product_id, $product_quantity, $variation_id, $variation );

		$quote_contents = wc()->session->get( 'quotes' );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'addify_quote', WC()->session->get( 'quotes' ) );
		}

		$product = '';

		if ( isset( $quote_contents[ $quote_item_key ] ) ) {
			$product = $quote_contents[ $quote_item_key ]['data'];
		} else {
			$product = wc_get_product( $variation_id );
		}

		$product_name = 'Product';
		if ( is_object( $product ) ) {
			$product_name = $product->get_name();
		}

		if ( 'yes' === get_option( 'enable_ajax_product' ) && false !== $quote_item_key ) {

			ob_start();

			if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php' ) ) {

				include get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php';

			} else {

				include_once AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote.php';
			}

			$mini_quote = ob_get_clean();

			ob_start();
			?>
				<a href="<?php echo esc_url( get_page_link( get_option( 'addify_atq_page_id' ) ) ); ?>" class="added_to_cart added_to_quote wc-forward" title="View Quote"><?php echo esc_html( get_option( 'afrfq_view_button_message' ) ); ?></a>
				<?php
				$view_quote_btn = ob_get_clean();

				wp_send_json(
				array(
					'mini-quote'   => $mini_quote,
					'view_button'  => $view_quote_btn,
				)
				);
		} else {

			if ( false === $quote_item_key ) {
				/* translators: %s: Product name */
				wc_add_notice( __( 'Quote is not available for selected variation.', 'addify_rfq' ), 'error' );
				echo 'success';
			} else {
				/* translators: %s: Product name */
				wc_add_notice( sprintf( __( '“%s” has been added to your quote.', 'addify_rfq' ), $product_name ), 'success' );
				echo 'success';
			}
		}

		die();

	}

	/**
	 * Ajax add to quote controller for single products.
	 */
	public function afrfq_add_to_quote_single_callback_function() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		$product_id       = isset( $_REQUEST['product_id'] ) ? intval( wp_unslash( $_REQUEST['product_id'] ) ) : 0;
		$product_quantity = isset( $_REQUEST['quantity'] ) ? intval( wp_unslash( $_REQUEST['quantity'] ) ) : 0;

		$ajax_add_to_quote = new AF_R_F_Q_Quote();

		$quote_item_key = $ajax_add_to_quote->add_to_quote( $product_id, $product_quantity );

		$quote_contents = wc()->session->get( 'quotes' );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'addify_quote', WC()->session->get( 'quotes' ) );
		}

		$product = '';
		if ( isset( $quote_contents[ $quote_item_key ] ) ) {
			$product = $quote_contents[ $quote_item_key ]['data'];
		} else {
			$product = wc_get_product( $product_id );
		}

		$product_name = 'Product';
		if ( is_object( $product ) ) {
			$product_name = $product->get_name();
		}

		if ( 'yes' === get_option( 'enable_ajax_product' ) && false !== $quote_item_key ) {

			ob_start();

			if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php' ) ) {

				include get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php';

			} else {

				include_once AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote.php';
			}

			$mini_quote = ob_get_clean();

			ob_start();
			?>
				<a href="<?php echo esc_url( get_page_link( get_option( 'addify_atq_page_id' ) ) ); ?>" class="added_to_cart added_to_quote wc-forward" title="View Quote"><?php echo esc_html( get_option( 'afrfq_view_button_message' ) ); ?></a>
				<?php
				$view_quote_btn = ob_get_clean();

				wp_send_json(
				array(
					'mini-quote'   => $mini_quote,
					'view_button'  => $view_quote_btn,
				)
				);
		} else {

			if ( false === $quote_item_key ) {
				/* translators: %s: Product name */
				wc_add_notice( sprintf( __( 'Quote is not available for “%s”.', 'addify_rfq' ), $product_name ), 'error' );
				echo 'success';
			} else {
				/* translators: %s: Product name */
				wc_add_notice( sprintf( __( '“%s” has been added to your quote.', 'addify_rfq' ), $product_name ), 'success' );
				echo 'success';
			}
		}

		die();

	}

	/**
	 * Ajax remove item from quote.
	 */
	public function afrfq_remove_quote_item_callback_function() {

		if ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) {

			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		} else {
			$nonce = 0;
		}

		if ( ! wp_verify_nonce( $nonce, 'afquote-ajax-nonce' ) ) {

			die( 'Failed ajax security check!' );
		}

		$quote_key = isset( $_POST['quote_key'] ) ? sanitize_text_field( wp_unslash( $_POST['quote_key'] ) ) : '';

		if ( empty( $quote_key ) ) {
			die( 'Quote key is empty' );
		}

		$quotes = WC()->session->get( 'quotes' );

		$product = $quotes[ $quote_key ]['data'];

		unset( $quotes[ $quote_key ] );

		WC()->session->set( 'quotes', $quotes );

		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'addify_quote', WC()->session->get( 'quotes' ) );
		}

		do_action( 'addify_quote_item_removed', $quote_key, $product );

		ob_start();
		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/front/quote-table.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/front/quote-table.php';

		} else {

			include AFRFQ_PLUGIN_DIR . 'front/templates/quote-table.php';
		}
		$quote_table = ob_get_clean();

		ob_start();
		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/quote/mini-quote.php';

		} else {

			include_once AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote.php';
		}
		$mini_quote = ob_get_clean();
		/* translators: %s: Product name */
		$message      = sprintf( __( '“%s” has been removed from quote basket.', 'addify_rfq' ), $product->get_name() );
		$message_html = '<div class="woocommerce-message" role="alert">' . $message . '</div>';

		ob_start();
		if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/front/quote-totals-table.php' ) ) {

			include get_template_directory() . '/woocommerce/addify/rfq/front/quote-totals-table.php';

		} else {

			include AFRFQ_PLUGIN_DIR . 'front/templates/quote-totals-table.php';
		}
		$quote_totals = ob_get_clean();

		if ( empty( $quote_totals ) ) {
			$quote_totals = '';
		}

		wp_send_json(
			array(
				'quote_empty'  => empty( WC()->session->get('quotes') ) ? true : false,
				'quote-table'  => $quote_table,
				'message'      => $message_html,
				'mini-quote'   => $mini_quote,
				'quote-totals' => $quote_totals,
			)
		);

		die();
	}
}

new AF_R_F_Q_Ajax_Controller();
