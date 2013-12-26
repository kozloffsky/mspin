<?php

/**
 * ThemesController - return css for current user mood
 *
 * @author
 * @version
 */

class ThemesController extends Zend_Controller_Action
{
    private $_identity = null;

    /**
     * Sets headers for css mime-type and disables layout
     */
    public function preDispatch()
    {
        $this->_response->setHeader('Content-Type', 'text/css; charset=UTF-8', true);
        $this->view->layout()->disableLayout();

        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_identity = Zend_Auth::getInstance()->getIdentity();
        }
    }

    /**
     * Returns css styles for current user mood
     */
    public function indexAction()
    {
        $theme = null;
        if ($this->_identity) {
            $userId = $this->_identity->getUserId();
            
            $modelStatuses = Model_Statuses::getInstance();
            $status = $modelStatuses->getCurrentStatus($userId);
            if ($status) {
                $mood = $status->findParentRow('Model_Moods');
                $themes = $mood->findDependentRowset('Model_Themes');
                $theme = $themes->current();
            }
        }
        
        $this->view->theme = $theme;
    }

}