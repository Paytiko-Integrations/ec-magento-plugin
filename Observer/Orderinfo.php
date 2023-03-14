<?php

namespace Paytiko\PaytikoPayments\Observer;



use Paytiko\PaytikoPayments\Model\Paytiko;



class Orderinfo implements \Magento\Framework\Event\ObserverInterface

{

    public function execute(\Magento\Framework\Event\Observer $observer)

    {

     	try {

            //$orderDetailData = 'your value';

            $order = $observer->getEvent()->getOrder();

            $order->setData('paytiko_order_ref', $response["orderReference"]);

            $order->save();

	        } catch (\Exception $e) {

	            error_log($e->getMessage());

	        }

    }

}

?>