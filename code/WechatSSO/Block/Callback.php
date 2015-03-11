<?php
/**
 * Fwm Wechat SSO OAuth callback block
 *
 * @category    Fwm
 * @package     Fwm_WechatSSO
 * @author      Shelton Tang<shelton.ms@gmail.com>
 * @copyright   Binqsoft (http://www.binqsoft.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Fwm_WechatSSO_Block_Callback extends Inchoo_Facebook_Block_Template
{
    protected function _toHtml()
    {
        return '<script src="'.($this->isSecure() ? 'https://' : 'http://').'connect.facebook.net/'.$this->escapeUrl($this->getData('locale') ?  $this->getData('locale') : $this->getLocale()).'/all.js"></script>';
    }
}