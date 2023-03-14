define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Paytiko_PaytikoPayments/js/form/form-builder',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/modal'
    ],
    function ($, quote, customerData,customer, fullScreenLoader, formBuilder,alert,modal) {
        'use strict';
        return function (messageContainer) {
            var restorecart,
                serviceUrl,
                email,
                form;

            if (!customer.isLoggedIn()) {
                email = quote.guestEmail;
            } else {
                email = customer.customerData.email;
            }

            fullScreenLoader.startLoader();

            serviceUrl = window.checkoutConfig.payment.paytiko.redirectUrl+'?email='+email;

            restorecart = window.checkoutConfig.payment.paytiko.redirectUrl+'?cartrestore=yes&email='+email;
            
            $.ajax({
                url: serviceUrl,
                type: 'post',
                context: this,
                data: {isAjax: 1},
                dataType: 'json',
                success: function (response) {
                    if ($.type(response) === 'object' && !$.isEmptyObject(response)) {
                        fullScreenLoader.stopLoader();
                        
                        form = formBuilder.build(
                            {
                                action: response.url,
                                fields: response.fields
                            }
                        );
                        
                        
                        customerData.invalidate(['cart']);
                        

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
                           clickableOverlay: false,
                           buttons: [{
                                text: $.mage.__('Proceed'),
                                class: '',
                                click: function () {
                                    /* some stuff */
                                    this.closeModal();
                                }
                            }]
                       };
                       var callforoption = modal(modaloption, $('.callfor-popup'));
                       $('.callfor-popup').modal('openModal');
                       
                       $('.modal-popup').on('modalclosed', function() { 
                                
                                $.ajax({
                                    url: restorecart,
                                    type: 'get',
                                    context: this,
                                    dataType: 'json',
                                    success: function (response) {
                                    }
                                });


                        });
                       
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


