<?php

namespace Paytiko\Paytikopayment\Controller\Standard;

class Response extends \Paytiko\Paytikopayment\Controller\PaytikoAbstract
{
    public function execute()
    {
        $returnUrl = $this->getCheckoutHelper()->getUrl("checkout");

        $params = $this->getRequest()->getParams();

        if (!isset($params["ref"])) {
            $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get(
                "Magento\Framework\App\ResourceConnection"
            );

            $this->_checkoutSession->restoreQuote();
            $this->messageManager->addErrorMessage(
                __(
                    "Payment failed. Please try again or choose a different payment method"
                )
            );

            $returnUrl = $this->getCheckoutHelper()->getUrl("checkout/cart");
        } else {
            try {
                $paymentMethod = $this->getPaymentMethod();
                $params = $this->getRequest()->getParams();

                $result = $this->getPaymentMethod()->getpaytikotransstatus(
                    $params["ref"]
                );

                $orderRef = $params["ref"];
                //$private_key = 'h3QR26CmRyEU';
                $private_key = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(
                        \Magento\Framework\App\Config\ScopeConfigInterface::class
                    )
                    ->getValue(
                        "payment/paytiko/private_key",
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );

                //$statusCode = $responsedecoded->statusCode;
                //$statusDescription = $responsedecoded->statusDescription;
                //$errorMessage = $responsedecoded->errorMessage;

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

                $orderId = $resultid[0];
                // $statusCode = $result->statusCode;
                if (isset($result->statusCode)) {
                    $statusCode = $result->statusCode;
                }
                if (isset($result->TransactionStatus)) {
                    $statusCode = $result->TransactionStatus;
                }

                $order = $this->getOrder($orderId);
                $orderStatus = $order->getStatus();

                if ($orderStatus == "complete") {
                    $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/onepage/success"
                    );
                    // $payment = $order->getPayment();
                    // $paymentMethod->postProcessing($order, $payment, $orderRef);
                    $this->messageManager->addSuccess(
                        __("Your payment was successful")
                    );
                    
                } elseif ($statusCode == 2) {
                    $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/onepage/success"
                    );
                    // $payment = $order->getPayment();
                    // $paymentMethod->postProcessing($order, $payment, $orderRef);
                    $this->messageManager->addSuccess(
                        __("Your payment was successful")
                    );
                } elseif ($statusCode == 3) {
                    if ($orderStatus == "complete") {
                        $returnUrl = $this->getCheckoutHelper()->getUrl(
                            "checkout/onepage/success"
                        );
                        // $payment = $order->getPayment();
                        // $paymentMethod->postProcessing($order, $payment, $orderRef);
                        $this->messageManager->addSuccess(
                            __("Your payment was successful")
                        );
                        
                    } else {
                        $order->cancel()->save();
                        $this->_checkoutSession->restoreQuote();
                        $this->messageManager->addErrorMessage(
                            __(
                                "Payment failed. Please try again or choose a different payment method"
                            )
                        );
                        $returnUrl = $this->getCheckoutHelper()->getUrl(
                            "checkout/onepage/failure"
                        );
                    }
                } elseif ($statusCode == 4) {
                    if ($orderStatus == "complete") {
                        $returnUrl = $this->getCheckoutHelper()->getUrl(
                            "checkout/onepage/success"
                        );
                        // $payment = $order->getPayment();
                        // $paymentMethod->postProcessing($order, $payment, $orderRef);
                        $this->messageManager->addSuccess(
                            __("Your payment was successful")
                        );
                        
                    } else {
                        $order->cancel()->save();
                        $this->_checkoutSession->restoreQuote();
                        $this->messageManager->addErrorMessage(
                            __(
                                "Payment failed. Please try again or choose a different payment method"
                            )
                        );
                        $returnUrl = $this->getCheckoutHelper()->getUrl(
                            "checkout/onepage/failure"
                        );
                    }
                } elseif ($statusCode == 1) {
                    $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/onepage/success"
                    );
                    $this->messageManager->addSuccess(
                        __("Your payment was successful")
                    );
                } elseif ($statusCode == "Rejected") {
                    $order->cancel()->save();
                    $this->_checkoutSession->restoreQuote();
                    $this->messageManager->addErrorMessage(
                        __(
                            "Payment failed. Please try again or choose a different payment method"
                        )
                    );
                    $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/onepage/failure"
                    );
                } else {
                    $this->messageManager->addNotice(
                        __("Your payment was already processed")
                    );
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    $e->getMessage()
                );
            } catch (\Exception $e) {
                //$this->_checkoutSession->restoreQuote();
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t place the order paytiko.')
                );
            }
        }
        $this->getResponse()->setRedirect($returnUrl);
    }
}
