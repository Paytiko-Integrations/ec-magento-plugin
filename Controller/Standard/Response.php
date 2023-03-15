<?php

namespace Paytiko\PaytikoPayments\Controller\Standard;

class Response extends \Paytiko\PaytikoPayments\Controller\PaytikoAbstract
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

                $result = $this->getPaymentMethod()->getpaytikotransstatus( $params["ref"]);
                $result = (object)$result;
                $orderRef = $params["ref"];
                $apiKey = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
                    ->getValue("payment/paytiko/api_key", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


                $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get(
                    "Magento\Framework\App\ResourceConnection"
                );
                $connection = $this->_resources->getConnection();

                $sql ="SELECT `entity_id` FROM {$this->_resources->getTableName("sales_order")} WHERE `paytiko_order_ref`='{$orderRef}'";
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
                    
                    $this->messageManager->addSuccess(
                        __("Your payment was successful")
                    );
                } elseif ($statusCode == 2) {
                    $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/onepage/success"
                    );
                   
                    $this->messageManager->addSuccess(
                        __("Your payment was successful")
                    );
                } elseif ($statusCode == 3) {
                    
                    
                    if ($orderStatus == "complete") {
                       
                        $returnUrl = $this->getCheckoutHelper()->getUrl(
                            "checkout/onepage/success"
                        );
                     
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
                            "checkout/cart"
                        );
                       
                    }
                } elseif ($statusCode == 4) {
                    if ($orderStatus == "complete") {
                        $returnUrl = $this->getCheckoutHelper()->getUrl(
                            "checkout/onepage/success"
                        );
                       
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
                            "checkout/cart"
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
                        "checkout/cart"
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
                $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/cart"
                    );
            } catch (\Exception $e) {
                $this->_checkoutSession->restoreQuote();
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t place the order paytiko.')
                );
                $returnUrl = $this->getCheckoutHelper()->getUrl(
                        "checkout/cart"
                    );
            }
        }
        $this->getResponse()->setRedirect($returnUrl);
    }
}
