<?php
class Moodspin_Log
{
    protected $_log;
    
    protected function __construct ($path,$writerType='file')
    {
        switch ($writerType) {
            case 'fb':
	            $writer = new Zend_Log_Writer_Firebug();
	            break;
            case 'file':
                $writer = new Zend_Log_Writer_Stream($path);
                break;
            default:
                $writer = new Zend_Log_Writer_Stream($path);
                break;
        }
        $this->_log = new Zend_Log($writer);
    }
    
    /**
     * Enter description here...
     *
     * @var Moodspin_Log
     */
    protected static $_instance;
    protected static $_path;
    protected static $_active = false;
    
    public static function log ($message,$writerType='file',$type=Zend_Log::INFO)
    {
        if (self::$_active == false)
            return;
        if (self::$_instance == null) {
            self::$_instance = new self(self::$_path,$writerType);
        }
        switch ($type) {
            case Zend_Log::INFO :
                self::$_instance->info($message);
                break;
            default:
                self::$_instance->error($message,$type);
                break;
        }
    }
    
    public function info ($message)
    {
        $this->_log->info($message);
    }
    
    public function error ($message,$type)
    {
        $this->_log->log($message,$type);
    }
    
    public static function setPath ($path)
    {
        self::$_path = $path;
    }
    
    public static function setActive ($active)
    {
        self::$_active = $active;
    }
}
?>