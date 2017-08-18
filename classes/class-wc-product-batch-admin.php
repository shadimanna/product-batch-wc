<?php

/**
 * WC_Product_Batch_Admin class.
 */
class WC_Product_Batch_Admin {

	private $editing;
	private $editing_id;

	/**
	 * __construct function.
	 */
	function __construct() {
		// Admin menu
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'woocommerce_screen_ids', array( $this, 'screen_ids' ) );
	}

	/**
	 * Screen ids
	 */
	public function screen_ids( $ids ) {
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );

		$ids[] = $wc_screen_id . '_page_product-batch';

		return $ids;
	}

	/**
	 * admin_menu function.
	 */
	function admin_menu() {
		$page = add_submenu_page( 'edit.php?post_type=product', __( 'Product Batch', 'wc-product-batch' ), __( 'Batches', 'wc-product-batch' ), 'manage_woocommerce', 'wc-product-batch', array( $this, 'admin_screen' ) );

		if ( function_exists( 'woocommerce_admin_css' ) )
			add_action( 'admin_print_styles-'. $page, 'woocommerce_admin_css' );
		add_action( 'admin_print_styles-'. $page, array( $this, 'admin_enqueue' ) );
	}

	/**
	 * admin_enqueue function.
	 */
	function admin_enqueue() {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.3.0', '<' ) ) {
			wp_enqueue_script( 'woocommerce_admin' );
			wp_enqueue_script( 'chosen' );
		}

		wp_enqueue_style( 'batch_css', plugins_url( 'assets/css/admin.css' , dirname( __FILE__ ) ) );
	}

	/**
	 * admin_screen function.
	 */
	function admin_screen() {
		global $wpdb;

		$admin = $this;

		if ( ! empty( $_GET['delete'] ) ) {

			check_admin_referer( 'delete_notification' );

			$delete = absint( $_GET['delete'] );

			$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_product_batch WHERE pb_id = {$delete};" );

			echo '<div class="updated fade"><p>' . __( 'Batch deleted successfully', 'wc-product-batch' ) . '</p></div>';

		} elseif ( ! empty( $_GET['add'] ) ) {

			if ( ! empty( $_POST['save_recipient'] ) ) {

				check_admin_referer( 'woocommerce_save_batch' );

				$result = $this->add_recipient();

				if ( is_wp_error( $result ) ) {
					echo '<div class="error"><p>' . $result->get_error_message() . '</p></div>';
				} elseif ( $result ) {
					echo '<div class="updated fade"><p>' . __( 'Batch saved successfully', 'wc-product-batch' ) . '</p></div>';
				}

			}

			include_once( 'includes/admin-screen-edit.php' );
			return;

		} elseif ( ! empty( $_GET['edit'] ) ) {

			$this->editing_id = absint( $_GET['edit'] );
			$this->editing = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}woocommerce_product_batch WHERE pb_id = " . $this->editing_id . ";" );

			if ( ! empty( $_POST['save_recipient'] ) ) {

				check_admin_referer( 'woocommerce_save_batch' );

				$result = $this->save_recipient();

				if ( is_wp_error( $result ) ) {
					echo '<div class="error"><p>' . $result->get_error_message() . '</p></div>';
				} elseif ( $result ) {
					echo '<div class="updated fade"><p>' . __( 'Batch saved successfully', 'wc-product-batch' ) . '</p></div>';
				}

			}

			include_once( 'includes/admin-screen-edit.php' );
			return;
		}

		if ( ! empty( $_GET['success'] ) ) {
			echo '<div class="updated fade"><p>' . __( 'Batch saved successfully', 'wc-product-batch' ) . '</p></div>';
		}

		if ( ! empty( $_GET['deleted'] ) ) {
			echo '<div class="updated fade"><p>' . __( 'Batch deleted successfully', 'wc-product-batch' ) . '</p></div>';
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		include_once( 'class-wc-product-batch-table.php' );
		include_once( 'includes/admin-screen.php' );
	}


	/**
	 * field_value function.
	 */
	function field_value( $name ) {
		global $wpdb;

		$value = '';

		if ( isset( $this->editing->$name ) ) {
			$value = $this->editing->$name;
		}

		$value = maybe_unserialize( $value );

		if ( isset( $_POST[ $name ] ) ) {
			$value = $_POST[ $name ];
		}

		if ( is_array( $value ) ) {
			$value = array_map( 'trim', array_map( 'esc_attr', array_map( 'stripslashes', $value ) ) );
		} else {
			$value = trim( esc_attr( stripslashes( $value ) ) );
		}

		return $value;
	}

	/**
	 * add_recipient function.
	 */
	function add_recipient() {
		global $wpdb;

		$batch_code 	= sanitize_text_field( stripslashes( $_POST['batch_code'] ) );
		$batch_notes	= sanitize_text_field( stripslashes( $_POST['batch_notes'] ) );
		$batch_qty 		= sanitize_text_field( stripslashes( $_POST['batch_qty'] ) );
		$batch_pri 		= sanitize_text_field( stripslashes( $_POST['batch_pri'] ) );
		$batch_product 	= sanitize_text_field( stripslashes( $_POST['batch_product'] ) );

		// Validate
		if ( empty( $batch_code ) ) {
			return new WP_Error( 'input', __( 'Batch Code is a required field', 'wc-product-batch' ) );
		}

		if ( empty( $batch_qty ) ) {
			return new WP_Error( 'input', __( 'Batch Quantity is a required field', 'wc-product-batch' ) );
		}

		if ( empty( $batch_pri ) ) {
			return new WP_Error( 'input', __( 'Batch Priority is a required field', 'wc-product-batch' ) );
		}

		if ( !is_numeric( $batch_qty ) ) {
			return new WP_Error( 'input', __( 'Batch Quantity must be a number', 'wc-product-batch' ) );
		}

		if ( !is_numeric( $batch_pri ) ) {
			return new WP_Error( 'input', __( 'Batch Priority must be a number', 'wc-product-batch' ) );
		}

		// Insert recipient
		$result = $wpdb->insert(
			"{$wpdb->prefix}woocommerce_product_batch",
			array(
				'batch_code' 	=> $batch_code,
				'batch_notes' 	=> $batch_notes,
				'batch_qty' 	=> $batch_qty,
				'batch_pri' 	=> $batch_pri,
				'product_id'	=> $batch_product
			),
			array(
				'%s', '%s', '%s', '%s', '%s'
			)
		);

		$id = $wpdb->insert_id;

		if ( $result && $id ) {
			return true;
		}

		return false;
	}

	/**
	 * save_recipient function.
	 */
	function save_recipient() {
		global $wpdb;

		$batch_code 	= sanitize_text_field( stripslashes( $_POST['batch_code'] ) );
		$batch_notes	= sanitize_text_field( stripslashes( $_POST['batch_notes'] ) );
		$batch_qty 		= sanitize_text_field( stripslashes( $_POST['batch_qty'] ) );
		$batch_pri 		= sanitize_text_field( stripslashes( $_POST['batch_pri'] ) );
		$batch_product 	= sanitize_text_field( stripslashes( $_POST['batch_product'] ) );
		
		// Validate
		if ( empty( $batch_code ) ) {
			return new WP_Error( 'input', __( 'Batch name is a required field', 'wc-product-batch' ) );
		}

		if ( empty( $batch_qty ) ) {
			return new WP_Error( 'input', __( 'Batch id is a required field', 'wc-product-batch' ) );
		}

		if ( empty( $batch_pri ) ) {
			return new WP_Error( 'input', __( 'Batch key is a required field', 'wc-product-batch' ) );
		}

		if ( !is_numeric( $batch_qty ) ) {
			return new WP_Error( 'input', __( 'Batch Quantity must be a number', 'wc-product-batch' ) );
		}

		if ( !is_numeric( $batch_pri ) ) {
			return new WP_Error( 'input', __( 'Batch Priority must be a number', 'wc-product-batch' ) );
		}

		// Insert recipient
		$wpdb->update(
			"{$wpdb->prefix}woocommerce_product_batch",
			array(
				'batch_code' 			=> $batch_code,
				'batch_notes' 	=> $batch_notes,
				'batch_qty' 			=> $batch_qty,
				'batch_pri' 			=> $batch_pri,
				'product_id'			=> $batch_product
			),
			array( 'pb_id' => absint( $this->editing_id ) ),
			array(
				'%s', '%s', '%s', '%s', '%s'
			),
			array( '%d' )
		);

		return true;
	}
}
