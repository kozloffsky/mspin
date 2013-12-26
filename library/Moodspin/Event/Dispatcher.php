<?php


class Moodspin_Event_Dispatcher 
{
    private $_listeners=array();

    public function addEventListener($eventType,Moodspin_Event_Listener $listener)
    {
        if (!isset($this->_listeners[$eventType])){
            $this->_listeners[$eventType] = array();
        }
        array_push($this->_listeners[$eventType],$listener);
    }

    public function dispatchEvent(Moodspin_Event $event)
    {
        if (isset($this->_listeners[$event->getType()])){
            $type = $event->getType();
            if (is_array($this->_listeners[$type])){
                foreach ($this->_listeners[$type] as $listener){
                    $listener->listen($event);
                }
            }
        }
    }
}

?>