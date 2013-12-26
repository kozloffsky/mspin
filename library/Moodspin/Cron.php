<?php

require_once 'Zend/Application.php';

class Moodspin_Cron extends Zend_Application {
	
    protected $_controller;
    
    public function __construct($env, $options = null) {
		parent::__construct($env, $options);
	    
    }
	
	/**
     * Get bootstrap object
     * 
     * @return Zend_Application_Bootstrap_BootstrapAbstract
     */
    public function getBootstrap()
    {
        if (null === $this->_bootstrap) {
            $this->_bootstrap = new Moodspin_Bootstrap($this);
        }
        return $this->_bootstrap;
    }
        
    public function run()
    {
        $startTime = time();
        //try{
            $this->getController()->dispatch();
        //}catch (Exception $e){
          //  $this->getController()->getLog()->err('Exception with message '.$e->getMessage().' was thrown');
        //}
        $endTime = time();
        $this->getController()->getLog()->info('Task was finished! Execution time: '. ($endTime-$startTime) . "sec");
    }
    
    public function getController()
    {
        if ($this->_controller == NULL){
            $this->_controller = Moodspin_Cron_Controller::getInstance();
        }
        
        return $this->_controller;
    }
    
   
	
}