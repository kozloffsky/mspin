<?php
/**
 * MyNetworkController
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 0.2
 */
class MyNetworkController extends Zend_Controller_Action
{
    const NETWORK_THEY_FOLLOW = 1;
    const NETWORK_I_FOLLOW = 2;
    const NETWORK_BOTH_FOLLOW = 3;
    
    const NETWORK_PAGE_COUNT = 10;
    const NETWORK_MAX_PAGE_COUNT = 100;
    
    private $_user;
    private $_userNetworkSettings;
    
    public function init()
    {
        $settings = new stdClass();
        $settings->moods = array();
        $settings->followings = self::NETWORK_BOTH_FOLLOW;
        // setup empty default settings
        $this->_userNetworkSettings = $settings;

        $this->_user = Zend_Auth::getInstance()->getIdentity();
        if ($this->_user)
            $this->_userNetworkSettings = $this->_user->getNetworkSettings();
    }
    
    /**
     * The default action
     * shows "set options"
     */
    public function indexAction()
    {
        if ($this->_user && $this->_userNetworkSettings) {
            $this->_forward('options');
        }
    }
    /**
     * shows option
     * 
     */
    public function optionsAction()
    {
        if (!$this->_user) {
            $this->_forward('index');
        }
        $this->view->userSettings = $this->_getUserSettings();
        
        $ajustSettings = $this->_getParam('adjust','');
        
        if ($ajustSettings != 'settings' && $this->_userNetworkSettings) {
            $this->_forward('results');
        }
        
        $modelMoodCategories = Model_Manager::getModel('MoodCategories');
        $this->view->moodCategories = $modelMoodCategories->getAllMoods();
    }
    /**
     * shows results
     * 
     */
    public function resultsAction()
    {
        // if not logged in - forward to index action
        if (!$this->_user) {
            $this->_forward('index');
            return;
        }
        
        $showTypes = array(
            'showResults',
            'showMoreResultsForMood',
            'showResultsPage'
        );
        $showType = 'showResults';
        
        // type of result
        $showType = $this->_getParam('results_type','showResults');
        if (!in_array($showType,$showTypes)) {
            $showType = 'showResults';
        }

        // disable layout if it is request for selected users for mood
        if ($showType != 'showResults') {
            $this->view->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $followings = $this->_userNetworkSettings->followings;
        } else {
            // following type
            $followings = $this->_getParam('include_type',self::NETWORK_BOTH_FOLLOW);
        }
        
        // moods
        $moodIds = $this->_setupResultsSettings();
        // user
        $userId = $this->_user->getId();
        
        // if no moodIds - try to get them
        // from saved user settings
        if ($moodIds) {
	        $settings = new stdClass();
	        $settings->moods = $moodIds;
	        $settings->followings = $this->_getParam(
	            'include_type',self::NETWORK_BOTH_FOLLOW);
	        $this->_setupUserSettings($settings);
	        // save user settings if we get form saved
	        if ($this->_getParam('save') == 'save') {
                Moodspin_User_Manager_Twitter::getInstance()->saveUserSettings($this->_user, 'networkSettings', $settings, true);
	        }
        } else {
            $this->_getUserSettings();
            if ($this->_userNetworkSettings) {
                $moodIds = $this->_userNetworkSettings->moods;
            }
        }
        
        // page offset
        $offset = (int)$this->_getParam('offset',0);
        // page direction
        $pageDir = $this->_getParam('page_dir',0);
        if ($pageDir != 0)
            $pageDir = (($pageDir < 0) ? -1 : 1);
        
        $offset += $pageDir * self::NETWORK_PAGE_COUNT;
        $this->view->offset = $offset;
        
        // result users array
        $users = array();
        // set users per page count
        // MAX for show all friends for mood
        if ($showType == 'showMoreResultsForMood') {
            $perPage = self::NETWORK_MAX_PAGE_COUNT;
        } else {
            $perPage = self::NETWORK_PAGE_COUNT;
        }
        
        $users = $this->_getFriendUsersByMoodIds($moodIds, $userId, $followings, $offset, $perPage);
        
        $this->view->users = $users;
        
        // use dependent view script
        switch ($showType) {
            case 'showMoreResultsForMood':
                $this->render('show-all');
                break;
            case 'showResultsPage':
                $this->render('page');
                break;
            case 'showResults':
            default:
                break;
        }
    }
    /**
     * trying to get mood ids
     * and following type from post query
     * if this is the case and we got "save"
     * save the settings to DB
     * 
     * @return array
     */
    private function _setupResultsSettings()
    {
        $moodIds = $this->_getParam('mood_id',array());

        // save user settings if we get form saved
        if ($this->_getParam('save') == 'save') {
            $settings = new stdClass();
            $settings->moods = $moodIds;
            $settings->followings = $this->_getParam('include_type', self::NETWORK_BOTH_FOLLOW);
            $this->_setupUserSettings($settings);
            Moodspin_User_Manager_Twitter::getInstance()->saveUserSettings($this->_user, 'networkSettings', $settings, true);
        }

        if(!$moodIds) {
            $this->_getUserSettings();
            if ($this->_userNetworkSettings) {
                $moodIds = $this->_userNetworkSettings->moods;
            }
        }

        return $moodIds;
    }
    /**
     * get users for moods
     * 
     * @param array|int $moodIds
     * @param int $userId
     * @param int $followings
     * @param int $offset
     * @param int $perPage
     * @return array
     */
    private function _getFriendUsersByMoodIds($moodIds,$userId,$followings,$offset,$perPage)
    {
        $model = Model_Manager::getModel('ReportUsers');
        $users = array();
        if (!is_array($moodIds)) {
            $moodIds = array($moodIds);
        }
        
        $result = $model->getFriendUsersByMoodIds($moodIds, $userId, $followings, $offset, $perPage);
        
        if ($result) {
            foreach ($moodIds as $mood) {
                foreach ($result as $res) {
                    $user = $res;
                    if ($user['latest_mood_id'] == $mood) {
                        $avatar = Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($user['login']);
                        $user['avatar'] = $avatar;
	                    $users[$mood]['users'][$user['id']] = $user;
                    }
                    unset($user);
                }
            }
        } 
        
        return $users;
    }
    /**
     * setting up user settings for "my network"
     * 
     * @param stdObj $settings
     */
    private function _setupUserSettings($settings)
    {
        $this->_userNetworkSettings = $settings;
        if ($this->_user) {
            $this->_user->setNetworkSettings($settings);
        }
    }
    /**
     * getting user settings from user object
     * 
     * @return stdClass
     */
    private function _getUserSettings()
    {
        if ($this->_user) {
            Moodspin_User_Manager_Twitter::getInstance()->fetchUserSettings($this->_user);
            $this->_userNetworkSettings = $this->_user->getNetworkSettings();
        }
        return $this->_userNetworkSettings;
    }
}
