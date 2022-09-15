define(function (require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const NumberFormatter = require('orolocale/js/formatter/number');

    const ProductPricesTableSubView = BaseView.extend({

        template: require('tpl-loader!realtime/templates/product-prices-table.html'),

        /**
         * @inheritdoc
         */
        constructor: function ProductPricesTableSubView(options) {
            ProductPricesTableSubView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function (options) {
            ProductPricesTableSubView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        getTemplateData: function () {
            let pricesByUnit = {};
            pricesByUnit[this.model.get('unit')] = this.model.get('prices');
            return {
                prices: pricesByUnit,
                formatter: NumberFormatter
            };
        },

        /**
         * @inheritdoc
         */
        render: function () {
            ProductPricesTableSubView.__super__.render.call(this);
            return this;
        }
    });

    return ProductPricesTableSubView;
});
