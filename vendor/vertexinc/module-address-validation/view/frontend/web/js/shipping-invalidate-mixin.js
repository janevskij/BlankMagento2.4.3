/**
 * @copyright  Vertex. All rights reserved.  https://www.vertexinc.com/
 * @author     Mediotype                     https://www.mediotype.com/
 */

define([
    'uiRegistry',
    'mage/utils/wrapper'
], function (registry, wrapper) {
    'use strict';

    const config = window.checkoutConfig.vertexAddressValidationConfig || {};

    return function (target) {
        if (!config.isAddressValidationEnabled) {
            return target;
        }

        const validationMessage = registry.get(
            'checkout.steps.shipping-step.shippingAddress' +
            '.before-shipping-method-form.shippingAdditional'
        );

        target.setSelectedShippingAddress = wrapper.wrap(target.setSelectedShippingAddress, function (original, args) {
            const addressValidator = registry.get(
                'checkout.steps.shipping-step.shippingAddress' +
                '.before-shipping-method-form.shippingAdditional' +
                '.address-validation-message.validator'
            );

            addressValidator.isAddressValid = false;
            validationMessage.clear();

            return original(args);
        });

        return target;
    }
});
