define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
],function(Component,renderList){
    'use strict';
    renderList.push({
        type : 'paytiko',
        component : 'Paytiko_PaytikoPayments/js/view/payment/method-renderer/paytiko-method'
    });

    return Component.extend({});
})
