<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class WC_Report_Customers_By_Batch extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct( array(
			'singular'  => __( 'Batch', 'woocommerce' ),
			'plural'    => __( 'Batches', 'woocommerce' ),
			'ajax'      => false
		) );
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		_e( 'No customers found.', 'woocommerce' );
	}

	/**
	 * Output the report.
	 */
	public function output_report() {
		$this->prepare_items();

		echo '<div id="poststuff" class="woocommerce-reports-wide">';

		echo '<form method="post" id="woocommerce_batch_by_customers">';

		$this->display();

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Get column value.
	 *
	 * @param $batches
	 * @param string $column_name
	 * @return string
	 */
	public function column_default( $batches, $column_name ) {
		global $wpdb;

		switch ( $column_name ) {

			case 'batch_code' :
				return $batches->batch_code;
			break;
			case 'emails' :

				// Get list of emails and add a 'mailto:' link so they can emailed if there is an issue with a batch
				$email_arr = explode(',', $batches->emails);
				array_walk($email_arr, function(&$value, $key) { $value = '<a href="mailto:' . trim($value) . '">' . trim($value) . '</a>'; });

				return implode(', ', $email_arr);

			break;
		}

		return '';
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'batch_code'        => __( 'Batch Code', 'woocommerce' ),
			'emails'           => __( 'Customer Emails', 'woocommerce' )
		);

		return $columns;
	}

	/**
	 * Prepare customer list items.
	 */
	public function prepare_items() {
		global $wpdb;

		$current_page = absint( $this->get_pagenum() );
		$per_page     = 20;

		/**
		 * Init column headers.
		 */
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		/**
		 * Query to get all customer order emails by batch code.
		 * Using '_billing_email' in case the user is a guest and has no user account
		 */
		$this->items = $wpdb->get_results( "SELECT DISTINCT(oim.meta_value) AS batch_code, GROUP_CONCAT(DISTINCT(pm.meta_value) SEPARATOR ', ') AS emails FROM {$wpdb->prefix}woocommerce_order_itemmeta oim 
			INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON (oim.order_item_id = oi.order_item_id)
			INNER JOIN {$wpdb->prefix}posts p ON (oi.order_id = p.ID)
			INNER JOIN {$wpdb->prefix}postmeta pm ON (p.ID = pm.post_id)
			WHERE oim.meta_key = 'batch_code' AND pm.meta_key = '_billing_email'
			GROUP BY oim.meta_value
			ORDER BY oim.meta_value" );

		/**
		 * Pagination.
		 */
		$this->set_pagination_args( array(
			'total_items' => $wpdb->num_rows,
			'per_page'    => $per_page,
			'total_pages' => ceil( $wpdb->num_rows / $per_page )
		) );
	}
}
