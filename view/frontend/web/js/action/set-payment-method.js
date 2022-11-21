define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Paytiko_Paytikopayment/js/form/form-builder',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/modal'
    ],
    function ($, quote, customerData,customer, fullScreenLoader, formBuilder,alert,modal) {
        'use strict';
        return function (messageContainer) {
            var serviceUrl,
                email,
                form;

            if (!customer.isLoggedIn()) {
                email = quote.guestEmail;
            } else {
                email = customer.customerData.email;
            }


            serviceUrl = window.checkoutConfig.payment.paytiko.redirectUrl+'?email='+email;
            // console.log(window.checkoutConfig.payment.paytiko.redirectUrl);
            // console.log(serviceUrl);
            //alert(serviceUrl);
            //fullScreenLoader.startLoader();
            
            $.ajax({
                url: serviceUrl,
                type: 'post',
                context: this,
                data: {isAjax: 1},
                dataType: 'json',
                success: function (response) {
                    if ($.type(response) === 'object' && !$.isEmptyObject(response)) {

                        

                        //alert(response.activation_key);
                        //console.log("working");
                        // console.log(response.activation_key);
                        // console.log(response.cashierBaseUrl);
                        // console.log(response.coreBaseUrl);
                        // console.log(response.embedScriptUrl);
                        // console.log(response.token_val);
                        
                        $('#paytiko_payment_form').remove();
                        form = formBuilder.build(
                            {
                                action: response.url,
                                fields: response.fields
                            }
                        );
                        
                        
                        customerData.invalidate(['cart']);
                        //fullScreenLoader.startLoader();

                        window.paytikoEcommerceSdk.renderCashier({
                                containerSelector: '#placeholder_paytikonew',
                                cashierUrl: response.cashierBaseUrl,
                                sessionToken: response.token_val,
                                locale: 'en-US'
                        });

                        var modaloption = {
                           type: 'popup',
                           modalClass: 'modal-popup',
                           responsive: true,
                           buttons: []
                       };
                       var callforoption = modal(modaloption, $('.callfor-popup'));
                       $('.callfor-popup').modal('openModal');
                       $("#placeholder_paytikonew").modal('show');

                        fullScreenLoader.stopLoader();
                        //form.submit();
                    } else {
                        fullScreenLoader.stopLoader();
                        alert({
                            content: $.mage.__('Sorry, something went wrong. Please try again.')
                        });
                    }
                },
                error: function (response) {
                    fullScreenLoader.stopLoader();
                    alert({
                        content: $.mage.__('Sorry, something went wrong. Please try again later.')
                    });
                }
            });
        };
    }
);


