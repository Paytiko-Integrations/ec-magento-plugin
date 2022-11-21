<?php
namespace Paytiko\Paytikopayment\Model;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\UrlInterface;

class Paytiko extends \Magento\Payment\Model\Method\AbstractMethod {

    const PAYMENT_PAYTIKO_CODE = 'paytiko';
   

    protected $_template = 'Fattura24_AppFatturazione::system/config/infoLink.phtml';

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
        \Paytiko\Paytikopayment\Helper\Paytiko $helper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        UrlInterface $urlBuilder
              
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        

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
         $params["private_key"] = $this->getConfigData("private_key");
         $private_key = $params['private_key'];

       $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://dev-core.paytiko.com/api/cashier/ecommerce/orderStatus/$payment_ref",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "api-key: $private_key",
                    "cache-control: no-cache",
                ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $output = json_decode($response);


        return $output;
    }

    public function buildCheckoutRequest() {
        $env = $this->getConfigData('environment');
        $timestamp = time();
        $order = $this->checkoutSession->getLastRealOrder();

        $billing_address = $order->getBillingAddress();

        $params = array();
        $params["appId"] = $this->getConfigData("app_id");
        $params["activation_key"] = $this->getConfigData("activation_key");
        $params["private_key"] = $this->getConfigData("private_key");
        $params["orderId"] = $order->getIncrementId();
        $params["orderAmount"] = round($order->getGrandTotal(), 2);
        $params["orderCurrency"] = $order->getOrderCurrencyCode();
        $params["customerName"] = $billing_address->getFirstName(). " ". $billing_address->getLastName();
        $params["customerEmail"] = $order->getCustomerEmail();
        $params["customerPhone"] = $billing_address->getTelephone();

        $appId = $params["appId"];
        $incrementedId = $params["orderId"];
        $grandtotalproduct = $params["orderAmount"];
        
        $basegrandtotals  = number_format($order->getBaseGrandTotal(), 2);
        $productTotal = round($order->getBaseSubtotal());
        $totalshippamount = number_format($order->getBaseShippingAmount(), 2);
        $TotalTaxAmount = number_format($order->getTaxAmount(), 2);


        $totalshiptaxprice = $TotalTaxAmount + $basegrandtotals;

        $totalshiptaxprice = number_format($totalshiptaxprice, 2);

        $product_data = '';
        foreach ($order->getAllItems() as $item) {
          $product_name = $item->getName();
          $product_id = $item->getProductId();
          $price = number_format($item->getPrice(), 2);
          $quantity = round($item->getQtyOrdered());
          //$tax = number_format($item->getBaseTaxAmount(), 2);
          $tax = number_format($item->getTaxAmount(), 2);
          $subtotal = number_format($item->getBaseRowTotal(), 2);
          $subtotalamount = $tax + $subtotal;
          $product_data .= '{"name":"'.$product_name.'","itemId":"'.$product_id.'","quantity":"'.$quantity.'","cost":"'.$price.'","price":"'.$price.'", "tax":"'.$tax.'", "variation_id":0, "subtotal":"'.$subtotalamount.'", "total":"'.$subtotalamount.'" },';
        }

        $invoice_number = "M2-".$incrementedId."-".$timestamp;

        // Before checkout request start

        $activationkey = $params['activation_key'];
        $private_key = $params['private_key'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://dev-core.paytiko.com/api/cashier/ecommerce/config/$activationkey",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "api-key: $private_key",
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $respons_paytiko = json_decode($response);
          $params["cashierBaseUrl"] = $respons_paytiko->cashierBaseUrl;
          $params["coreBaseUrl"] = $respons_paytiko->coreBaseUrl;
          $params["embedScriptUrl"] = $respons_paytiko->embedScriptUrl;
        }
        // Before checkout request end

        //checkout curl post data start
        $totalPrice = $totalshiptaxprice * 100;
        $inputString =  '{
            "amount": "'.$totalPrice.'",
            "currency": "'.$order->getOrderCurrencyCode().'",
            "orderId": "'.$invoice_number.'",
            "redirectUrl": "'.$this->getReturnUrl().'?ref='.$invoice_number.'",
            "FailedRedirectUrl": "'.$this->getReturnUrl().'?ref='.$invoice_number.'",
            "SuccessRedirectUrl": "'.$this->getReturnUrl().'?ref='.$invoice_number.'",
            "webhookUrl": "'.$this->getNotifyUrl().'",
            "billingDetails":
             {
            "uniqueIdentifier": "'.$invoice_number.'",
            "firstName": "'.$billing_address->getFirstName().'",
            "lastName": "'.$billing_address->getLastName().'",
            "email": "'.$order->getCustomerEmail().'",
            "street": "'.$billing_address->getCity().'",
            "region": "'.$billing_address->getRegion().'",
            "city": "'.$billing_address->getCity().'",
            "phone": "'.$billing_address->getTelephone().'",
            "zipCode": "'.$billing_address->getPostcode().'",
            "country": "'.$billing_address->getCountryId().'",
            "dateOfBirth": "1990-03-15"
             }
            }';

        $url = 'https://dev-core.paytiko.com/api/cashier/ecommerce/checkout';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS,$inputString);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Api-Key: $private_key","Content-Type: application/json"));

        $response = curl_exec($ch);
        curl_close($ch);
        

        $response = json_decode($response,true);
        //checkout curl post data end
        $cashierBaseUrl = $params["cashierBaseUrl"];
        $payment_base_url =    "'.$cashierBaseUrl.'?hash='";

        $params["urlparam"] = $payment_base_url;
        $params["token_val"] = $response['cashierSessionToken'];

        $params["url"] = $params["urlparam"].$params["token_val"];

         $params["orderReference"] = $invoice_number;

         $params["mola_inc_id"] = $invoice_number;

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
