<?php

namespace Paytiko\PaytikoPayments\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;


class Index implements \Magento\Framework\App\ActionInterface
// class Index extends \Magento\Framework\App\Action\Action
{
    public $helperData;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    public function __construct(
        \Magento\Framework\App\Action\Context      $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Paytiko\PaytikoPayments\Helper\Paytiko    $helperData)
    {
        $this->_request = $context->getRequest();
        $this->_pageFactory = $pageFactory;
        $this->helperData = $helperData;
        return $context;
    }

    public function execute()
    {
        try {
            $response = $this->helperData->APIReqActivation(
                $this->getRequest()->getPost('environment'),
                $this->getRequest()->getPost('apiKey'),
                $this->getRequest()->getPost('activationKey')
            );
        } catch (\Exception $e) {
            print_r(json_encode(['status' => 'fail', 'message' => 'Activation failed. Check your input or contact support.']));
            return;
        }

        if (isset($response["cashierBaseUrl"])) {
            $cashierBaseUrl = $response["cashierBaseUrl"];
            $coreBaseUrl = $response["coreBaseUrl"];
            $embedScriptUrl = $response["embedScriptUrl"];
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('core_config_data');

            $sql = "UPDATE {$tableName} SET value='{$cashierBaseUrl}' WHERE path='payment/paytiko/cashierBaseUrl'";
            $connection->query($sql);

            $sql = "UPDATE {$tableName} SET value='{$coreBaseUrl}' WHERE path='payment/paytiko/coreBaseUrl'";
            $connection->query($sql);

            $sql = "UPDATE {$tableName} SET value='{$embedScriptUrl}' WHERE path='payment/paytiko/embedScriptUrl'";
            $connection->query($sql);
            print_r(json_encode(array_merge(['status' => 'ok', 'message' => 'Activation successful'], $response)));
            return;
        }

        print_r(json_encode(array_merge(['status' => 'fail', 'message' => 'Something went wrong. Check your input or contact support.'], $response)));
    }

    /**
     * Retrieve request object
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    public function getRequest()
    {
        return $this->_request;
    }
}
