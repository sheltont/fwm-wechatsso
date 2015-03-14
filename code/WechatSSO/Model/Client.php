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
class Fwm_WechatSSO_Model_Client
{
	const WECHAT_SNS_OAUTH2 = 'https://api.weixin.qq.com/sns/oauth2';
    const WECHAT_SNS_OAUTH2_ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const WECHAT_SNS_USERINFO = 'https://api.weixin.qq.com/sns/userinfo';

	protected $_appId;
	protected $_secret;
	protected $_code;
    protected $_session;
	
	protected $_accessToken;
    protected $_openId;
    protected $_unionid;
    protected $_userInfo;

	protected static $_httpClient; 

	public function __construct()
	{
		$args = func_get_args();
		
		if(isset($args[0]) && is_array($args[0])) {
			$args = $args[0];
		}
		
		if(count($args) < 3) {
			trigger_error('Missing arguments for Fwm_Wechatsso_Model_Client::__construct()', E_USER_ERROR);
		}
		
		$this->_appId      = $args[0];
		$this->_secret      = $args[1];
        $this->_code        = $args[2];

		$session = isset($args[3]) ? $args[3] : null;

		if(is_array($session)) {
			$this->_session  = new Varien_Object($session);
		} elseif($session instanceof Varien_Object) {
			$this->_session = $session;
		} else {
			$this->_session  = new Varien_Object();
		}

        $this->_getAccessTokenAndOpenId();
	}

    public function getUnionid()
    {
        if (!empty($this->_unionid)) {
            return $this->_unionid;
        }
        $this->getUserInfo();
        return $this->_unionid;
    }

    public function getUserInfo()
    {
        if (!empty($this->_userInfo)) {
            return $this->_userInfo;
        }

		$url = self::WECHAT_SNS_USERINFO;
        $params = array(
            'access_token' => $this->_getAccessToken(),
            'openid' => $this->_getOpenId(),
            'lang' => 'zh_CN'
        );
	    $result = $this->_oauthRequest($url, $params);
	    
		if(is_array($result) && isset($result['errcode'])) {
			throw new Mage_Core_Exception($result['errmsg'], $result['error_code']);
		}

        $this->_userInfo = $result;
        $this->_unionid = $result['unionid'];
        return $this->_userInfo;
	}
	

    protected function _getOpenId()
    {
        if ($this->_openId) {
            return $this->_openId;
        }
        $this->_getAccessTokenAndOpenId();
        return $this->_openId;

    }

	protected function _getAccessToken()
    {
        if ($this->_accessToken) {
            return $this->_accessToken;
        }
        $this->_getAccessTokenAndOpenId();
        return $this->_accessToken;
    }

    protected function _getAccessTokenAndOpenId()
    {
        try {
            $client = self::_getHttpClient()
                ->setUri(self::WECHAT_SNS_OAUTH2_ACCESS_TOKEN)
                ->setMethod(Zend_Http_Client::GET)
                ->resetParameters()
                ->setParameterGet($this->_prepareParams(array(
                    'appid'		=>	$this->_appId,
                    'secret'	=>	$this->_secret,
                    'code'	=> $this->_code,
                    'grant_type' =>	'authorization_code',
                )));
            $responseParams = Zend_Json::decode($client->request()->getBody());
            if (isset($responseParams['access_token'])) {
                $this->_accessToken = $responseParams['access_token'];
            } else{
                $res = 1;
                $res = $res +1;
            }
            if (isset($responseParams['openid'])) {
                $this->_openId = $responseParams['openid'];
            }
            if (isset($responseParams['unionid'])) {
                $this->_unionid = $responseParams['unionid'];
            }
        } catch(Exception $e) {}

    }

	
	protected function _prepareParams($params)
	{
	    foreach ($params as $key => &$val) {
      		if (!is_array($val)) continue;
        	$val = Zend_Json::encode($val);
    	}
    	
		return $params;
	}
	
	protected function _oauthRequest($url, $params)
	{
		
		if (!isset($params['access_token'])) {
			$params['access_token'] = $this->_getAccessToken();
		}
        if (!isset($params['openid'])) {
            $params['openid'] = $this->_getOpenId();
        }
				
		$params = $this->_prepareParams($params);
		
		$client = self::_getHttpClient()
				->setUri($url)
				->setMethod(Zend_Http_Client::GET)
				->resetParameters()
                ->setParameterGet($params);

		try {
			$response = $client->request();
		} catch(Exception $e) {
			throw new Mage_Core_Exception('Service temporarily unavailable.');
		}
		
		$result = Zend_Json::decode($response->getBody());
		
		return $result;			
	}
	
	private static function _getHttpClient()
    {
        if (!self::$_httpClient instanceof Varien_Http_Client) {
            self::$_httpClient = new Varien_Http_Client();
        }

        return self::$_httpClient;
    }

}