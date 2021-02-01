<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'AF_R_F_Q_Admin' ) ) {

	class AF_R_F_Q_Admin extends Addify_Request_For_Quote {

		public $errors;

		public function __construct() {

			// Enqueue Scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'afrfq_admin_scripts' ) );

			// Custom meta boxes.
			add_action( 'add_meta_boxes', array( $this, 'afrfq_add_metaboxes' ) );
			add_action( 'admin_init', array( $this, 'afrfq_register_metaboxes' ), 10 );

			// Add menus.
			add_action( 'admin_menu', array( $this, 'afrfq_custom_menu_admin' ) );

			// Save Settings.
			add_action( 'wp_loaded', array( $this, 'afrfq_save_settings' ), 10 );

			// Save custom post types.
			add_action( 'save_post_addify_rfq', array( $this, 'afrfq_meta_box_save' ) );
			add_action( 'save_post_addify_quote', array( $this, 'afrfq_update_quote_details' ) );
			add_action( 'save_post_addify_rfq_fields', array( $this, 'afrfq_update_fields_meta' ) );

			// Manage table of Quotes.
			add_filter( 'manage_addify_quote_posts_columns', array( $this, 'addify_quote_columns_head' ) );
			add_action( 'manage_addify_quote_posts_custom_column', array( $this, 'addify_quote_columns_content' ), 10, 2 );

			// Manage table of quote fields.
			add_filter( 'manage_addify_rfq_fields_posts_columns', array( $this, 'addify_quote_fields_columns_head' ) );
			add_action( 'manage_addify_rfq_fields_posts_custom_column', array( $this, 'addify_quote_fields_columns_content' ), 10, 2 );

			// Manage table of quote rules.
			add_filter( 'manage_addify_rfq_posts_columns', array( $this, 'addify_quote_rules_columns_head' ) );
			add_action( 'manage_addify_rfq_posts_custom_column', array( $this, 'addify_quote_rules_columns_content' ), 10, 2 );

			// Module Settings.
			add_action( 'admin_init', array( $this, 'af_r_f_q_setting_files' ), 10 );

			// Add variation level settings for out of stock.
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'af_r_f_q_variable_fields' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'af_r_f_q_save_custom_field_variations' ), 10, 2 );

			// Add Custom fields in page attributes for fields.
			add_action( 'page_attributes_misc_attributes', array( $this, 'addify_afrfq_add_fields' ) );
		}

		public function af_r_f_q_setting_files() {
			include_once AFRFQ_PLUGIN_DIR . '/admin/settings/tabs/general.php';
			include_once AFRFQ_PLUGIN_DIR . '/admin/settings/tabs/custom-message.php';
			include_once AFRFQ_PLUGIN_DIR . '/admin/settings/tabs/captcha-settings.php';
			include_once AFRFQ_PLUGIN_DIR . '/admin/settings/tabs/emails-setting.php';
			include_once AFRFQ_PLUGIN_DIR . '/admin/settings/tabs/editors-settings.php';
			include_once AFRFQ_PLUGIN_DIR . '/admin/settings/tabs/quote-attributes.php';
		}

		public function af_r_f_q_variable_fields( $loop, $variation_data, $variation ) {

			woocommerce_wp_checkbox(
				array(
					'id'          => "disable_rfq$loop",
					'name'        => 'disable_rfq[' . $variation->ID . ']',
					'value'       => get_post_meta( $variation->ID, 'disable_rfq', true ),
					'label'       => __( 'Disable Quote', 'addify_rfq' ),
					'desc_tip'    => true,
					'description' => __( 'Disable request a quote for above variation.', 'addify_rfq' ),
				)
			);
		}

		public function af_r_f_q_save_custom_field_variations( $variation_id, $loop ) {

			if ( isset( $_POST['afrfq_field_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['afrfq_field_nonce'] ) ), 'afrfq_fields_nonce' ) ) {
				return;
			}

			if ( isset( $_POST['disable_rfq'][ $variation_id ] ) ) {
				update_post_meta( $variation_id, 'disable_rfq', sanitize_text_field( wp_unslash( $_POST['disable_rfq'][ $variation_id ] ) ) );
			} else {
				update_post_meta( $variation_id, 'disable_rfq', '' );
			}
		}

		public function addify_afrfq_add_fields() {
			global $post;

			if ( 'addify_rfq_fields' !== $post->post_type ) {
				return;
			}

			$post_id = $post->ID;

			$afrfq_field_enable   = get_post_meta( $post_id, 'afrfq_field_enable', true );
			$afrfq_field_required = get_post_meta( $post_id, 'afrfq_field_required', true );

			?>
			<p class="post-attributes-label-wrapper afrfq-label-wrapper">
				<label class="post-attributes-label" for="quote_status">
					<?php esc_html_e( 'Enable/Disable', 'addify_rfq' ); ?>
				</label>
			</p>
				<select name="afrfq_field_enable" id="afrfq_field_enable" >
					<option value="enable" <?php echo selected( 'enable', $afrfq_field_enable ); ?> > <?php esc_html_e( 'Enable', 'addify_rfq' ); ?></option>
					<option value="disable" <?php echo selected( 'disable', $afrfq_field_enable ); ?> > <?php esc_html_e( 'Disable', 'addify_rfq' ); ?></option>
				</select>
			<p class="post-attributes-label-wrapper afrfq-label-wrapper">
				<label class="post-attributes-label" for="quote_status">
					<?php esc_html_e( 'Required', 'addify_rfq' ); ?>
				</label>
			</p>
			<input type="checkbox" value="yes" <?php echo checked( 'yes', $afrfq_field_required ); ?> name="afrfq_field_required">
			<?php
		}

		public function afrfq_update_fields_meta( $post_id ) {

			try {

				// if our nonce isn't there, or we can't verify it, return
				if ( isset( $_POST['afrfq_field_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['afrfq_field_nonce'] ) ), 'afrfq_fields_nonce' ) ) {
					return;
				}

				$form_data = sanitize_meta( '', wp_unslash( $_POST ), '' );

				$validation = true;

				$quote_fields_obj = new AF_R_F_Q_Quote_Fields();

				if ( isset( $form_data['afrfq_field_name'] ) && ! $quote_fields_obj->afrfq_validate_field_name( $form_data['afrfq_field_name'], $post_id ) ) {
					throw new Exception( __( 'Field name should be unique for each field.', 'addify_rfq' ), 1 );
				}

				if ( ! empty( $form_data ) ) {
					include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/fields/save-fields.php';
				}
			} catch ( Exception $e ) {

				echo esc_html( 'Error: ' . $e->getMessage() );
			}

		}

		public function afrfq_update_quote_details() {

			$screen = get_current_screen();

			if ( ! 'addify_quote' === $screen->post_type ) {
				return;
			}

			// if our nonce isn't there, or we can't verify it, return
			if ( isset( $_POST['afrfq_field_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['afrfq_field_nonce'] ) ), 'afrfq_fields_nonce' ) ) {
				return;
			}

			$form_data = sanitize_meta( '', wp_unslash( $_POST ), '' );

			if ( !empty( $form_data ) ) {
				include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/update-quote.php';
			}

			if ( isset( $form_data['addify_convert_to_order'] ) ) {
				include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/convert-quote-to-order.php';
			}
		}


		public function afrfq_admin_scripts() {

			$screen = get_current_screen();

			if ( ! in_array( $screen->post_type, array( 'addify_rfq', 'addify_quote', 'addify_rfq_fields' ), true ) ) {
				return;
			}

			wp_enqueue_style( 'afrfq-adminc', plugins_url( '../assets/css/afrfq_admin.css', __FILE__ ), false, '1.0' );
			wp_enqueue_style( 'select2', plugins_url( '../assets/css/select2.css', __FILE__ ), false, '1.0' );

			wp_enqueue_script( 'select2', plugins_url( '../assets/js/select2.js', __FILE__ ), array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'afrfq-adminj', plugins_url( '../assets/js/afrfq_admin.js', __FILE__ ), array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'jquery-ui', plugins_url( '../assets/js/jquery-ui.js', __FILE__ ), array( 'jquery' ), '1.0', true );

			$afrfq_data = array(
				'admin_url' => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'afquote-ajax-nonce' ),

			);
			wp_localize_script( 'afrfq-adminj', 'afrfq_php_vars', $afrfq_data );

			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_media();
		}

		public function afrfq_register_metaboxes() {

			add_meta_box(
				'afrfq-rule-settings',
				esc_html__( 'Rule Settings', 'addify_rfq' ),
				array( $this, 'afrfq_rule_setting_callback' ),
				'addify_rfq',
				'normal',
				'high'
			);

		}

		public function afrfq_add_metaboxes() {

			add_meta_box(
				'afrfq-user-info',
				esc_html__( 'Customer Information', 'addify_rfq' ),
				array( $this, 'afrfq_customer_info_callback' ),
				'addify_quote',
				'normal',
				'high'
			);

			add_meta_box(
				'afrfq-quote-info',
				esc_html__( 'Quote Details', 'addify_rfq' ),
				array( $this, 'afrfq_quote_info_callback' ),
				'addify_quote',
				'normal',
				'high'
			);

			add_meta_box(
				'afrfq-quote-status',
				esc_html__( 'Quote Attributes', 'addify_rfq' ),
				array( $this, 'afrfq_quote_status_callback' ),
				'addify_quote',
				'side',
				'high'
			);

			// Add meta boxes for fields.
			add_meta_box(
				'afrfq-field-attributes',
				esc_html__( 'Field Attributes and Values', 'addify_rfq' ),
				array( $this, 'afrfq_field_attribute_callback' ),
				'addify_rfq_fields',
				'normal',
				'high'
			);
		}

		public function afrfq_field_attribute_callback() {
			include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/fields/field-attribute.php';
		}

		public function afrfq_customer_info_callback() {

			$quote_fields_obj = new AF_R_F_Q_Quote_Fields();
			$quote_fields     = (array) $quote_fields_obj->quote_fields;

			include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/customer-info.php';

		}

		public function afrfq_quote_status_callback() {
			include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/quote-status.php';
		}

		public function afrfq_quote_info_callback() {

			global $post;

			if ( ! empty( get_post_meta( $post->ID, 'quote_proid', true ) ) ) {

				include_once AFRFQ_PLUGIN_DIR . 'admin/templates/addify-afrfq-edit-form.php';

			} else {

				include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/quotes/quote-details.php';
			}

		}

		public function afrfq_save_settings() {

			// if our nonce isn't there, or we can't verify it, return
			if ( isset( $_POST['afrfq_field_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['afrfq_field_nonce'] ) ), 'afrfq_fields_nonce' ) ) {
				return;
			}

			if ( ! isset( $_POST['afrfq_save_settings'] ) ) {
				return;
			}

			include_once AFRFQ_PLUGIN_DIR . 'admin/settings/save-settings.php';

			add_action( 'admin_notices', array( $this, 'afrfq_author_admin_notice' ) );

		}

		public function afrfq_rule_setting_callback() {
			global $post;
			wp_nonce_field( 'afrfq_field_nonce', 'afrfq_field_nonce' );
			$afrfq_rule_type          = get_post_meta( intval( $post->ID ), 'afrfq_rule_type', true );
			$afrfq_rule_priority      = get_post_meta( intval( $post->ID ), 'afrfq_rule_priority', true );
			$afrfq_hide_products      = unserialize( get_post_meta( intval( $post->ID ), 'afrfq_hide_products', true ) );
			$afrfq_hide_categories    = unserialize( get_post_meta( intval( $post->ID ), 'afrfq_hide_categories', true ) );
			$afrfq_hide_user_role     = unserialize( get_post_meta( intval( $post->ID ), 'afrfq_hide_user_role', true ) );
			$afrfq_is_hide_price      = get_post_meta( intval( $post->ID ), 'afrfq_is_hide_price', true );
			$afrfq_hide_price_text    = get_post_meta( intval( $post->ID ), 'afrfq_hide_price_text', true );
			$afrfq_is_hide_addtocart  = get_post_meta( intval( $post->ID ), 'afrfq_is_hide_addtocart', true );
			$afrfq_custom_button_text = get_post_meta( intval( $post->ID ), 'afrfq_custom_button_text', true );
			$afrfq_form               = get_post_meta( intval( $post->ID ), 'afrfq_form', true );
			$afrfq_contact7_form      = get_post_meta( intval( $post->ID ), 'afrfq_contact7_form', true );
			$afrfq_custom_button_link = get_post_meta( intval( $post->ID ), 'afrfq_custom_button_link', true );

			include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/rules/new-quote-rule.php';
		}

		public function afrfq_meta_box_save( $post_id ) {

			// return if we're doing an auto save
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( get_post_status( $post_id ) === 'auto-draft' ) {
				return;
			}

			// if our current user can't edit this post, return
			if ( ! current_user_can( 'edit_posts' ) ) {
				return;
			}

			include_once AFRFQ_PLUGIN_DIR . 'admin/meta-boxes/rules/save-rule-settings.php';
		}

		public function afrfq_custom_menu_admin() {

			if ( defined('AFB2B_PLUGIN_DIR')) {
				return;
			}
			
			add_menu_page(
				esc_html__( 'Request a Quote', 'addify_rfq' ),
				esc_html__( 'Request a Quote', 'addify_rfq' ),
				'manage_options',
				'edit.php?post_type=addify_quote',
				'',
				AFRFQ_URL . 'assets/images/grey.png',
				'30'
			);

			add_submenu_page(
				'edit.php?post_type=addify_quote',
				esc_html__( 'All Quote', 'addify_rfq' ),
				esc_html__( 'All Quotes', 'addify_rfq' ),
				'manage_options',
				'edit.php?post_type=addify_quote',
				''
			);

			add_submenu_page(
				'edit.php?post_type=addify_quote',
				esc_html__( 'All Quote Rules', 'addify_rfq' ),
				esc_html__( 'Quote Rules', 'addify_rfq' ),
				'manage_options',
				'edit.php?post_type=addify_rfq',
				''
			);

			add_submenu_page(
				'edit.php?post_type=addify_quote',
				esc_html__( 'ALL Quote Fields', 'addify_rfq' ),
				esc_html__( 'Quote Fields', 'addify_rfq' ),
				'manage_options',
				'edit.php?post_type=addify_rfq_fields',
				''
			);

			add_submenu_page(
				'edit.php?post_type=addify_quote',
				esc_html__( 'Settings', 'addify_rfq' ),
				esc_html__( 'Settings', 'addify_rfq' ),
				'manage_options',
				'af-rfq-settings',
				array( $this, 'af_r_f_q_settings' )
			);

		}

		public function af_r_f_q_settings() {
			include_once AFRFQ_PLUGIN_DIR . 'admin/settings/settings.php';
		}

		public function afrfq_author_admin_notice() {
			?>
			<div class="updated notice notice-success is-dismissible">
				<p><?php echo esc_html__( 'Settings saved successfully.', 'addify_rfq' ); ?></p>
			</div>
			<?php
		}

		public function addify_quote_rules_columns_head( $columns ) {

			unset( $columns['date'] );
			$columns['af_users']    = __( 'User Roles', 'addify_rfq' );
			$columns['af_price']    = __( 'Hide Price', 'addify_rfq' );
			$columns['af_btn']      = __( 'Button Text', 'addify_rfq' );
			$columns['af_priority'] = __( 'Rule Priority', 'addify_rfq' );
			$columns['date']        = __( 'Date', 'addify_rfq' );
			return $columns;
		}

		public function addify_quote_rules_columns_content( $column_name, $post_id ) {
			switch ( $column_name ) {
				case 'af_users':
					echo esc_attr( implode( ', ', (array) unserialize( (string) get_post_meta( $post_id, 'afrfq_hide_user_role', true ) ) ) );
					break;
				case 'af_btn':
					echo esc_attr( get_post_meta( $post_id, 'afrfq_custom_button_text', true ) );
					break;
				case 'af_priority':
					global $post;
					echo esc_attr( $post->menu_order );
					break;
				case 'af_price':
					echo esc_attr( 'yes' === get_post_meta( $post_id, 'afrfq_is_hide_price', true ) ? 'Yes' : 'No' );
					break;
			}
		}

		public function addify_quote_fields_columns_head( $columns ) {
			unset( $columns['date'] );
			$columns['af_label']   = __( 'Label', 'addify_rfq' );
			$columns['af_type']    = __( 'Type', 'addify_rfq' );
			$columns['af_name']    = __( 'Meta Key/ Field Name', 'addify_rfq' );
			$columns['af_default'] = __( 'Default Value', 'addify_rfq' );
			$columns['af_order']   = __( 'Display Order', 'addify_rfq' );
			$columns['af_satus']   = __( 'Status', 'addify_rfq' );
			$columns['af_requi']   = __( 'Required', 'addify_rfq' );
			$columns['date']       = __( 'Date', 'addify_rfq' );
			return $columns;
		}

		public function addify_quote_fields_sortable_columns( $columns ) {

			$columns['af_order'] = __( 'Display Order', 'addify_rfq' );
			return $columns;
		}

		public function addify_quote_fields_columns_content( $column_name, $post_id ) {
			switch ( $column_name ) {
				case 'af_label':
					echo esc_attr( ucwords( get_post_meta( $post_id, 'afrfq_field_label', true ) ) );
					break;

				case 'af_type':
					echo esc_attr( ucwords( get_post_meta( $post_id, 'afrfq_field_type', true ) ) );
					break;
				case 'af_name':
					echo esc_attr( get_post_meta( $post_id, 'afrfq_field_name', true ) );
					break;
				case 'af_default':
					echo esc_attr( ucwords( str_replace( '_', ' ', get_post_meta( $post_id, 'afrfq_field_value', true ) ) ) );
					break;
				case 'af_order':
					global $post;
					echo esc_attr( $post->menu_order );
					break;
				case 'af_satus':
					echo esc_attr( ucwords( get_post_meta( $post_id, 'afrfq_field_enable', true ) ) );
					break;
				case 'af_requi':
					echo esc_attr( 'yes' === get_post_meta( $post_id, 'afrfq_field_required', true ) ? 'Yes' : 'No' );
					break;
			}
		}

		public function addify_quote_columns_head( $columns ) {

			$new_columns           = array();
			$new_columns['cb']     = '<input type="checkbox" />';
			$new_columns['title']  = esc_html__( 'Quote #', 'addify_rfq' );
			$new_columns['name']   = esc_html__( 'Customer Name', 'addify_rfq' );
			$new_columns['email']  = esc_html__( 'Customer Email', 'addify_rfq' );
			$new_columns['status'] = esc_html__( 'Quote Status', 'addify_rfq' );
			$new_columns['date']   = esc_html__( 'Date', 'addify_rfq' );
			return $new_columns;

		}

		public function addify_quote_columns_content( $column_name, $post_ID ) {

			$af_fields_obj = new AF_R_F_Q_Quote_Fields();

			switch ( $column_name ) {
				case 'name':
					echo esc_attr( $af_fields_obj->afrfq_get_user_name( $post_ID ) );
					break;

				case 'email':
					echo esc_attr( $af_fields_obj->afrfq_get_user_email( $post_ID ) );
					break;
				case 'status':
					echo esc_attr( ucwords( str_replace( 'af_', '', get_post_meta( $post_ID, 'quote_status', true ) ) ) );
					break;
			}

		}
	}

	new AF_R_F_Q_Admin();
}
