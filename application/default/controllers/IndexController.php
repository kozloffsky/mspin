<?php

/**
 * IndexController - The default controller class
 *
 * @author
 * @version
 */

class IndexController extends Zend_Controller_Action
{
    private $_identity = null;

    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $this->_identity = Zend_Auth::getInstance()->getIdentity();
        $this->view->identity = $this->_identity;

        $this->_helper->layout->getLayoutInstance()->setLayout('page');
        $this->view->unknownUserLogin = $this->_getParam('unknownUserLogin','');
    }

}