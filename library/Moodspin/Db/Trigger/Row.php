<?php
require_once ('Zend/Db/Table/Row.php');
class Moodspin_Db_Trigger_Row extends Zend_Db_Table_Row
{
    
    protected function _insert()
    {
        parent::_insert();
        $this->_trigger('insert');
    }
    
    protected function _postInsert()
    {
        parent::_postInsert();
        $this->_trigger('postInsert');
    }
    
    protected function _update()
    {
        parent::_update();
        $this->_trigger('update');     
    }
    
    protected function _postUpdate()
    {
        parent::_postUpdate();
        $this->_trigger('postUpdate');
    }

    protected function _delete()
    {
        parent::_delete();
        $this->_trigger('delete');
    }
    
    protected function _trigger($event)
    {
        $this->getTable()->trigger($event, $this);
    }
    
}
?>