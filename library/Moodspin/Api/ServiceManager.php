<?php
class Moodspin_Api_ServiceManager
{
    const SERVICE_NAMESPACE = "Moodspin_Api_Service";
    
    protected static $_instance;
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();   
        }
        
        return self::$_instance;
    }
    
    
    public function getService($serviceName){
        $serviceClass = self::SERVICE_NAMESPACE . '_'.ucfirst($serviceName);

        if(class_exists($serviceClass)){
            $service = new $serviceClass();
        }
        
        return $service;
    }
}
?>