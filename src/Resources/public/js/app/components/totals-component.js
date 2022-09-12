define(function(require) {
    'use strict';

    const BaseTotalsComponent = require('oropricing/js/app/components/totals-component');

    const RealtimeTotalsComponent = BaseTotalsComponent.extend({

        constructor: function RealtimeTotalsComponent(options) {
            RealtimeTotalsComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            RealtimeTotalsComponent.__super__.initialize.call(this, options);
            if (window.realTimePricesTotals) {
                this.updateTotals();
            }
        },
        /**
         * Get and render totals
         */
        updateTotals: function(e) {
            this.showLoadingMask();
            const totalValue = window.realTimePricesTotals['total_value'];
            const total = window.realTimePricesTotals['total'];
            let totals = {
                subtotals: [{
                    amount: totalValue,
                    signedAmount: totalValue,
                    formattedAmount: total,
                    label: 'Subtotal',
                    type: 'subtotal',
                    visible: true
                }],
                total: {
                    amount: totalValue,
                    signedAmount: totalValue,
                    formattedAmount: total,
                    label: 'Total',
                    type: 'total',
                    visible: true
                }
            };
            this.hideLoadingMask();
            this.triggerTotalsUpdateEvent(totals);
            totals = this.setDefaultTemplatesForData(totals);
            this.render(totals);
        }
    });

    return RealtimeTotalsComponent;
});