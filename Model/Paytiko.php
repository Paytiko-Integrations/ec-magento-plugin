<?php
namespace Paytiko\PaytikoPayments\Model;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\UrlInterface;

class Paytiko extends \Magento\Payment\Model\Method\AbstractMethod {

    const PAYMENT_PAYTIKO_CODE = 'paytiko';
   

    protected $_template = 'Paytiko_PaytikoPayments::system/config/infoLink.phtml';

    protected $_code = self::PAYMENT_PAYTIKO_CODE;

    protected $_canCapture = true;

    protected $_canCapturePartial = true;

    protected $_canRefund = true;

    protected $_canRefundInvoicePartial = true;

    /**
     *
     * @var \Magento\Framework\UrlInterface 
     */
    protected $urlBuilder;

    protected $_urlBuilder;
    
    private $checkoutSession;

    /**
     * 
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
      public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Paytiko\PaytikoPayments\Helper\Paytiko $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        UrlInterface $urlBuilder,
        \Paytiko\PaytikoPayments\Helper\Paytiko $helperData
              
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->helperData = $helperData;
        

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );

    }

    public function getRedirectUrl() {
        return $this->helper->getUrl($this->getConfigData('redirect_url'));
    }

    public function getReturnUrl() {
        return $this->helper->getUrl($this->getConfigData('return_url'));
    }

    public function getNotifyUrl() {
        return $this->helper->getUrl($this->getConfigData('notify_url'));
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
      {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }
        return $this;
      }
    /**
     * Capture payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }
        return $this;
    }
    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
      throw new \Magento\Framework\Exception\LocalizedException(__('Refund not available.'));
    }

    /**
     * Return url according to environment
     * @return string
     */
    public function getCgiUrl() {
        $env = $this->getConfigData('environment');
        if ($env === 'prod') {
            return $this->getConfigData('prod_url');
        }
        return $this->getConfigData('test_url');
    }

    public function getpaytikotransstatus($payment_ref){

         $env = $this->getConfigData('environment');
         $private_key = $this->getConfigData("private_key");
         $response = $this->helperData->APIReq("/api/cashier/ecommerce/orderStatus/$payment_ref","GET","",$private_key);
         return $response;
    }

    public function buildCheckoutRequest() {
        $env = $this->getConfigData('environment');
        $timestamp = time();
        $order = $this->checkoutSession->getLastRealOrder();
        $billingAddress = $order->getBillingAddress();

        $params = array();
        $params["appId"] = $this->getConfigData("app_id");
        $params["activation_key"] = $this->getConfigData("activation_key");
        $params["private_key"] = $this->getConfigData("private_key");
        $params["orderId"] = $order->getIncrementId();
        $params["orderAmount"] = round($order->getGrandTotal(), 2);
        $params["orderCurrency"] = $order->getOrderCurrencyCode();
        $params["customerName"] = $billingAddress->getFirstName(). " ". $billingAddress->getLastName();
        $params["customerEmail"] = $order->getCustomerEmail();
        $params["customerPhone"] = $billingAddress->getTelephone();

        $params["cashierBaseUrl"] = $this->getConfigData("cashierBaseUrl");
        $params["coreBaseUrl"] = $this->getConfigData("coreBaseUrl");
        $params["embedScriptUrl"] = $this->getConfigData("embedScriptUrl");

//        $product_data = '';
//        foreach ($order->getAllItems() as $item) {
//          $product_name = $item->getName();
//          $product_id = $item->getProductId();
//          $price = number_format($item->getPrice(), 2);
//          $quantity = round($item->getQtyOrdered());
//          //$tax = number_format($item->getBaseTaxAmount(), 2);
//          $tax = number_format($item->getTaxAmount(), 2);
//          $subtotal = number_format($item->getBaseRowTotal(), 2);
//          $subtotalamount = $tax + $subtotal;
//          $product_data .= '{"name":"'.$product_name.'","itemId":"'.$product_id.'","quantity":"'.$quantity.'","cost":"'.$price.'","price":"'.$price.'", "tax":"'.$tax.'", "variation_id":0, "subtotal":"'.$subtotalamount.'", "total":"'.$subtotalamount.'" },';
//        }

        $invoiceId = "M2-{$params["orderId"]}-{$timestamp}";
        $data = [
            'amount' => (int)($order->getTaxAmount() + $order->getBaseGrandTotal() * 100),
            'currency' => $order->getOrderCurrencyCode(),
            'orderId' => $invoiceId,
            'successRedirectUrl' => $this->getReturnUrl().'?ref='.$invoiceId,
            'failedRedirectUrl' => $this->getReturnUrl().'?ref='.$invoiceId,
            'webhookUrl' => $this->getNotifyUrl(),
            'billingDetails' => [
                'uniqueIdentifier' => $order->getCustomerId() ? "M2-".$order->getCustomerId() : "M2-G-".$timestamp,
                'firstName' => $billingAddress->getFirstName(),
                'lastName' => $billingAddress->getLastName(),
                'email' => $order->getCustomerEmail(),
                'phone' => str_replace(['-', ' '], '', $billingAddress->getTelephone()),
                'street' => implode(' ', $billingAddress->getStreet()),
                'region' => $billingAddress->getRegion(),
                'city' => $billingAddress->getCity(),
                'zipCode' => $billingAddress->getPostcode(),
                'country' => $billingAddress->getCountryId()
            ]
        ];
        $response = $this->helperData->APIReq("/api/cashier/ecommerce/checkout/","POST", json_encode($data), $params['private_key']);
        
        //checkout curl post data end
        $cashierBaseUrl = $params["cashierBaseUrl"];
        $payment_base_url =    "'.$cashierBaseUrl.'?hash='";

        $params["urlparam"] = $payment_base_url;
        $params["token_val"] = $response['cashierSessionToken'];
        $params["url"] = $params["urlparam"].$params["token_val"];
        $params["orderReference"] = $invoiceId;
        $params["mola_inc_id"] = $invoiceId;

        return $params;
    }

     public function preProcessing(\Magento\Sales\Model\Order $order,
            \Magento\Framework\DataObject $payment, $response) {
        
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');

        $connection = $this->_resources->getConnection();

        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getIncrementId();

        $sql = "Update " . $this->_resources->getTableName('sales_order') . " Set `paytiko_order_ref` ='".$response["orderReference"]."' where `increment_id` = ".$orderId;
        $connection->query($sql);

        $sql = "Update " . $this->_resources->getTableName('sales_order') . " Set `paytiko_order_id` ='".$response["mola_inc_id"]."' where `increment_id` = ".$orderId;
        $connection->query($sql);
    }


    public function postProcessing(\Magento\Sales\Model\Order $order,
            \Magento\Framework\DataObject $payment,  $transactionReference) {
        
        $payment->setTransactionId($transactionReference);
        $payment->setTransactionAdditionalInfo('Transaction Message', $transactionReference);
        $payment->setAdditionalInformation('paytiko_payment_status', 'Paid');
        $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        $payment->setIsTransactionClosed(0);
        $payment->place();
        $order->setStatus('complete');
        $order->save();
    }

}
