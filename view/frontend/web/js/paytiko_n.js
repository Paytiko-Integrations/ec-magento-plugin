(function () {

    const PAYTIKO_IFRAME_DEFAULT_CLASS = 'paytiko-cashier';
    const REDIRECT_EVENT_TYPE = 3;

    class PaytikoEcommerceSdk {

        renderCashier(options) {

            if (!options.containerSelector) {
                throw 'Container selector was not provided.';
            }

            const $container = document.querySelector(options.containerSelector);
            if (!$container) {
                throw 'Container not found by the provided selector.';
            }

            if (!options.cashierUrl) {
                throw 'Cashier URL was not provided.';
            }

            if (!options.sessionToken) {
                throw 'Session token was not provided.';
            }

            const queryParams = {
                hash: options.sessionToken
            };

            if (options.amount) {
                queryParams.m_amount = options.amount;
            }

            if (options.locale) {
                queryParams.m_locale = options.locale;
            }

            if (options.disableRedirects) {
                queryParams.m_redirectsDisabled = true;
            }

            let completeCashierUrl = options.cashierUrl;

            if (queryParams) {
                const queryString = this._toQueryString(queryParams);
                completeCashierUrl = `${completeCashierUrl}?${queryString}`;
            }

            const $iframe = document.createElement('iframe');
            $iframe.src = completeCashierUrl;
            $iframe.classList.add(PAYTIKO_IFRAME_DEFAULT_CLASS);

            while ($container.firstChild) {
                $container.removeChild($container.lastChild);
            }

            $container.appendChild($iframe);

            this._subscribeToCashierEventsIfRequired();
        }

        _subscribeToCashierEventsIfRequired() {

            if (!this._subscribedToCashierEvents) {

                const self = this;

                var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent",
                    addListener = window[eventMethod],
                    messageEvent = (eventMethod == "attachEvent") ? "onmessage" : "message";

                addListener(messageEvent, function (e) {
                    const payload = JSON.parse(e.data);
                    switch (payload.type) {
                        case REDIRECT_EVENT_TYPE:
                            self._handleRedirectEvent(payload.url);
                            break;
                        default:
                            throw `Unable to handle message type '${data.type}'.`;
                    }
                }, false);

                this._subscribedToCashierEvents = true;
            }
        }

        _handleRedirectEvent(redirectUri) {
            window.location.href = redirectUri;
        }

        _toQueryString(obj) {

            const strings = [];

            for (var property in obj) {
                if (obj.hasOwnProperty(property)) {
                    strings.push(encodeURIComponent(property) + "=" + encodeURIComponent(obj[property]));
                }
            }

            return strings.join("&");
        }
    }

    window.paytikoEcommerceSdk = new PaytikoEcommerceSdk();
})();
