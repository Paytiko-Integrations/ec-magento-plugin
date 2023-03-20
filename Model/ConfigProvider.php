<?php

namespace Paytiko\PaytikoPayments\Model;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    protected $methodCode = \Paytiko\PaytikoPayments\Model\Paytiko::PAYMENT_PAYTIKO_CODE;
    
    
    protected $method;
	

    public function __construct(\Magento\Payment\Helper\Data $paymenthelper){
        $this->method = $paymenthelper->getMethodInstance($this->methodCode);
    }

    public function getConfig(){
        return $this->method->isAvailable() ? [
            'payment'=>['paytiko'=>[
                'redirectUrl'=>$this->method->getRedirectUrl()  
            ]
        ]
        ]:[];
    }
}
