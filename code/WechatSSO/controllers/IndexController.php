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
class Fwm_WechatSSO_IndexController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('fwm_wechatsso/config')->isEnabled()) {
            $this->norouteAction();
        }

        return $this;
    }

    public function loginAction()
    {
        /**
         * https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&lang=zh_CN
         *
         * 首先，判断用户是否已经登录。如果已经登录，重定向到首页。
         * 其次，用户如果没有登录，重定向到微信认证页面。
         */
        if($this->_getCustomerSession()->isLoggedIn()) {
            $this->_redirect('/');
            return;
        }

        $data = $this->getRequest()->getParams();
        if (!isset($data['appid']))
        {
            Mage::log("Missing the parameter: appid");
            $this->_redirect('/');
            return;
        }

        $appid = $data['appid'];
        if (!Mage::getSingleton('fwm_wechatsso/config')->isValidAppId($appid))
        {
            Mage::log("Invalid appid " . $appid, null, "wechatsso.log");
            return;
        }

        $scope = Mage::getSingleton('fwm_wechatsso/config')->getScope($appid);
        $callback = Mage::getUrl('fwm/wechatsso/callback/', array('_secure' => true));
        $state = $appid;

        $url = $this->_getWechatOAuthUrl($appid, $scope, $callback, $state);

        Mage::log('Redirect the user to ' . $url, null, "wechatsso.log");

        $this->getResponse()->setRedirect($url);
    }

    public function callbackAction()
    {
        /**
         * https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&lang=zh_CN
         *
         * 用户在微信登录回到这个页面后，
         * 1. 通过access_token获取用户union_id;
         * 2. 查找该微信用户是否已经注册，如果没有注册，创建一个Customer，并且设置Customer的微信union_id
         * 3. 将当前Session设置为该用户，并重定向到首页；
         * 4. 会带两个参数 redirect_uri?code=CODE&state=STATE
         */

        Mage::log('The callback url is ' . $this->getRequest()->getRequestUri(), null, "wechatsso.log");

        $data = $this->getRequest()->getParams();

        if (!isset($data['code']) || !isset($date['state'])) {
            Mage::log("The callback url is invalid since no code or state", null, "wechatsso.log");
            return;
        }

        $code = $data['code'];
        $appid = $data['state'];
        $secret =  $scope = Mage::getSingleton('fwm_wechatsso/config')->getSecret($appid);
        $userInfo = $this->_getUserInfo($appid, $secret, $code);

        $this->loadLayout();
        $this->renderLayout();
    }





    private function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    private function _getSession()
    {
        return Mage::getSingleton('fwm_wechatsso/session');
    }

    private function _getWechatOAuthUrl($appid, $scope, $callbackurl, $state)
    {
        $client = Zend_Uri_Http::fromString("https://open.weixin.qq.com/connect/oauth2/authorize");
        $client->addReplaceQueryParameters(
            array("appid" => $appid,
                "redirect_uri" => $callbackurl,
                "response_type" => "code",
                "scope" => $scope,
                "sate" => $state ));
        $client->setFragment("wechat_redirect");
        return $client->getUri();
    }

    private  function _getWechatUserInfo($appId, $secret, $code)
    {
        try {
            $client1 = new Zend_Http_Client();
            $client1->setUri("https://api.weixin.qq.com/sns/oauth2/access_token");
            $client1->setConfig(array('maxredirects' => 0, 'timeout' => 10));
            $client1->setParameterGet(array("appid" => $appId, "secret" => $secret, "code" => $code, "grant_type" => "authorization_code"));
            $response1 = $client1->request();
            $res1 = $response1->getBody();
            $json_obj1 = json_decode($res1, true);

            //根据openid和access_token查询用户信息
            $access_token = $json_obj1['access_token'];
            $openid = $json_obj1['openid'];
            $client2 = new Zend_Http_Client();
            $client2->setUri("https://api.weixin.qq.com/sns/userinfo");
            $client2->setParameterGet(array("access_token" => $access_token, "openid" => $openid, "lang" => "zh_CN"));
            $response2 = $client2->request();
            $res2 = $response2->getBody();
            $user_obj = json_decode($res2, true);

            return $user_obj;
        } catch(Exception $e) {
            Mage::log($e, null, "wechatsso.log", true);
        }
        return null;
    }
}