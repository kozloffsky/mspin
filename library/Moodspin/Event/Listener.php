<?php

class Moodspin_Event_Listener 
{
    private $_functionName;
    private $_listenObject;

    public function __construct($listenObject,$functionName)
    {
        if(!method_exists($listenObject,$functionName)){
            throw new Exception('Wrong event handler function. Function '.$functionName.' does no exists in object'.var_export($listenObject));
        }
        $this->_functionName = $functionName;
        $this->_listenObject = $listenObject;
    }

    public function listen(Moodspin_Cms_Event $event)
    {
        $this->_listenObject->{$this->_functionName}($event);
    }

}

?>