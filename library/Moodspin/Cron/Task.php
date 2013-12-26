<?php

class Moodspin_Cron_Task
{
    
    protected $_logger;
    
    
    /**
     * Logger
     *
     * @return Zend_Log
     */
    public function getLogger()
    {
        return $this->_logger;
    }
    
    public function setLogger(Zend_Log $logger)
    {
        $this->_logger = $logger;
        return $this;
    }
    
}


?>