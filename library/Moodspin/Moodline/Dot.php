<?php
require_once ('Moodspin/Moodline/Mood.php');
class Moodspin_Moodline_Dot extends Moodspin_Moodline_Mood
{
    protected $_type = 'dot';
    
    /*public function __construct ($mood)
    {
        parent::__construct($mood);
    }*/
    
    /*public function toArray(){
        $res = parent::toArray();
        foreach($this->_children as $child){
            $res['messages'][] = $child->getMessage();
        }
        return $res;
    }*/

}
?>