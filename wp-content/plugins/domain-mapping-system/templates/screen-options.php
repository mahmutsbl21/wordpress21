<div id="screen-meta" class="metabox-prefs">
    <div id="screen-options-wrap" class="" tabindex="-1" aria-label="Screen Options Tab">
        <form id="adv-settings" method="post" action="<?= admin_url( 'admin-post.php' ); ?>">
            <fieldset class="screen-options">
                <legend><?php echo __( 'Pagination', 'domain-mapping-system' ) ?></legend>
                <label for="edit_mappings_per_page"><?php echo __( 'Number of mappings per page:', 'domain-mapping-system' ) ?></label>
                <input type="number" step="1" min="1" class="screen-per-page" name="dms_mappings_per_page"
                       id="edit_mappings_per_page" value="<?php echo $items_per_page ?>">
                <br/><br/>
                <label for="edit_values_per_mapping"><?php echo __( 'Number of values per mapping:', 'domain-mapping-system' ) ?></label>
                <input type="number" step="1" min="1" class="screen-per-page" name="dms_values_per_mapping"
                       id="edit_values_per_mapping" value="<?php echo $values_per_mapping ?>">
            </fieldset>
            <p class="submit"><input type="submit" name="screen-options-apply" id="screen-options-apply"
                                     class="button button-primary" value="Apply"></p>
            <input name="action" value="save_dms_screen_options" type="hidden">
			<?php wp_nonce_field( 'save_dms_screen_options', 'save_dms_screen_options_nonce' ) ?>
        </form>
    </div>
</div>
<div id="screen-meta-links">
    <div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
        <button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap"
                aria-expanded="true"><?php echo __( 'Screen Options', 'domain-mapping-system' ) ?></button>
    </div>
</div>