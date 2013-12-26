<?php
class Moodspin_Moodline implements Moodspin_Controller_Action_Json_Serializable 
{
    
    const STACK_DATE_RANGE = 1000; // seconds
    
    protected $_moods;
    protected $_dost;
    protected $_colorLines;
    
    protected $_lastDot;
    protected $_lastColorLine;
    protected $_showMoods = true;
    
    const COLOR_CATEGORY_ID = 1;
    
    protected $_lastItem;
    protected $_endDate; 
    
    public function __construct()
    {
      $this->_moods = array();
      $this->_dost = array();
      $this->_colorLines = array();
      $this->_endDate = getdate();  
    }
    
    public function addMood($mood, $showItem = true){
        
        $moodItem = null;
        
        // id mood dot or smile;
        if($this->_moodIsColor($mood) || $mood->mood_id == 1){
            $moodItem = new Moodspin_Moodline_Dot($mood);
        }else{
            $moodItem = new Moodspin_Moodline_Mood($mood);
        }
        
        // if item is to close to previous item then add this item to child list,
        // and return from function
        if($moodItem->getTimestamp() - $this->_getLastTimestamp() < self::STACK_DATE_RANGE){
            $this->_lastItem->addChild($moodItem);
        }else{
            //if not, then add this item to list of items to show and set this item as last
            if($showItem){
                $this->_moods[$moodItem->getTimeStamp()] = $moodItem;
            }
            $this->_lastItem = $moodItem;
        }
        
        //check if last color line exists and mood is not color, then finish line
        if($this->_lastColorLine != null){
            if($this->_lastColorLine->getId() != $moodItem->getId()){
                $this->_lastColorLine->setEndDate($moodItem->getDate());
                $this->_lastColorLine = null;
            }
        }
        
        //if this mood is color then create color line or continue existing
        if($this->_moodIsColor($mood)){
            if($this->_lastColorLine == null){
                $this->_lastColorLine = new Moodspin_Moodline_ColorLine($mood);
                $this->_colorLines[$this->_lastColorLine->getTimestamp()] = $this->_lastColorLine;
            }
            $this->_lastColorLine->setEndDate($this->_endDate);
            
        }
        
        
    }
    
    public function setEndDate($value)
    {
        $newDate = getdate(strtotime($value));
        if($this->_endDate[0] > $newDate[0]){
            $this->_endDate = $newDate;
        }
    }
    
    public function showMoods($value)
    {
        $this->_showMoods = $value;
    }
    
    protected function _moodIsColor($mood)
    {
        return $mood->mood_category_id == self::COLOR_CATEGORY_ID && $mood->mood_id != Model_Moods::EMPTY_MOOD_ID;
    }
    
    protected function _getLastTimestamp()
    {
        if($this->_lastItem != null) {
            return $this->_lastItem->getTimestamp(); 
        }
        
        return 0;
    }
    
    public function toArray()
    {
        $res = array();
        if($this->_showMoods){
            foreach ($this->_dost as $timestamp=>$dot) {
                $tmp = $dot->toArray();
                if ($tmp)
                    $res[] = $tmp;
            }
            
            foreach ($this->_moods as $timestamp => $mood) {
                $tmp = $mood->toArray();
                if ($tmp)
                   $res[] = $tmp;
            }
        }
        
        foreach ($this->_colorLines as $timestamp=>$colorLine) {
            $line = $colorLine->splitForDays();
            if ($line != null) {
                $tmp = $line->toArray();
                if ($tmp)
                    $res[] = $tmp;
                while (($line = $line->splitForDays()) !== false) {
                    $tmp = $line->toArray();
                    if ($tmp)
                        $res[] = $tmp;
                }
            }
            $tmp = $colorLine->toArray();
            if ($tmp)
                $res[] = $tmp;
        }
        
        
        
        return $res;
    }
    
}
?>