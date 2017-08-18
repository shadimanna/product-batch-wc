<?php
/**
 * WC_Product_Batch_Table class.
 *
 * @extends WP_List_Table
 */
class WC_Product_Batch_Table extends WP_List_Table {

	/**
	 * __construct function.
	 */
	function __construct(){
		parent::__construct( array(
			'singular'  => 'batch',
			'plural'    => 'batches',
			'ajax'      => false
		) );
	}

	/**
	 * column_default function.
	 *
	 * @access public
	 * @param mixed $item
	 * @param mixed $column_name
	 * @return void
	 */
	function column_default( $item, $column_name ) {
		global $woocommerce, $wpdb;

		switch( $column_name ) {
			case 'batch_code' :
				$return = $item->batch_code;

				$return = wpautop( $return );

				$return .= '
				<div class="row-actions">
					<span class="edit"><a href="' . admin_url( 'admin.php?page=wc-product-batch&edit=' . $item->pb_id ) . '">' . __( 'Edit', 'wc-product-batch' ) . '</a> | </span><span class="trash"><a class="submitdelete" href="' . wp_nonce_url( admin_url( 'admin.php?page=wc-product-batch&delete=' . $item->pb_id ), 'delete_notification' ) . '">' . __( 'Delete', 'wc-product-batch' ) . '</a></span>
				</div>';

				return $return;
			break;
			case 'batch_notes' :
				$return = $item->batch_notes;
				return $return;
			break;
			case 'batch_qty' :
				$return = $item->batch_qty;
				return $return;
			break;
			case 'batch_pri' :
				$return = $item->batch_pri;
				return $return;
			break;
			case 'product_id' :

				if( empty($item->product_id) ) {
					return '-';
				} else {
					$product = wc_get_product( $item->product_id );					
					
				    if ($product->product_type == 'variation') {
				    	return strip_tags($product->get_formatted_variation_attributes( ));
					} else {
						return $product->get_title();
					}
				}

			break;
		}
	}

	/**
	 * column_cb function.
	 *
	 * @access public
	 * @param mixed $item
	 * @return void
	 */
	function column_cb( $item ){
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />' , $item->pb_id );
	}

	/**
	 * get_columns function.
	 *
	 * @access public
	 * @return void
	 */
	function get_columns(){
		$columns = array(
			'cb'        	=> '<input type="checkbox" />',
			'batch_code'    => __( 'Batch Name', 'wc-product-batch' ),
			'batch_notes' 	=> __( 'Batch Notes', 'wc-product-batch' ),
			'batch_qty' 	=> __( 'Batch Quantity', 'wc-product-batch' ),
			'batch_pri' 	=> __( 'Batch Priority', 'wc-product-batch' ),
			'product_id' 	=> __( 'Linked Product', 'wc-product-batch' )
		);
		return $columns;
	}

	 /**
	 * Get bulk actions
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete'    => __( 'Delete', 'wc-product-batch' )
		);
		return $actions;
	}

	/**
	 * Process bulk actions
	 */
	function process_bulk_action() {
		global $wpdb;

		if ( ! $this->current_action() )
			return;

		$batch_qtys = array_map( 'intval', $_POST['id'] );

		if ( $batch_qtys ) {

			if ( 'delete' === $this->current_action() ) {

			   foreach ( $batch_qtys as $batch_qty ) {

				   $batch_qty = absint( $batch_qty );

				   $wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_product_batch WHERE pb_id = {$batch_qty};" );
			   }
			}

			echo '<div class="updated"><p>' . __( 'Batches updated', 'wc-product-batch' ) . '</p></div>';
		}
	}


	/**
	 * prepare_items function.
	 *
	 * @access public
	 * @return void
	 */
	function prepare_items() {
		global $wpdb;

		/**
		 * Init column headers
		 */
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		/**
		 * Process bulk actions
		 */
		$this->process_bulk_action();

		/**
		 * Get experiements
		 */
		$count = $wpdb->get_var( "SELECT COUNT(pb_id) FROM {$wpdb->prefix}woocommerce_product_batch;" );

		$this->items = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_product_batch LIMIT " . ( 25 * ( $this->get_pagenum() - 1 ) ) . ", 25;" );

		/**
		 * Handle pagination
		 */
		$this->set_pagination_args( array(
			'total_items' => $count,
			'per_page'    => 25,
			'total_pages' => ceil( $count / 25 )
		) );
	}

}