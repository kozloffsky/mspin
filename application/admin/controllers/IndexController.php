<?php

/**
 * Admin_IndexController - shows last registered users
 *
 * @author
 * @version
 */

class Admin_IndexController extends Moodspin_Action_AdminController 
{
    const DATE_FORMAT = 'YYYY-MM-dd';
    const PERIOD_DURATION = 1;
    const USERS_PER_PAGE = 10;
    
    public function preDispatch()
    {
        parent::preDispatch();
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
    
    /**
     * Show admin dashboard
     */
    public function indexAction()
    {
        $modelUsers = new Model_Users();
        $modelReportRegistrations = Model_Manager::getModel('ReportRegistrations');
        $this->view->registeredUsers = $modelReportRegistrations->getRegisteredUsersCount();
        $this->view->potentialUsers = $modelUsers->getPotentialUsersCount();
        $this->view->invitedUsers = $modelUsers->getInvitedUsersCount();
    }
    /**
     * update user avatar
     */
    public function updateAvatarAction()
    {
    	$userLogin = trim($this->_getParam('user-login',''));
    	if ($userLogin) {
    		$result = $this->_createAvatar($userLogin);
    		if (false === $result) {
    			$this->view->message = "error update avatar";
    		} else {
    			$this->view->message = "avatar updated";
    		}
    	} else {
    		$this->view->message = "";
    	}
    }
    /**
     * upload user avatar
     * 
     * @param string $user
     */
    private function _createAvatar($user)
    {
        Moodspin_Log::log("getting info for user " . $user . "\n");
        $service = Moodspin_Service::getInstance()->getService('Twitter');
        $avatarManager = Moodspin_Avatar_Manager::getInstance();
        try {
            $result = $service->getUserInfo($user);
            $imageUrl = $result->profile_image_url;
            if ($avatarManager->isImageOriginal($imageUrl)) {
                // update status
                $modelStatuses = Model_Manager::getModel('Statuses');
                $modelUsersTwitter = Model_Manager::getModel('UsersTwitter');
                $modelStatuses->updateStatus(
                   $modelUsersTwitter->getUserIdByTwiterName($user),
                   Model_Moods::EMPTY_MOOD_ID, '');
            }
        } catch (Exception $e) {
            Moodspin_Log::log("ERROR!!! getting info for user " . $user . " failed\n");
            return false;
        }
        try {
            Moodspin_Log::log("upload avatar for user " . $user . "\n");
            $avatarManager->saveOriginalAvatar($user, $avatarManager->getLargeImageUrl($imageUrl),'png',true);
        } catch (Exception $e) {
            Moodspin_Log::log("ERROR!!! upload avatar for user " . $user . " failed\n");
            Moodspin_Log::log($e->getMessage());
            return false;
        }
    }
    /**
     * 
     * @param string $imageUrl
     * @return string
     */
    private function _getLargeImageUrl ($imageUrl)
    {
        return preg_replace("~normal(\.\w{3,4})$~is","bigger\\1",$imageUrl);
    }
    
    /**
     * Show list of users filtered by registration date range
     */
    public function registeredUsersAction()
    {
        $form = $this->_getForm();
        if ($this->_request->isPost()) {
            if ($form->isValid($this->_getAllParams())) {
                ;
            }
        }
        $this->view->form = $form;
        
        $values = $form->getValues();
        
        $page = $this->_getParam('page',0);
        $perPage = self::USERS_PER_PAGE;
        $this->view->page = $page;
        
        $sortColumns = array(
            'login',
            'followers_num',
            'following_num',
            'creation_date',
            'followers_on_moodspin',
            'following_on_moodspin',
            'ratio',
            'status_count',
            'using_badge',
            'using_iphone',
            'moodspin_following'
        );
        $sort = $this->_getParam('sort','login');
        if (!in_array($sort,$sortColumns)) {
            $sort = 'login';
        }
        $this->view->sort = $sort;
        $sortOrder = $this->_getParam('sort_order','ASC');
        if ($sortOrder != 'ASC' && $sortOrder != 'DESC') {
            $sortOrder = 'ASC';
        }
        $this->view->sortOrder = $sortOrder;

        $model = Model_Manager::getModel('ReportUsers');
        $users = $model
            ->getUsersReport(
                true,$values['from_date'],$values['to_date'],$page,$perPage,
                null,null,null,
                $sort,$sortOrder);
        
        $this->view->users = $users;
    }

    public function sendMessagesAction() {
        $errors = array();
        if($this->_request->isPost()) {
            $users_list = $this->_getParam('users_list', '');
            $users_list = explode("\n", $users_list);
            $tmp = array();
            foreach($users_list as $user) {
                $user = trim($user);
                if(empty($user)) {
                    continue;
                }
                $tmp[] = $user;
            }
            $users_list = $tmp;

            $message = htmlspecialchars(trim($this->_getParam('message', '')));
            $message = substr($message, 0, 140);

            if (!$message) {
                $errors[] = 'Message must be filled in';
            }

            if (empty($users_list)) {
                $errors[] = 'Users list must be filled in';
            }

            $service = Moodspin_Service::getInstance()->getService('Twitter');
            foreach ($users_list as $user) {
                try {
                    $service->reTweetBySystem($user,$message);
                } catch (Exception $e) {
                    $error[] = 'Can\'t send message to '.$user;
                    Moodspin_Log::log("Exception retweeting from admin module!");
                    Moodspin_Log::log($e->getMessage());
                }
            }
        }
        $this->view->errors = $errors;
    }
    
    /**
     * Show list of potential users
     * filtered by last mood change date range
     */
    public function potentialUsersAction()
    {
        $this->view->moodCategories = $this->_getAllMoods();
        $form = $this->_getForm();
        if ($this->_request->isPost()) {
            if ($form->isValid($this->_getAllParams())) {
                ;
            }
        }
        $this->view->form = $form;
        
        $values = $form->getValues();
        
        $moodsTmp = $this->_getParam('moods',array());
        $moods = array();
        foreach ($moodsTmp as $mood) {
            $moods[] = (int)$mood;
        }
        unset($moodsTmp);
        $this->view->moods = $moods;
        
        $minFollowers = (int)$this->_getParam('minFollowers',0);
        $maxFollowers = (int)$this->_getParam('maxFollowers',0);
        
        $this->view->minFollowers = $minFollowers;
        $this->view->maxFollowers = $maxFollowers;
        
        $page = (int)$this->_getParam('page',0);
        $perPage = self::USERS_PER_PAGE;
        $this->view->page = $page;
        
        $sortColumns = array(
            'login',
            'followers_num',
            'following_num',
            'latest_status_date',
            'contacted_date',
            'moodspin_following'
        );
        $sort = $this->_getParam('sort','login');
        if (!in_array($sort,$sortColumns)) {
        	$sort = 'login';
        }
        $this->view->sort = $sort;
        $sortOrder = $this->_getParam('sort_order','ASC');
        if ($sortOrder != 'ASC' && $sortOrder != 'DESC') {
        	$sortOrder = 'ASC';
        }
        $this->view->sortOrder = $sortOrder;
        
        $model = Model_Manager::getModel('ReportUsers');
        $users = $model
            ->getUsersReport(
                false,$values['from_date'],$values['to_date'],$page,$perPage,
                $moods,$minFollowers,$maxFollowers,
                $sort,$sortOrder);

        $this->view->users = $users;
    }
    /**
     * moods admin panel
     */
    public function moodsAction()
    {
        $modelMoods = new Model_Moods();
        $moods = $modelMoods->getUsersMoodsReport();
        $this->view->moods = $moods;
    }
    /**
     * moodspin users admin panel
     */
    public function moodspinUsersAction()
    {
    	$page = $this->_getParam('page',0);
        $perPage = self::USERS_PER_PAGE;
        $this->view->page = $page;
        $sortColumns = array(
            'login',
            'creation_date',
            'followers_on_moodspin',
            'following_on_moodspin',
        );
        $sort = $this->_getParam('sort','login');
        if (!in_array($sort,$sortColumns)) {
            $sort = 'login';
        }
        $this->view->sort = $sort;
        $sortOrder = $this->_getParam('sort_order','ASC');
        if ($sortOrder != 'ASC' && $sortOrder != 'DESC') {
            $sortOrder = 'ASC';
        }
        $this->view->sortOrder = $sortOrder;
        $this->view->sort = $sort;

        $model = Model_Manager::getModel('ReportUsers');
    	$this->view->users = $model->getMoodspinUsers($page,$perPage,$sort,$sortOrder);
    }
    
    /**
     * xhr request to follow/unfollow user(s)
     * users ids are post by 'users' param
     * unfollow flag is post by 'unfollow' param
     * returns json string of result which could be
     * successfull or failure
     * 
     * @return string
     */
    public function followAction()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        $users = $this->_getParam('users',array());
        $unfollow = $this->_getParam('unfollow',0);
        $error = array();
        
        $service = Moodspin_Service::getInstance()->getService('Twitter');
        if (!$service) {
            $this->view->error = "error";
            $this->renderScript('_jsonResponse.phtml');
            return;
        }
            
        if ($users && is_array($users)) {
            $modelUsers = new Model_Users();
            foreach ($users as $user) {
                try {
                    if ($unfollow) {
                        $service->unFollowSystem($user);
                        $modelUsers->updateFollowByMoodspin(false,$user);
                    } else {
                        $service->followSystem($user);
                        $modelUsers->updateFollowByMoodspin(true,$user);
                    }
                } catch (Exception $e) {
                    $error[] = $user;
                }
            }
        }
        $this->view->error = $error;
        $this->renderScript('_jsonResponse.phtml');
    }
    
    /**
     * xhr request to sent user(s) message
     * users ids are post by 'users' param
     * message text is posted by 'message' param
     * returns json string of result which could be
     * successfull or failure
     * 
     * @return string
     */
    public function sendMessageAction()
    {
        $this->view->layout()->disableLayout();
        
        $users = $this->_getParam('users',array());
        $names = $this->_getParam('names',array());
        $message = htmlspecialchars(trim($this->_getParam('message','')));
        $message = substr($message,0,140);
        if (!$message)
            return;
        $error = array();
            
        $service = Moodspin_Service::getInstance()->getService('Twitter');
        if (!$service) {
            $this->view->error = "error";
            $this->renderScript('_jsonResponse.phtml');
            return;
        }

        if ($users && $names && is_array($users) && is_array($names)) {
            $modelUsers = new Model_Users();
            $i = 0;
            foreach ($users as $user) {
                try {
                    $service->reTweetBySystem($names[$i],$message);
                    $modelUsers->updateContactedDate($user);
                } catch (Exception $e) {
                    $error[] = $user;
                    Moodspin_Log::log("Exception retweeting from admin module!");
                    Moodspin_Log::log($e->getMessage());
                }
                $i++;
            }
        }
        $this->view->error = $error;
        $this->renderScript('_jsonResponse.phtml');
    }

    private function _getForm()
    {
        $dateValidate = new Zend_Validate_Date(self::DATE_FORMAT);
        $fromDate = Zend_Date::now();
        $toDate = Zend_Date::now()->addDay(self::PERIOD_DURATION);
        
        $form = new Zend_Form();
        $form->addElement('text', 'from_date', array(
            'value' => $fromDate->toString(self::DATE_FORMAT),
            'validators' => array($dateValidate),
            'required' => true
        ));
        $form->addElement('text', 'to_date', array(
            'value' => $toDate->toString(self::DATE_FORMAT),
            'validators' => array($dateValidate),
            'required' => true
        ));
        return $form;
    }
    
    private function _getAllMoods()
    {
        $modelMoods = Model_Moods::getInstance(); 
        $modelMoodCategories = new Model_MoodCategories(); 
        
        $categories = array();
        $moodCategories = $modelMoodCategories->getList();
        foreach ($moodCategories as $moodCategory) {
            $categories[] = array(
                'category' => $moodCategory,
                'moods'    => $moodCategory->findDependentRowset('Model_Moods')
            );
        }
        return $categories;
    }
}