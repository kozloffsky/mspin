<?php

/**
 * LoginController - manage login
 *
 * @author
 * @version
 */

class LoginController extends Zend_Controller_Action
{
    /**
     * Display popup window with proposition to login 
     */
    public function popupAction()
    {
        $loggedIn = false;
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $loggedIn = true;
        }
        $this->view->loggedIn = $loggedIn;
    }

}