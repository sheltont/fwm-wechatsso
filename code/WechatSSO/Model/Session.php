<?php
/**
 * Fwm Wechat SSO OAuth session model
 *
 * @category    Fwm
 * @package     Fwm_WechatSSO
 * @author      Shelton Tang<shelton.ms@gmail.com>
 * @copyright   Binqsoft (http://www.binqsoft.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Fwm_WechatSSO_Model_Session extends Varien_Object
{
	private $_client;

	public function __construct()
	{
        $data =  Mage::app()->getRequest()->getParams();

        if (!isset($data['code']) || !isset($data['state'])) {
            Mage::log("The callback url is invalid since no code or state", null, "wechatsso.log");
            return;
        }
        $this->setData($data);
	}
	
	public function isConnected()
    {
		return $this->validate();
    }

    public function validate()
    {
    	if(!$this->hasData()) {
    		return false;
    	}
    	return true;
    }

	     
	public function getClient()
	{
		if(is_null($this->_client)) {
			$this->_client = Mage::getModel('fwm_wechatsso/client',array(
									Mage::getSingleton('fwm_wechatsso/config')->getAppId(),
									Mage::getSingleton('fwm_wechatsso/config')->getSecret(),
                                    $this->getData('code'),
									$this
							));
		}
		return $this->_client;
	}
}