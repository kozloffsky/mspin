<?php
class Moodspin_Moodline_Mood
{
    
    protected $_id;
    protected $_day;
    protected $_hours;
    protected $_type = 'mood';
    protected $_date;
    protected $_mood;
    protected $_message;
    protected $_children;
    
    public function __construct($mood)
    {
        $this->setId($mood->mood_id);
        $this->setDate($mood->date);
        $this->_mood = $mood;
        $this->_message = $mood->message;
        $this->_children = array();
    }
    
    public function getId()
    {
        return $this->_id;
    }
    
    public function setId($value)
    {
        $this->_id = $value; 
    }
    
    public function getDay()
    {
        return $this->_date['wday'];
    }
        
    public function getHours()
    {
        return $this->_date['hours'] + $this->_date['minutes']/60;
    }
        
    public function getType()
    {
        return $this->_type;
    }
    
    public function getTimestamp()
    {
        return $this->_date[0];
    }
    
    public function setTimestamp($value)
    {
        $this->_date = getdate($value); 
    }
    
    public function setDate($value)
    {
        $this->_date = getdate(strtotime($value));
    }
    
    public function getDate()
    {
        return $this->_date;
    }
    
    public function getMessage()
    {
        return $this->_message;   
    }
    
    public function addChild(Moodspin_Moodline_Mood $dot){
        if (array_key_exists($dot->getTimestamp(),$this->_children)) {
        	return;
        }
        
        $this->_children[$dot->getTimestamp()] = $dot;
    }
    
    public function getLastId()
    {
    	if(sizeof($this->_children)>0){
    		$lastChild = end($this->_children);
    		$id = $lastChild->getId();
		}else{
			$id = $this->getId();
		}
    	
    	return $id;
    }
    
    public function getLastType()
    {
        if(sizeof($this->_children)>0){
    		$lastChild = end($this->_children);
    		$type = $lastChild->getType();
		}else{
			$type = $this->getType();
		}
    	
    	return $type;
    }

    public function toArray()
    {   
        
        $messages = array(
                array('text'=>$this->_message,'mood_id'=>$this->getId())
            );
        foreach($this->_children as $child){
            $messages[] = array('text'=>$child->getMessage(),'mood_id'=>$child->getId());
        }
        
        return array(
        		'moodId'=>$this->getLastId(),
        		'day'=>$this->getDay(),
        		'hours'=>$this->getHours(),
        		'type'=>$this->getLastType(),
                'messages'=>$messages
            );
    }
    
}
?>