<?php
namespace Paytiko\Paytikopayment\Controller\Standard;

class Notify extends \Paytiko\Paytikopayment\Controller\PaytikoAbstract
{
    public function execute()
    {
        $json = file_get_contents("php://input");


        $action = json_decode($json, true);
        $orderRef = $action["OrderId"];
        $status = $action["TransactionStatus"];

        $paymentMethod = $this->getPaymentMethod();

        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get(
            "Magento\Framework\App\ResourceConnection"
        );
        $connection = $this->_resources->getConnection();

        $sql =
            "Select `entity_id` from " .
            $this->_resources->getTableName("sales_order") .
            " where `paytiko_order_ref` = '" .
            $orderRef .
            "'";
        $resultid = $connection->fetchAll($sql);
        $orderId = $resultid[0]["entity_id"];
        $orderId = $orderId;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager
            ->create("\Magento\Sales\Model\OrderRepository")
            ->get($orderId);
        $orderStatus = $order->getStatus();

        if ($orderStatus == "pending" || $orderStatus == "canceled") {
            if ($status == "Success") {
                $payment = $order->getPayment();
                $paymentMethod->postProcessing($order, $payment, $orderRef);
            } elseif ($status == "Cancelled" || $status == "Declined" || $status == "Rejected") {
                $order->cancel()->save();
                $this->_cancelPayment();
            }
        }
    }
}
