<?php
class Moodspin_View_Helper_PageStatus extends Zend_View_Helper_Abstract
{
    protected $viewName = '_pageStatus.phtml';
    protected $_flash;
    
    public function pageStatus ()
    {
        $flash = $this->getFlash();
        $this->view->type = $flash->type;
        $this->view->message = $flash->message;

        $flash->isViewed = true;
        
        return $this->view->render($this->viewName);
    }

    /**
     * Returns instance of Zend_Session_Namespace, if not exists - creates new instance.
     *
     * @return Zend_Session_Namespace
     */
    public function getFlash ()
    {
        if ($this->_flash == NULL) {
            $this->_flash = new Zend_Session_Namespace('status');
        }
        return $this->_flash;
    }
}