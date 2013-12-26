<?php
class Moodspin_Db_Table_Manager
{
    /**
     *
     * @var Zend_Db_Table_Abstract
     */
    protected $_table;
    public function getEmptyRow (array $data = array(), $defaultSource = null)
    {
        return $this->_table->createRow($data, $defaultSource);
    }
}