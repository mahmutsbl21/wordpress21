<?php

use DMS\Includes\Data_Objects\Mapping;
use DMS\Includes\Data_Objects\Setting;
if ( !empty( $dms_fs ) && $dms_fs instanceof Freemius ) {
    // Get screen options
    $items_per_page = get_option( 'dms_mappings_per_page', 10 );
    $values_per_mapping = get_option( 'dms_values_per_mapping', 5 );
    // Retrieve global mapping options
    $archive_global_mapping = get_option( 'dms_archive_global_mapping' );
    $woo_shop_global_mapping = get_option( 'dms_woo_shop_global_mapping' );
    $dms_global_parent_page_mapping = get_option( 'dms_global_parent_page_mapping' );
    include_once plugin_dir_path( __FILE__ ) . 'screen-options.php';
    ?>
    <div class="dms-n">
        <form id="dms-map" method="post" action="<?php 
    echo admin_url( 'admin-post.php' );
    ?>">
            <div class="dms-n-row dms-n-config dms-n-config-fixed">
                <div class="dms-n-loading-container"><div class="dms-n-loader"></div></div>
                <h3 class="dms-n-row-header"><?php 
    _e( 'Domain Mapping System Configuration', 'domain-mapping-system' );
    ?></h3>
                <p class="dms-n-row-subheader">
                    <span class="dms-n-row-subheader-important"><?php 
    _e( 'Important!', 'domain-mapping-system' );
    ?></span>
                    <span>
                         <?php 
    printf( __( 'This plugin requires configuration with your DNS host and on your server (cPanel, etc). Please see %1$sour documentation%2$s for configuration requirements.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                </span>
                </p>
                <div class="dms-n-config-in">
                    <div id="domains" class="dms-n-config-container dms-tab-container">
						<?php 
    if ( !empty( $platform ) && !$platform->showMappingForm() ) {
        ?>
                            <div class="dms-hide-mapping-overlay"></div>
						<?php 
    }
    ?>
                        <h3 class="dms-n-row-header dms-n-config-header"><?php 
    _e( 'Domains', 'domain-mapping-system' );
    ?></h3>
                        <div class="dms-n-row-footer">
                            <div class="dms-n-row-add">
                                <input type="hidden" id="dms-domains-to-remove" name="dms_map[domains_to_remove]"
                                       value=""
                                       style="display: none">
                                <a class="dms-add-row" href="#">
									<?php 
    _e( '+ Add Domain Map Entry', 'domain-mapping-system' );
    ?>
                                </a>
                            </div>
                            <div class="dms-n-mappings-pagination"
                                 class="tablenav-pages">
                                <span class="displaying-num"></span>
								<?php 
    include plugin_dir_path( __FILE__ ) . 'pagination.php';
    ?>
                            </div>
                        </div>
                        <div class="dms-n-row-submit-wrapper">
                            <div class="dms-n-row-submit">
                                <input type="submit" value="<?php 
    _e( 'Save', 'domain-mapping-system' );
    ?>" class="dms-submit">
                                <div class="dms-n-loader"></div>
                            </div>
                        </div>
                    </div>
                    <!-- New Table START -->
                    <div id="api" class="dms-tab-container">
                        <ul class="dms-n-api">
                            <li class="dms-n-row-subheader">
                                <a class="dms-n-row-subheader-link"
                                   href="https://docs.domainmappingsystem.com/features/rest-api"
                                   target="_blank"><?php 
    echo __( 'See our documentation for more', 'domain-mapping-system' );
    ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="dms-n-row dms-n-additional">
                <div class="dms-n-additional-accordion opened">
                    <div class="dms-n-additional-accordion-header">
                        <h3>
                            <span><?php 
    _e( 'Additional Options', 'domain-mapping-system' );
    ?></span>
                        </h3>
                        <i></i>
                    </div>
                    <div class="dms-n-additional-accordion-body">
                        <ul>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_global_mapping"
                                                name="dms_global_mapping"
					                        <?php 
    $opt = get_option( 'dms_global_mapping' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Global Domain Mapping', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'Enable Global Domain Mapping, which means that all pages will be served for all mapped domains. Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
                                        <span class="dms-main-domain-container">
                                            <?php 
    $mappings = Mapping::where();
    $main_mapping = Setting::find( 'dms_main_mapping' )->get_value();
    ?>
					                        <?php 
    if ( !empty( $mappings ) && count( $mappings ) > 1 ) {
        ?>
						                        <?php 
        _e( 'Select the domain [+path] to serve for all unmapped pages:', 'domain-mapping-system' );
        ?>
                                                <select name="dms_main_mapping"
                                                        class="dms-main-domain" <?php 
        echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
        ?>>
                                                        <option value="0"><?php 
        echo __( 'Select domain', 'domain-mapping-system' );
        ?></option>
                                                        <?php 
        foreach ( $mappings as $domain ) {
            if ( !empty( $domain->host ) && !empty( $domain->path ) && !empty( $domain->values ) ) {
            }
            ?>
                                                                <option value="<?php 
            echo $domain->id;
            ?>" <?php 
            echo ( $main_mapping == $domain->id ? 'selected' : '' );
            ?> ><?php 
            echo $domain->host . (( !empty( $domain->path ) ? '/' . $domain->path : '' ));
            ?></option>
                                                        <?php 
        }
        ?>
                                                    </select>
					                        <?php 
    }
    ?>
                                        </span>
				                        <?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
						                        <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
				                        <?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_archive_global_mapping"
                                                name="dms_archive_global_mapping"
					                        <?php 
    if ( $archive_global_mapping === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Global Archive Mapping', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'All posts within an archive or category automatically map to the specified domain (archive mappings override Global Domain Mapping). Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
				                        <?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
						                        <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
				                        <?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_global_parent_page_mapping"
                                                name="dms_global_parent_page_mapping"
					                        <?php 
    $opt = get_option( 'dms_global_parent_page_mapping' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Global Parent Page Mapping', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'Automatically map all pages attached to a Parent Page.  Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
				                        <?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
						                        <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
				                        <?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_woo_shop_global_mapping"
                                                name="dms_woo_shop_global_mapping"
					                        <?php 
    $opt = get_option( 'dms_woo_shop_global_mapping' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Global Product Mapping', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'When you map a domain to the Shop page, all products on your site will be available through that domain. Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
				                        <?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
						                        <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
				                        <?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_force_site_visitors"
                                                name="dms_force_site_visitors"
					                        <?php 
    $opt = get_option( 'dms_force_site_visitors' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Redirection', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'Force site visitors to see only mapped domains of a page (e.g. - disallow visitors to see the primary site domain version of a page). Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
				                        <?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
						                        <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
				                        <?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_rewrite_urls_on_mapped_page"
                                                name="dms_rewrite_urls_on_mapped_page"
					                        <?php 
    $opt = get_option( 'dms_rewrite_urls_on_mapped_page' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                             <strong><?php 
    _e( 'URL Rewriting', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    _e( 'Rewrite all URLs on a mapped domain with:', 'domain-mapping-system' );
    ?>
	                                        <?php 
    $rewrite_scenario = get_option( 'dms_rewrite_urls_on_mapped_page_sc' );
    ?>
                                            <select name="dms_rewrite_urls_on_mapped_page_sc"
                                                    <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>>
                                                    <option value="1" <?php 
    echo ( $rewrite_scenario == '1' && $dms_fs->can_use_premium_code__premium_only() ? 'selected' : '' );
    ?>><?php 
    echo __( 'Global Rewriting', 'domain-mapping-system' );
    ?></option>
                                                    <option value="2" <?php 
    echo ( $rewrite_scenario == '2' && $dms_fs->can_use_premium_code__premium_only() ? 'selected' : '' );
    ?>><?php 
    echo __( 'Selective Rewriting', 'domain-mapping-system' );
    ?></option>
                                                </select>
                                                <?php 
    echo sprintf(
        __( '%s Warning: %s  Global Rewriting may create dead links if you haven’t mapped internally linked pages properly. Read more in our %s Documentation > %s', 'domain-mapping-system' ),
        '<strong>',
        '</strong>',
        '<a class="info" href="https://docs.domainmappingsystem.com/features/url-rewriting" target="_blank" >',
        '</a>'
    );
    ?>
	                                        <?php 
    ?>
                                                <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
                                                    <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                                </a>
	                                        <?php 
    ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
					                        <?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_remove_parent_page_slug_from_child"
                                                name="dms_remove_parent_page_slug_from_child"
					                        <?php 
    $opt = get_option( 'dms_remove_parent_page_slug_from_child' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Parent Page Slugs', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'Remove Parent Page slugs from mapped Child Page URLs. Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
				                        <?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
						                        <?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
				                        <?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <strong><?php 
    _e( 'Yoast SEO', 'domain-mapping-system' );
    ?></strong>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
											<?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_seo_options_per_domain"
                                                name="dms_seo_options_per_domain"
											<?php 
    $opt = get_option( 'dms_seo_options_per_domain' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo 'checked="checked"';
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                                <strong><?php 
    _e( 'Duplicate Yoast SEO Options', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'Each mapped page will have duplicated Yoast SEO options for each mapped domain tied to it.  Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
										<?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
												<?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
										<?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
											<?php 
    echo ( !$dms_fs->can_use_premium_code__premium_only() ? 'disabled=disabled' : '' );
    ?>
                                                id="dms_seo_sitemap_per_domain"
                                                name="dms_seo_sitemap_per_domain"
											<?php 
    $opt = get_option( 'dms_seo_sitemap_per_domain' );
    if ( $opt === 'on' && $dms_fs->can_use_premium_code__premium_only() ) {
        echo "checked=\"checked\"";
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Yoast Sitemap per Domain', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    printf( __( 'Dynamically generate a unique sitemap per domain. Read more in our %1$s Documentation%2$s.', 'domain-mapping-system' ), '<a class="dms-n-row-subheader-link" target="_blank" href="https://docs.domainmappingsystem.com">', '</a>' );
    ?>
                                        </span>
										<?php 
    ?>
                                            <a class="upgrade" href="<?php 
    echo $dms_fs->get_upgrade_url();
    ?>">
												<?php 
    _e( 'Upgrade', 'domain-mapping-system' );
    ?>&#8594;
                                            </a>
										<?php 
    ?>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="dms-n-additional-accordion-li del">
                                    <div class="dms-n-additional-accordion-checkbox">
                                        <input
                                                class="checkbox"
                                                type="checkbox"
                                                id="dms_delete_upon_uninstall"
                                                name="dms_delete_upon_uninstall"
											<?php 
    $opt = get_option( 'dms_delete_upon_uninstall' );
    if ( $opt === 'on' ) {
        echo 'checked="checked"';
    }
    ?>
                                        />
                                    </div>
                                    <div class="dms-n-additional-accordion-content">
                                        <span class="label">
                                            <strong><?php 
    _e( 'Data Removal', 'domain-mapping-system' );
    ?></strong> -
                                            <?php 
    _e( 'Delete plugin, data, and settings (full removal) when uninstalling.', 'domain-mapping-system' );
    ?>
                                            <?php 
    echo sprintf( __( '%s Warning: %s This action is irreversible.', 'domain-mapping-system' ), '<strong>', '</strong>' );
    ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div class="dms-n-row-submit-wrapper">
                            <div class="dms-n-row-submit">
                                <input type="submit"
                                       value="<?php 
    _e( 'Save', 'domain-mapping-system' );
    ?>"
                                       class="dms-submit"/>
                                <div class="dms-n-loader"></div>
                            </div>
                        </div>
                        <input name="action" value="save_dms_mapping" type="hidden">
						<?php 
    wp_nonce_field( 'save_dms_mapping_action', 'save_dms_mapping_nonce' );
    ?>
                    </div>
                </div>
            </div>
            <div class="dms-n-row dms-n-post-types">
                <h3 class="dms-n-row-header"><?php 
    _e( 'Available Post Types', 'domain-mapping-system' );
    ?></h3>
                <p class="dms-n-row-subheader"><?php 
    _e( 'Select the Post Types or Custom Taxonomies that should be available for Domain Mapping System.', 'domain-mapping-system' );
    ?></p>
                <div class="dms-n-post-types-in">
                    <div class="dms-n-post-types-container">
                        <label class="dms-n-post-types-label <?php 
    echo ( get_option( 'dms_use_page' ) == 'on' ? 'checked' : '' );
    ?>"
                               for="dms_use_page">
                            <input class="dms-n-post-types-checkbox" name="dms_use_page" type="checkbox"
                                   id="dms_use_page"
								<?php 
    echo ( get_option( 'dms_use_page' ) == 'on' ? ' checked="checked"' : '' );
    ?>>
                            <span>
                                <?php 
    _e( 'Pages', 'domain-mapping-system' );
    ?>
                            </span>
                        </label>
						<?php 
    ?>
                        <label class="dms-n-post-types-label <?php 
    echo ( get_option( 'dms_use_post' ) == 'on' ? 'checked' : '' );
    ?>"
                               for="dms_use_post">
                            <input class="dms-n-post-types-checkbox" name="dms_use_post" type="checkbox"
                                   id="dms_use_post"
								<?php 
    echo ( get_option( 'dms_use_post' ) == 'on' ? ' checked="checked"' : '' );
    ?>>
                            <span>
                                <?php 
    _e( 'Posts', 'domain-mapping-system' );
    ?>
                            </span>
                        </label>
						<?php 
    ?>
                        <label class="dms-n-post-types-label <?php 
    echo ( get_option( 'dms_use_categories' ) == 'on' ? 'checked' : '' );
    ?>"
                               for="dms_use_categories">
                            <input class="dms-n-post-types-checkbox" name="dms_use_categories" type="checkbox"
                                   id="dms_use_categories"
								<?php 
    echo ( get_option( 'dms_use_categories' ) == 'on' ? ' checked="checked"' : '' );
    ?>>
                            <span>
                        <?php 
    _e( 'Blog Categories', 'domain-mapping-system' );
    ?>
                    </span>
                        </label>
						<?php 
    $types = $this->get_content_types();
    foreach ( $types as $type ) {
        $value = get_option( "dms_use_{$type['name']}" );
        ?>
                            <label class="dms-n-post-types-label <?php 
        echo ( $value == "on" ? ' checked' : '' );
        ?>"
                                   for="dms_use_<?php 
        echo $type['name'];
        ?>">
                                <input class="dms-n-post-types-checkbox" name="dms_use_<?php 
        echo $type['name'];
        ?>"
                                       type="checkbox" id="dms_use_<?php 
        echo $type['name'];
        ?>"
									<?php 
        echo ( $value == 'on' ? 'checked="checked"' : '' );
        ?>>
                                <span><?php 
        echo $type["label"];
        ?></span>
                            </label>
							<?php 
        if ( !empty( $type['has_archive'] ) ) {
            $value = get_option( "dms_use_{$type['name']}_archive" );
            ?>
                                <label class="dms-n-post-types-label <?php 
            echo ( $value == "on" ? ' checked' : '' );
            ?>"
                                       for="dms_use_<?php 
            echo $type['name'];
            ?>_archive">
                                    <input class="dms-n-post-types-checkbox" name="dms_use_<?php 
            echo $type['name'];
            ?>_archive"
                                           type="checkbox"
                                           id="dms_use_<?php 
            echo $type['name'];
            ?>_archive" <?php 
            echo ( $value == 'on' ? 'checked="checked"' : '' );
            ?>>
                                    <span><?php 
            echo $type['label'];
            ?><strong><?php 
            echo __( 'Archive', 'domain-mapping-system' );
            ?></strong></span>
                                </label>
								<?php 
        }
        if ( $dms_fs->can_use_premium_code__premium_only() && !empty( $type['taxonomies'] ) ) {
            ?>
								<?php 
            foreach ( $type['taxonomies'] as $taxonomy ) {
                ?>
									<?php 
                $value = get_option( "dms_use_cat_" . $type['name'] . "_" . $taxonomy['name'] );
                ?>
                                    <label class="dms-n-post-types-label <?php 
                echo ( $value == "on" ? ' checked' : '' );
                ?>"
                                           for="dms_use_cat_<?php 
                echo $type['name'];
                ?>_<?php 
                echo $taxonomy['name'];
                ?>">
                                        <input class="dms-n-post-types-checkbox"
                                               name="dms_use_cat_<?php 
                echo $type['name'];
                ?>_<?php 
                echo $taxonomy['name'];
                ?>"
                                               type="checkbox"
                                               id="dms_use_cat_<?php 
                echo $type['name'];
                ?>_<?php 
                echo $taxonomy['name'];
                ?>"
											<?php 
                echo ( $value == 'on' ? 'checked="checked"' : '' );
                ?>>
                                        <span><?php 
                echo $taxonomy['label'];
                ?></span>
                                    </label>
								<?php 
            }
            ?>
							<?php 
        }
        ?>
							<?php 
    }
    ?>
                    </div>
                    <div class="dms-n-row-submit-wrapper">
                        <div class="dms-n-row-submit">
                            <input type="submit" class="dms-submit"
                                   value="<?php 
    _e( 'Save', 'domain-mapping-system' );
    ?>"/>
                            <div class="dms-n-loader"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php 
}