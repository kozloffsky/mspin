<?php

class Moodspin_Service_Twitter_Auth_Adapter implements Zend_Auth_Adapter_Interface
{

    protected $_oauthToken;
    
    public function __construct($oauthToken)
    {
        $this->_oauthToken = $oauthToken;
    }
    
    public function authenticate()
    {
        $result = Moodspin_Service::getInstance()->getService('Twitter')->authenticate($this->_oauthToken);

        $data = $result->getData();
        
        require_once('Moodspin/User.php');
        
        $identity = Moodspin_User_Manager_Twitter::getInstance()->createUser(array(
            'id'             => $data->id, 
            'screenName'     => $data->screen_name, 
            'profileImage'   => $data->profile_image_url,
            'imageUrl'       => $data->profile_image_url,
            'followersCount' => $data->followers_count,
            'followingCount' => $data->friends_count,
            'bio'            => $data->description,
            'url'            => $data->url,
            'tokens'         => $result
        ));
        
        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity);
    }
}