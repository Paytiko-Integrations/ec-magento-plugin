<?php

namespace Paytiko\PaytikoPayments\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Block class of Admin health check field
 *
 * @author Paytiko Co - Plugin Team
 * @link   https://paytiko.co
 **/
class HealthCheck extends Field
{
    const HEALTH_CHECK_CACHE_ID = 'paytiko_payment_health_check';
    /**
     * @var string
     */
    protected $_template = 'Paytiko_PaytikoPayments::system/config/check_credential_button.phtml';

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    public function getStatusLabel($statusLevel = null)
    {
        $statusList = [
            \Paytiko\PaytikoPayments\Model\Config\HealthCheck::STATUS_SUCCESS => __('Success'),
            \Paytiko\PaytikoPayments\Model\Config\HealthCheck::STATUS_WARNING => __('Warning'),
            \Paytiko\PaytikoPayments\Model\Config\HealthCheck::STATUS_ERROR => __('Error')
        ];

        return ($statusLevel !== null && isset($statusList[$statusLevel])) ? $statusList[$statusLevel] : null;
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxHealthCheckUrl()
    {
        return $this->getUrl('paytikopayments/healthcheck', ['_current' => true]);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
