<?php

require_once 'Zend/Application/Resource/ResourceAbstract.php';

class Moodspin_Bootstrap_Resource_Cron extends Zend_Application_Resource_ResourceAbstract
{
     
    public $_explicitType = "logger";
    
    public function init()
    {
        foreach ($this->_options as $key => $value) {
        	
            switch ($key) {
            	case 'tasksPath':
            	    Moodspin_Cron_Controller::getInstance()->setTasksPath($value);
            	break;
            	
            	default:
            		;
            	break;
            }
            
            
        }
           
    }
    
}