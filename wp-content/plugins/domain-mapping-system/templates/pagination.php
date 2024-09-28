<?php $page = ! empty( $_GET['paged'] ) ? $_GET['paged'] : 1 ?>
<span class="pagination-links">
	<a href="<?php echo site_url() ?>/wp-admin/admin.php?page=domain-mapping-system&paged=1"
       class="first-page button disabled">
		<span class="screen-reader-text"><?php echo __('Next page', 'domain-mapping-system'); ?></span>
		<span aria-hidden="true">«</span>
	</a>
	<a href="<?= site_url() ?>/wp-admin/admin.php?page=domain-mapping-system&paged=<?= $page - 1 ?>"
       class="prev-page button disabled">
		<span class="screen-reader-text"><?php echo __('Last page', 'domain-mapping-system'); ?></span>
		<span aria-hidden="true">‹</span>
	</a>
	<span class="screen-reader-text"><?php echo __('Current Page', 'domain-mapping-system'); ?></span>
	<span id="table-paging" class="paging-input">
		<span class="tablenav-paging-text">
			<?php echo $page ?> <?php echo __('of', 'domain-mapping-system'); ?> <span class="total-pages"></span>
		</span>
	</span>
	<a href="<?php echo site_url() ?>/wp-admin/admin.php?page=domain-mapping-system&paged=<?php echo $page + 1 ?>"
       class="next-page button disabled">
		<span class="screen-reader-text"><?php echo __('Next page', 'domain-mapping-system'); ?></span>
		<span aria-hidden="true">›</span>
	</a>
	<a href="<?php echo site_url() ?>/wp-admin/admin.php?page=domain-mapping-system>"
       class="last-page button disabled">
		<span class="screen-reader-text"><?php echo __('Last page', 'domain-mapping-system'); ?></span>
		<span aria-hidden="true">»</span>
	</a>
</span>