<div class="wrap woocommerce">
	<div id="icon-woocommerce" class="icon32 icon32-woocommerce-email"></div>
	<h2><?php if( isset( $_REQUEST['edit'] ) ) { _e('Edit Batch', 'wc-product-batch'); } else { _e('Add Batch', 'wc-product-batch'); }  ?></h2>

	<form class="add" method="post">

		<h3><?php _e( 'Batch', 'wc-product-batch' ); ?></h3>
		<p><?php _e( 'These fields determine which product a batch should be linked to.', 'wc-product-batch' ); ?></p>
		<table class="form-table">
			<tr>
				<th>
					<label for="batch_code"><?php _e( 'Batch Code', 'wc-product-batch' ); ?></label>
				</th>
				<td>
					<input type="text" name="batch_code" id="batch_code" class="input-text regular-text" value="<?php echo $admin->field_value( 'batch_code' ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="batch_notes"><?php _e( 'Batch Notes', 'wc-product-batch' ); ?></label>
				</th>
				<td>
					<textarea name="batch_notes" id="batch_notes" class="input-text regular-text" cols="25" rows="3"><?php echo $admin->field_value( 'batch_notes' ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="batch_qty"><?php _e( 'Batch Quantity', 'wc-product-batch' ); ?></label>
				</th>
				<td>
					<input type="text" name="batch_qty" id="batch_qty" class="input-text regular-text" value="<?php echo $admin->field_value( 'batch_qty' ); ?>" />
				</td>
			</tr>
			<tr>
				<th>
					<label for="batch_pri"><?php _e( 'Batch Priority', 'wc-product-batch' ); ?></label>
				</th>
				<td>
					<input type="text" name="batch_pri" id="batch_pri" class="input-text regular-text" value="<?php echo $admin->field_value( 'batch_pri' ); ?>" />
				</td>
			</tr>
		</table>

		<h3><?php _e( 'Linked Product', 'wc-product-batch' ); ?></h3>
		<p><?php _e( 'You can choose which Simple Product or Product Variation to link the batch to.', 'wc-product-batch' ); ?></p>
		<table class="form-table">
			<tr>
				<th>
					<label for="batch_product"><?php _e( 'Product List', 'wc-product-batch' ); ?></label>
				</th>
				<td>
					<?php
						$product_id = $admin->field_value( 'product_id' );
					?>
					<select id="batch_product" name="batch_product" data-placeholder="<?php _e('Choose Page&hellip;', 'wc_table_rate'); ?>" class="wc-enhanced-select chosen_select">
						<option value="none"><?php _e( '- none -', 'wc-product-batch' ); ?></option>
						
							<?php
								
								$args = array( 'post_type' => array('product'), 'posts_per_page' => -1 );

								// Filter to change the products query to display in the drop down
								$args = apply_filters( 'wc_product_batch_product_query', $args );

								$loop = new WP_Query( $args );

								while ( $loop->have_posts() ) : $loop->the_post();
									$theid = get_the_ID();
									$thetitle = get_the_title();
									$product_obj = wc_get_product( $theid );
									
									if ($product_obj->product_type == 'variable') {
										$args = array(
									        'post_parent' => $theid,
									        'post_type'   => 'product_variation',
									        'numberposts' => -1,
									    );
									    $variations = $product_obj->get_available_variations();
									
									    echo '<optgroup label="' . $thetitle . '">';

									    foreach ($variations as $value) {
									    	$var_id = $value['variation_id'];
									    	$var_name = $value['attributes']['attribute_option'];

									    	echo '<option value="' . $var_id . '" ' . selected( $var_id, $product_id ) . '>' . $var_name . '</option>';
									    }

									    echo '</optgroup>';
									} else {

										echo '<option value="' . $theid . '" ' . selected( $theid, $product_id ) . '>' . $thetitle . '</option>';

									}
								endwhile; wp_reset_query();
							?>
						
					</select>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button button-primary" name="save_recipient" value="<?php _e('Save changes', 'wc-product-batch'); ?>" />
			<?php wp_nonce_field( 'woocommerce_save_batch' ); ?>
		</p>

	</form>

	<?php if ( version_compare( WOOCOMMERCE_VERSION, '2.3.0', '<' ) ) : ?>
		<script type="text/javascript">
			jQuery(function() {
				jQuery( 'select.chosen_select' ).chosen();
			});
		</script>
	<?php endif; ?>
</div>