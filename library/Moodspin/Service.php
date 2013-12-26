<?php
require_once ('Moodspin/Event/Dispatcher.php');
require_once ('Moodspin/Service/Interface.php');

class Moodspin_Service extends Moodspin_Event_Dispatcher implements Moodspin_Service_Interface
{
    protected static $_instance;
    
    protected $_pluginLoader;
    protected $_services;
    
    /**
     * Enter description here...
     *
     * @return Moodspin_Service
     */
    public static function getInstance ()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    protected function __construct ()
    {
        $this->_services = array();
        $this->_pluginLoader = new Zend_Loader_PluginLoader(array('Moodspin_Service' => 'Moodspin/Service'));
    }
    
    public function useService ($serviceName)
    {
        if (! $this->_pluginLoader->isLoaded($serviceName)) {
            $serviceClass = $this->_pluginLoader->load($serviceName);
            $service = new $serviceClass();

            if ($service instanceof Moodspin_Service_Interface) {
                $this->_services[$serviceName] = $service;
            } else {
                throw new Exception('Service Shoud implement Moodspin_Service_Interface');
            }
        }
    }
    
    public function getService ($serviceName)
    {
        $this->useService($serviceName);

        if (isset($this->_services[$serviceName])) {
            return $this->_services[$serviceName];
        }
        
        return null;
    }
    
    /**
     * Logout user
     *
     */
    public function logout ()
    {
        foreach ($this->_services as $service) {
            $service->logout();
        }
    }

    /**
     * Update status
     *
     * @param string $text Mood status message
     * @param string $image_path Path to mood status image
     * @return mixed|null
     */
    public function updateStatus ($identity, $text, $imagePath)
    {
        $responses = array();
        foreach ($this->_services as $service) {
            $responses[$service->getServiceName()] = $service->updateStatus($identity, $text, $imagePath);
        }
        return $responses;
    }

    /**
     * Return service name
     *
     * @return string
     */
    public function getServiceName() {
        return 'Service';
    }
}