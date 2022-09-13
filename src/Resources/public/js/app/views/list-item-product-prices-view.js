define(function(require) {
    'use strict';

    const BaseListItemProductPricesView = require('oropricing/js/app/views/list-item-product-prices-view');
    const routing = require('routing');

    const ListItemProductPricesView = BaseListItemProductPricesView.extend({
        productIds: [],

        timeout: null,

        realTimePricesRoute: 'real_time_frontend_price',

        constructor: function ListItemProductPricesView(options) {
            ListItemProductPricesView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            BaseListItemProductPricesView.prototype.initialize.call(this, options);

            if (!options.realTimePricesEnabled) {
                return;
            }

            if (this.model.get('id')) {
                this.storeId(this.model.get('id'));
            }
            else {
                this.storeId(this.model.get('prices')[0]['product_id']);
            }
        },

        updatePrice: function(prices) {
            this.model.set('prices', prices);

            this.render();
        },

        storeId: function(id) {
            if (!ListItemProductPricesView.productIds) {
                ListItemProductPricesView.productIds = {};
            }

            if (!Object.keys(ListItemProductPricesView.productIds).includes(id)) {
                if (!ListItemProductPricesView.productIds[id]) {
                    ListItemProductPricesView.productIds[id] = [];
                }

                ListItemProductPricesView.productIds[id].push(this.updatePrice.bind(this));
            }

            this.checkIfDone(id);
        },

        checkIfDone: function(id) {
            if (ListItemProductPricesView.timeout !== null) {
                clearTimeout(ListItemProductPricesView.timeout);
                ListItemProductPricesView.timeout = null;
            }

            if (Object.keys(ListItemProductPricesView.productIds).length > 0) {
                ListItemProductPricesView.timeout = setTimeout(() => {
                    const params = {
                        product_ids: Object.keys(ListItemProductPricesView.productIds)
                    };
                    const URL = routing.generate(this.realTimePricesRoute, params);
                    fetch(URL)
                        .then(response => response.json())
                        .then(data => {
                            for (let id in data) {
                                const prices = data[id];
                                for (let index in ListItemProductPricesView.productIds[id]) {
                                    ListItemProductPricesView.productIds[id][index](prices);
                                }
                            }
                        });
                }, 500);
            }
        }
    });

    return ListItemProductPricesView;
});
