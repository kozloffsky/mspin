<?php

class Moodspin_User_Manager_Twitter_Api extends Moodspin_User_Manager_Twitter {

    /**
     * Instance of Moodspin_User_Manager_Twitter
     *
     * @var Moodspin_User_Manager_Twitter
     */
    protected static $_instance;

    /**
     * Get manager instance
     *
     * @return Moodspin_User_Manager_Twitter
     */
    public static function getInstance()
    {
        if(self::$_instance == null){
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Get twitter service
     *
     * @return Moodspin_Service_Twitter_Api
     */
    public function getServiceInstance() {
        if($this->_serviceInstance === null) {
            $this->_serviceInstance = Moodspin_Service::getInstance()->getService('Twitter_Api');
        }

        return $this->_serviceInstance;
    }

    public function setUsername($username)
    {
        $this->getServiceInstance()->setUsername($username);
    }

    public function setPassword($password)
    {
        $this->getServiceInstance()->setPassword($password);
    }
}