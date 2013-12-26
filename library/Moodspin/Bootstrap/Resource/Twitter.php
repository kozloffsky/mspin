<?php

require_once 'Zend/Application/Resource/ResourceAbstract.php';

class Moodspin_Bootstrap_Resource_Twitter extends Zend_Application_Resource_ResourceAbstract
{
    public $_explicitType = "twitter";
    
    public function init ()
    {
        $service = Moodspin_Service::getInstance()->getService('Twitter');
        $service_api = Moodspin_Service::getInstance()->getService('Twitter_Api');

        foreach ($this->_options as $key => $value) {
            switch ($key) {
                case 'customerKey':
                    $service->setCustomerKey($value);
                    $service_api->setCustomerKey($value);
                    break;
                case 'customerSecret':
                    $service->setCustomerSecret($value);
                    $service_api->setCustomerSecret($value);
                    break;
                case 'systemFollowerKey':
                    $service->setSystemFollowerKey($value);
                    $service_api->setSystemFollowerKey($value);
                    break;
                case 'systemFollowerSecret':
                    $service->setSystemFollowerSecret($value);
                    $service_api->setSystemFollowerSecret($value);
                    break;
                case 'systemFollower':
                    $service->setSystemFollowerUsername($value['username']);
                    $service->setSystemFollowerPassword($value['password']);
                    $service_api->setSystemFollowerUsername($value['username']);
                    $service_api->setSystemFollowerPassword($value['password']);
                    break;
                case 'systemJoin':
                    $service->setSystemJoinUsername($value['username']);
                    $service->setSystemJoinPassword($value['password']);
                    $service_api->setSystemJoinUsername($value['username']);
                    $service_api->setSystemJoinPassword($value['password']);
                    break;
            }
        }

        $service_api->init();
        $service->init();
    }
}