<div class="wrap woocommerce product_batch">
	<div id="icon-woocommerce" class="icon32 icon32-woocommerce-email"></div>
	<h2>
    	<?php _e('Batches', 'wc-product-batch'); ?>

    	<a href="<?php echo admin_url( 'admin.php?page=wc-product-batch&amp;add=true' ); ?>" class="add-new-h2"><?php _e('Add batch', 'wc-product-batch'); ?></a>
    </h2><br/>
    
    <form method="post">
    <?php
	    $table = new WC_Product_Batch_Table();
	    $table->prepare_items();
	    $table->display()
    ?>
    </form>
</div>
<script type="text/javascript">
	
	jQuery('a.submitdelete').live('click', function(){
		var answer = confirm('<?php _e( 'Are you sure you want to delete this batch?', 'wc-product-batch' ); ?>');
		if (answer){
			return true;
		}
		return false;
	});
	
</script>