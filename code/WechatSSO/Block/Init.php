<?php
/**
 * Wechat SSO template block
 * 
 * @category    Fwm
 * @package     Fwm_WechatSSO
 * @author      Shelton Tang <shelton.ms@gmail.com>
 * @copyright   Binqsoft (http://binqsoft.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Fwm_WechatSSO_Block_Init extends Mage_Core_Block_Template
{
	
	public function isSecure()
	{
		return Mage::app()->getStore()->isCurrentlySecure();
	}
	
	public function isEnabled()
	{
		return Mage::getSingleton('fwm_wechatsso/config')->isEnabled();
	}

    public function isWechatClient()
    {
        $userAgent = Mage::app()->getRequest()->getHeader('User-Agent');
        return (strpos($userAgent, 'MicroMessenger') != false);
    }

    public function isLoggedIn()
    {
        return $this->_getCustomerSession()->isLoggedIn();
    }

    public function getConnectUrl()
    {
        $url = $this->helper('fwm_wechatsso/data')->getConnectUrl();
        return $url;
    }

    private function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _toHtml()
    {
        if (!$this->isEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }
	
}