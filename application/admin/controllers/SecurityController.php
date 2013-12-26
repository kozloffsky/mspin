<?php

/**
 * Admin_SecurityController - manage admin authentication
 *
 * @author
 * @version
 */

class Admin_SecurityController extends Zend_Controller_Action
{
    
    const LOGIN_STATUS_FAIL = "fail";
    
    /**
     * Shows login form
     */
    public function loginAction()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            if ($auth->getIdentity() instanceof Moodspin_AdminUser ) {
                $this->_redirect('/admin/index/');
            }
        }
        
        $flash = $this->getHelper('PageStatus');
        $messages = array();
        
        if ($flash->getType() == self::LOGIN_STATUS_FAIL) {
            $messages[] = $this->view->translate('admin.auth.badPassword');
        }
        
        $this->view->form = $this->_getForm($messages);
        
        $flash->setType(null);
        
    }
    
    /**
     * Login callback. authenticate admin user
     */
    public function authAction()
    {
        $form = $this->_getForm();
        
        $form->isValid($_POST);
        
        $result = Zend_Auth::getInstance()->authenticate(new Moodspin_Auth_AdminAdapter($form->getValue('password')));
        
        if (!$result->isValid()) {
            $flash = $this->getHelper('PageStatus');
            $flash->setType(self::LOGIN_STATUS_FAIL);
            $this->_redirect('/admin/security/login');
        }
        
        $this->_redirect('/admin/index');
    }
    
    /**
     * Logs out from admin pages
     */
    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_redirect('/admin/security/login');
    }
    
    /**
     * Returns login form
     *
     * @return Zend_Form
     */
    protected function _getForm($errorMessages = array())
    {
        $form = new Zend_Form();
        $form->setAction('/admin/security/auth');
        $form->setMethod('post');
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('Password');
        $form->addElement($password);
        
        $submit = new Zend_Form_Element_Submit('login');
        $submit->setLabel('login');
        $form->addElement($submit);
        
        $form->setErrorMessages($errorMessages);
        
        return $form;
    }
    
    public function preDispatch()
    {
        $layout = Zend_Layout::getMvcInstance();
        $layout->getView()
               ->setScriptPath(
                   array_merge(
                       $layout->getView()->getScriptPaths(),
                       array(
                           APPLICATION_PATH . '/views/scripts',
                           APPLICATION_PATH . '/../admin/views/scripts',
                       )
                   )
        );
        $layout->getView()->assign('messageType','admin');
    }
    
}
