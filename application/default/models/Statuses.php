<?php


class Model_Statuses extends Moodspin_Db_Trigger_Table 
{

    public static $LATEST_STATUSES_LIMIT = 2;
    
    protected $_name = 'statuses';

    protected $_referenceMap = array(
        'User' => array(
            'columns'        => array('user_id'),
            'refTableClass'  => 'Model_Users',
            'refColumns'     => array('id'), 
        ),

        'Mood' => array(
            'columns'        => array('mood_id'),
            'refTableClass'  => 'Model_Moods',
            'refColumns'     => array('id'), 
        ),
    );
    
    protected $_triggers = array(
        'insert' => array(
            array('class'=>'Model_Trigger_MoodReports','callback'=>'statusPostInsert')
        ),
        'postInsert' => array(
            array('class'=>'Model_Trigger_UserReports','callback'=>'statusPostInsert')
        )
    );

    protected static $_instance;

    /**
     * get singleton instance of the model
     *
     * @return Model_Statuses
     */
    public static function getInstance()
    {
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    public function getLatestStatuses($page = 1)
    {   
        return $this->fetchAll(
            $this->select()
                 ->order('date DESC')
                 ->limit(self::$LATEST_STATUSES_LIMIT, $this->_getOffset($page))
        );
    }
    
    public function getPagesNum()
    {
        $res = $this->getAdapter()->fetchAll('SELECT COUNT(*) as count FROM '.$this->_name);
        
        $count = $res[0]['count'];
        
        return (int)round((int)$count / self::$LATEST_STATUSES_LIMIT);
    }
    
    public function getStatusesForMood($moodId, $page=1)
    {
        return $this->fetchAll(
               $this->select()
                    ->where('mood_id=?',$moodId)
                    ->order('date DESC')
                    ->limit(self::$LATEST_STATUSES_LIMIT, $this->_getOffset($page))
        );
    }
    
    public function getPagesNumForMood($moodId)
    {
        $res = $this->getAdapter()->fetchAll('SELECT COUNT(*) as count FROM '.$this->_name . ' WHERE mood_id=?', $moodId);
        
        $count = $res[0]['count'];
        
        return (int)round((int)$count / self::$LATEST_STATUSES_LIMIT);
    }
    
    public function updateStatus($userId, $moodId, $message, $date=null)
    {
        $status = $this->fetchNew();
      
        $status->user_id = $userId;
        $status->mood_id = $moodId;
        $status->message = $message;
         
        if ($date) {
            $status->date = date('Y-m-d H:i:s', strtotime($date));
        } else {
            $status->date = date('Y-m-d H:i:s', time());
        }
        $status->save();
        return $status;
    }
    
    public function getCurrentStatus($userId)
    {
        return $this->fetchRow(array('user_id = ?' => $userId), 'date DESC');
    }
    
    protected function _getOffset($page)
    {
        return ($page - 1) * self::$LATEST_STATUSES_LIMIT;
    }
    
    public function findLastUserStatus($userId, $moodId)
    {
        $select = $this->select();
        $select->where('user_id=?',$userId)->where('mood_id=?',$moodId);
        return $this->fetchAll($select)->current();
    }
    /**
     * Check if tweet already exists in db
     * 
     * @param int $userId
     * @param string $tweetDate
     * @return boolean
     */
    public function isTweetExist($userId,$tweetDate)
    {
        try {
            $created = new Zend_Date(strtotime($tweetDate));
        } catch (Exception $e) {
            throw new Exception("Wrong date string!");
        }
        $query = $this->select()
                      ->from(array('st' => $this->_name),array('id'))
                      ->where("st.user_id = ?",$userId)
                      ->where('st.date = ?', $created->toString('Y-M-d H:m:s'));
        $row = $this->fetchRow($query);
        if ($row) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Find mood_id for user and date
     * if no such mood_id found - return empty_mood_id
     * 
     * @param array $mood
     * @return int
     */
    public function findIdForMood($mood) {
        $query = $this->select()->setIntegrityCheck(false)
                      ->from(array('s' => $this->_name), 's.mood_id')
                      ->joinInner(array('m' => 'moods'),'s.mood_id = m.id', 'm.mood_category_id')
                      ->where("s.user_id = ?", $mood['user_id'])
                      ->where("s.date < ?", $mood['date'])
                      ->order('s.date DESC')
                      ->limit(1);

        $row = $this->fetchRow($query);

        if ($row !== false) {
            if ($row['mood_category_id'] == 1) {
                return $row['mood_id'];
            }
        }
        return Model_Moods::EMPTY_MOOD_ID;
    }
    
}
