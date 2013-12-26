<?php
class Moodspin_Action_Helper_PageStatus extends Zend_Controller_Action_Helper_Abstract
{
    const TYPE_MESSAGE = 'message';
    const TYPE_ERROR = 'error';
    
    protected $_flash;
    
    public function getFlash ()
    {
        if ($this->_flash == NULL) {
            $this->_flash = new Zend_Session_Namespace('status');
        }
        return $this->_flash;
    }
    
    public function setType ($value)
    {
        $this->getFlash()->type = $value;
    }
    
    public function setMessage ($value)
    {
        $this->getFlash()->message = $value;
    }
    
    public function getType ()
    {
        return $this->getFlash()->type;
    }
    
    public function getMessage ()
    {
        return $this->getFlash()->message;
    }
    
    public function setCurrentMood ($value)
    {
        $this->getFlash()->currentMood = $value;
    }
    
    public function getCurrentMood ()
    {
        return $this->getFlash()->currentMood;
    }

    public function setStatusMessage($value)
    {
        $this->getFlash()->statusMessage = $value;
    }

    public function getStatusMessage()
    {
        return $this->getFlash()->statusMessage;
    }

    public function clear()
    {
        $this->setType(null);
        $this->setMessage(null);
        $this->setStatusMessage(null);
        $this->setCurrentMood(null);
    }
}