<?php

class Model_UsersSettings extends Moodspin_Db_Trigger_Table
{
    protected $_name = 'users_settings';

    protected $_referenceMap = array(
        'User' => array(
            'columns'        => array('user_id'),
            'refTableClass'  => 'Model_Users',
            'refColumns'     => array('id'), 
        )
    );
    
    //TODO setup triggers
    /*protected $_triggers = array(
        'insert' => array(
            array('class'=>'Model_Trigger_UserReports','callback'=>'usersTwitterUpdate')
        ),
        'update' => array(
            array('class'=>'Model_Trigger_UserReports','callback'=>'usersTwitterUpdate')
        ),
    );*/
    
    /**
     * @param int $userId
     * @return Zend_Db_Table_Rowset
     */
    public function getUserSettings($userId)
    {
        return $this->fetchAll(
            $this->select()->from($this->_name)->where("user_id = ?", $userId)
        );
    }
    
    /**
     * @param int $userId
     * @param string $settingName
     * @param string $settingValue
     */
    public function setUserSettings($userId,$settingName,$settingValue,$serialize=false)
    {
        $rowset = $this->fetchRow(
            $this->select()->from($this->_name)->where(
                "user_id = " . $userId . " AND name = '" . $settingName . "'")
        );
        
        if (!$rowset || count($rowset->toArray()) < 1) {
            $row = $this->createRow(
                array(
                    'user_id' => $userId,
                    'name' => $settingName,
                    'value' => $serialize ?  serialize($settingValue) : $settingValue
                )
            );
            $row->save();
        } else {
            $rowset->value = $serialize ?  serialize($settingValue) : $settingValue;
            $rowset->save();
        }
    }
    /**
     * check if user has lastSendDate
     *
     * @param int $id
     * @return boolean
     */
    public function isUserHasLastSentDate($id)
    {
        $query = $this->select()->from($this->_name)
                ->where(
                    "user_id = ? AND name='lastSendDate'", $id
                )
                ->limit(1);
        $row = $this->fetchAll($query)->toArray();
        if ($row) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * update lastSendDate to user with userId
     * 
     * @param int $userId
     */
    public function updateLastSentDate($userId)
    {
        $where = "user_id = " . (int)$userId . " AND name = 'lastSendDate'";
        $data = array('value' => new Zend_Db_Expr('NOW()'));
        $this->update($data,$where);
    }
}
