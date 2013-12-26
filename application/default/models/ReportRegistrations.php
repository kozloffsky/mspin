<?php
/**
 * ReportRegistrations
 *  
 * @author Administrator
 * @version 
 */
require_once 'Zend/Db/Table/Abstract.php';

class Model_ReportRegistrations extends Zend_Db_Table_Abstract
{
    /**
     * The default table name 
     */
    protected $_name = 'report_registrations';
    /**
     * get registered users count
     * 
     * @return int
     */
    public function getRegisteredUsersCount()
    {
        $query = $this->select()->from($this->_name,array('usersTotal' => 'registered_total'))->order('date DESC')->limit(1);
        $count = $this->fetchRow($query);
        if ($count) {
        	return (int)$count->usersTotal;
        } else {
        	return 0;
        }
    }
}
