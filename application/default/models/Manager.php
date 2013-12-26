<?php
class Model_Manager
{
    
    protected static $_models;
    
    /**
     * Enter description here...
     *
     * @param string $name
     * @return Moodspin_Db_Trigger_Table
     */
    public static function getModel($name){
        if(self::$_models == null){
            self::$_models = array();
        }
        
        if(!isset(self::$_models[$name])){
            $class = 'Model_'.ucfirst($name);
            self::$_models[$name] = new $class;
        }
        
        return self::$_models[$name];
    }
    
}
?>