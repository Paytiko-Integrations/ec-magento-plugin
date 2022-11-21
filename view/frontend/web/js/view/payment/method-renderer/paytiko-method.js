define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Paytiko_Paytikopayment/js/action/set-payment-method',
    ],
    function(Component,setPaymentMethod){
    'use strict';

    return Component.extend({
        defaults:{
            'template':'Paytiko_Paytikopayment/payment/paytiko'
        },
        redirectAfterPlaceOrder: false,
        
        afterPlaceOrder: function () {
            setPaymentMethod();
            //return false;    
        }

    });
});
