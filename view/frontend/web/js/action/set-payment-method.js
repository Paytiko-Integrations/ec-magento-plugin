define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/alert'
    ],
    function ($, quote, customerData, customer, fullScreenLoader, alert) {
        'use strict';
        return function (messageContainer) {
            let email = customer.isLoggedIn() ? customer.customerData.email : quote.guestEmail;
            let serviceUrl = window.checkoutConfig.payment.paytiko.redirectUrl+'?email='+email;
            let restorecart = window.checkoutConfig.payment.paytiko.redirectUrl+'?cartrestore=yes&email='+email;
            let jqCont;
            let updateIfr = () => {
                const ifr = $('#paytiko_ifr');
                if (!ifr.length) return;
                const w = $(window).width(), h = $(window).height();
                const isMob = ((h > w ? w : h) <= 760);
                ifr.css({ width:(isMob ? '90%' : '720px'), height:(isMob ? '90%' : '650px')});
                const pos = ifr.offset();
                $('#paytiko_close').css({
                    left:parseInt(pos.left + ifr.width() - $(window).scrollLeft() + 6)+'px',
                    top:parseInt(pos.top - $(window).scrollTop() - 12) + 'px'
                });
            }
            let handleScrLoad = (resp) => {
                fullScreenLoader.stopLoader();
                //customerData.invalidate(['cart']);

                jqCont = $(
                    '<div id="paytiko_container" style="position:fixed; top:0; left:0; bottom:0; right:0; z-index: 10000; background:rgba(40,40,40,0.5);">' +
                    '   <div id="paytiko_ifr" style="position:absolute; top:0; bottom:0; left:0; right:0; padding:7px; border-radius:6px; margin:auto; width:720px; height:650px; background-color:white"></div>' +
                    '   <div id="paytiko_close" style="position:absolute; cursor:pointer; color:white; border-radius: 12px; height:24px; width:24px; background-color:#555; font-weight:bold; font-size:22px; line-height:24px; text-align:center">&#215</div>' +
                    '</div>'
                ).appendTo('body');

                try {
                    window.paytikoEcommerceSdk.renderCashier({
                        containerSelector: '#paytiko_ifr',
                        cashierUrl: resp.cashierBaseUrl,
                        sessionToken: resp.sessionToken,
                        locale: 'en-US'
                    });
                } catch (err) {
                    jqCont.hide();
                    alert({ content: $.mage.__('Unable to render cashier:\n' + err) });
                    return;
                }
                $('.paytiko-cashier').css({ width:'100%', height:'100%', border:'none' });
                $('#paytiko_close').click(() => {
                    jqCont.hide();
                    $.ajax({
                        url: restorecart,
                        type: 'get',
                        context: this,
                        dataType: 'json',
                        success: () => {}
                    });
                });
                window.addEventListener('resize', updateIfr, false);
                window.addEventListener('orientationchange', updateIfr, false);
                updateIfr();
            }

            fullScreenLoader.startLoader();
            $.ajax({
                url: serviceUrl,
                type: 'post',
                context: this,
                data: { isAjax: 1 },
                dataType: 'json',
                success: (resp) => {
                    if ($.type(resp)!=='object' || $.isEmptyObject(resp)) {
                        fullScreenLoader.stopLoader();
                        alert({ content: $.mage.__('Sorry, something went wrong. Please try again.') });
                        return;
                    }
                    let scr = document.createElement('script');
                    scr.type = 'text/javascript';
                    scr.src  = resp.embedScriptUrl;
                    scr.onload = () => { handleScrLoad(resp) };
                    document.head.appendChild(scr);
                },
                error: () => {
                    fullScreenLoader.stopLoader();
                    alert({ content: $.mage.__('Sorry, something went wrong. Please try again later.') });
                }
            });
        };
    }
);


