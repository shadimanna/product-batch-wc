<?php
/**
 * Plugin Name: Product Batch for WooCommerce
 * Description: Add a batch code for each product under "Products->Batches" submenu to track ordered products back to manufacturing batches. See batch reports under "WooCommerce->Reports" submenu.
 * Note - The plugin scope does not include a synchonization with inventory stock management, it is independent of it.
 * Version: 1.0.0
 * Author: Shadi Manna
 * Author URI: http://progressusmarketing.com/
 * License: GPLv3
 */

/**
 * Required functions
 */
if ( ! class_exists( 'WC_Dependencies' ) )
  require_once ( 'woo-includes/class-wc-dependencies.php' );

/**
 * WC Detection
 */
if ( ! function_exists( 'is_woocommerce_active' ) ) {
  function is_woocommerce_active() {
    return WC_Dependencies::woocommerce_active_check();
  }
}

/**
 * Localisation
 **/
load_plugin_textdomain( 'wc-product-batch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


/**
 * init_product_batch function.
 */
function init_product_batch() {
	if ( is_woocommerce_active() ) {
		include_once( 'classes/class-wc-product-batch.php' );
	}
}

add_action( 'plugins_loaded', 'init_product_batch', 0 );


/**
 * Activation
 */
register_activation_hook( __FILE__, 'activate_product_batch' );

function activate_product_batch() {
	global $wpdb;

	$wpdb->hide_errors();

	$collate = '';
    if ( $wpdb->has_cap( 'collation' ) ) {
		if ( ! empty($wpdb->charset ) ) {
			$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty($wpdb->collate ) ) {
			$collate .= " COLLATE $wpdb->collate";
		}
    }

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    /**
     * Table for product batch
     */
    $sql = "
CREATE TABLE {$wpdb->prefix}woocommerce_product_batch (
  pb_id bigint(20) NOT NULL auto_increment,
  batch_code varchar(200) NULL,
  batch_notes LONGTEXT NULL,
  batch_qty bigint(20) NULL,
  batch_pri bigint(20) NULL,
  product_id bigint(20) NULL,
  PRIMARY KEY  (pb_id)
) $collate;
";
    dbDelta( $sql );
}
