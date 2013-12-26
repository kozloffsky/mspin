<?php
class Moodspin_Cron_Controller {
	
    protected static $_instance;
    
    protected $_tasksPath;
    
    protected $_request;
    
    protected $_log;
    
    /**
     * Singleton
     *
     * @return Moodspin_Cron_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
        	self::$_instance = new self();
        }
        
        return self::$_instance;
        
    }
    
    protected function __construct() {

	}
	
	public function setTasksPath($value)
	{
	    $this->_tasksPath = $value;
	}
	
	public function getTasksPath()
	{
	    return $this->_tasksPath;
	}
	
     public function getRequiest()
    {
        if ($this->_request == null) {
        	$this->_request = new Moodspin_Cron_CliRequest();
        }
        
        return $this->_request;
    }
    
    /**
     * Logger
     *
     * @return Zend_Log
     */
    public function getLog(){
        if ($this->_log ==  null) {
        	$this->_log = new Zend_Log(new Zend_Log_Writer_Stream('php://output'));
        	//$this->_log->addWriter(new Zend_Log_Writer_Stream('cron.log'));
        }
        
        return $this->_log;
        
    }
    
    public function dispatch()
    {
        $taskName = $this->getRequiest()->getTaskName();
        $this->getLog()->info('Starting task '.$taskName);
        $task = $this->loadTaskClass($taskName);
        //$this->getLog()->info('Task is ' . var_export($task, true));
        
        if ($task instanceof Moodspin_Cron_Task ) {
        	$task->setLogger($this->getLog());
        }
        
        
        if ($task instanceof Moodspin_Cron_Task_Interface ) {
        	$task->run();
        }
        
    }
    
    public function loadTaskClass($taskName)
    {
        $taskClassName = ucfirst($taskName).'Task';

        $taskFileName = $taskClassName.'.php';
        
        $taskPath = realpath($this->getTasksPath()) .'/'.$taskFileName;

        $this->getLog()->info('task class path is in '.$taskPath);
        
        if(!include_once $taskPath){
            $this->getLog()->err('Task not found!');
            return;
        }
        
        if (!class_exists($taskClassName)) {
        	$this->getLog()->err('Class not found');
        	return;
        }
        
        return new $taskClassName();
        
    }
    
}