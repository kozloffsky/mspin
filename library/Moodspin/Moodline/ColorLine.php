<?php
require_once ('Moodspin/Moodline/Mood.php');
class Moodspin_Moodline_ColorLine extends Moodspin_Moodline_Mood 
{
    protected $_type = 'color';
        
    /**
     * End date
     *
     * @var array
     */
    protected $_endDate;
    
    public function __construct ($mood)
    {
        parent::__construct($mood);
    }
    
    public function setEndDate($value)
    {
        $this->_endDate = $value;
    }
    
    public function getEndDate()
    {
        return $this->_endDate;
    }
    
    public function getEndDay(){
        return $this->_endDate['day'];
    }
    
    public function getEndDateHours()
    {
        return $this->_endDate['hours'] + $this->_endDate['minutes']/60;
    }
    
    public function toArray()
    {
        //if($this->getDuration()<1000) return array();
        $return = parent::toArray();
        unset($return['messages']);
        $return['duration'] = $this->getDuration();
        return $return;
    }
    
    public function getDuration()
    {
        $duration = ($this->_endDate[0] - $this->_date[0])/3600;
        return $duration > 24 ? 24: $duration;
    }
    
    public function splitForDays()
    {
        $dayEndTimestamp = mktime(23,59,59,(int)$this->_date['mon'],$this->_date['mday'],(int)$this->_date['year']);
        
        if($this->_endDate[0] > $dayEndTimestamp){
            $newLine = clone $this;
            $newLine->setTimestamp($dayEndTimestamp + 1);
            $newLine->setId($this->getId());
            $newDate = getdate($dayEndTimestamp);            
            
            if($newDate['wday'] == 6){
                return false;
            }
            
            $this->setEndDate($newDate);
            return $newLine;
        }
        
        return false;
    }
    
    

    
}
?>