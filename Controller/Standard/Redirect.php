<?php

namespace Paytiko\Paytikopayment\Controller\Standard;

class Redirect extends \Paytiko\Paytikopayment\Controller\PaytikoAbstract
{
    public function execute()
    {
        $cartrestore = $this->getRequest()->getParam("cartrestore");
        if ($cartrestore == "yes") {
            $this->_checkoutSession->restoreQuote();
            $quote = $this->getQuote();

            $email = $this->getRequest()->getParam("email");
            if ($this->getCustomerSession()->isLoggedIn()) {
                $this->getCheckoutSession()->loadCustomerQuote();
                $quote->updateCustomerData($this->getQuote()->getCustomer());
            } else {
                $quote->setCustomerEmail($email);
            }

            if ($this->getCustomerSession()->isLoggedIn()) {
                $quote->setCheckoutMethod(
                    \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER
                );
            } else {
                $quote->setCheckoutMethod(
                    \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST
                );
            }

            $quote->setCustomerEmail($email);
            $quote->save();
        }

        if (!$this->getRequest()->isAjax()) {
            $this->_cancelPayment();
            $this->_checkoutSession->restoreQuote();
            $this->getResponse()->setRedirect(
                $this->getCheckoutHelper()->getUrl("checkout")
            );
        }

        $quote = $this->getQuote();

        $email = $this->getRequest()->getParam("email");
        if ($this->getCustomerSession()->isLoggedIn()) {
            $this->getCheckoutSession()->loadCustomerQuote();
            $quote->updateCustomerData($this->getQuote()->getCustomer());
        } else {
            $quote->setCustomerEmail($email);
        }

        if ($this->getCustomerSession()->isLoggedIn()) {
            $quote->setCheckoutMethod(
                \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER
            );
        } else {
            $quote->setCheckoutMethod(
                \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST
            );
        }

        $quote->setCustomerEmail($email);
        $quote->save();

        $paymentMethod = $this->getPaymentMethod();
        $params = [];
        $params = $this->getPaymentMethod()->buildCheckoutRequest();
        $params["url"] = $params["url"];
        $order = $this->getOrder();
        $orderStatus = $order->getStatus();
        $payment = $order->getPayment();
        $paymentMethod->preProcessing($order, $payment, $params);

        return $this->resultJsonFactory->create()->setData($params);
    }
}
