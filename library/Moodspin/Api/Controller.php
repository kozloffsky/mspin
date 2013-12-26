<?php
require_once ('Moodspin/Controller/Action/Json.php');
class Moodspin_Api_Controller extends Moodspin_Controller_Action_Json
{
    
    public function getServiceManager(){
        return Moodspin_Api_ServiceManager::getInstance();
    }
    
    public function getService($name){
        return $this->getServiceManager()->getService($name); 
    }
    
        
    
}