<?php

require_once 'Moodspin/Db/Trigger/Table.php';
require_once 'Moodspin/Db/Trigger/Row.php';

class Model_Users extends Moodspin_Db_Trigger_Table
{

    protected $_name = 'users';

    protected $_dependedTables = array(
        'Model_Statuses',
        'Model_UsersTwitter',
        'Model_UsersSettings'
    );
    
    protected $_triggers = array(
        'insert' => array(
            array('class'=>'Model_Trigger_RegistrationReports','callback'=>'userInsert')
        ),
        'update' => array(
            array('class'=>'Model_Trigger_RegistrationReports','callback'=>'userUpdate'),
            array('class'=>'Model_Trigger_UserReports','callback'=>'userUpdate')
        )
    );
    
    
    public function init()
    {
        parent::init();
    }
    
    public function createUser($name)
    {
    	$user = $this->createRow();
    	
    	$user->login = $name;
	    $user->password = md5("password");
	    $user->creation_date = new Zend_Db_Expr("NOW()"); 
	    $user->save();
	    
	    return $user;
    }
    
    public function createEmptyUser($name)
    {
        $user = $this->createRow();
    	
    	$user->login = $name;
	    $user->save();
	    
	    return $user;
    }
    
    public function changeLogin($name, $id)
    {
    	$user = $this->find($id)->current();
    	$user->login = $name;
    	$user->save();
    	
    	return $user;
    }
    
    public function isUserFollowingMoodspin($userId)
    {
        return (int)$this->find($userId)->current()->moodspin_following;
    }
    
    public function updateUserFollowingMoodspin($userId, $userFollowing)
    {
        $user = $this->find($userId)->current();
        $user->moodspin_following = $userFollowing;
        $user->save();
    }

    public function useIPhone($id, $iPhone) {
        $user = $this->find($id)->current();
        $user->using_iphone = $iPhone;
        $user->save();

        return $user;
    }

    /**
     * returns registered users count
     * 
     * @return int
     */
    public function getRegisteredUsersCount()
    {
        $query = $this->select()
                      ->from($this->_name, array('number' => new Zend_Db_Expr('count(*)')))
                      ->where('`creation_date` IS NOT NULL');
        return $this->fetchRow($query)->number;
    }
    
    /**
     * returns potential users count
     * 
     * @return int
     */
    public function getPotentialUsersCount()
    {
        $query = $this->select()
                      ->from($this->_name, array('number' => new Zend_Db_Expr('count(*)')))
                      ->where('`creation_date` IS NULL');
        return $this->fetchRow($query)->number;
    }
    
    /**
     * returns potential users count
     * that had been invited by message
     * 
     * @return int
     */
    public function getInvitedUsersCount()
    {
        $query = $this->select()
                      ->from($this->_name, array('number' => new Zend_Db_Expr('count(*)')))
                      ->where('`contacted_date` IS NOT NULL');
        return $this->fetchRow($query)->number;
    }
    
    /**
     * updates date when user was contacted by system
     * 
     * @param int $userId
     * @return int
     */
    public function updateContactedDate($userId)
    {
        $modelUserTwitter = new Model_UsersTwitter();
        $id = $modelUserTwitter->getUserIdByTwiterId($userId);
        return $this->update(
            array('contacted_date' => new Zend_Db_Expr('NOW()')),
            'id=' . (int)$id
        );
    }
        
    /**
     * updates followByMoodspin field for user
     * 
     * @param bool $follow
     * @param int $userId
     * @return int
     */
    public function updateFollowByMoodspin($follow,$userId)
    {
        $modelUserTwitter = new Model_UsersTwitter();
        $id = $modelUserTwitter->getUserIdByTwiterId($userId);
        return $this->update(
            array('moodspin_following' => $follow ? 1 : 0),
            'id=' . (int)$id
        );
    }
    
    /**
     * gets users registered within date range
     * 
     * @param string $fromDate
     * @param string $toDate
     * @return Zend_Db_Table_Rowset
     */
    public function getUsersRegistrationReport($fromDate,$toDate)
    {
        $query = $this->getAdapter()->select()
                      ->from('report_registrations')
                      ->where('date >= ?',$fromDate)
                      ->where('date < ?',$toDate);
        return $this->getAdapter()->fetchAll($query);
    }

    /**
     * returns modspin users report
     * 
     * @param $registered - whether to get registered users
     * @param $fromDate   - from date tweeted
     * @param $toDate     - to date tweeted
     * @param $page       - current page
     * @param $perPage    - count per page
     * @param $moods      - array of moods ids to search for
     * @param $minFollowers - min followers number
     * @param $maxFollowers - max followers number
     * 
     * @return Zend_Paginator
     */
    public function getUsersReport(
        $registered,$fromDate,$toDate,$page=0,$perPage=10,
        // for potential users
        $moods=null,$minFollowers=null,$maxFollowers=null,
        // sorting
        $sort='login',$sortOrder='ASC')
    {
        if ($registered) {
            $query = $this->getAdapter()->select()
                          ->from('report_users');
            $query->where('creation_date IS NOT NULL');
            $query->where('creation_date > ?', $fromDate);
            $query->where('creation_date <= ?', $toDate);
        } else {
            $query = $this->getAdapter()->select()
                          ->from('report_users');
            $query->where('creation_date IS NULL');
            $query->where('latest_status_date >= ?', $fromDate);
            $query->where('latest_status_date < ?', $toDate);
            if ($minFollowers)
                $query->where('followers_num >= ?',(int)$minFollowers);
            if ($maxFollowers)
                $query->where('followers_num < ?',(int)$maxFollowers);

	        if ($moods) {
	            $query->where('latest_mood_id IN (' . implode(',',$moods) . ')');
	        } elseif (!$moods && !$registered) {
	            $query->where('id = 0');
	        }
        }
        
        if ($sort && $sortOrder) {
            $query->order($sort . ' ' . $sortOrder);
        }
        
        $oPaginator = Zend_Paginator::factory( $query );
        $oPaginator->setCurrentPageNumber( $page );
        $oPaginator->setItemCountPerPage( $perPage );
        
        return $oPaginator;
    }
    
}
