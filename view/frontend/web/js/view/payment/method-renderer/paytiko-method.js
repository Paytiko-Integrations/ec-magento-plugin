define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Paytiko_PaytikoPayments/js/action/set-payment-method',
    ],
    function(Component, setPaymentMethod){
    'use strict';

    return Component.extend({
        defaults:{
            'template':'Paytiko_PaytikoPayments/payment/paytiko'
        },
        redirectAfterPlaceOrder: false,
        
        afterPlaceOrder: function () {
            setPaymentMethod();
            //return false;    
        }

    });
});
