<?php

if ( ! session_id() ) {
	session_start();
}

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'AF_R_F_Q_Front' ) ) {

	class AF_R_F_Q_Front extends Addify_Request_For_Quote {

		public $quote_rules;

		public function __construct() {

			$this->quote_rules = $this->afrfq_get_quote_rules();
			// Enqueue scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'afrfq_front_script' ) );

			// Hide price for selected products.
			add_filter( 'woocommerce_get_price_html', array( $this, 'afrfq_remove_woocommerce_price_html' ), 10, 2 );

			// Process and initialize the hooks.
			add_action( 'init', array( $this, 'afrfq_add_archive_page_hooks' ) );
			add_action( 'init', array( $this, 'afrfq_add_product_page_hooks' ) );

			// Process form submit actions.
			add_action( 'wp_loaded', array( $this, 'addify_convert_to_order_customer' ) );
			add_action( 'wp_loaded', array( $this, 'addify_insert_customer_quote' ) );

			// Display Quote basket in menus.
			add_action( 'wp_nav_menu_items', array( $this, 'afrfq_quote_basket' ), 10, 2 );

			// Display add to quote after add to cart button.
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'afrfq_custom_button_add_replacement' ), 30 );

			// Request a Quote page short code. 
			add_shortcode( 'addify-quote-request-page', array( $this, 'addify_quote_request_page_shortcode_function' ) );

			// Add endpoint of quote and process its content.
			add_action( 'init', array( $this, 'addify_add_endpoints' ) );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'addify_new_menu_items' ) );
			add_action( 'woocommerce_account_request-quote_endpoint', array( $this, 'addify_endpoint_content' ) );
			add_filter( 'query_vars', array( $this, 'addify_add_query_vars' ), 0 );
			add_filter( 'the_title', array( $this, 'addify_endpoint_title' ) );
			
			// Start customer session for guest users.
			add_action( 'woocommerce_init', array( $this, 'afrfq_start_customer_session' ) );

			// Load and update saved quotes of registered users.
			add_action( 'wp_login', array( $this, 'afrfq_update_quote_data_after_login' ), 100, 2 );
		}

		public function afrfq_get_quote_rules() {

			$args = array(
				'post_type'        => 'addify_rfq',
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
				'suppress_filters' => true,

			);
			return get_posts( $args );
		}

		public function afrfq_show_rfq_out_of_stock( $html, $product ) {

			if ( $product->is_in_stock() ) {
				return $html;
			}

			if ( 'yes' !== get_option( 'enable_o_o_s_products' ) ) {
				return $html;
			}

			if ( 'simple' !== $product->get_type() ) {
				return $html;
			}

			include_once AFRFQ_PLUGIN_DIR . 'front/templates/simple-out-of-stock.php';

			return '';
		}

		public function afrfq_load_quote_from_session() {

			if ( isset( wc()->session ) && empty( wc()->session->get( 'quotes' ) ) ) {

				if ( is_user_logged_in() ) {

					$quotes = get_user_meta( get_current_user_id(), 'addify_quote', true );

					if ( ! empty( $quotes ) ) {
						wc()->session->set( 'quotes', $quotes );
					}
				}
			}
		}

		public function afrfq_update_quote_data_after_login( $user_login, $user ) {

			$saved_quotes   = (array) get_user_meta( $user->ID, 'addify_quote', true );
			$session_quotes = (array) WC()->session->get( 'quotes' );
			$final_quotes   = $session_quotes;

			// Merge saved quotes and session quotes.
			foreach ( (array) $saved_quotes as $key => $value ) {

				if ( ! isset( $final_quotes[ $key ] ) && ! empty( $value ) ) {
					$final_quotes[ $key ] = $value;
				}
			}

			// Filter quotes.
			foreach ( $final_quotes as $key => $value ) {
				if ( empty( $value ) ) {
					unset( $final_quotes[ $key ] );
				}
			}

			update_user_meta( $user->ID, 'addify_quote', $final_quotes );
			WC()->session->set( 'quotes', $final_quotes );

		}

		public function addify_insert_customer_quote() {

			if ( isset( $_REQUEST['_afrfq__wpnonce'] ) && ! wp_verify_nonce( esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['_afrfq__wpnonce'] ) ) ), '_afrfq__wpnonce' ) ) {
				die( esc_html__( 'Site security violated.', 'addify_rfq' ) );
			}

			if ( isset( $_POST['afrfq_action'] ) ) {

				unset( $_POST['afrfq_action'] );

				$data = (array) sanitize_meta( '', wp_unslash( $_POST ), '' );

				$af_quote = new AF_R_F_Q_Quote();

				$af_quote->insert_new_quote( array_merge( $data, (array) $_FILES ) );
			}

		}

		public function addify_convert_to_order_customer() {

			if ( isset( $_REQUEST['_afrfq__wpnonce'] ) && ! wp_verify_nonce( esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['_afrfq__wpnonce'] ) ) ), '_afrfq__wpnonce' ) ) {
				wp_die( esc_html__( 'Site security violated.', 'addify_rfq' ) );
			}

			if ( isset( $_POST['addify_convert_to_order_customer'] ) ) {

				$quote_id = sanitize_text_field( wp_unslash( $_POST['addify_convert_to_order_customer'] ) );

				if ( empty( intval( $quote_id ) ) ) {
					return;
				}

				$af_quote = new AF_R_F_Q_Quote();

				$af_quote->convert_quote_to_order( $quote_id );
				

			} else {
				return;
			}
		}

		public function afrfq_add_product_page_hooks() {

			$sol2_array = array( get_option( 'afrfq_enable_elementor_compt' ), get_option( 'afrfq_enable_divi_compt' ), get_option( 'afrfq_enable_solution2' ) );

			if ( in_array( 'yes', $sol2_array, true ) ) {

				add_action( 'woocommerce_simple_add_to_cart', array( $this, 'afrfq_custom_product_button_elementor' ), 1, 0 );
				add_action( 'woocommerce_variable_add_to_cart', array( $this, 'afrfq_custom_product_button_elementor' ), 1, 0 );
			} else {

				add_action( 'woocommerce_single_product_summary', array( $this, 'afrfq_custom_product_button' ), 1, 0 );
			}
		}

		public function afrfq_custom_product_button_elementor() {

			global $user, $product;

			$quote_button = false;

			foreach ( $this->quote_rules as $rule ) {

				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );

				$istrue = false;

				if ( $quote_button && in_array( $afrfq_is_hide_addtocart, array( 'replace', 'addnewbutton' ), true ) ) {
					continue;
				}

				if ( !$this->afrfq_check_rule_for_product( $product->get_id(), $rule->ID ) ) {
					continue;
				}

				if ( 'replace' === $afrfq_is_hide_addtocart || 'replace_custom' === $afrfq_is_hide_addtocart ) {

					$quote_button = true;

					if ( 'variable' === $product->get_type() ) {
						remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
						add_action( 'woocommerce_single_variation', array( $this, 'afrfq_custom_button_replacement' ), 20 );
					} else {
						remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
						add_action( 'woocommerce_simple_add_to_cart', array( $this, 'afrfq_custom_button_replacement' ), 30 );
					}
				}
				continue;
			}
		}

		public function afrfq_start_customer_session() {

			if ( is_user_logged_in() || is_admin() ) {
				return;
			}

			if ( isset( WC()->session ) ) {
				if ( ! WC()->session->has_session() ) {
					WC()->session->set_customer_session_cookie( true );
				}
			}
		}

		public function afrfq_add_archive_page_hooks() {

			// Replace add to cart button with custom button on shop page.
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'afrfq_replace_loop_add_to_cart_link' ), 10, 2 );

			// Add Custom button along with add to cart button on shop page.
			add_action( 'woocommerce_after_shop_loop_item', array( $this, 'afrfq_custom_add_to_quote_button' ), 11, 2 );

		}

		public function afrfq_front_script() {

			wp_enqueue_style( 'afrfq-front', plugins_url( '../assets/css/afrfq_front.css', __FILE__ ), false, '1.1' );
			wp_enqueue_style( 'jquery-model', plugins_url( '../assets/css/jquery.modal.min.css', __FILE__ ), false, '1.0' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-model', plugins_url( '../assets/js/jquery.modal.min.js', __FILE__ ), array( 'jquery' ), '1.0', true );
			wp_enqueue_script( 'afrfq-frontj', plugins_url( '../assets/js/afrfq_front.js', __FILE__ ), array( 'jquery' ), '1.3', true );

			$afrfq_data = array(
				'admin_url' => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'afquote-ajax-nonce' ),
				'redirect'  => get_option( 'afrfq_redirect_to_quote' ),
				'pageurl'   => get_page_link( get_option( 'addify_atq_page_id', true ) ),
			);
			wp_localize_script( 'afrfq-frontj', 'afrfq_phpvars', $afrfq_data );
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_script( 'Google reCaptcha JS', '//www.google.com/recaptcha/api.js', array( 'jquery' ), '1.0', true );
		}

		public function afrfq_remove_woocommerce_price_html( $price, $product ) {
			global $user;
			$price_txt = $price;

			$args = array(
				'post_type'        => 'addify_rfq',
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
				'suppress_filters' => true,
			);
			
			$quote_button = false;
			
			foreach ( $this->quote_rules as $rule ) {

				$afrfq_rule_type          = get_post_meta( intval( $rule->ID ), 'afrfq_rule_type', true );
				$afrfq_hide_products      = (array) unserialize( get_post_meta( intval( $rule->ID ), 'afrfq_hide_products', true ) );
				$afrfq_hide_categories    = (array) unserialize( get_post_meta( intval( $rule->ID ), 'afrfq_hide_categories', true ) );
				$afrfq_hide_user_role     = (array) unserialize( get_post_meta( intval( $rule->ID ), 'afrfq_hide_user_role', true ) );
				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );

				$istrue = false;

				$applied_on_all_products = get_post_meta( $rule->ID, 'afrfq_apply_on_all_products', true );

				if ( $quote_button && in_array( $afrfq_is_hide_addtocart, array( 'replace', 'addnewbutton' ), true ) ) {
					continue;
				}

				if ( is_user_logged_in() ) {

					//Registred Users
					if ( is_user_logged_in() ) {

						// get Current User Role
						$curr_user      = wp_get_current_user();
						$user_data      = get_user_meta( $curr_user->ID );
						$curr_user_role = $curr_user->roles[0];

						if ( 'yes' === $applied_on_all_products && in_array( $curr_user_role, (array) $afrfq_hide_user_role, true ) ) {
							$istrue = true;
						} elseif ( in_array( $curr_user_role, (array) $afrfq_hide_user_role, true ) && in_array( $product->get_id(), (array) $afrfq_hide_products, false ) ) {
							$istrue = true;
						}

						//Products
						if ( $istrue ) {

							if ( 'yes' === $afrfq_is_hide_price ) {

								$price_txt = $afrfq_hide_price_text;
								?>
								<style>
									.woocommerce-variation-price{ display: none !important;}
								</style>
								<?php
							}
						}

						//Categories
						if ( ! empty( $afrfq_hide_categories ) && ! $istrue ) {

							foreach ( $afrfq_hide_categories as $cat ) {

								if ( has_term( $cat, 'product_cat', $product->get_id() ) ) {

									if ( in_array( $curr_user_role, $afrfq_hide_user_role ) ) {

										if ( 'yes' === $afrfq_is_hide_price ) {

											$price_txt = $afrfq_hide_price_text;
											?>
											<style>
												.woocommerce-variation-price{ display: none !important;}
											</style>
											<?php
										}
									}
								}
							}
						}
					}
				} else {

					if ( in_array( 'guest', (array) $afrfq_hide_user_role, true ) || 'afrfq_for_guest_users' === $afrfq_rule_type ) {

						//Guest Users
						//Products

						if ( 'yes' === $applied_on_all_products ) {
							$istrue = true;
						} elseif ( in_array( $product->get_id(), (array) $afrfq_hide_products, false ) ) {
							$istrue = true;
						}

						if ( $istrue ) {

							if ( 'yes' === $afrfq_is_hide_price ) {

								$price_txt = $afrfq_hide_price_text;
								?>
								<style>
									.woocommerce-variation-price{ display: none !important;}
								</style>
								<?php
							}
						}

						//Categories
						if ( ! empty( $afrfq_hide_categories ) && ! $istrue ) {

							foreach ( $afrfq_hide_categories as $cat ) {

								if ( has_term( $cat, 'product_cat', $product->get_id() ) ) {

									if ( 'yes' === $afrfq_is_hide_price ) {

										$price_txt = $afrfq_hide_price_text;
										?>
										<style>
											.woocommerce-variation-price{ display: none !important;}
										</style>
										<?php
									}
								}
							}
						}
					}
				}
			}

			return $price_txt;

		}

		public function check_required_addons( $product_id ) {
			// No parent add-ons, but yes to global.
			if ( in_array( 'woocommerce-product-addons/woocommerce-product-addons.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$addons = WC_Product_Addons_Helper::get_product_addons( $product_id, false, false, true );

				if ( $addons && ! empty( $addons ) ) {
					foreach ( $addons as $addon ) {
						if ( isset( $addon['required'] ) && '1' === $addon['required'] ) {
							return true;
						}
					}
				}
			}

			return false;
		}

		public function afrfq_check_rule_for_product( $product_id, $rule_id ) {

			$afrfq_rule_type         = get_post_meta( intval( $rule_id ), 'afrfq_rule_type', true );
			$afrfq_hide_products     = (array) unserialize( get_post_meta( intval( $rule_id ), 'afrfq_hide_products', true ) );
			$afrfq_hide_categories   = (array) unserialize( get_post_meta( intval( $rule_id ), 'afrfq_hide_categories', true ) );
			$afrfq_hide_user_role    = (array) unserialize( get_post_meta( intval( $rule_id ), 'afrfq_hide_user_role', true ) );
			$applied_on_all_products = get_post_meta( $rule_id, 'afrfq_apply_on_all_products', true );

			if ( ! is_user_logged_in() ) {

				if ( !in_array( 'guest', (array) $afrfq_hide_user_role, true ) && 'afrfq_for_guest_users' !== $afrfq_rule_type ) {

					return false;
				}

			} else {

				$curr_user      = wp_get_current_user();
				$curr_user_role = current( $curr_user->roles );

				if ( !in_array( $curr_user_role, (array) $afrfq_hide_user_role, true ) ) {
					return false;
				}
			}
			

			if ( 'yes' === $applied_on_all_products ) {
				return true;
			}

			if ( in_array( $product_id, $afrfq_hide_products ) ) {
				return true;
			}

			foreach ( $afrfq_hide_categories as $cat ) {

				if ( !empty( $cat) && has_term( $cat, 'product_cat', $product_id ) ) {

					return true;
				}
			}

			return false;
		}

		public function afrfq_replace_loop_add_to_cart_link( $html, $product ) {

			$pageurl = get_page_link( get_option( 'addify_atq_page_id', true ) );
			
			$cart_txt = $html;

			if ( 'simple' !== $product->get_type() ) {
				return $html;
			}

			if ( !$product->is_in_stock() && 'yes' !== get_option('enable_o_o_s_products') ) {

				return $html;
			}

			$quote_button = false;

			foreach ( $this->quote_rules as $rule ) {

				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );
				$afrfq_custom_button_link = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_link', true );

				$istrue = false;

				if ( $quote_button && in_array( $afrfq_is_hide_addtocart, array( 'replace', 'addnewbutton' ), true ) ) {
					continue;
				}

				if ( !$this->afrfq_check_rule_for_product( $product->get_id(), $rule->ID ) ) {
					continue;
				}

				if ( $this->check_required_addons( $product->get_id() ) ) {
					//WooCommerce Product Add-ons compatibility
					return $html;

				} else {

					if ( 'replace' === $afrfq_is_hide_addtocart ) {
						$quote_button = true;
						$cart_txt     = '<div class="added_quote" id="added_quote' . $product->get_id() . '">' . esc_html( get_option( 'afrfq_pro_success_message' ) ) . '<br /><a href="' . esc_url( $pageurl ) . '">' . esc_html( get_option( 'afrfq_view_button_message' ) ) . '</a></div><a href="javascript:void(0)" rel="nofollow" data-product_id="' . $product->get_ID() . '" data-product_sku="' . $product->get_sku() . '" class="afrfqbt button add_to_cart_button product_type_' . $product->get_type() . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';
					} elseif ( 'replace_custom' === $afrfq_is_hide_addtocart ) {

						if ( ! empty( $afrfq_custom_button_text ) ) {
							$cart_txt = '<a href="' . esc_url( $afrfq_custom_button_link ) . '" rel="nofollow"  class=" button add_to_cart_button product_type_' . $product->get_type() . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';
						} else {

							$cart_txt = '';
						}
					}
				}

			}

			if ( $quote_button ) {
				do_action( 'addify_rfq_after_add_to_quote_button_loop');
			}
			return $cart_txt;
			
		}

		public function afrfq_custom_add_to_quote_button() {

			global $user, $product;

			if ( !$product->is_in_stock() && 'yes' !== get_option('enable_o_o_s_products') ) {
				return;
			}

			$pageurl = get_page_link( get_option( 'addify_atq_page_id', true ) );

			$quote_button = false;

			if ( did_action('addify_rfq_after_add_to_quote_button_loop') ) {
				$quote_button = true;
			}

			foreach ( $this->quote_rules as $rule ) {

				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );
				$afrfq_custom_button_link = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_link', true );

				$istrue = false;

				if ( !$this->afrfq_check_rule_for_product( $product->get_id(), $rule->ID ) ) {
					continue;
				}

				if ( $quote_button && in_array( $afrfq_is_hide_addtocart, array( 'replace', 'addnewbutton' ), true ) ) {
					continue;
				}

				if ( $this->check_required_addons( $product->get_id() ) ) {

					return apply_filters( 'addons_add_to_cart_text', __( 'Select options', 'woocommerce-product-addons' ) );
				} else {

					if ( 'addnewbutton' === $afrfq_is_hide_addtocart && 'simple' === $product->get_type() ) {
						$quote_button = true;
						echo '<div class="added_quote" id="added_quote' . intval( $product->get_id() ) . '">' . esc_html( get_option( 'afrfq_pro_success_message' ) ) . '<br /><a href="' . esc_url( $pageurl ) . '">' . esc_html( get_option( 'afrfq_view_button_message' ) ) . '</a></div><a href="javascript:void(0)" rel="nofollow" data-product_id="' . intval( $product->get_ID() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="afrfqbt button add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';

						return;

					} elseif ( 'addnewbutton_custom' === $afrfq_is_hide_addtocart && 'simple' === $product->get_type() ) {

						if ( ! empty( $afrfq_custom_button_text ) ) {
							echo '<a href="' . esc_url( $afrfq_custom_button_link ) . '" rel="nofollow"  class=" button add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';
						} else {

							echo '';
						}

						return;

					}
				}
			}
		}

		public function afrfq_quote_basket( $items, $args ) {

			if ( is_user_logged_in() ) {
				$user_role = current( wp_get_current_user()->roles );
			} else {
				$user_role = 'guest';
			}

			if ( !empty( get_option('afrfq_customer_roles') ) && in_array( $user_role , (array) get_option('afrfq_customer_roles') ) ) {
				return $items;
			}
			
			$menu_ids   = is_serialized( get_option( 'quote_menu' ) ) ? unserialize( get_option( 'quote_menu' ) ) : get_option( 'quote_menu' );
			$menu_match = false;
			$args_arr   = get_object_vars( $args );
			$menu_match = in_array( (string) $args->menu->term_id, (array) $menu_ids, true ) ? true : false;

			if ( ! $menu_match ) {
				return $items;
			}

			ob_start();
			include AFRFQ_PLUGIN_DIR . 'includes/quote/templates/mini-quote.php';

			return $items . ob_get_clean();

		}

		public function afrfq_custom_product_button() {

			global $user, $product;

			foreach ( $this->quote_rules as $rule ) {

				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );

				$istrue = false;

				if ( !$this->afrfq_check_rule_for_product( $product->get_id(), $rule->ID ) ) {
					continue;
				}

				if ( 'replace' === $afrfq_is_hide_addtocart || 'replace_custom' === $afrfq_is_hide_addtocart ) {

					if ( 'variable' === $product->get_type() ) {

						remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
						add_action( 'woocommerce_single_variation', array( $this, 'afrfq_custom_button_replacement' ), 30 );
						return;
					} else {

						remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
						add_action( 'woocommerce_simple_add_to_cart', array( $this, 'afrfq_custom_button_replacement' ), 30 );
						return;
					}
				}
			}

		}

		public function afrfq_custom_button_replacement() {

			$pageurl = get_page_link( get_option( 'addify_atq_page_id', true ) );

			global $user, $product;

			$quote_button = false;

			foreach ( $this->quote_rules as $rule ) {

				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );
				$afrfq_custom_button_link = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_link', true );

				$istrue = false;

				if ( !$this->afrfq_check_rule_for_product( $product->get_id(), $rule->ID ) ) {
					continue;
				}

				if ( $quote_button && in_array( $afrfq_is_hide_addtocart, array( 'replace', 'addnewbutton' ), true ) ) {
					continue;
				}

				if ( 'variable' === $product->get_type() ) {

					$disable_class = 'disabled wc-variation-selection-needed';
				} else {
					$disable_class = '';
				}

				if ( 'replace' === $afrfq_is_hide_addtocart ) {
					$quote_button = true;
					if ( 'simple' === $product->get_type() ) {

						include_once AFRFQ_PLUGIN_DIR . 'front/templates/simple.php';

					} else {

						include_once AFRFQ_PLUGIN_DIR . 'front/templates/variable.php';

					}
				} elseif ( 'replace_custom' === $afrfq_is_hide_addtocart ) {

					if ( ! empty( $afrfq_custom_button_text ) ) {
						echo '<a href="' . esc_url( $afrfq_custom_button_link ) . '" rel="nofollow" class="button single_add_to_cart_button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';
					} else {
						echo '';
					}
				}
			}
		}

		public function afrfq_custom_button_add_replacement() {

			$pageurl = get_page_link( get_option( 'addify_atq_page_id', true ) );

			global $user, $product;
			
			$quote_button = false;

			if ( did_action('addify_after_add_to_quote_button') ) {
				$quote_button = true;
			}

			foreach ( $this->quote_rules as $rule ) {

				$afrfq_is_hide_price      = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_price', true );
				$afrfq_hide_price_text    = get_post_meta( intval( $rule->ID ), 'afrfq_hide_price_text', true );
				$afrfq_is_hide_addtocart  = get_post_meta( intval( $rule->ID ), 'afrfq_is_hide_addtocart', true );
				$afrfq_custom_button_text = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_text', true );
				$afrfq_custom_button_link = get_post_meta( intval( $rule->ID ), 'afrfq_custom_button_link', true );

				$istrue = false;

				if ( !$this->afrfq_check_rule_for_product( $product->get_id(), $rule->ID ) ) {
					continue;
				}

				if ( $quote_button && in_array( $afrfq_is_hide_addtocart, array( 'replace', 'addnewbutton' ), true ) ) {
					continue;
				}

				if ( 'variable' === $product->get_type() ) {

					$disable_class = 'disabled wc-variation-selection-needed';
				} else {
					$disable_class = '';
				}

				if ( 'addnewbutton' === $afrfq_is_hide_addtocart ) {
					$quote_button = true;
					echo '<a href="javascript:void(0)" rel="nofollow" data-product_id="' . intval( $product->get_ID() ) . '" data-product_sku="' . esc_attr( $product->get_sku() ) . '" class="afrfqbt_single_page single_add_to_cart_button button alt  ' . esc_attr( $disable_class ) . '   product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';
				} elseif ( 'addnewbutton_custom' === $afrfq_is_hide_addtocart ) {

					if ( ! empty( $afrfq_custom_button_text ) ) {

						echo '<a href="' . esc_url( $afrfq_custom_button_link ) . '" rel="nofollow" class="button product_type_' . esc_attr( $product->get_type() ) . '">' . esc_attr( $afrfq_custom_button_text ) . '</a>';

					} else {

						echo '';
					}
				}
			}
		}

		public function addify_quote_request_page_shortcode_function() {

			ob_start();

			if ( file_exists( get_template_directory() . '/woocommerce/addify/rfq/emails/addify-quote-request-page.php' ) ) {
				require_once get_template_directory() . '/woocommerce/addify/rfq/front/addify-quote-request-page.php';
			} else {
				require_once AFRFQ_PLUGIN_DIR . 'front/templates/addify-quote-request-page.php';
			}

			return ob_get_clean();
		}

		public function addify_add_endpoints() {

			add_rewrite_endpoint( 'request-quote', EP_ROOT | EP_PAGES );
			flush_rewrite_rules();
		}

		public function addify_add_query_vars( $vars ) {
			$vars[] = 'request-quote';
			return $vars;
		}

		public function addify_endpoint_title( $title ) {
			global $wp_query;
			$is_endpoint = isset( $wp_query->query_vars['request-quote'] );
			if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				// New page title.
				$title = esc_html__( 'Quotes', 'addify_rfq' );
				remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
			}
			return $title;
		}

		public function addify_new_menu_items( $items ) {
			// Remove the logout menu item.
			$logout = $items['customer-logout'];
			unset( $items['customer-logout'] );
			// Insert your custom endpoint.
			$items['request-quote'] = esc_html__( 'Quotes', 'addify_rfq' );
			// Insert back the logout item.
			$items['customer-logout'] = $logout;
			return $items;
		}

		public function addify_endpoint_content() {

			//Single Quote

			$statuses = array(
				'af_pending'    => __( 'Pending', 'addify_rfq' ),
				'af_in_process' => __( 'In Process', 'addify_rfq' ),
				'af_accepted'   => __( 'Accepted', 'addify_rfq' ),
				'af_converted'  => __( 'Converted to Order', 'addify_rfq' ),
				'af_declined'   => __( 'Declined', 'addify_rfq' ),
				'af_cancelled'  => __( 'Cancelled', 'addify_rfq' ),
			);

			$afrfq_id = get_query_var( 'request-quote' );

			$quote = get_post( $afrfq_id );

			if ( ! empty( $afrfq_id ) && is_a( $quote, 'WP_Post' ) ) {
				$quotedataid = get_post_meta( $afrfq_id, 'quote_proid', true );

				if ( ! empty( $quotedataid ) ) {
					include_once AFRFQ_PLUGIN_DIR . 'front/templates/quote-details-my-account-old-quotes.php';
				} else {
					include_once AFRFQ_PLUGIN_DIR . 'front/templates/quote-details-my-account.php';
				}
			} else {

				$customer_quotes = get_posts(
					array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => get_current_user_id(),
						'post_type'   => 'addify_quote',
						'post_status' => 'publish',
					)
				);

				include_once AFRFQ_PLUGIN_DIR . 'front/templates/quote-list-table.php';
			}
		}
	}
	new AF_R_F_Q_Front();
}
