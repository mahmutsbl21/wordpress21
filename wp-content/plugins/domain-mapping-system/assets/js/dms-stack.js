window.dmsRest = {
    dmsStack: {
        items: [],
        savedValues: [],

        push(item) {
            this.items.push(item);
        },

        pop() {
            if (!this.isEmpty()) {
                return this.items.pop();
            }
            return null;
        },

        isEmpty() {
            return this.items.length === 0;
        },

        clear() {
            this.items = [];
        },

        size() {
            return this.items.length;
        },

        peek() {
            return !this.isEmpty() ? this.items[this.items.length - 1] : null;
        },

        clearStack() {
            this.items = [];
        }
    },

    restController: {
        async init() {
            try {
                await this.fetchItems();
            } catch (error) {
                window.dms_log.error('Error fetching items:', error);
            }
        },

        getSeparator(){
            let separator = '?'
            if (dms_fs.rest_url.includes(separator)){
                separator = '&';
            }

            return separator
        },

        async fetchItems() {
            try {
                const response = await fetch(`${dms_fs.rest_url}mappings${this.getSeparator()}${this.getQueryParams()}`, {
                    headers: {
                        'X-WP-Nonce': dms_fs.rest_nonce
                    }
                });

                await this.checkError(response);

                const responseData = await response.json();

                const itemsCount = responseData.items.length;
                const totalItems = responseData._total;

                window.dms_controls.paginationControl(itemsCount, totalItems);

                for (const item of responseData.items) {
                    mapping.index++;
                    await mapping.loadMapping(item);
                }
                window.dms_controls.hideLoadingContainer();
                if (itemsCount === 0){
                    await window.dms_controls.addRow();
                }
            } catch (error) {
                window.dms_log.error('Fetch error:', error);
                throw error;
            }
        },

        async fetchValues(route, valuesOnly = false) {
            try {
                const response = await fetch(route, {
                    headers: {
                        'X-WP-Nonce': dms_fs.rest_nonce
                    }
                });

                await this.checkError(response);

                const responseData = await response.json();

                if (responseData.items.length) {
                    dmsRest.dmsStack.savedValues[responseData.items[0].value.mapping_id] = responseData.items;
                }

                return mapping.generateValues(responseData, mapping, valuesOnly);
            } catch (error) {
                throw error;
            }
        },

        getQueryParams() {
            return `paged=${dms_fs.paged}&limit=${dms_fs.mappings_per_page}&include=mapping_values`;
        },

        async fetchSVG(url) {
            try {
                const response = await fetch(url, {
                    headers: {
                        'X-WP-Nonce': dms_fs.rest_nonce
                    }
                });

                await this.checkError(response);
                return await response.text();
            } catch (error) {
                window.dms_log.error('SVG fetch error:', error);
                throw error;
            }
        },

        async fetchImageUrl(attachmentId) {
            try {
                const response = await fetch(`${dms_fs.site_url}/wp-json/wp/v2/media/${attachmentId}`, {
                    headers: {
                        'X-WP-Nonce': dms_fs.rest_nonce
                    }
                });

                await this.checkError(response);

                const imageData = await response.json();

                return imageData.source_url || '';
            } catch (error) {
                window.dms_log.error('Error fetching image URL:', error);
                return null;
            }
        },

        async saveItemsViaRest() {
            if (dmsRest.dmsStack.isEmpty()) {
                window.dms_controls.showMessage(window.collector.translate('Items saved successfully!'), 'success');
                window.dms_controls.hideLoading()
                return;
            }
            try {
                const updatedOptions = this.prepareOptionItems();
                for (const [key, value] of Object.entries(updatedOptions)) {
                    await this.updateOption(key, value);
                }
                const updatedItems = this.prepareUpdatedItems();
                for (const [key, value] of Object.entries(updatedItems)) {
                    await this.updateMapping(key, value);
                }

                const createdItems = this.prepareCreatedItems();
                for (const [key, value] of Object.entries(createdItems)) {
                    await this.createMapping(key, value);
                }

                const deletedItems = this.prepareDeletedItems();
                for (const [key, value] of Object.entries(deletedItems)) {
                    await this.deleteMapping(key, value);
                }

                dmsRest.dmsStack.clearStack();
                window.dms_controls.showMessage(window.collector.translate('Items saved successfully!'), 'success');
                window.dms_controls.hideLoading();
            } catch (error) {
                window.dms_log.error('Error saving items via REST:', error);
            }
        },

        prepareOptionItems() {
            const options = {};

            dmsRest.dmsStack.items.forEach(item => {
                if (item.key) {
                    options[item.key] = item.value;
                }
            });

            return options;
        },

        prepareUpdatedItems() {
            const updatedItems = {};

            dmsRest.dmsStack.items.forEach(item => {
                if (item.mappingID && item.field && item.method === 'update') {
                    if (!updatedItems[item.mappingID]) {
                        updatedItems[item.mappingID] = {};
                    }
                    updatedItems[item.mappingID][item.field] = item.value;
                }
            });

            return updatedItems;
        },

        prepareDeletedItems() {
            const deletedItems = [];

            dmsRest.dmsStack.items.forEach(item => {
                if (item.mappingID && item.method === 'delete') {
                    deletedItems.push(item.mappingID);
                }
            });

            return deletedItems;
        },

        prepareCreatedItems() {
            const createdItems = {};

            dmsRest.dmsStack.items.forEach(item => {
                if (item.field && item.method === 'create') {
                    if (!createdItems[item.mappingID]) {
                        createdItems[item.mappingID] = {};
                    }
                    createdItems[item.mappingID][item.field] = item.value;
                }
            });

            return createdItems;
        },

        async updateOption(key, value) {
            const apiEndpoint = `${dms_fs.rest_url}settings/${key}`;
            const response = await fetch(apiEndpoint, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dms_fs.rest_nonce
                },
                body: JSON.stringify({value: value})
            });

            await this.checkError(response);

            const responseData = await response.json();
        },

        async checkError(response) {
            if (!response.ok) {
                let errorMessage = await response.json();
                let message;
                if (errorMessage.message.length) {
                    message = errorMessage.message;
                } else {
                    message = window.collector.translate('Something went wrong');
                }
                window.dms_controls.showMessage(message, 'error');
                window.dms_controls.hideLoading();
            }
        },

        async updateMapping(mappingId, value) {
            const apiEndpoint = `${dms_fs.rest_url}mappings/${mappingId}`;
            const mappingValues = value.mapping_value;
            delete value.mapping_value;
            let mappingValuesEndpoint;
            if (!value.hasOwnProperty('host') &&
                !value.hasOwnProperty('path') &&
                !value.hasOwnProperty('attachment_id') &&
                !value.hasOwnProperty('custom_html')) {
                mappingValuesEndpoint = `${dms_fs.rest_url}mappings/${mappingId}/values`
            } else {
                const response = await fetch(apiEndpoint, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': dms_fs.rest_nonce
                    },
                    body: JSON.stringify(value)
                });

                await this.checkError(response);

                const responseData = await response.json();
                mappingValuesEndpoint = responseData._links.values.href;
            }

            if (mappingValues) {
                await this.deleteOldValues(mappingId);
                await this.createValues(mappingValuesEndpoint, mappingValues);
            }
        },

        async deleteMapping(key, mappingId) {
            const apiEndpoint = `${dms_fs.rest_url}mappings/${mappingId}`;
            const response = await fetch(apiEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dms_fs.rest_nonce
                },
            });
            await this.checkError(response);
            await this.deleteValues(mappingId);
        },

        async createMapping(mappingId, value) {
            const apiEndpoint = `${dms_fs.rest_url}mappings/`;
            const mappingValues = value.mapping_value;
            delete value.mapping_value;
            value.attachment_id = value.attachment_id == 0 ? null : value.attachment_id;
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dms_fs.rest_nonce
                },
                body: JSON.stringify(value)
            });

            await this.checkError(response);

            const responseData = await response.json();

            if (mappingValues) {
                await this.createValues(responseData._links.values.href, mappingValues);
            }
        },

        async deleteValues(mappingId) {
            const apiEndpoint = `${dms_fs.rest_url}mappings/${mappingId}/values`;
            const response = await fetch(apiEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dms_fs.rest_nonce
                },
            });
            await this.checkError(response);
        },

        async createValues(endpoint, mappingValues) {
            if (typeof mappingValues.value == 'string') {
                mappingValues.primary = mappingValues.value;
                mappingValues.value = new Array(mappingValues.value);
            }
            for (const mappingValue of mappingValues.value) {
                if (mappingValue === 'load-more') {
                    continue;
                }
                let resVal = 0;
                let objectType = 'post';
                if (mappingValue.startsWith("term_")) {
                    resVal = parseInt(mappingValue.substring(5));
                    objectType = 'term';
                } else if(mappingValue.startsWith("wcfm_store_")){
                    resVal = parseInt(mappingValue.substring(11));
                    objectType = 'wcfm_store';
                } else if (!isNaN(mappingValue)) {
                    resVal = +mappingValue;
                }

                let primary = mappingValue === mappingValues.primary ? 1 : 0;
                primary = mappingValues.length === 1 ? 1 : primary;

                const result = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': dms_fs.rest_nonce
                    },
                    body: JSON.stringify({
                        'object_type': objectType,
                        'object_id': resVal,
                        'primary': primary,
                    })
                });

                await this.checkError(result);

                const resultData = await result.json();
            }
        },

        async deleteOldValues(mappingId) {
            let count = jQuery('select[data-mapping="' + mappingId + '"]').next().next().val();
            const apiEndpoint = `${dms_fs.rest_url}mappings/${mappingId}/values${this.getSeparator()}count=${count}`;

            const result = await fetch(apiEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dms_fs.rest_nonce
                },
            });

            await this.checkError(result);
        },
    },
};
