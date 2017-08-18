<?php

/**
 * WC_Product_Batch class.
 */
class WC_Product_Batch {

	var $admin;

	/**
	 * __construct function.
	 */
	function __construct() {

		// Admin section
		if ( is_admin() ) {
			include_once( 'class-wc-product-batch-admin.php' );
			$this->admin = new WC_Product_Batch_Admin();
		}
		
		// Hook into status order changed changed to potentially link an order item to a product batch code
		add_action( 'woocommerce_order_status_changed', array( $this, 'link_batch_to_completed_order' ), 10, 3 ) ;

		// Hook into WooCommerce reports to add batch reports by order and customer
		add_filter('woocommerce_admin_reports', array( $this, 'batch_report_tab' ));

	}

	/**
	 * Get the plugin path
	 */
	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
	}

	/**
	 * Links the order item(s) of an order to product batch(es)
	 *
	 * Filter 'wc_product_batch_order_status' enables the default order status to be overriden, default is 'completed'
	 *
	 * Action 'wc_product_batch_order_item_batch_code' hook fires when an order item has been linked to a batch code
	 */
	function link_batch_to_completed_order( $order_id, $old_status, $new_status ) {	
		// Enable override of which status to process batch per order
		$batch_order_status = apply_filters( 'wc_product_batch_order_status', 'completed' );

		// If not correct status, return
		if( $batch_order_status != $new_status ) {
			return;
		}

		global $wpdb;
		
		$order = wc_get_order( $order_id );
		$items = $order->get_items();

	    foreach ( $items as $item_id => $item_data ) {
	    	
	    	if ( !empty($item_data['variation_id']) ) {
				$product_id = $item_data['variation_id'];
			} else {
				$product_id = $item_data['product_id'];
			}

	    	// Get batches sorted by priorty for batches with quantity greater than 0, for the given product_id
	    	// Need to get all batches to reduce quantity in more than one batch code if needed
	    	$order_item_batch_code_res = $wpdb->get_results( "SELECT pb_id, batch_code, batch_qty FROM {$wpdb->prefix}woocommerce_product_batch WHERE product_id = " . $product_id . " and batch_qty > 0 order by batch_pri;" );

	    	if ( $order_item_batch_code_res ) {

		    	$batch_item_qty = $item_data['qty'];
		    	$break_flag = false;
	    		
	    		foreach ( $order_item_batch_code_res as $order_item_batch_code ) {
		    		// Link the batch code with the order item via order item meta
		    		$res_order_item_batch = wc_add_order_item_meta($item_id , 'batch_code', $order_item_batch_code->batch_code);

		    		if( $res_order_item_batch ) {
		    			// Action to inform that an order item has been linked to a batch code
		    			do_action( 'wc_product_batch_order_item_batch_code', $item_id, $order_item_batch_code->batch_code, $res_order_item_batch );

		    			$new_batch_qty = $order_item_batch_code->batch_qty - $batch_item_qty;

		    			// If the new batch quantity is less than 0, then we must loop further and reduce quantity in another batch code for the same product
		    			if( $new_batch_qty < 0 ) {
		    				$batch_item_qty = abs($new_batch_qty);
		    				$new_batch_qty = 0;
		    			} else {
		    				$break_flag = true; // break flag since the whole quantity was reduce in this batch
		    			}

		    			// Update new quantity for a given batch id
		    			$wpdb->query(
							"UPDATE {$wpdb->prefix}woocommerce_product_batch
							SET batch_qty = $new_batch_qty
							WHERE product_id = $product_id 
								AND pb_id = $order_item_batch_code->pb_id;"
						);

		    			// Break for loop since the ordered quantity was decremented from the batches
						if( $break_flag ) {
							break;
						}
		    		}
	    		}	    		
	    	}
	    }
	}

	/**
	 * Add a new batch report under WooCommerce Reports submenu.
	 */
	function batch_report_tab($reports) {
		
		$reports['batch'] = array(
			'title'       => __( 'Batches', 'wc-product-batch' ),
			'reports' => array(
				"customers_by_batch" => array(
					'title'       => __( 'Customers by batch', 'wc-product-batch' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => array( __CLASS__, 'get_report' )
				),
				"orders_by_batch" => array(
					'title'       => __( 'Orders by batch', 'wc-product-batch' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => array( __CLASS__, 'get_report' )
				)
			)
		);
		
		return $reports;
	}

	/**
	 * Get a report from our reports subfolder.
	 */
	public static function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Report_' . str_replace( '-', '_', $name );

		include_once( WC_Product_Batch::plugin_path() . '/classes/class-wc-report-' . $name . '.php' );

		if ( ! class_exists( $class ) )
			return;

		$report = new $class();
		$report->output_report();
	}
}

$GLOBALS['wc-product-batch'] = new WC_Product_Batch();