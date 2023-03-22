<?php
namespace Paytiko\PaytikoPayments\Controller\Standard;

class Redirect extends \Paytiko\PaytikoPayments\Controller\PaytikoAbstract
{
    public function execute()
    {
        $data = json_decode($this->getRequest()->getContent(), true);
        $resp = [];

        if ($data['action'] === 'getCheckoutData') {
            $resp = $this->getPaymentMethod()->buildCheckoutRequest();
            $this->getPaymentMethod()->preProcessing(
                $this->getOrder(),
                $resp['orderId']
            );

        } elseif ($data['action'] === 'restoreCart') {
            $this->_checkoutSession->restoreQuote();
            $resp = ['result' => 'ok'];
        }

        return $this->resultJsonFactory->create()->setData($resp);
    }
}


//        $cartrestore = $this->getRequest()->getParam("cartrestore");
//        if ($cartrestore == "yes") {
//            $this->_checkoutSession->restoreQuote();
//
//            $quote = $this->getQuote();
//            $email = $this->getRequest()->getParam("email");
//            if ($this->getCustomerSession()->isLoggedIn()) {
//                $this->getCheckoutSession()->loadCustomerQuote();
//                $quote->updateCustomerData($this->getQuote()->getCustomer());
//            } else {
//                $quote->setCustomerEmail($email);
//            }
//
//            if ($this->getCustomerSession()->isLoggedIn()) {
//                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
//            } else {
//                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
//            }
//
//            $quote->setCustomerEmail($email);
//            $quote->save();
//        }

//        if (!$this->getRequest()->isAjax()) {
//            $this->_cancelPayment();
//            $this->_checkoutSession->restoreQuote();
//            $this->getResponse()->setRedirect(
//                $this->getCheckoutHelper()->getUrl("checkout")
//            );
//        }

//        $quote = $this->getQuote();
//        $email = $this->getRequest()->getParam("email");
//        if ($this->getCustomerSession()->isLoggedIn()) {
//            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
//            $this->getCheckoutSession()->loadCustomerQuote();
//            $quote->updateCustomerData($this->getQuote()->getCustomer());
//        } else {
//            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
//            $quote->setCustomerEmail($email);
//        }
//
//        $quote->setCustomerEmail($email);
//        $quote->save();
//
//        $paymentMethod = $this->getPaymentMethod();
//        $params = $this->getPaymentMethod()->buildCheckoutRequest();
//        $order = $this->getOrder();
//        $payment = $order->getPayment();
//        $paymentMethod->preProcessing($order, $payment, $params);
//
//        return $this->resultJsonFactory->create()->setData($params);
//    }
//}
