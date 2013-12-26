<?php
class Moodspin_Action_AdminController extends Zend_Controller_Action
{
    protected $_identity;
    
    public function preDispatch ()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $identity = Zend_Auth::getInstance()->getIdentity();
            if ($identity instanceof Moodspin_AdminUser) {
                $this->_identity = $identity;
                return;
            }
        }
        
        $this->_redirect('/admin/security/login');
    }
}