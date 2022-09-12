define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const routing = require('routing');

    const RealTimePrices = BaseComponent.extend({

        /**
         * @property {Grid}
         */
        datagrid: null,

        /**
         * @property {Object}
         * */
        productToLineItem: {},

        /**
         * @property {string}
         */
        realTimePricesRoute: 'datagrid_real_time_frontend_price',

        constructor: function RealTimePrices(options) {
            RealTimePrices.__super__.constructor.call(this, options);
        },
        /**
         * @param {Object} [options.grid] grid instance
         * @param {Object} [options.options] grid initialization options
         */
        initialize: function(options) {
            this.datagrid = options.grid;

            if (this.datagrid.rendered) {
                this.updatePrices();
            }
            // for price update from FE
            this.datagrid.on('content:update', this.updatePrices, this);

        },

        updatePrices: function() {
            const lineItemModels = this.datagrid.collection.models;
            const productQuantities = {};
            lineItemModels.forEach(lineItemModel => {
                const productId = lineItemModel.get('productId');
                const lineItemModelId = lineItemModel.get('id');
                if (lineItemModelId.indexOf('bind') === -1) {
                    productQuantities[productId] = lineItemModel.get('quantity');
                    this.productToLineItem[productId] = lineItemModelId;
                }
            });

            const params = {
                product_quantities: productQuantities
            };
            const URL = routing.generate(this.realTimePricesRoute, params);
            fetch(URL)
                .then(response => response.json())
                .then(pricesData => {
                    // for totals-component
                    window.realTimePricesTotals = {
                        total_value: pricesData['totals']['total_value'],
                        total: pricesData['totals']['total']
                    };
                    this.setPrices(pricesData);
                });
        },

        setPrices: function(pricesData) {
            const prices = pricesData['line_items'];
            for (const productId in prices) {
                const lineItemId = this.productToLineItem[productId];
                const lineItemModel = this.datagrid.collection.get(lineItemId);
                lineItemModel.set('price', prices[productId]['unit_price']);
                lineItemModel.set('subtotal', prices[productId]['subtotal']);
            }
        }
    });

    return {
        /**
         * @param {jQuery.Deferred} deferred
         * @param {Object} options
         */
        init: function(deferred, options) {
            options.gridPromise.done(function(grid) {
                const validation = new RealTimePrices({
                    grid: grid,
                    options: options
                });
                deferred.resolve(validation);
            }).fail(function() {
                deferred.reject();
            });
        }
    };
});
