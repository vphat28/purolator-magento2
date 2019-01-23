

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    'Purolator_Shipping/js/model/shipping-rates-validator',
    'Purolator_Shipping/js/model/shipping-rates-validation-rules'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    ShippingRatesValidator,
    ShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('purolator', ShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('purolator', ShippingRatesValidationRules);

    return Component;
});
