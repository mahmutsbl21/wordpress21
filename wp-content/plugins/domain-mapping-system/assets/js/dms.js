(function ($, dms_fs) {
    var collector = {
            init: function () {
                var check,
                    saveButtons = $(".dms-submit"),
                    disabledDelay = $(saveButtons[0]).data('disabled_delay');
                // Set onclick to all of them
                if (disabledDelay) {
                    var disabledNote = $('.dms-disabled-delay-note');
                    var disableSetInterval = setInterval(function () {
                        disabledDelay--;
                        if (disabledDelay <= 0) {
                            // Remove main delay note
                            disabledNote.remove();
                            // Stops the setInterval
                            clearInterval(disableSetInterval);
                            // Enable all buttons
                            saveButtons.removeAttr('disabled');
                            var otherButtons = $('button[data-disabled_delay]');
                            if (otherButtons.length) {
                                otherButtons.removeAttr('disabled');
                            }
                        } else {
                            disabledNote.find('b.timer').html(disabledDelay);
                        }
                    }, 1000);
                }
            },
            translate: function ($string) {
                return dms_fs.translations[$string] ? dms_fs.translations[$string] : $string;
            },
            sprintf:  function (format, ...args) {
                return format.replace(/{(\d+)}/g, function(match, number) {
                    return typeof args[number] != 'undefined' ? args[number] : match;
                });
            },
            is_premium: function () {
                return dms_fs.is_premium === '0' ? false : dms_fs.is_premium === '1'
            }
        },
        controls = {
            init: function () {
                var that = this,
                    body = $('body');
                /**
                 * Delete mapping row
                 */
                body.on('click', '.dms-n-config-table-delete', function (e) {
                    e.preventDefault();
                    var toBeRemovedEl = $('#dms-domains-to-remove'),
                        toBeRemovedElVal = toBeRemovedEl.val(),
                        toBeRemovedElValArr = toBeRemovedElVal ? toBeRemovedElVal.split(',') : [],
                        id = $($(this).parent().find('.dms-map-id')[0]).val();
                    that.removeRow($(this));
                    // Fill the hidden input value to collect remove domains.
                    toBeRemovedElValArr.push(id);
                    toBeRemovedEl.val(toBeRemovedElValArr.join(','));
                    // Trigger global mapping select change
                    $('tr.dms-single-mapping .dms-mapping-host').trigger('change');
                });

                /**
                 * Add mapping row
                 */
                body.on('click', '.dms-add-row', function (e) {
                    e.preventDefault();
                    that.addRow(false);
                });

                /**
                 * Check/uncheck post types
                 */
                body.on('click', '.dms-n-post-types-checkbox', function(e){
                    $(this).parent().toggleClass('checked');
                });

                /**
                 * Show more/less
                 */
                body.on('click', '.dms-n-config-table-dropdown', function(e){
                    e.preventDefault()
                    $(this).toggleClass('opened')
                    $(this).parent().find('.dms-n-config-table-row').last().toggleClass('closed')
                    that.shrunkSet();
                });

                /**
                 * Update main domain select on each mapping input change
                 */
                body.on('change', 'tr.dms-single-mapping .dms-mapping-host,tr.dms-single-mapping .dms-mapping-path', function (e) {
                    if (!collector.is_premium()) {
                        return;
                    }
                    e.preventDefault();
                    var existingSelect = $('.dms-main-domain'),
                        hostEls = $('.dms-mapping-host');

                    if (existingSelect.length && hostEls.length) {
                        controls.addMainDomainSelect();
                    }
                });

                /**
                 * Hide configs bar
                 */
                body.on('click', '#show-settings-link', function (e) {
                    e.preventDefault()
                    $('#screen-options-wrap').toggleClass('dms-hide-configuration-bar')
                })

                // Check WPCS main button existence existence
                if ($('.dms-n-config-table-wpcs-set-main-domain').length) {
                    /*
                    * Set main domain for WPCS Tenant platform
                    * */
                    body.on('click', '.dms-n-config-table-wpcs-set-main-domain', function (e) {
                        e.preventDefault();
                        var check = confirm(collector.translate('Warning! You will be logged out, and you will need to login again using the new domain. Be sure you know your login details. It may take up to 3 minutes for the change to process.'));
                        if (check === false) {
                            return;
                        }
                        $('#dms_platform_wpcs_domain_map_id_value').val($(this).data('map_id'));
                        $('#dms_platform_wpcs_set_tenant_main_domain_form').submit();
                    });
                }

                /**
                 * Remove favicon
                 */
                body.on('click', '.dms-delete-img', function (e) {
                    e.preventDefault();
                    var $this = $(this);
                    $this.parent().find('.favicon').remove();
                    $this.parent().find('.dms-attachment-id')[0].value = 0;
                    $this.hide();
                });

                /**
                 * MDM related import
                 */
                body.on('click', '#dms-mdm-import a.yes, #dms-mdm-import a.no', function (e) {
                    e.preventDefault();
                    var $this = $(this);
                    if($this.hasClass('no')) {
                        // Remove bar, hide notification
                        if(confirm(collector.translate('Are you sure you would like to avoid importing mappings form Multiple Domain Mapping?'))) {
                            $.post( 
                                dms_fs.ajax_url, 
                                {
                                    action: 'dms_hide_mdm_note',
                                    nonce: dms_fs.nonce
                                }
                            ).done(function( data ) {
                                if(data && data.status) {
                                    $this.parent().parent().remove();
                                }
                            });
                        }
                    } else {
                        if($this.hasClass('yes')) {
                            if(confirm(collector.translate('Are you sure you would like to import mappings from Multiple Domain Mapping? Warning: Ensure you have a backup available, as errors may occur.'))) {
                                // Yes proceed import, hide notification bar
                                $('#dms-mdm-import-form').submit();
                            }
                        }
                    }
                });

                body.on('click', '.pagination-links a.button.disabled', function(e){
                    e.preventDefault();
                })

                /**
                 * Expand and collapse additional options
                 */
                body.on('click', '.dms-n-additional-accordion-header', function (){
                    $(this).parent().toggleClass('opened');
                });

                body.on( 'click', '.dms-submit', function(e){
                    window.dms_controls.clickedButton = $(this);
                    e.preventDefault();
                    window.dms_controls.showLoading($(this));
                    dmsRest.restController.saveItemsViaRest();
                    e.stopPropagation()
                });

                body.on('change', '.dms-n-config-table-existing .dms-mapped-primary-val', function () {
                    $('.dms-n-config-table-existing .dms-domain-mapping-values[data-index="' + $(this).data('mapping-id') + '"]').trigger('change');
                })

                body.on('change', '.dms-n-config-table-new .dms-mapped-primary-val', function(){
                    $('.dms-n-config-table-new .dms-domain-mapping-values[data-index="' + $(this).data('mapping-id') + '"]').trigger('change');
                })

                body.on('change', '.dms-n-additional-accordion-checkbox input[type="checkbox"], .dms-n-post-types-checkbox', function () {
                    let checkbox = $(this),
                        val = checkbox.prop('checked') ? checkbox.val() : '';
                    stackController.prepareStackSettingObject(checkbox.attr('name'), val);
                })

                body.on('change', 'select[name="dms_rewrite_urls_on_mapped_page_sc"]', function(){
                    let select = $(this),
                        val = select.val();

                    stackController.prepareStackSettingObject(select.attr('name'), val);
                })
                body.on('change', 'select[name="dms_main_mapping"]', function(){
                    let select = $(this),
                        val = select.val();

                    stackController.prepareStackSettingObject(select.attr('name'), val);
                })

                body.on('click', '.dms-n-config-table-delete', function(){
                    let mappingId = $(this).data('mappingid');
                    dmsRest.dmsStack.items  = dmsRest.dmsStack.items.filter(obj => obj.mappingID !== mappingId);
                    stackController.prepareStackMappingObject(mappingId, 'delete');
                })

                body.on('click', '.notice-dismiss', function(){
                    $(this).parent().removeClass('dms-fade-in').addClass('dms-fade-out');
                })

                /**
                 * Handle change events for existing table rows
                 */
                controls.handleChange('.dms-n-config-table-existing .dms-host', 'existing', 'host');
                controls.handleChange('.dms-n-config-table-existing .dms-path', 'existing', 'path');
                controls.handleChange('.dms-n-config-table-existing .dms-domain-mapping-values', 'existing', 'mapping_value');
                controls.handleChange('.dms-n-config-table-existing .dms-n-config-table-input-code', 'existing', 'custom_html');
                controls.handleChange('.dms-n-config-table-existing .dms-attachment-id', 'existing', 'attachment_id');
                /**
                 * Handle change events for new table rows
                 */
                controls.handleChange('.dms-n-config-table-new .dms-host', 'new', 'host');
                controls.handleChange('.dms-n-config-table-new .dms-path', 'new', 'path');
                controls.handleChange('.dms-n-config-table-new .dms-domain-mapping-values', 'new', 'mapping_value');
                controls.handleChange('.dms-n-config-table-new .dms-n-config-table-input-code', 'new', 'custom_html');
                controls.handleChange('.dms-n-config-table-new .dms-attachment-id', 'new', 'attachment_id');


                /**
                 * Load more selected mappings of the matching host
                 */
                $(document).on('click', '.dms-mapped-page-load-more', async function (e) {
                    try {
                        e.preventDefault();
                        let mappingIndex = $(this).data('index').replace(/\D/g, ''),
                            loadMoreButton = $(this),
                            mappingId = loadMoreButton.data('map-id').replace(/\D/g, ''),
                            startCountInp = $('input#dms-count-inp-' + mappingIndex),
                            optGroupContainer = $('#dms-selected-values-' + mappingIndex),
                            selectBox = $('select[name="dms_map[domains]['+mappingIndex+'][mappings][values][]"]'),
                            route = dms_fs.rest_url + 'mappings/' + mappingId + '/values/' + dmsRest.restController.getSeparator() + 'values_per_row=' + dms_fs.values_per_mapping + '&start=' + startCountInp.val();

                        $('.load-more-opt[data-map-id="dms-host-' + mappingId + '"]').remove();

                        const options = await dmsRest.restController.fetchValues(route, true);
                        // Add missing options
                        optGroupContainer.append(options);
                        startCountInp.val( +(startCountInp.val()) + +(dms_fs.values_per_mapping) );
                        // Init select2 for current element
                        selectBox.select2('destroy').select2(select2.args());
                    } catch (error) {
                        window.dms_log.error('Fetch error:', error);
                        throw error;
                    }
                });
            },

            hideLoadingContainer(){
                $('.dms-n-loading-container').addClass('dms-fade-out');
                $('.dms-n-config-fixed').removeClass('dms-n-config-fixed');
                setTimeout(function(){
                    $('.dms-n-loading-container').remove();
                }, 100)
            },

            showLoading(btn){
                btn.next('.dms-n-loader').show();
                $('input.dms-submit').attr('disabled', 'disable');
            },

            hideLoading(){
                $('.dms-n-loader').hide();
                $('input.dms-submit').removeAttr('disabled');
            },

            handleChange(selector, actionType, field) {
                $('body').on('change', selector, function(e) {
                    let mappingId = $(this).data('mapping') || $(this).data('index');
                    let value = $(this).val();

                    let updateType = (actionType === 'existing') ? 'update' : 'create';

                    if ($(this).hasClass('dms-domain-mapping-values')) {
                        let index = $(this).data('index');
                        let val = $(this).val();

                        let primary = $('input[name="dms_map[domains][' + index + '][mappings][primary]"]:checked').val();

                        if (!primary) {
                            primary = val[0];
                        }

                        value = { primary: primary, value: val };
                    }

                    stackController.prepareStackMappingObject(mappingId, updateType, field, value);
                });
            },

            showMessage(message, type){
                $('.notice.is-dismissible').remove();
                if (window.dms_controls.clickedButton) {
                    window.dms_controls.clickedButton.parent().after(
                        ' <div class="notice dms-fade-in notice-' + type + ' is-dismissible">' +
                        '<p>' + message + '</p>' +
                        '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
                        '</div>'
                    );
                }
            },

            removeRow: function (btn) {
                $(btn).closest(".dms-n-config-table").remove();
            },

            paginationControl: function(count, totalItems) {
                const itemsLabel = totalItems == 1 ? collector.translate('item') : collector.translate('items');
                const $displayingNum = $('.displaying-num');
                $displayingNum.html(`${totalItems} ${itemsLabel}`);

                if (+totalItems <= +dms_fs.mappings_per_page) {
                    $('.pagination-links').hide();
                    return;
                }

                const totalPages = Math.ceil(totalItems / dms_fs.mappings_per_page);
                const $firstPage = $('.first-page');
                const $lastPage = $('.last-page');
                const $nextPage = $('.next-page');
                const $prevPage = $('.prev-page');

                $('.total-pages').html(totalPages);

                if (dms_fs.paged < totalPages) {
                    $nextPage.removeClass('disabled');
                    $lastPage.removeClass('disabled');
                }

                if (dms_fs.paged > 1) {
                    $firstPage.removeClass('disabled');
                    $prevPage.removeClass('disabled');
                }

                const lastPageUrl = `${dms_fs.site_url}/wp-admin/admin.php?page=domain-mapping-system&paged=${totalPages}`;
                $lastPage.attr('href', lastPageUrl);
            },

            
            /**
             * Saving a current shrinked field position in the cookie
             */
            shrunkSet: function () {
                var shrink_btn_array = $(".dms-n-config-table-dropdown"),
                    shrinked_all_filed_array = $(".dms-n-config-table-row"),
                    shrinked_filed_array = [],
                    will_shrink = 0,
                    // If you need a save some data in cookie you can push it in dms_cookie, which is associative array
                    dms_cookie = {'shrinked': []},
                    // Those vars are, for settings a cookies empires time
                    date = new Date(),
                    days = 365;
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                for (let i = 0; i < shrink_btn_array.length; i++) {
                    shrink_btn_array[i] === this ? will_shrink = i : ''
                }
                // This is generated a new array only with shrunked fields
                for (let i = 1 ; i < shrinked_all_filed_array.length; i += 2) {
                    shrinked_filed_array.push(shrinked_all_filed_array[i]);
                }
                for (let i = 0; i < shrinked_filed_array.length; i++) {
                    shrinked_filed_array[i].classList.length === 1 ? dms_cookie['shrinked'][i] = "opened" : dms_cookie['shrinked'][i] = "closed";
                }
                document.cookie = `dms_cookie=${JSON.stringify(dms_cookie)};expires=${date.toUTCString()}`;
            },

            /**
             * Collapse a field, based on the cookies
             */
            shrunkCheck: function () {
                let cookie_array = (!getCookie("dms_cookie")) ? null : JSON.parse(getCookie("dms_cookie")),
                    shrinked_all_filed_array = $(".dms-n-config-table-row"),
                    shrink_btn = $(".dms-n-config-table-dropdown");
                function getCookie(cname) {
                    var name = cname + "=",
                        all_cookie_array = document.cookie.split(';');
                    for (let i = 0; i < all_cookie_array.length; i++) {
                        let c = all_cookie_array[i];
                        while (c.charAt(0) == ' ') {
                            c = c.substring(1);
                        }
                        if (c.indexOf(name) == 0) {
                            return c.substring(name.length, c.length);
                        }
                    }
                    return "";
                }
                for (let i = 1, j = 0; i < shrinked_all_filed_array.length; i += 2, j++) {
                    if ( cookie_array !== null && cookie_array["shrinked"].length ) {
                        if (cookie_array["shrinked"][j] === "closed") {
                            shrinked_all_filed_array[i].classList.add("closed");
                            shrink_btn[j].classList.remove("opened");
                        } else if (cookie_array["shrinked"][j] === "opened") {
                            shrinked_all_filed_array[i].classList.remove("closed");
                            shrink_btn[j].classList.add("opened");
                        }
                    }
                    else{
                        // this part for showing a last field opened , and other closed, if cookie not exist
                        if(i == (shrinked_all_filed_array.length - 1)){
                            shrinked_all_filed_array[i].classList.remove("closed");
                            shrink_btn[j].classList.add("opened");
                        }
                        else {
                            shrinked_all_filed_array[i].classList.add("closed");
                            shrink_btn[j].classList.remove("opened");
                        }
                    }
                }
            },

            addRow: async function (flag) {
                // Define variables
                // Get all rows,
                // Find the last index and create new index , then apply to new row ( both to row and select )
                // Get default select options, create select and apply options to it
                var map = $("#dms-map"),
                    mappings = map.find('.dms.dms-n-config-table-select'),
                    lastInd = $('.dms-n-config-table-new').length + 1,
                    multiple = collector.is_premium() ? 'multiple ' : '',
                    index = mappings && mappings.length ? (mappings.last().data('index') + 1) : 0,
                    options = $($('#dms-default-select').find('select')[0]).html(),
                    tr = '<div class=\'dms-n-config-table dms-n-config-table-new\'>\n' +
                        '<button class=\'dms-n-config-table-dropdown opened\'>\n' +
                        '<i></i>\n' +
                        '</button>\n' +
                        '<div class=\'dms-n-config-table-in\'>\n' +
                        '<div class=\'dms-n-config-table-row first\'>\n' +
                        '<div class=\'dms-n-config-table-column domain\'>\n' +
                        '<div class=\'dms-n-config-table-header' +
                        (! collector.is_premium() ? ' free-version' : '') +
                        '\'>\n' +
                        '<p>\n' +
                        '<span>' + collector.translate('Enter Mapped Domain') +'</span>\n' +
                        '</p>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-body\'>\n' +
                        '<span  class="dms-n-config-table-body-scheme">'+dms_fs.scheme+'://</span>\n' +
                        '<input type=\'text\'\n' +
                        'name=\'dms_map[domains]['+ index +'][host]\'\n' +
                        'data-index="' + index + '" ' +
                        'class=\'dms-n-config-table-input dms-host\'\n' +
                        'placeholder=\'example.com\'>\n' +
                        '<span class="slash">/</span>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-column subdirectory\'>\n' +
                        '<div class=\'dms-n-config-table-header' +
                        (! collector.is_premium() ? ' free-version' : '') +
                        '\'>\n' +
                        '<p>\n' +
                        '<span>' + collector.translate('Enter Subdirectory (optional)') + '</span>\n' +
                        (! collector.is_premium() ? 
                        '<a href="'+dms_fs.upgrade_url+'">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                        '</p>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-body\'>\n' +
                        '<input type=\'text\'\n' +
                        'data-index="' + index + '" ' +
                        'name=\'dms_map[domains]['+ index +'][path]\'\n' +
                        (! collector.is_premium() ? 'disabled' : '') + '\n' +
                        'class=\'dms-n-config-table-input dms-path\'\n' +
                        'placeholder=\'' + collector.translate('Sub Directory') + '\'>\n' +
                        '<span class="slash">/</span>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-column content\'>\n' +
                        '<div class=\'dms-n-config-table-header' +
                        (! collector.is_premium() ? ' free-version' : '') +
                        '\'>\n' +
                        '<p>\n' +
                        '<span>' + collector.translate('Select the Published Content to Map for this Domain.') + '</span>\n' +
                        (! collector.is_premium() ? '<span>' + collector.translate('To map multiple published resources to a single domain, please .') + '</span>\n' : '' ) +
                        (! collector.is_premium() ? 
                        '<a href="'+dms_fs.upgrade_url+'">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                        '</p>\n' +
                        ( collector.is_premium() ? '<div class="dms-n-config-table-info">\n' +
                            await dmsRest.restController.fetchSVG( dms_fs.plugin_url+ '/assets/img/info.svg') +
                            '<div class="dms-n-config-table-info-text">' + collector.sprintf( collector.translate('Use the radio button to select a Microsite homepage. Read our {0}documentation{1} for details.'), '<a target="_blank" href="https://docs.domainmappingsystem.com/features/creating-microsites-multisite-alternative">', '</a>') + '\n</div>' +
                        '</div>\n' : '' ) +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-body\'>\n' +
                        '<select class="dms dms-n-config-table-select dms-domain-mapping-values"\n' +
                        'data-index="' + index + '"' +
                        'name="dms_map[domains][' + index + '][mappings][values][]"\n' +
                        'data-placeholder="' + collector.translate('The choice is yours.') + '"\n' + multiple +
                        'value="">\n' +
                        options +
                        '</select>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-row\'>\n' +
                        '<div class=\'dms-n-config-table-column code\'>\n' +
                        '<div class=\'dms-n-config-table-header\'>\n' +
                        '<p>\n' +
                        '<span>' + collector.translate('Custom HTML Code') + '</span>\n' +
                        (! collector.is_premium() ?
                        '<a href="'+dms_fs.upgrade_url+'">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                        '</p>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-body\'>\n' +
                        '<input type=\'text\'\n' +
                        'data-index="' + index + '" ' +
                        'name=\'dms_map[domains]['+ index +'][custom_html]\'\n' +
                        'class=\'dms-n-config-table-input-code\'\n' +
                        'placeholder=\'</' + collector.translate('Code here') + '>\'' +
                        '' + (! collector.is_premium() ? 'disabled' : '') + '/>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-column favicon\'>\n' +
                        '<div class=\'dms-n-config-table-header\'>\n' +
                        '<p>\n' +
                        '<span>' + collector.translate('Favicon per Domain') + '</span>\n' +
                        (! collector.is_premium() ? 
                        '<a href="'+dms_fs.upgrade_url+'">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                        '</p>\n' +
                        '</div>\n' +
                        '<div class=\'dms-n-config-table-body\'>\n' +
                        '<div class=\'dms-n-config-table-favicon\'>\n' +
                        '<input type="button" name="upload-btn"\n' +
                        'class="' + (! collector.is_premium() ? 'disabled' : '') + ' upload upload-btn"\n' +
                        'value="'+collector.translate('Upload Image')+'" \n' +
                        'id="'+ index +'" >\n' +
                        (collector.is_premium() ?
                        '<input class="dms-attachment-id"  type="hidden"\n' +
                        'data-index="' + index + '" ' +
                        'name="dms_map[domains]['+index+'][attachment_id]"\n' +
                        'value="">\n' +
                        '<button class="dms-delete-img"\n' +
                        'title="delete"\n' +
                        'style="display: none"\n' +
                        '>&times;\n' +
                        '</button>\n' : '') +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        '</div>\n' +
                        (!flag ?
                        '<button class="dms-n-config-table-delete">' : "")+
                        '<i></i>' +
                        '</button>' +
                        '</div>';
                // Insert in the table
                $(".dms-n-row-footer").before($(tr));
                // var select2El = $($('#dms-map select.dms[data-index="' + index + '"]')[0]);
                var select2El = $('#dms-map select.dms[data-index="' + index + '"]');
                select2El.select2(select2.args())
                    .on('select2:select', function (e) {
                    $(this).children().each(function($item){
                        $(this).attr('data-primary', 0);
                    });
                });
                // Initialize on change
                if (collector.is_premium()) {
                    // Set up unselect event to remove domain from some options connected
                    select2.eventsConfiguration(select2El);
                }
            },
            addMainDomainSelect: function () {
                if (!collector.is_premium()) {
                    return;
                }
                var hostEls = $('.dms-mapping-host'),
                    existingSelect = $('.dms-main-domain'),
                    selectedInitial = existingSelect.length ? existingSelect.find('option:selected').val() : null,
                    container = $('.dms-main-domain-container'),
                    dmsMainDomains = '',
                    options = '';
                for (var i = 0; i < hostEls.length; i++) {
                    var hostEl = $(hostEls[i]),
                        path = hostEl.next().next().children(0).val(),
                        value = hostEl.val() && path ? hostEl.val() + '/' + path : (hostEl.val() ? hostEl.val() : ''),
                        selected = selectedInitial === value ? 'selected' : '';
                    if (value.trim() === '') {
                        continue;
                    }
                    options += '<option value="' + value + '" ' + selected + '>' + value + '</option>'
                }
                dmsMainDomains = collector.translate('Select the domain [+path] to serve for all unmapped pages:') +
                    '<select name="dms_main_domain" class="dms-main-domain">' +
                    '<option value="0">' + collector.translate('Select domain') + '</option>' +
                    options +
                    '</select>';
                if (existingSelect.length) {
                    existingSelect.remove();
                }
                container.html(dmsMainDomains);
            }
        },
        select2 = {
            args: function() {
                var args = {
                    placeholder: collector.translate('The choice is yours.'),
                    ajax: {
                        url: dms_fs.ajax_url,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    action: 'mapping_values_search',
                                    search_term: params.term,
                                    mapping: $(this).data('mapping')
                                };
                            },
                        processResults: function(data) {
                            var groups = data.map(function(group) {
                                var children = group.children.map(function(child) {
                                    return { id: child.id, text: child.text, primary: 0 };
                                });
                                return { text: group.text, children: children };
                            });
                            return { results: groups };
                        },
                        cache: true
                    }
                }
                if (collector.is_premium()) {
                    args.templateSelection = this.select2Template;
                }
                
                return args;
            },
            init: function () {
                // Initialize select2 on all mapping values selects
                var selects = $("select.dms");
                selects.select2(this.args());
                // Set up unselect event to remove domain from some options connected
                this.eventsConfiguration(selects);
            },
            eventsConfiguration: function (selects) {
                // Set up events connected with options selecting/unselecting
                var index, args = {
                    placeholder: collector.translate('The choice is yours.')
                };
                if (collector.is_premium()) {
                    args.templateSelection = this.select2Template;
                }
                selects.off('select2:unselect');
                selects.on('select2:unselect', function (e) {
                    var $select = $(this);
                    var unselectedOption = e.params.data.element;
                    var wasPrimary = $(unselectedOption).data('primary') === 1;
                    $(unselectedOption).removeAttr('selected')
                    if (wasPrimary) {
                        var selectedOptions = $select.find('option:selected');
                        var firstSelectedOption = selectedOptions.first();

                        firstSelectedOption.data('primary', 1);
                        requestAnimationFrame(function(){
                            $select.select2('destroy').select2(select2.args());
                        });
                    }
                });
                selects.off('select2:select');
                selects.on('select2:select', function (e) {
                    var $select = $(this)
                    var loadMore = $(this).children('optgroup').children('option.load-more-opt');

                    if (loadMore.length > 0) {
                        var index = loadMore.data('index');

                        $(loadMore).remove();
                        let selectedValuesContainer = $('#dms-selected-values-' + index);

                        let valuesList = selectedValuesContainer.parent().next().children().children().children('ul');

                        let liWithLoadMore = valuesList.find('li').filter(function () {
                            return $(this).find('span.dms-mapped-page-load-more').length > 0;
                        });

                        let mapId = $select.data('mapping');

                        requestAnimationFrame(function () {
                            liWithLoadMore.remove();

                            // Append the removed items back to valuesList
                            valuesList.append(liWithLoadMore);
                            $select.children('optgroup').append('<option selected class="load-more-opt" data-index="' + index + '" value="load-more" data-map-id="' + mapId + '" data-select2-id="select2-data-6-jxb4">Load more</option>');
                        });
                    }
                });
            },
            select2Template: function (state, container) {
                if (!state.id) {
                    return state.text;
                }
                var stateElement = $(state.element),
                    selectElement = $(state.element).parents('select'),
                    selectedElements = selectElement.find("option:selected"),
                    isPrimary = stateElement.data('primary') ? true : (selectedElements && selectedElements.length === 1 ? stateElement.data('primary', 1) : 0),
                    index = selectElement.data('index'),
                    checked = isPrimary ? 'checked' : '',
                    $state;
                if ($(state.element).hasClass('load-more-opt')) {
                    var mappingId = $(state.element).data('map-id');
                    $state = $(
                        '<span>' +
                        '<span class="dms-mapped-page-load-more" data-index="mapping-' + index + '" data-map-id="dms-host-' + mappingId + '">' + state.text + '</span>' +
                        '</span>'
                    );
                } else {
                    $state = $(
                        '<span>' +
                        '<span class="dms-mapped-page-selected"></span>' +
                        '<span>' +
                        '<input class="dms-mapped-primary-val" style="margin-left: 5px;" ' +
                        'name="dms_map[domains][' + index + '][mappings][primary]" ' +
                        'data-mapping-id="' + index + '" ' +
                        'value="' + state.id + '" ' +
                        'type="radio" ' + checked + '  />' +
                        '</span>' +
                        '</span>'
                    );


                    $($state.find("span")[0]).text(state.text);
                    $($state.find('input[type="radio"]')[0]).on('click', function (e) {
                        e.stopPropagation();
                    }).on('change', function (e) {
                        // Remove data primary from all elements first
                        $(selectedElements).each(function (index) {
                            $(selectedElements[index].element).data('primary', 0);
                        });
                        // Add to exact one
                        if ($(this).is(':checked')) {
                            $(state.element).data('primary', 1);
                        } else {
                            $(state.element).data('primary', 0);
                        }
                    });
                }
                return $state;
            }
        },
        tabs = {
            init: function () {
                var nav_tabs = $('.dms.nav-tab');
                if (nav_tabs.length) {
                    nav_tabs.on('click', function (e) {
                        e.preventDefault();
                        $('.dms.nav-tab').removeClass('nav-tab-active');
                        $('.dms-tab-container').hide();
                        $(this).addClass('nav-tab-active');
                        $($(this).attr('href')).show();
                    });
                    $('.dms.nav-tab.nav-tab-active').trigger('click');
                }
            }
        },
        favicon = {
            getImageSelectOptions: function (attachment, controller) {
                var realWidth = attachment.get('width'),
                    realHeight = attachment.get('height');
                return {
                    handles: true,
                    keys: true,
                    instance: true,
                    persistent: true,
                    imageWidth: realWidth,
                    imageHeight: realHeight,
                    minWidth: attachment.get('width') < 512 ? attachment.get('width') : 512,
                    minHeight: attachment.get('height') < 512 ? attachment.get('height') : 512,
                    x1: 0,
                    y1: 0,
                    x2: realWidth,
                    y2: realHeight
                };
            },
            init: function () {
                var body = $('body');
                /**
                 * Creates media uploader for favicons
                 */
                body.on('click', '.upload-btn', function (event) {
                    event.preventDefault();
                    if (!collector.is_premium()) {
                        return;
                    }
                    var mediaUploader,
                        cropControl = {
                            id: "control-id",
                            params: {
                                flex_width: false,  // set to true if the width of the cropped image can be different to the width defined here
                                flex_height: false, // set to true if the height of the cropped image can be different to the height defined here
                                width: 512,  // set the desired width of the destination image here
                                height: 512, // set the desired height of the destination image here
                            },
                        };
                    mediaUploader = wp.media({
                        button: {
                            text: 'Select', // l10n.selectAndCrop,
                            close: false
                        },
                        states: [
                            new wp.media.controller.Library({
                                title: collector.translate('Select and Crop'), // l10n.chooseImage,
                                library: wp.media.query({type: 'image'}),
                                multiple: false,
                                date: false,
                                priority: 20,
                                suggestedWidth: 512,
                                suggestedHeight: 512
                            }),
                            new wp.media.controller.CustomizeImageCropper({
                                imgSelectOptions: favicon.getImageSelectOptions,
                                control: cropControl
                            })
                        ]
                    });

                    mediaUploader.on('cropped', function (croppedImage) {
                        // let index = event.target.id;
                        if(!event.target.parentNode.querySelector('.favicon')){
                            let favicon = document.createElement('img');
                            favicon.classList.add('favicon');
                            favicon.src = croppedImage.url;
                            event.target.parentNode.prepend(favicon);
                        } else {
                            event.target.parentNode.querySelector('.favicon').src = croppedImage.url;
                        }
                        event.target.parentNode.querySelector(".dms-attachment-id").value = croppedImage.id;
                        event.target.parentNode.querySelector('.dms-delete-img').style.display = 'block';
                        ($(event.target).parent().find('.dms-attachment-id')).trigger('change');
                    });

                    mediaUploader.on("select", function () {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        if (cropControl.params.width === attachment.width
                            && cropControl.params.height === attachment.height
                            && !cropControl.params.flex_width
                            && !cropControl.params.flex_height) {
                                if(!event.target.parentNode.querySelector('.favicon')){
                                    let favicon = document.createElement('img');
                                    favicon.classList.add('favicon');
                                    favicon.src = attachment.url;
                                    event.target.parentNode.prepend(favicon);
                                } else {
                                    event.target.parentNode.querySelector('.favicon').src = attachment.url;
                                }
                                event.target.parentNode.querySelector(".dms-attachment-id").value = attachment.id;
                                event.target.parentNode.querySelector('.dms-delete-img').style.display = 'block';
                                ($(event.target).parent().find('.dms-attachment-id')).trigger('change');
                                mediaUploader.close();
                        } else {
                            mediaUploader.setState('cropper');
                        }

                    });

                    mediaUploader.open();
                });
            }
        },

        mapping = {
            index: 0,
            generateValues: (responseData, mapping, valuesOnly) => {
                let res = valuesOnly ? '' : `<optgroup class="dms-selected-values" id="dms-selected-values-${mapping.index}" label="Selected">`;

                responseData.items.forEach(item => {
                    res += mapping.loadValue(item);
                });

                const loadMore = responseData._total > responseData.items.length;
                if (responseData.items[0]) {
                    const mappingId = responseData.items[0].value.mapping_id;

                    if (loadMore) {
                        let totalCount = +($('select[data-mapping="' + mappingId + '"]').next().next().val());
                        if (+(responseData._total) > totalCount + 2 && !isNaN(totalCount)){
                            res += mapping.loadMoreValue(mappingId, mapping.index);
                        } else if (isNaN(totalCount)){
                            res += mapping.loadMoreValue(mappingId, mapping.index);
                        }
                    }
                }

                res = valuesOnly ? res : `${res}</optgroup>`;
                res = valuesOnly ? res : `${res}<input class="dms-count-inp" id="dms-count-inp-${mapping.index}" name="dms_map[domains][${mapping.index}][count]" value="${dms_fs.values_per_mapping}" type="hidden">`;

                return res;
            },

            loadMoreValue: (index, rowId)=> {
                return '<option selected data-index="' + rowId + '" class="load-more-opt" value="load-more"\n' +
                    'data-map-id="dms-host-' + index + '">Load more</option>';
            },

            loadValue: (item) => {
                let label = item._object.object_name;
                let value = item.value.object_id;
                let type = item.value.object_type;
                value = (type === 'wcfm_store') ? (type + '_' + value) : value;
                value = (type === 'term') ? (type + '_' + value) : value;

                return '<option selected data-primary="' + item.value.primary + '" ' +
                    'class="level-0"\n' +
                    'value="' + value + '">' + label + '</option>'
            },


            loadMapping: async (item) => {
                // let $options = await dmsRest.restController.fetchValues(item._links.values.href + '?values_per_row=' + dms_fs.values_per_mapping);
                let $options = mapping.generateValues(item._values, mapping, false);
                let multiple = collector.is_premium() ? 'multiple ' : '';
                let favicon = '';

                if (item.mapping.attachment_id && item.mapping.attachment_id !== '0' ){
                    const imageUrl = item._links.attachment_url;
                    if (imageUrl){
                        favicon = `<img class="favicon" src="${item._links.attachment_url}" alt="Favicon">`;
                    }
                }
                let customHtml = '';
                if (item.mapping.custom_html){
                    customHtml = item.mapping.custom_html.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                }
                item.mapping.path = item.mapping.path == null ? '' : item.mapping.path;
                    let $mapping = '<div class=\'dms-n-config-table\'>\n' +
                    '<button class=\'dms-n-config-table-dropdown opened\'>\n' +
                    '<i></i>\n' +
                    '</button>\n' +
                    '<div class=\'dms-n-config-table-in dms-n-config-table-existing\'>\n' +
                    '<div class=\'dms-n-config-table-row first\'>\n' +
                    '<div class=\'dms-n-config-table-column domain\'>\n' +
                    '<div class=\'dms-n-config-table-header' +
                    (!collector.is_premium() ? ' free-version' : '') +
                    '\'>\n' +
                    '<p>\n' +
                    '<span>' + collector.translate('Enter Mapped Domain') + '</span>\n' +
                    '</p>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-body\'>\n' +
                    '<span  class="dms-n-config-table-body-scheme">' + dms_fs.scheme + '://</span>\n' +
                    '<input type=\'text\'\n' +
                    'name=\'dms_map[domains][' + mapping.index + '][host]\'\n' +
                    'data-mapping="' + item.mapping.id + '"' +
                    'class=\'dms-n-config-table-input dms-host\'\n' +
                    'value=\"' + item.mapping.host +'\"'+
                    'placeholder=\'example.com\'>\n' +
                    '<span class="slash">/</span>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-column subdirectory\'>\n' +
                    '<div class=\'dms-n-config-table-header' +
                    (!collector.is_premium() ? ' free-version' : '') +
                    '\'>\n' +
                    '<p>\n' +
                    '<span>' + collector.translate('Enter Subdirectory (optional)') + '</span>\n' +
                    (!collector.is_premium() ?
                        '<a href="' + dms_fs.upgrade_url + '">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                    '</p>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-body\'>\n' +
                    '<input type=\'text\'\n' +
                    'name=\'dms_map[domains][' + mapping.index + '][path]\'\n' +
                    (!collector.is_premium() ? 'disabled' : '') + '\n' +
                    'class=\'dms-n-config-table-input dms-path\'\n' +
                    'data-mapping="' + item.mapping.id + '"' +
                    'value=\"' + item.mapping.path +'\"'+
                    'placeholder=\'' + collector.translate('Sub Directory') + '\'>\n' +
                    '<span class="slash">/</span>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-column content\'>\n' +
                    '<div class=\'dms-n-config-table-header' +
                    (!collector.is_premium() ? ' free-version' : '') +
                    '\'>\n' +
                    '<p>\n' +
                    '<span>' + collector.translate('Select the Published Content to Map for this Domain.') + '</span>\n' +
                    (!collector.is_premium() ? '<span>' + collector.translate('To map multiple published resources to a single domain, please .') + '</span>\n' : '') +
                    (!collector.is_premium() ?
                        '<a href="' + dms_fs.upgrade_url + '">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                    '</p>\n' +
                    (collector.is_premium() ? '<div class="dms-n-config-table-info">\n' +
                        '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="15" height="15" viewBox="0,0,256,256">'+
                        '<g fill="#0085ba" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-size="none" style="mix-blend-mode: normal">' +
                        '<g transform="scale(9.84615,9.84615)">'+
                        '<path d="M13,1.1875c-6.52344,0 -11.8125,5.28906 -11.8125,11.8125c0,6.52344 5.28906,11.8125 11.8125,11.8125c6.52344,0 11.8125,-5.28906 11.8125,-11.8125c0,-6.52344 -5.28906,-11.8125 -11.8125,-11.8125zM15.46094,19.49609c-0.60937,0.23828 -1.09375,0.42188 -1.45703,0.54688c-0.36328,0.125 -0.78125,0.1875 -1.26172,0.1875c-0.73437,0 -1.30859,-0.17969 -1.71875,-0.53906c-0.40625,-0.35547 -0.60937,-0.8125 -0.60937,-1.36719c0,-0.21484 0.01563,-0.43359 0.04688,-0.65625c0.02734,-0.22656 0.07813,-0.47656 0.14453,-0.76172l0.76172,-2.6875c0.06641,-0.25781 0.125,-0.5 0.17188,-0.73047c0.04688,-0.23047 0.06641,-0.44141 0.06641,-0.63281c0,-0.33984 -0.07031,-0.58203 -0.21094,-0.71484c-0.14453,-0.13672 -0.41406,-0.20312 -0.8125,-0.20312c-0.19531,0 -0.39844,0.03125 -0.60547,0.08984c-0.20703,0.0625 -0.38281,0.12109 -0.53125,0.17578l0.20313,-0.82812c0.49609,-0.20312 0.97266,-0.375 1.42969,-0.51953c0.45313,-0.14453 0.88672,-0.21875 1.28906,-0.21875c0.73047,0 1.29688,0.17969 1.69141,0.53125c0.39453,0.35156 0.59375,0.8125 0.59375,1.375c0,0.11719 -0.01172,0.32422 -0.03906,0.61719c-0.02734,0.29297 -0.07812,0.5625 -0.15234,0.8125l-0.75781,2.67969c-0.0625,0.21484 -0.11719,0.46094 -0.16797,0.73438c-0.04687,0.27344 -0.07031,0.48438 -0.07031,0.625c0,0.35547 0.07813,0.60156 0.23828,0.73047c0.15625,0.12891 0.43359,0.19141 0.82813,0.19141c0.18359,0 0.39063,-0.03125 0.625,-0.09375c0.23047,-0.06641 0.39844,-0.12109 0.50391,-0.17187zM15.32422,8.61719c-0.35156,0.32813 -0.77734,0.49219 -1.27344,0.49219c-0.49609,0 -0.92578,-0.16406 -1.28125,-0.49219c-0.35547,-0.32812 -0.53125,-0.72656 -0.53125,-1.19141c0,-0.46484 0.17969,-0.86719 0.53125,-1.19922c0.35547,-0.33203 0.78516,-0.49609 1.28125,-0.49609c0.49609,0 0.92188,0.16406 1.27344,0.49609c0.35547,0.33203 0.53125,0.73438 0.53125,1.19922c0,0.46484 -0.17578,0.86328 -0.53125,1.19141z"></path>'+
                        '</g>'+
                        '</g>'+
                        '</svg>' +
                        '<div class="dms-n-config-table-info-text">' + collector.sprintf(collector.translate('Use the radio button to select a Microsite homepage. Read our {0}documentation{1} for details.'), '<a target="_blank" href="https://docs.domainmappingsystem.com/features/creating-microsites-multisite-alternative">', '</a>') + '\n</div>' +
                        '</div>\n' : '') +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-body\'>\n' +
                    '<select class="dms dms-n-config-table-select dms-domain-mapping-values"\n' +
                    'data-mapping="' + item.mapping.id + '"' +
                    'name="dms_map[domains][' + mapping.index + '][mappings][values][]"\n' +
                    'data-index="' + mapping.index + '"\n' +
                    'data-placeholder="' + collector.translate('The choice is yours.') + '"\n' + multiple +
                    'value="">\n' +
                    $options +
                    '</select>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-row\'>\n' +
                    '<div class=\'dms-n-config-table-column code\'>\n' +
                    '<div class=\'dms-n-config-table-header\'>\n' +
                    '<p>\n' +
                    '<span>' + collector.translate('Custom HTML Code') + '</span>\n' +
                    (!collector.is_premium() ?
                        '<a href="' + dms_fs.upgrade_url + '">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                    '</p>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-body\'>\n' +
                    '<input type=\'text\'\n' +
                    'name=\'dms_map[domains][' + mapping.index + '][custom_html]\'\n' +
                    'data-mapping="' + item.mapping.id + '"' +
                    'class=\'dms-n-config-table-input-code\'\n' +
                    'value=\"' + customHtml +'\"'+
                    'placeholder=\'</' + collector.translate('Code here') + '>\'' +
                    '' + (!collector.is_premium() ? 'disabled' : '') + '/>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-column favicon\'>\n' +
                    '<div class=\'dms-n-config-table-header\'>\n' +
                    '<p>\n' +
                    '<span>' + collector.translate('Favicon per Domain') + '</span>\n' +
                    (!collector.is_premium() ?
                        '<a href="' + dms_fs.upgrade_url + '">' +
                        collector.translate('Upgrade') + '&#8594' +
                        '</a>' : '') +
                    '</p>\n' +
                    '</div>\n' +
                    '<div class=\'dms-n-config-table-body\'>\n' +
                    '<div class=\'dms-n-config-table-favicon\'>\n' +
                    favicon +
                    '<input type="button" name="upload-btn"\n' +
                    'class="' + (!collector.is_premium() ? 'disabled' : '') + ' upload upload-btn"\n' +
                    'value="' + collector.translate('Upload Image') + '" \n' +
                    'id="' + mapping.index + '" >\n' +
                    (collector.is_premium() ?
                        '<input class="dms-attachment-id"  type="hidden"\n' +
                        'data-mapping="' + item.mapping.id + '"' +
                        'name="dms_map[domains][' + mapping.index + '][attachment_id]"\n' +
                        'value="' + item.mapping.attachment_id + '">\n' +
                        '<button class="dms-delete-img"\n' +
                        'title="delete"\n' +
                        'style="display: none"\n' +
                        '>&times;\n' +
                        '</button>\n' : '') +
                    '</div>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '</div>\n' +
                        '<button data-mappingid="' + item.mapping.id + '" class="dms-n-config-table-delete">' +
                    '<i></i>' +
                    '</button>' +
                    '</div>';

                $('.dms-n-row-footer').before($mapping);
                select2.init();

            },
        }

        var stackController = {
            prepareStackMappingObject: (mappingId, method, field, value ) => {
                dmsRest.dmsStack.push({
                    'mappingID' : mappingId,
                    'method': method,
                    'field': field,
                    'value': value
                })
            },
            prepareStackSettingObject(key, value) {
                dmsRest.dmsStack.push({
                    key: key,
                    value: value,
                });
            }
        },
        dms_log = {
            error(err) {
                if (this.debug) {
                    console.error(err);
                }
            },

            info(message) {
                if (this.debug) {
                    console.log(message);
                }
            },

            warn(warning) {
                if (this.debug) {
                    console.warn(warning);
                }
            },
    };
    // Document ready event
    $(document).ready(async function () {
        try {
            dms_log.debug = false;
            // Initialize REST API and wait for fetchItems() to complete
            window.mapping = mapping;
            window.dms_controls = controls;
            window.collector = collector;
            window.dms_log = dms_log;

            await dmsRest.restController.init();
            // After REST API is initialized and items are fetched, proceed with other initializations
            window.collector.init();
            controls.init();
            tabs.init();
            favicon.init();
            // Add empty line
            controls.shrunkCheck();
        } catch (error) {
            window.dms_log.error('Initialization error:', error);
        }
    });
})(jQuery, dms_fs);
