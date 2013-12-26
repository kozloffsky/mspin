<?php
/**
 * TwitterController - manage twitter authentification
 *
 * @author
 * @version
 */
class TwitterController extends Zend_Controller_Action
{
    protected $_service;
    /**
     * Retrieves twitter service
     */
    public function preDispatch ()
    {
        $this->_service = Moodspin_Service::getInstance()->getService('Twitter');
    }
    /**
     * Check if user authenticated otherwise
     * redirects to twitter authentificate url
     */
    public function indexAction ()
    {
        //FOR DEBUG ONLY!
        //Zend_Auth::getInstance()->clearIdentity();
        try {
            if (! Zend_Auth::getInstance()->hasIdentity()) {
                $this->_redirect($this->_service->getAuthenticateUrl(), array('prependBase' => true , 'exit' => true));
            } else {
                $this->_redirect('/');
            }
        } catch (Exception $e) {
            Moodspin_Log::log('Exception in ' . get_class($e) . ' twitter controller: ' . $e->getMessage());
            $flash = $this->getHelper('PageStatus');
            $flash->setMessage($this->view->translate('error.twitterDown'));
            $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_ERROR);
            $this->_redirect('/twitter/logout');
        }
    }
    /**
     * Manage twitter authentication. Should be registered in
     * twitter aplication as callback url
     */
    public function loginAction ()
    {
        $oauthToken = $this->getRequest()->getParam('oauth_token', 0);
        if ($oauthToken == 0) {
            $this->_forward('index', 'index');
        }
        require_once ('Moodspin/Service/Twitter/Auth/Adapter.php');
        try {
            $result = Zend_Auth::getInstance()->authenticate(new Moodspin_Service_Twitter_Auth_Adapter($oauthToken));
        } catch (Exception $e) {
            Moodspin_Log::log('Error ' . get_class($e) . ' during login. ' . $e->getMessage());
            $flash = $this->getHelper('PageStatus');
            $flash->setMessage($this->view->translate('moodchooser.form.error'));
            $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_ERROR);
            $this->_redirect('/twitter/logout');
        }
        $this->_redirect('/users/moodline');
    }
    /**
     * Logs out user
     */
    public function logoutAction ()
    {
        try {
            $this->_service->logout();
            Zend_Auth::getInstance()->clearIdentity();
            Moodspin_Service::getInstance()->logout();
        } catch (Exception $e) {}
        $this->_redirect('/');
    }
}