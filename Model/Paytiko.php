<?php
namespace Paytiko\PaytikoPayments\Model;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Paytiko extends \Magento\Payment\Model\Method\AbstractMethod {

    const PAYMENT_PAYTIKO_CODE = 'paytiko';

    protected $_template = 'Paytiko_PaytikoPayments::system/config/infoLink.phtml';
    protected $_code = self::PAYMENT_PAYTIKO_CODE;
    protected $urlBuilder;
    protected $_urlBuilder;
    protected $storeManager;
    private $checkoutSession;

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
        \Paytiko\PaytikoPayments\Helper\Paytiko $helperData,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->orderSender = $orderSender;
        $this->httpClientFactory = $httpClientFactory;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager;

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

    public function getTransactionStatus($payment_ref){
         return $this->helperData->APIReq("orderStatus/{$payment_ref}",'GET','', $this->getConfigData('api_key'));
    }

    public function buildCheckoutRequest() {
        $timestamp = time();
        $order = $this->checkoutSession->getLastRealOrder();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $orderId = $order->getIncrementId();
        $invoiceId = "M2-{$orderId}-{$timestamp}";
        $email = $order->getCustomerEmail();
        $firstName = $billingAddress->getFirstName();
		$lastName = $billingAddress->getLastName();
		$fullname = $firstName . ' ' . $lastName;

        $data = [
            'amount' => (int)($order->getTaxAmount() + $order->getBaseGrandTotal() * 100),
            'currency' => $order->getBaseCurrencyCode(),    //$order->getOrderCurrencyCode(),
            'orderId' => $invoiceId,
            'cashierDescription' => $this->getCashierDescription($invoiceId, $email, $this->storeManager->getStore()->getName(), $this->storeManager->getStore()->getBaseUrl(), $fullname),
            'successRedirectUrl' => $this->getReturnUrl().'?st=1&ref='.$invoiceId,
            'failedRedirectUrl' => $this->getReturnUrl().'?st=0&ref='.$invoiceId,
            'webhookUrl' => $this->getNotifyUrl(),
            'billingDetails' => [
                'uniqueIdentifier' => $order->getCustomerId() ? "M2-".$order->getCustomerId() : "M2-G-".$timestamp,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'phone' => str_replace(['-', ' '], '', $billingAddress->getTelephone()),
                'street' => implode(' ', $billingAddress->getStreet()),
                'region' => $billingAddress->getRegion(),
                'city' => $billingAddress->getCity(),
                'zipCode' => $billingAddress->getPostcode(),
                'country' => $billingAddress->getCountryId()
            ],
            'orderDetails' => [
                'shipping_address' => [
                    'address_line_1' => implode(' ',$shippingAddress->getStreet()),
                    'address_line_2' => '',
                    'admin_area_1'   => $shippingAddress->getRegion(),
                    'admin_area_2'   => $shippingAddress->getCity(),
                    'postal_code'    => $shippingAddress->getPostcode(),
                    'country_code'   => $shippingAddress->getCountryId()
                ]
            ]
        ];
        $toHostedPage = $this->getConfigData('paymentDialog') == 'hosted';
        $response = $this->helperData->APIReq("checkout/" . ($toHostedPage ? 'hosted-page' : ''),"POST", json_encode($data), $this->getConfigData('api_key'));

        return [
            'embedScriptUrl' => $this->getConfigData('embedScriptUrl'),
            'cashierBaseUrl' => $this->getConfigData('cashierBaseUrl'),
            'redirectUrl' => $toHostedPage ? $response['redirectUrl'] : '',
            'sessionToken'   => $toHostedPage ? '' : $response['cashierSessionToken'],
            'orderId' => $invoiceId
        ];
    }

    public function preProcessing(
         \Magento\Sales\Model\Order $order,
         $orderRef
    ) {
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection = $this->_resources->getConnection();
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getIncrementId();
        $sql = "UPDATE {$this->_resources->getTableName('sales_order')} SET `paytiko_order_ref`='{$orderRef}' WHERE `increment_id`={$orderId}";
        $connection->query($sql);
    }

    public function postProcessing(
        \Magento\Sales\Model\Order $order,
        $orderRef
    ) {
        $payment = $order->getPayment();
        $payment->setTransactionId($orderRef);
        //$payment->setTransactionAdditionalInfo('Transaction Message', $orderRef);
        $payment->setAdditionalInformation('paytiko_payment_status', 'Paid');
        $payment->addTransaction(TransactionInterface::TYPE_ORDER);
        $payment->setIsTransactionClosed(0);
        $payment->place();
        $order->setState('complete')->setStatus('complete');
        $order->save();
//
//        $this->checkoutSession->clearQuote();
//        $this->checkoutSession->clearStorage();
//        $this->checkoutSession->restoreQuote();
    }

    private function getCashierDescription( $order, $email, $shop_name, $shop_url, $client_fullname )
    {
		$cashierDescription = $this->getConfigData('cashierDescription');
		$replacements = array(
			'{order}' => $order,
			'{email}' => $email,
			'{shop_name}' => $shop_name,
			'{shop_url}' => '<a href="' . $shop_url . '" target="_blank">' . $shop_url . '</a>',
			'{client_fullname}' => $client_fullname
		);

		foreach ($replacements as $shortcode => $value) {
			$cashierDescription = str_replace($shortcode, $value, $cashierDescription);
		}

		return $cashierDescription;
	}
}
