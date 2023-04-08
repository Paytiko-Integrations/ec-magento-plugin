<?php

namespace Paytiko\PaytikoPayments\Controller\Standard;

class Notify extends \Paytiko\PaytikoPayments\Controller\PaytikoAbstract
{
    public function execute()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $orderRef = $data['OrderId'];
        $newStatus = strtolower($data['TransactionStatus']);

        $apiKey = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue('payment/paytiko/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($data['Signature'] !== hash('sha256', "{$apiKey}:{$orderRef}")) {
            return;
        }

        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get("Magento\Framework\App\ResourceConnection");
        $connection = $this->_resources->getConnection();

        $orderRec = $connection->fetchAll("SELECT `entity_id` FROM {$this->_resources->getTableName("sales_order")} WHERE `paytiko_order_ref`='{$orderRef}'");
        if (empty($orderRec) || empty($orderRec[0])) return;

        $orderId = $orderRec[0]['entity_id'];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create("\Magento\Sales\Model\OrderRepository")->get($orderId);
        $currStatus = $order->getStatus();

        if ($currStatus == "pending" || $currStatus == "canceled") {
            if ($newStatus == "success") {
                $this->getPaymentMethod()->postProcessing($order, $orderRef);

            } elseif (
                $newStatus == "failed" ||
                $newStatus == "rejected"
            ) {
                $order->cancel()->save();
                $this->_cancelPayment();
            }
        }
    }
}
