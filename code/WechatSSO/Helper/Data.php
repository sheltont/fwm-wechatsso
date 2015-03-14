<?php


/**
 * Class Fwm_WechatSSO_Helper_Data
 */
class Fwm_WechatSSO_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getConnectUrl()
    {
        return $this->_getUrl('fwm_wechatsso/customer_account/login', array('_secure'=>true));
    }

    public function isWechatCustomer($customer)
    {
        if($customer->getWechatUid()) {
            return true;
        }
        return false;
    }

    public function isWechatClient()
    {
        $userAgent = $this->getRequest()->getHeader('User-Agent');
        return (strpos('MicroMessenger', $userAgent) != false);
    }
}