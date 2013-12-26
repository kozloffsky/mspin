<?php
class Moodspin_Db_Table_Trigger
{
    
    protected static $_instances = array();
    
    public static function getTrigger($class)
    {
        if (isset(self::$_instances[$class])) {
        	return self::$_instances[$class];
        }
        
        return self::loadTrigger($class);
    }
    
    protected static function loadTrigger($class){
        
        if (! class_exists($class)) {
            throw new Zend_Db_Exception('Bad Trigger class ' . $class . '!');
        }
        
        $instance = new $class();
        self::$_instances[$class] = $instance;
        
        return $instance;
    }
    
    public function execute($callback, $data){
        if(method_exists($this, $callback)){
            return call_user_func_array(array($this, $callback), array($data));
        }
        
        throw new Zend_Db_Exception('Bad Trigger Callback' . $callback);
    }
    
}
?>