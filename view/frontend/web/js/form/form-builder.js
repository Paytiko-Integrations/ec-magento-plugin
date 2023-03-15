define(
    [
        'jquery',
        'underscore',
        'mage/template'
    ],
    function ($, _, mageTemplate) {
        'use strict';
        return {
            build: function (formData) {
                //var iframe_url = 'https://dev-cashier.paytiko.com?hash=DAAAAHtqQFtmbpvjmxop8RAAAABJAqcHvrLPtIG0ImiXidmTlpkehR_qRvpmpkO-LcithEVkfO85aQKeN0NnTOViWzVZW1mQvViyHGcHgzy4C5xVJrrcBLCQPiMcDJ5VGlqQs-0fMI8feMQsGcckHbdkKKaepZTIoWyQhnBNYuQAPjcc6q6ekmDVazW6UE3jrDWZxh4pJqA_vCdY5S780YUzRIwukZVaOnSnAIOWyw9Z7M1o_w'
                var formTmpl = mageTemplate(
                    '<style>.paytiko-cashier { width:100%; height:100%; border:none } .modal-content{height:502px}</style>'+
                    '<div class="callfor-popup" id="placeholder_paytikonew"></div>'
                );

                return $(formTmpl({
                    data: {
                        action: formData.action,
                        fields: formData.fields
                    }
                })).appendTo($('[data-container="body"]'));
            }

        };
    }
);
