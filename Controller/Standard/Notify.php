<?php

namespace Paytiko\PaytikoPayments\Controller\Standard;

class Notify extends \Paytiko\PaytikoPayments\Controller\PaytikoAbstract
{
    public function execute()
    {
        $json = file_get_contents("php://input");

        $action = json_decode($json, true);
        $orderRef = $action["OrderId"];
        $status = $action["TransactionStatus"];

        $apiKey = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue(
                "payment/paytiko/api_key",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

        if ($action["Signature"] !== hash("sha256", "{$apiKey}:{$orderRef}")) {
            exit();
        }

        $paymentMethod = $this->getPaymentMethod();
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get("Magento\Framework\App\ResourceConnection");
        $connection = $this->_resources->getConnection();

        $sql = "SELECT `entity_id` from {$this->_resources->getTableName("sales_order")} WHERE `paytiko_order_ref`='{$orderRef}'";
        $resultId = $connection->fetchAll($sql);
        $orderId = $resultId[0]["entity_id"];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create("\Magento\Sales\Model\OrderRepository")->get($orderId);
        $orderStatus = $order->getStatus();

        if ($orderStatus == "pending" || $orderStatus == "canceled") {
            if ($status == "Success") {
                $payment = $order->getPayment();
                $paymentMethod->postProcessing($order, $payment, $orderRef);
            } elseif (
                $status == "Cancelled" ||
                $status == "Declined" ||
                $status == "Rejected"
            ) {
                $order->cancel()->save();
                $this->_cancelPayment();
            }
        }
    }
}
