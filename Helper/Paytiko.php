<?php

namespace Paytiko\Paytikopayment\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Helper\AbstractHelper;

class Paytiko extends AbstractHelper
{
    protected $session;
    protected $quote;
    protected $quoteManagement;
    protected $orderSender;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
        $this->session = $session;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        parent::__construct($context);
    }

    public function cancelCurrentOrder($comment)
    {
        $order = $this->session->getLastRealOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
            return true;
        }
        return false;
    }

    public function restoreQuote()
    {
        return $this->session->restoreQuote();
    }

    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }

    
    // -- function for get environment --
    public function baseurlpaytiko(){
        // $env = $this->getConfigData('environment');
    
   $env = \Magento\Framework\App\ObjectManager::getInstance()
    ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
    ->getValue(
        'payment/paytiko/environment',
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
    );
       
         if($env == 'sandbox'){
            $api_url = 'https://qa-core.paytiko.com';
         }
         else if($env == 'live') {
            $api_url = 'https://core.paytiko.com';
         } 
         return $api_url;
    }
    
    // -- function for api send call start --
    public function APIReq($path, $method, $data, $apiKey = null)
    {
        $baseUrl = $this->baseurlpaytiko();
        return $this->send("{$baseUrl}{$path}",$method,$data,$apiKey ?: $this->apiKey );
    }
    
    public function APIReqcheckconfig($baseUrl,$path, $method, $data, $apiKey = null)
    {
        
        return $this->send("{$baseUrl}{$path}",$method,$data,$apiKey ?: $this->apiKey );
    }

    public function send($url, $method, $data = "", $apiKey = null)
    {
        $headers = [];
        if (!empty($apiKey)) {
            $headers[] = "Api-Key: {$apiKey}";
        }
        if (!empty($data)) {
            $headers[] = "Content-Type: application/json";
            $data = is_string($data) ? $data : json_encode($data);
        }
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \Exception("Error: unable to send HTTP request");
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($result === false || ($status !== 201 && $status !== 200 && $status !== 400)) {
            throw new \Exception(
                $result ?: "Error: server request failed with status {$status}"
            );
        }
        curl_close($ch);
        $resp = json_decode($result, true);
        if ($resp === null) {
            throw new \Exception("Error: server sent an unexpected response");
        }
        return $resp ?: [];
    }

    // -- function for api send call end --
}
