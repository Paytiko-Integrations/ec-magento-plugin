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
            let serviceUrl = window.checkoutConfig.payment.paytiko.redirectUrl;
            let jqCont;

            let showErr = (msg) => {
                alert({ content: $.mage.__(msg || 'Sorry, something went wrong. Please try again later.') });
            }

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

            let showCashier = (resp) => {
                fullScreenLoader.stopLoader();
                //customerData.invalidate(['cart']);

                window.addEventListener('beforeunload', beforeUnloadHandler, { capture: true });

                jqCont = $('#paytiko_container');
                if (jqCont.length) {
                    jqCont.show();
                    updateIfr();
                    return;
                }

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
                    console.error(err);
                    jqCont.hide();
                    alert({ content: $.mage.__('Unable to render cashier:\n' + err) });
                    return;
                }
                $('.paytiko-cashier').css({ width:'100%', height:'100%', border:'none' });
                $('#paytiko_close').click(() => { hideCashier(); });
                window.addEventListener('resize', updateIfr, false);
                window.addEventListener('orientationchange', updateIfr, false);
                updateIfr();
            }

            let beforeUnloadHandler = function() {
                hideCashier(true);
            }

            let hideCashier = (reloadWindow) => {
                if (!reloadWindow) {
                    fullScreenLoader.startLoader();
                }
                fetch(serviceUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: 'restoreCart', email }),
                    keepalive: true
                })
                .then(resp => resp.json())
                .then(resp => {
                    if (!reloadWindow) {
                        window.removeEventListener('beforeunload', beforeUnloadHandler, {capture: true});
                        fullScreenLoader.stopLoader();
                        jqCont.hide();
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (!reloadWindow) {
                        fullScreenLoader.stopLoader();
                        showErr();
                    }
                });
            }

            ///////////////

            fullScreenLoader.startLoader();
            fetch(serviceUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: 'getCheckoutData' }),
            })
                .then(resp => resp.json())
                .then(resp => {
                    if (typeof resp !== 'object') {
                        fullScreenLoader.stopLoader();
                        showErr();
                        return;
                    }
                    let scr = document.createElement('script');
                    scr.type = 'text/javascript';
                    scr.src  = resp.embedScriptUrl;
                    scr.onload = () => { showCashier(resp) };
                    document.head.appendChild(scr);
                })
                .catch(err => {
                    console.error(err);
                    fullScreenLoader.stopLoader();
                    showErr();
                });
        };
    }
);
