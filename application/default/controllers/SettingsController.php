<?php
/**
 * SettingsController
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 1.0
 */
class SettingsController extends Zend_Controller_Action
{
    private $_user;
    private $_userSettings;
    
    protected $_sendAlerts;
    protected $_emailField;
    protected $_alerts;
    
    protected $_moodspinFollowing;
    
    /**
     * set page layout
     */
    public function preDispatch()
    {
        $this->_helper->layout->getLayoutInstance()->setLayout('page');
    }
    
    public function init()
    {
        $this->_user = Zend_Auth::getInstance()->getIdentity();
        if (!$this->_user) {
            $this->_redirect('/');
        }
        $this->getFrontController()->getDispatcher()->loadClass('MyNetworkController');
        $this->_userSettings = Model_Manager::getModel('UsersSettings')->getUserSettings($this->_user->getUserId());
        $this->_parseUserSettings();
    }
    
    private function _parseUserSettings()
    {
        if ($this->_userSettings) {
            foreach ($this->_userSettings->toArray() as $set) {
                switch ($set['name']) {
                    case 'sendAlerts':
                        $this->_sendAlerts = $set['value'];
                        break;
                    case 'emailField':
                        $this->_emailField = $set['value'];
                        break;
                    case 'alerts':
                        $this->_alerts = unserialize($set['value']);
                        break;
                    default:
                        break;
                }
            }
            $model = Model_Manager::getModel('Users');
            $this->_moodspinFollowing = $model->isUserFollowingMoodspin($this->_user->getUserId());
        }
    }
    
    /**
     * create default settings object
     * 
     * @return stdClass settings object
     */
    private function _createDefaultSettings(){
        $settings = new stdClass();
        $settings->alerts = array();
        $settings->alerts['moods'] = array();
        $settings->alerts['includeType'] = MyNetworkController::NETWORK_BOTH_FOLLOW;
        $settings->sendAlerts = 0;
        $settings->email = '';
        $settings->moodspinFollowing = 0;
        return $settings;
    }
    
    /**
     * The default action
     */
    public function indexAction ()
    {
        $modelMoodCategories = Model_Manager::getModel('MoodCategories');
        $this->view->moodCategories = $modelMoodCategories->getAllMoods();
        $this->view->sendAlerts = $this->_sendAlerts;
        $this->view->emailField = $this->_emailField;
        $this->view->alerts = $this->_alerts;
        $this->view->moodspinFollowing = $this->_moodspinFollowing;
    }
    /**
     * validate email
     *
     * @param string $email
     * @return boolean
     */
    private function _isEmailValid($email)
    {
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($email)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * save settings
     * 
     */
    public function saveAction()
    {
        if ('cancel' == $this->_getParam('cancel','')) {
            $this->_redirect('/');
        } elseif ('save' == $this->_getParam('save','')) {
	        $moodspinFollowing = (int)$this->_getParam('moodspin_following',0);
	        
	        $sendAlerts = (int)$this->_getParam('moodspin_send_alerts',0);
	        $emailField = $this->_getParam('email_field','');
	        $moodsIds = $this->_getParam('mood_id',array());
	        $includeType = (int)$this->_getParam('include_type',MyNetworkController::NETWORK_BOTH_FOLLOW);

            $userManager = Moodspin_User_Manager_Twitter::getInstance();
            $userManager->saveUserSettings($this->_user, 'sendAlerts', $sendAlerts, false);

            $isError = false;

            if ($sendAlerts) {
                $this->view->emailError = "";
                if (false === $this->_isEmailValid($emailField)) {
                    $this->view->emailError = "error";
                    $isError = true;
                }

                $userManager->saveUserSettings($this->_user, 'emailField', $emailField, false);
                $userManager->saveUserSettings(
                        $this->_user,
                        'alerts',
                        array(
                            'moods' => $moodsIds,
                            'includeType' => $includeType
                        ),
                        true
                );

                $model = Model_Manager::getModel('UsersSettings');
                if (false === $model->isUserHasLastSentDate($this->_user->getUserId())) {
                    $userManager->saveUserSettings($this->_user, 'lastSendDate', date("Y-m-d H:m:s",time()), false);
                }
            }

	        // follow moodspin on twitter
	        $model = Model_Manager::getModel('Users');
            $service = Moodspin_Service::getInstance()->getService('Twitter');
            try {
                $alreadyFollow = $model->isUserFollowingMoodspin($this->_user->getUserId());

                if(!$alreadyFollow && $moodspinFollowing) {
		            $service->followingSystem($this->_user->getId());
		            $model->updateUserFollowingMoodspin($this->_user->getUserId(), 1);
                } elseif($alreadyFollow && !$moodspinFollowing) {
                    $service->unFollowingSystem($this->_user->getId());
                    $model->updateUserFollowingMoodspin($this->_user->getUserId(), 0);
                }
            } catch (Exception $e) {
                $isError = true;
            }

            $flash = $this->getHelper("PageStatus");

            if($isError) {
                $this->view->saveStatus = $this->view->translate('page.settings.updateStatusError');
                $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_ERROR);
            } else {
                $this->view->saveStatus = $this->view->translate('page.settings.updateStatusOk');
                $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_MESSAGE);
            }

            $flash->setMessage($this->view->saveStatus);
        }
        $this->_forward('index');
    }
}
