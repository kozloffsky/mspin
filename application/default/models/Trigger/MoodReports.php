<?php
require_once ('Moodspin/Db/Table/Trigger.php');
class Model_Trigger_MoodReports extends Moodspin_Db_Table_Trigger
{
    
    protected function statusPostInsert($data)
    {   
        $moodReportsModel = new Model_ReportMoods();
        
        $moodId = $data->mood_id;
        
        $report = $moodReportsModel->fetchRow($moodReportsModel->select()->where('mood_id=?',$moodId));
        
        if($report == null){
            $report = $moodReportsModel->createRow();
            $report->mood_id = $moodId;
            $report->used_num = 0;
            $report->used_unique= 0;
            
            
        }
        
        $usedNum = (int)$report->used_num;
        $report->used_num = ++$usedNum;
        $report->mood_name = $this->_findMoodName($moodId);
        $report->used_unique += (int)$this->_countUnique($moodId,$data->user_id);
        $report->used_last_date = $data->date;
        $report->save();
        
    }
    
    protected function _findMoodName($moodId){
        // Model_Manager::getModel('Moods');
        $modelMoods = new Model_Moods();
        $moods = $modelMoods->find($moodId);

        return $moods->current()->name;
    }
    
    protected function _countUnique($moodId, $userId)
    {
        /*//TODO use table name from model class;
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $row = $adapter->fetchOne('SELECT count(*) c from(SELECT s.user_id FROM statuses s WHERE s.mood_id = ? GROUP BY s.user_id) as q;', array($moodId));
        Moodspin_Log::log('Unique users for this mood' . $row['c']);
        return $row['c'];*/
        
        $statusesModel = new Model_Statuses();
        $select = $statusesModel->select()->where('mood_id=?', $moodId)->where('user_id=?', $userId);
        return $statusesModel->fetchAll($select)->count() > 0 ? 0 : 1;
    }
        
}
?>