<?php

$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

$installer->addAttribute('customer', 'wechat_uid', array(
        'type'	 => 'varchar',
        'label'		=> 'WechatSSO Uid',
        'visible'   => false,
		'required'	=> false
));

$installer->addAttribute('customer', 'mobilephone', array(
    'type'	 => 'varchar',
    'label'		=> 'Mobile Phone',
    'visible'   => true,
    'required'	=> false
));

$installer->endSetup();
