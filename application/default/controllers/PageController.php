<?php

/**
 * PageController - displays static pages
 *
 * @author
 * @version
 */

class PageController extends Zend_Controller_Action
{

    /**
     * Sets layout for all static pages
     */
    public function preDispatch()
    {
        $this->_helper->layout->getLayoutInstance()->setLayout('page');
    }

    /**
     * Shows About Us page
     */
    public function aboutUsAction()
    {
    }

    /**
     * Shows Legal Info page
     */
    public function legalInfoAction()
    {
    }

    /**
     * Shows Blog
     */
    public function blogAction()
    {
    }

    /**
     * Shows help page
     */
    public function helpAction()
    {
    }

        /**
     * Shows iPhone page
     */
    public function iphoneAction()
    {
        $this->_helper->layout->getLayoutInstance()->setLayout('main');
    }

}