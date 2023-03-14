<?php
namespace Paytiko\PaytikoPayments\Controller\Index;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;


class Index extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Paytiko\PaytikoPayments\Helper\Paytiko $helperData)
	{
		$this->_pageFactory = $pageFactory;
		$this->helperData = $helperData;
		return parent::__construct($context);
	}

	public function execute()
	{
		
		// Before checkout request start
		
		$activationkey = $this->getRequest()->getPost('activationkey');
		$privatekey = $this->getRequest()->getPost('privatekey');
		$api_url = $this->getRequest()->getPost('api_url'); 
		
	
        $response = $this->helperData->APIReqcheckconfig($api_url,"/api/cashier/ecommerce/config/$activationkey","GET","",$privatekey);
	   
	    if($response  ==  'Wrong API key.') {
	        $response = array('status' => '300', 'message' => $response);
                 echo json_encode($response); die();
	    }
	    
		if(isset($response["cashierBaseUrl"])){
			$cashierBaseUrl = $response["cashierBaseUrl"];
	        $coreBaseUrl = $response["coreBaseUrl"];
	        $embedScriptUrl = $response["embedScriptUrl"];
	        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		    $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		    $connection = $resource->getConnection();
		    $tableName = $resource->getTableName('core_config_data'); // the table name in this example is 'mytest'
		    $sql = "UPDATE " . $tableName . " SET value = '$cashierBaseUrl'  WHERE path = 'payment/paytiko/cashierBaseUrl'";
		    $connection->query($sql);

		    $sql = "UPDATE " . $tableName . " SET value = '$coreBaseUrl'  WHERE path = 'payment/paytiko/coreBaseUrl'";
		    $connection->query($sql);

		    $sql = "UPDATE " . $tableName . " SET value = '$embedScriptUrl'  WHERE path = 'payment/paytiko/embedScriptUrl'";
		    $connection->query($sql);

                $message = array('status' => '200', 'message' => 'Details are valid !');
                
                $message1= $response;
                $response = array_merge($message,$message1);
                 echo json_encode($response); die();
        


        } else {
             $message = array('status' => '400', 'message' => 'Something went wrong !');
             $message1= $response;
                $response = array_merge($message,$message1);
		         echo json_encode($response); die();
        }

	}
}
