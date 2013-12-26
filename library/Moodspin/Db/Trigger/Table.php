<?php
require_once ('Zend/Db/Table.php');

class Moodspin_Db_Trigger_Table extends Zend_Db_Table
{

    protected $_triggers;
    
    public function init()
    {
        $this->setRowClass('Moodspin_Db_Trigger_Row');
    }
    
    public function trigger ($event, $data)
    {
        if (isset($this->_triggers[$event]) && is_array($this->_triggers[$event])){
            
            foreach ($this->_triggers[$event] as $trigger) {
               if (isset($trigger['class']) && isset($trigger['callback'])) {
                   $class = $trigger['class'];
                   $instance = Moodspin_Db_Table_Trigger::getTrigger($class);
                   $instance->execute($trigger['callback'], $data); 
               }
            }
        }
    }
    
    public function insert(array $data) {
        $this->trigger('rowInsert', $data);
        $result = parent::insert($data);
        $this->trigger('rowPostInsert', $data);
        
        return $result;
    }
    
    public function update(array $data, $where) {
        $this->trigger('rowUpdate', array('data'=>$data,'where'=>$where));
        $result = parent::update($data, $where);
        $this->trigger('rowPostUpdate', array('data'=>$data,'where'=>$where));
        
        return $result;
    }
    
    public function delete($where) {
        $this->trigger('rowDelete', $where);
        $result = parent::delete($where);
        $this->trigger('rowPostDelete', $where);
        
        return $result;
    }
    
    public function addTrigger($event, $trigger) {
        if ($this->_triggers == null) {
            $this->_triggers = array();
        }
        if (!isset($this->_triggers[$event])) {
            $this->_triggersp[$event] = array();
        }
        $this->_triggers[$event][] = $trigger;
    }
    
}