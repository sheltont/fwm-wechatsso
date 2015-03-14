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
    const XML_PATH_ENABLED = 'customer/wechatsso/enabled';
    const XML_PATH_APP_ID = 'customer/wechatsso/app_id';
    const XML_PATH_SECRET = 'customer/wechatsso/secret';

    public function isEnabled($storeId=null)
    {
        if( Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $storeId) &&
            $this->getAppId($storeId) &&
            $this->getSecret($storeId))
        {
            return true;
        }

        return false;
    }
    public function getAppId($storeId=null)
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_APP_ID, $storeId));
    }

    public function getSecret($storeId=null)
    {
        return trim(Mage::getStoreConfig(self::XML_PATH_SECRET, $storeId));
    }

}
