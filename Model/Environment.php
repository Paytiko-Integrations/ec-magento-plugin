<?php


namespace Paytiko\Paytikopayment\Model;


class Environment implements \Magento\Framework\Option\ArrayInterface
{
    // const ENVIRONMENT_PROD   = 'live';
    // const ENVIRONMENT_TEST   = 'sandbox';
    // const ENVIRONMENT_TEST   = 'demo';

    /**
     * Possible environment types
     * 
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'live',
                'label' => 'live'
            ],
            [
                'value' => 'sandbox',
                'label' => 'sandbox'
            ]
        ];
    }
}
