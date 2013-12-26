<?php
class Moodspin_Cron_CliRequest{
    
    protected $_args;
    
    public function __construct(){
        
    }
    
    public function getArguments()
    {
        if ($this->_args == null) {
        	$this->_parseArguments();
        }
        
        return $this->_args;
    }
    
    public function getTaskName()
    {
        if ($this->_taskName == null) {
        	$this->_parseArguments();
        }
        
        return $this->_taskName;
        
    }
    
    protected function _parseArguments($args = null)
    {
        if ($this->_args == null){
             if ($args == null){
                 $this->_args = $_SERVER['argv'];       
             }else{
                 $this->_args = $args;
             }
        }
        
        $this->_taskName = array_shift($this->_args);
        
        if ($this->_taskName == 'cron.php') {
        	$this->_taskName = array_shift($this->_args);
        }
        
    }
    
    protected $_taskName;
    
}

?>