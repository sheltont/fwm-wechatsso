<?php
/**
 * Facebook Customer account controller
 *
 * @category    Fwm
 * @package     Fwm_WechatSSO
 * @author      Shelton Tang <shelton.ms@gmail.com>
 * @copyright   Binqsoft (http://binqsoft.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Fwm_WechatSSO_Customer_AccountController extends Mage_Core_Controller_Front_Action
{
    const WECHAT_OAUTH_SCOPE = 'snsapi_userinfo';
    const WEB_WECHAT_OAUTH_SCOPE = 'snsapi_login';
    const WECHAT_OAUTH_URL = 'https://open.weixin.qq.com/connect/oauth2/authorize';
    const WEB_WECHAT_OAUTH_URL = 'https://open.weixin.qq.com/connect/qrconnect';
	
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('fwm_wechatsso/config')->isEnabled()) {
            $this->norouteAction();
        }
        
        return $this;
    }
    
    public function postDispatch()
    {
    	parent::postDispatch();
    	//Mage::app()->getCookie()->delete('fb-referer');
    	return $this;
    }

    public function loginAction()
    {
        $fromUrl = $this->getRequest()->{'from'};
        if (empty($fromUrl)) {
            $fromUrl = $_SERVER['HTTP_REFERER'];
            if (empty($fromUrl))
            {
                $fromUrl = Mage::getUrl('/');
            }
            $fromUrl = urlencode($fromUrl);
        }

        if($this->_getCustomerSession()->isLoggedIn()) {
            $this->_redirect($fromUrl);
            return;
        }

        $callback = Mage::getUrl('fwm_wechatsso/customer_account/callback/', array('_secure' => true));
        $url = $this->_getWechatOAuthUrl($callback, $fromUrl);
        Mage::log('Redirect to ' . $url, null, "wechatsso.log");
        $this->getResponse()->setRedirect($url);
    }

	public function callbackAction()
    {
    	if(!$this->_getSession()->validate()) {
    		$this->_getCustomerSession()->addError($this->__('Wechat SSO failed.'));
    		$this->_redirect('customer/account');
    		return;
    	}

        $fromUrl = urldecode($this->_getSession()->getState());
        if($this->_getCustomerSession()->isLoggedIn()) {
            $this->_redirect($fromUrl);
            return;
        }

    	$customer = Mage::getModel('customer/customer');
    	$unionId = $this->_getSession()->getClient()->getUnionId();
    	$collection = $customer->getCollection()
    	 			->addAttributeToFilter('wechat_uid', $unionId)
    				->setPageSize(1);
    				
    	if($customer->getSharingConfig()->isWebsiteScope()) {
            $collection->addAttributeToFilter('website_id', Mage::app()->getWebsite()->getId());
        }
        
        if($this->_getCustomerSession()->isLoggedIn()) {
        	$collection->addFieldToFilter('entity_id', array('neq' => $this->_getCustomerSession()->getCustomerId()));
        }
        
        $uidExist = (bool)$collection->count();
        
        if($this->_getCustomerSession()->isLoggedIn() && $uidExist) {
        	$existingCustomer = $collection->getFirstItem();
			$existingCustomer->setWechatUid('');
        	$existingCustomer->getResource()->saveAttribute($existingCustomer, 'wechat_uid');
        }
        	
		if($this->_getCustomerSession()->isLoggedIn()) {
       		$currentCustomer = $this->_getCustomerSession()->getCustomer();
 			$currentCustomer->setWechatUid($unionId);
			$currentCustomer->getResource()->saveAttribute($currentCustomer, 'wechat_uid');        	
			
			$this->_getCustomerSession()->addSuccess(
				$this->__('Your Wechat account has been successfully connected. Now you can fast login using Wechat account anytime.')
			);

			$this->_redirect($fromUrl);
			return;
        }
        
        if($uidExist) {
        	$uidCustomer = $collection->getFirstItem();
			if($uidCustomer->getConfirmation()) {
				$uidCustomer->setConfirmation(null);
				Mage::getResourceModel('customer/customer')->saveAttribute($uidCustomer, 'confirmation');
			}
			$this->_getCustomerSession()->setCustomerAsLoggedIn($uidCustomer);
            $this->_redirect($fromUrl);
            return;
        }

        $userInfo = $this->_getSession()->getClient()->getUserInfo();
		if(!isset($userInfo['email'])) {
            $userInfo['email'] = $userInfo['unionid'] . '@weixin.qq.com';
		}
        if (!isset($userInfo['first_name'])) {
            $userInfo['first_name'] = $userInfo['nickname'];
        }
        if (!isset($userInfo['last_name'])) {
            $userInfo['last_name'] = 'Wechat';
        }
        if (!isset($userInfo['gender'])) {
            $userInfo['gender'] = $userInfo['sex'] == '1' ? 'male' : 'female';
        }
		
		$customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($userInfo['email']);
		
		if($customer->getId()) {
			$customer->setWechatUid($userInfo['unionid']);
			Mage::getResourceModel('customer/customer')->saveAttribute($customer, 'wechat_uid');
			
			if($customer->getConfirmation()) {
				$customer->setConfirmation(null);
				Mage::getResourceModel('customer/customer')->saveAttribute($customer, 'confirmation');
			}
			
			$this->_getCustomerSession()->setCustomerAsLoggedIn($customer);
			$this->_getCustomerSession()->addSuccess(
				$this->__('Your Wechat account has been successfully connected. Now you can fast login using Wechat account anytime.')
			);
			$this->_redirect($fromUrl);
    		return;
		}
		
		//registration needed
		
		$randomPassword = $customer->generatePassword(8);
		
		$customer	->setId(null)
					->setSkipConfirmationIfEmail($userInfo['email'])
					->setFirstname($userInfo['first_name'])
					->setLastname($userInfo['last_name'])
					->setEmail($userInfo['email'])
					->setPassword($randomPassword)
					->setConfirmation($randomPassword)
					->setWechatUid($userInfo['unionid']);

		//FB: Show my sex in my profile
		if(isset($standardInfo['gender']) && $gender=Mage::getResourceSingleton('customer/customer')->getAttribute('gender')) {
			$genderOptions = $gender->getSource()->getAllOptions();
			foreach($genderOptions as $option) {
				if($option['label']==ucfirst($userInfo['gender'])) {
					 $customer->setGender($option['value']);
					 break;
				}
			}
		}

		
		//registration will fail if tax required, also if dob, gender aren't allowed in profile
		$errors = array();
		$validationCustomer = $customer->validate();
		if (is_array($validationCustomer)) {
				$errors = array_merge($validationCustomer, $errors);
		}
		$validationResult = count($errors) == 0;

		if (true === $validationResult) {
			$customer->save();
			
			$this->_getCustomerSession()->addSuccess(
				$this->__('Thank you for registering with %s', Mage::app()->getStore()->getFrontendName()) .
				'. ' . 
				$this->__('You will receive welcome email with registration info in a moment.')
			);
			
			$customer->sendNewAccountEmail();
			
			$this->_getCustomerSession()->setCustomerAsLoggedIn($customer);
			$this->_redirect($fromUrl);
			return;
		
		//else set form data and redirect to registration
		} else {
 			$this->_getCustomerSession()->setCustomerFormData($customer->getData());
 			$this->_getCustomerSession()->addError($this->__('Facebook profile can\'t provide all required info, please register and then connect with Facebook for fast login.'));
			if (is_array($errors)) {
				foreach ($errors as $errorMessage) {
					$this->_getCustomerSession()->addError($errorMessage);
				}
			}
			
			$this->_redirect('customer/account/create');
			
		}

    }

	
	private function _getCustomerSession()
	{
		return Mage::getSingleton('customer/session');
	}
    
	private function _getSession()
	{
		return Mage::getSingleton('fwm_wechatsso/session');
	}

    private function _getWechatOAuthUrl($callbackurl, $state)
    {
        $appId = Mage::getSingleton('fwm_wechatsso/config')->getAppId();
        $oauthUrl = self::WEB_WECHAT_OAUTH_URL;
        $scope = self::WEB_WECHAT_OAUTH_SCOPE;
        if ($this->_isWechatClient())
        {
            $oauthUrl = self::WECHAT_OAUTH_URL;
            $scope = self::WECHAT_OAUTH_SCOPE;
        }

        $client = Zend_Uri_Http::fromString($oauthUrl);
        $client->addReplaceQueryParameters(
            array(
                "appid" => $appId,
                "redirect_uri" => $callbackurl,
                "response_type" => "code",
                "scope" => $scope,
                "state" => $state ));
        $client->setFragment("wechat_redirect");
        return $client->getUri();
    }

    private function _isWechatClient()
    {
        $userAgent = $this->getRequest()->getHeader('User-Agent');
        $res = stripos($userAgent, 'MicroMessenger');
        return ($res != false);
    }
}
