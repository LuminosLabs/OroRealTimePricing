define(function(require) {
    'use strict';

    const BaseTotalsComponent = require('oropricing/js/app/components/totals-component');
    const mediator = require('oroui/js/mediator');

    const RealtimeTotalsComponent = BaseTotalsComponent.extend({

        constructor: function RealtimeTotalsComponent(options) {
            RealtimeTotalsComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            RealtimeTotalsComponent.__super__.initialize.call(this, options);

            mediator.on('real-time-prices-update', this.updateTotals, this);

            if (window.realTimePricesTotals) {
                this.updateTotals(window.realTimePricesTotals);
            }
        },
        /**
         * Get and render totals
         */
        updateTotals: function(realTimeTotals) {
            this.showLoadingMask();

            let totals = {
                subtotals: [{
                    amount: realTimeTotals.total_value,
                    signedAmount: realTimeTotals.total_value,
                    formattedAmount: realTimeTotals.total,
                    label: 'Subtotal',
                    type: 'subtotal',
                    visible: true
                }],
                total: {
                    amount: realTimeTotals.total_value,
                    signedAmount: realTimeTotals.total_value,
                    formattedAmount: realTimeTotals.total,
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
