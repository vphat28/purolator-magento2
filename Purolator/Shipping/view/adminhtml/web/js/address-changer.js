define([
    'jquery',
    'Magento_Ui/js/form/form',
    'ko',
    'uiComponent',
    'mage/translate',
    'Magento_Ui/js/modal/modal'
    ], function ($, Component, ko, uiComponent, $t) {
        'use strict';
        return Component.extend({
            initialize: function () {
                this._super();
            },
            successModal: $('<div><p algin=center>' + $t('Origin address has be updated for this order') + '</p></div>').modal({
                autoOpen: false,
                buttons: [],
                responsive: true
            }),
            updateAddress: function () {
                this.source.set('params.invalid', false);
                this.source.trigger('shippingAddress.data.validate');
                var self = this;

                if (!this.source.get('params.invalid')) {
                    // if form valid
                    $.ajax({
                        showLoader: true,
                        url: this.source.baseUrl,
                        data: {
                            'form_key': FORM_KEY,
                            'order_id': this.source.order_id,
                            'city': this.source.get('shippingAddress.city'),
                            'postcode': this.source.get('shippingAddress.postcode'),
                            'region': this.source.get('shippingAddress.region'),
                            'country_id': this.source.get('shippingAddress.country_id'),
                            'street_line_1': this.source.get('shippingAddress.streetLine1'),
                            'region_id': this.source.get('shippingAddress.region_id')
                        },
                        type: "POST",
                        dataType: 'json'
                    }).done(function (data) {
                        self.successModal.modal('openModal');
                    });
                }
            }
        });
    }
);