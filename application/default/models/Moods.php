<?php

class Model_Moods extends Moodspin_Db_Trigger_Table
{
    const EMPTY_MOOD_ID = 1;
    const DEFAULT_MOOD_ID = 13;
    
    protected $_name = 'moods';
    
    protected $_dependedTables = array('Model_Statuses', 'Model_Themes');

    protected $_referenceMap = array(
        'Category' => array(
            'columns'       => array('mood_category_id'),
            'refTableClass' => 'Model_MoodCategories',
            'refColumns'    => array('id')
        )
    );

    protected static $_instance;

    public static function getInstance()
    {
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    public function getMoodById($moodId)
    {
        $mood = $this->fetchRow($this->select()->where('id=?',$moodId));
        
        if($mood == null){
            throw new Exception('Mood with id '.$moodId.' not found');
        }
        
        return $mood;
    }
    
    public function getSearchCriteriaByMoodId($moodId) {
        try {
            $searchCriteria = $this->find($moodId)->current()->search_criteria;
	        return $searchCriteria;
        } catch (Exception $e) {
            return "";
        }
    }
    
    public function getEmptyMood()
    {
        return $this->find(self::EMPTY_MOOD_ID)->current();
    }

    public function getDefaultMood()
    {
        return $this->find(self::DEFAULT_MOOD_ID)->current();
    }
    
    public function getUsersMoodsReport()
    {
        $query = 'SELECT rm.* FROM report_moods rm JOIN moods m ON(rm.`mood_id`=m.`id`)'
               . 'ORDER BY m.mood_category_id, rm.mood_id';
        return $this->getAdapter()->fetchAll($query);
    }

}