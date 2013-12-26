<?php

/**
 * NavigationController - displays top navigation menu
 *
 * @author
 * @version
 */

class NavigationController extends Zend_Controller_Action
{
    /**
     * Displays navigation menu.
     * 
     * Usage: <code><?php echo $this->action('index', 'navigation'); ?></code>
     */
    public function indexAction()
    {
        $loggedIn = false;
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $loggedIn = true;
        }
        $this->view->loggedIn = $loggedIn;
    }

}