<?php

class Model_UsersTwitter extends Moodspin_Db_Trigger_Table 
{

    protected $_name = 'users_twitter';

    protected $_referenceMap = array(
        'User' => array(
            'columns'        => array('user_id'),
            'refTableClass'  => 'Model_Users',
            'refColumns'     => array('id'), 
        )
    );
    
    protected $_triggers = array(
        'insert' => array(
            array('class'=>'Model_Trigger_UserReports','callback'=>'usersTwitterUpdate')
        ),
        'update' => array(
            array('class'=>'Model_Trigger_UserReports','callback'=>'usersTwitterUpdate')
        ),
    );
    
    /**
     * Checks, if user with $twitterId is in database, then returns object of this user
     * else returns empty user
     *
     * @param int $twitterId
     * @return Zend_Db_Table_Row
     */
    public function isUserIdRegistered($twitterId)
    {
    	$rowset = $this->find($twitterId);
    	
    	if($rowset->count() < 1){
    		return $this->createRow(array('user_id'=> 0 ));
    	}
    	
    	return $rowset->current();
    }
    
    public function getTwitterIdByUserId($userId)
    {
        $row = $this->fetchRow('user_id = ' . $userId);
        if ($row) {
            return (int)$row->twitter_id;
        }
        return NULL;
    }
    
    public function getTwitterIdByTwiterName($name)
    {
        $row = $this->fetchRow('name = ' . $this->getAdapter()->quote($name));
        if ($row) {
            return (int)$row->twitter_id;
        }
        return NULL;
    }
    
    public function getUserIdByTwiterName($name)
    {
        $row = $this->fetchRow('name = ' . $this->getAdapter()->quote($name));
        if ($row) {
            return (int)$row->user_id;
        }
        return NULL;
    }
    
    public function getUserIdByTwiterId($id)
    {
        $row = $this->find((int)$id)->current();
        if ($row) {
            return $this->find((int)$id)->current()->user_id;
        }
        return NULL;
    }

    public function getMonthMoodiestsUsers($limit) {
        $query = $this->getAdapter()->select();
        $query->from(array('ru' => 'report_users'))
              ->where('creation_date IS NOT NULL')
              ->order('status_count DESC')
              ->limit($limit);
        $list = $this->getAdapter()->fetchAll($query);

        return $list;
    }

    public function getRecentMoodsUsers($limit) {
        $query = $this->getAdapter()->select();
        $query->from(array('ru' => 'report_users'))
              ->where('creation_date IS NOT NULL')
              ->order('latest_status_date DESC')
              ->limit($limit);

        $list = $this->getAdapter()->fetchAll($query);
        return $list;
    }
}
