<?php


/**
 * Class Fwm_WechatSSO_Helper_Data
 */
class Fwm_WechatSSO_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getConnectUrl()
    {
        return $this->_getUrl('fwm_wechatsso/customer_account/connect', array('_secure'=>true));
    }

    public function isWechatCustomer($customer)
    {
        if($customer->getWechatUid()) {
            return true;
        }
        return false;
    }
}