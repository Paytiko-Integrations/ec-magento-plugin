<?php
namespace Paytiko\PaytikoPayments\Observer;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Orderinfo implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        try {
            //$orderDetailData = 'your value';
            $order = $observer->getEvent()->getOrder();
            $order->setData('paytiko_order_ref', $response["orderReference"]);
            $order->save();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
}
