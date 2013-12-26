<?php

class Model_MoodCategories extends Moodspin_Db_Trigger_Table 
{
    const DEFAULT_ORDER_FIELD = 'id';

    protected $_name = 'mood_categories';

    protected $_dependedTables = array('Model_Moods');

    public function getList()
    {
        return $this->fetchAll(null, self::DEFAULT_ORDER_FIELD);
    }
    
    public function getAllMoods()
    {
        $categories = array();
        $moodCategories = $this->fetchAll(null, self::DEFAULT_ORDER_FIELD);
        foreach ($moodCategories as $moodCategory) {
            $categories[] = array(
                'category' => $moodCategory,
                'moods'    => $moodCategory->findDependentRowset('Model_Moods')
            );
        }
        return $categories;
    }

}