<?php

namespace Paytiko\PaytikoPayments\Model;

class PaymentDialog implements \Magento\Framework\Option\ArrayInterface {
    public function toOptionArray()
    {
        return [
            [
                'value' => 'popup',
                'label' => 'Show popup on top of checkout screen'
            ],
            [
                'value' => 'hosted',
                'label' => 'Redirect to dedicated payment page'
            ]
        ];
    }
}
