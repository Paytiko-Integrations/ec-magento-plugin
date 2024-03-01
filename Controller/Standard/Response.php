<?php
namespace Paytiko\PaytikoPayments\Controller\Standard;

class Response extends \Paytiko\PaytikoPayments\Controller\PaytikoAbstract
{
    private $_resources;

    public function execute()
    {
        $url = 'checkout';
        $params = $this->getRequest()->getParams();

        if (!isset($params['ref'])) {
            $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
            $this->_checkoutSession->restoreQuote();
            $this->messageManager->addErrorMessage(__('Payment failed. Please try again or choose a different payment method'));
            $url = 'checkout/cart';

        } else {
            try {
                $orderRef = $params['ref'];
                $paytikoTransactData = $this->getPaymentMethod()->getTransactionStatus($orderRef);

                $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
                $connection = $this->_resources->getConnection();
                $orderRec = $connection->fetchAll("SELECT `entity_id` FROM {$this->_resources->getTableName('sales_order')} WHERE `paytiko_order_ref`='{$orderRef}'");
                if (empty($orderRec) || empty($orderRec[0])) {
                    throw new \Exception('Problematic order reference id');
                }
                $order = $this->getOrder($orderRec[0]['entity_id']);
                $currStatus = $order->getStatus();
                $newStatus = strtolower($paytikoTransactData['statusDescription']);

                if ($newStatus==='success') {
                    $this->messageManager->addSuccess(__('Your payment was successful'));
                    $url = 'checkout/onepage/success';

                } elseif ($newStatus==='pending') {
                    $this->messageManager->addSuccess(__('Your payment is pending'));
                    $url = 'checkout/onepage/success';

                } elseif ($newStatus==='rejected' || $newStatus==='failed') {
                    $this->_checkoutSession->restoreQuote();
                    $this->messageManager->addErrorMessage(__('Payment has been canceled or failed'));
                    $url = 'checkout/cart';

                } else {
                    $this->messageManager->addNotice(__('Your payment was already processed'));
                }

                if ($currStatus == "pending" || $currStatus == "canceled") {
                    if ($newStatus == "success") {
                        $this->getPaymentMethod()->postProcessing($order, $orderRef);
                    } elseif ($newStatus == "rejected" || $newStatus == "failed") {
//                      $order->cancel()->save();
                        $order->setState('canceled')->setStatus('canceled');
                        $order->save();
                    }
                }

            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('There has been an error. Please try refreshing this page.'.$e));
                $url = 'checkout/cart';
            }
        }
        $this->getResponse()->setRedirect($this->getCheckoutHelper()->getUrl($url));
    }
}
