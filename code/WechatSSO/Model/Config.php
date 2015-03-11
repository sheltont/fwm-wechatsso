<?php
/**
 * Fwm Wechat SSO OAuth configuration model
 *
 * @category    Fwm
 * @package     Fwm_WechatSSO
 * @author      Shelton Tang<shelton.ms@gmail.com>
 * @copyright   Binqsoft (http://www.binqsoft.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Fwm_WechatSSO_Model_Config extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        $this->_init('fwm_wechatsso/config');
        parent::_construct();
    }

    public function isEnabled($storeId = null)
    {
        return true;
    }

    public function isValidAppId($appId)
    {
        return true;
    }

    public function getAppId($storeId = null)
    {
        return "wxdfb9245f80ddf4a1";
    }

    public function getScope($storeId = null)
    {
        return "snsapi_login";
    }

    /**
	const XML_PATH_ENABLED = 'customer/wechatsso/enabled';
	const XML_PATH_API_ID = 'customer/wechatsso/api_id';

    public function isEnabled($storeId=null)
    {
		if( Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $storeId) && 
			$this->getApiKey($storeId) && 
			$this->getSecret($storeId))
		{
        	return true;
        }
        
        return false;
    }
	
    public function getApiId($storeId=null)
    {
    	return trim(Mage::getStoreConfig(self::XML_PATH_API_ID, $storeId));
    }
    
    public function getScope($storeId=null)
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_SCOPE, $storeId));
    }
    */
}
