<?php
class Moodspin_View_Helper_TimeAgo extends Zend_View_Helper_Abstract
{
    
    function timeAgo ($time)
    {
        $delta = time() - strtotime($time);
        /*$current = new Zend_Date(time());
        $past = new Zend_Date(strtotime($time));
        $current = $current->toArray();
        $past = $past->toArray();*/
        
        if ($delta > 365 * 24 * 3600) {
            $diff = round($delta / (365 * 24 * 3600));
            
            if ($diff > 1) {
                return sprintf($this->view->translate("time.years"), $diff);
            } else {
                return $this->view->translate("time.year");
            }
        } else if ($delta > 30 * 24 * 3600) {
            $diff = round($delta / (30 * 24 * 3600));
            
            if ($diff > 1) {
                return sprintf($this->view->translate("time.months"), $diff);
            } else {
                return $this->view->translate("time.month");
            }
        } else if ($delta > 24 * 3600) {
            $diff = round($delta / (24 * 3600));
            
            if ($diff > 1) {
                return sprintf($this->view->translate("time.days"), $diff);
            } else {
                return $this->view->translate("time.day");
            }
        } else if ($delta > 3600) {
            $diff = round($delta / 3600);
            
            if ($diff > 1) {
                return sprintf($this->view->translate("time.hours"), $diff);
            } else {
                return $this->view->translate("time.hour");
            }
        } else if ($delta > 60) {
            $diff = round($delta / 60);
            
	        if ($diff > 1) {
	            return sprintf($this->view->translate("time.minutes"), $diff);
	        } else {
	            return $this->view->translate("time.minute");
	        }
        } else {
            return $this->view->translate("time.lessThanMinute");
        }
    }
}
?>