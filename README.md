# fwm-wechatsso
WeChat SSO for Freshway Magento Customization

1. If a client has logined, does nothing.
2. If the client is unauthenticated, redirects to wechat oauth.
3. Once the client passes the wechar authentication, searches the customer with the wechat_uid
   3.1 if we can  find the customer, set the session to the customer.
   3.2 if the client has not been registered, creates the customer and set the session

4. wechat_uid is mapped to wechat's unionid
