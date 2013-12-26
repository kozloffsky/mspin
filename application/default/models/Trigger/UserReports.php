<?php

require_once ('Moodspin/Db/Table/Trigger.php');

class Model_Trigger_UserReports extends Moodspin_Db_Table_Trigger
{
    const DATE_FORMAT = "Y-M-d H:m:s";
    
    protected $_statusCache;
    protected function _putToStatusCache($status)
    {
	    if ($this->_statusCache == null) {
	       $this->_statusCache = array();
	    }
	     
	    $this->_statusCache[$status->id] = $status;        
    }
    
    protected function _getFromStatusCache($statusId)
    {
        if (isset($this->_statusCache[$statusId])) {
            return $this->_statusCache[$statusId];
        }
        
        return null;
    }
    
    protected function userUpdate($data)
    {        
        $report = $this->_getReport($data->id);
        
        $report->login = $data->login;
        $report->creation_date = $data->creation_date;
        $report->contacted_date = $data->contacted_date;
        $report->using_iphone = $data->using_iphone;
        $report->using_badge = $data->using_badge;
        $report->moodspin_following = $data->moodspin_following;
        
        $report->save();
        
    }
    
    protected function statusPostInsert($data)
    {
    	if ($data->mood_id == Model_Moods::EMPTY_MOOD_ID) {
            return;
    	}
        $this->_putToStatusCache($data);
        
        $report = $this->_getReport($data->user_id);
        if ($report->latest_status_date &&
            ($this->_compareDates($data->date,$report->latest_status_date) <= 0)) {
        	return;
        }
        
        $report->status_count += 1;
        $report->latest_status_message = $data->message;
        $report->latest_status_date = $data->date;
        if ($data->mood_id) {
            $report->latest_mood_id = $data->mood_id;
        } else {
            $data->mood_id = Model_Moods::DEFAULT_MOOD_ID;
        }
        $report->save(); 
        
    }
    /**
     * compare two dates in string format
     * @param string $date1
     * @param string $date2
     * @return int (-1|0|1)
     */
    private function _compareDates($date1,$date2)
    {
        try {
	    	$date1Date = new Zend_Date();
	        $date2Date = new Zend_Date();
	        $date1Date->set($date1,self::DATE_FORMAT);
	        $date2Date->set($date2,self::DATE_FORMAT);
	        return $date1Date->compare($date2Date);
        } catch (Exception $e) {
            Moodspin_Log::Log(" Date compare exception! ");
            throw new Zend_Date_Exception("Date must be specified!");
        }
    }
    
    protected function usersTwitterUpdate($data)
    {
        if (!$data->name) {
            throw new Exception("Login is required!");
        }
        
        $report = $this->_getReport($data->user_id);
        $report->login = $data->name; 
        
        $report->followers_num = $data->followers_num;
        $report->following_num = $data->following_num;
        //TODO move this 2 into cron
        $report->followers_on_moodspin = $this->_findFollowers($data->twitter_id);
        $report->following_on_moodspin = $this->_findFollowing($data->twitter_id);
        
        $report->bio = $data->bio;
        $report->url = $data->url;
        $report->twitter_id = $data->twitter_id;
        
        if ((int)$report->followers_num == 0)
            $report->ratio = 0;
        else
            $report->ratio = $report->followers_on_moodspin / $report->followers_num;
        
        $report->save();
        
    }
    
    protected $_reports;
    
    protected function _getReport($userId)
    {
        if ($this->_reports == null){
            $this->_reports = array();
        }
        
        if (!isset($this->_reports[$userId])) {
	        $model = Model_Manager::getModel('ReportUsers');
	        $report = $model->fetchRow($model->select()->where("user_id=?",$userId));
	        
	        if ($report == null) {
	            $report = $model->createRow();
	            $report->user_id = $userId;
	        }
	        
	        $this->_reports[$userId] = $report;
        }
        return $this->_reports[$userId];
    }
    
    protected function _countUserStatuses($userId)
    {
        //TODO change to simple increment
        $model = Model_Manager::getModel('Statuses');
        
        $result = $model->getAdapter()->fetchRow("SELECT COUNT(*) c from statuses s WHERE s.user_id=?",$userId);
        
        return $result['c'];
    }
    
    protected function _findFollowers($twitterId)
    {
        // TODO move to cron
        $db = Zend_Db_Table::getDefaultAdapter();
        $res= $db->fetchRow("SELECT COUNT(*) c FROM users_twitter_followers WHERE twitter_id=?",$twitterId);
        return $res['c'];
    }
    
    protected function _findFollowing($twitterId)
    { 
        // TODO move to cron
        $db = Zend_Db_Table::getDefaultAdapter();
        $res = $db->fetchRow("SELECT COUNT(*) c FROM users_twitter_followers WHERE follower_id=?",$twitterId);
        return $res['c'];
    }
}