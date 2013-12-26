<?php

/**
 * StatusController - updates user status
 *
 * @author
 * @version
 */

class StatusController extends Zend_Controller_Action
{
    protected $_identity;
   
    /**
     * Checks if user logged in and redirects to home page if not
     */
    public function preDispatch()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            $this->_identity = Zend_Auth::getInstance()->getIdentity();
        }
    }

    public function recentAction()
    {
        $model = new Model_UsersTwitter();
        $this->view->recentMoods = Moodspin_Avatar_Manager::getInstance()->assignAvatar(
                                        $model->getRecentMoodsUsers(4)
                                   );
    }
    
    /*
     * Displays mood chooser
     */
    public function indexAction()
    {
        $userName = "";
        $avatar = "/images/avatars/default.png";

        if ($this->_identity && !($this->_identity instanceof Moodspin_AdminUser)) {
            $userName = $this->_identity->getScreenName();
            $avatar   = Moodspin_User_Manager_Twitter::getInstance()->getLargeImage($this->_identity);
        }
        
        $this->view->userName    = $userName;
        $this->view->avatar      = $avatar;
         
        $modelMoods = Model_Moods::getInstance(); 
        $modelMoodCategories = new Model_MoodCategories();

        $this->view->emptyMood = $modelMoods->find(Model_Moods::EMPTY_MOOD_ID)->current();
        
        $categories = array();
        $useCache = false;
        
        if (Zend_Registry::isRegistered('cacheLong')) {
            $cache = Zend_Registry::get('cacheLong');
            $useCache = true;
        }
        if ($useCache) {
            $categories = $cache->load('m_moodCategories');
        }
        
        if (empty($categories)) {
	        $moodCategories = $modelMoodCategories->getList();
	        foreach ($moodCategories as $moodCategory) {
	            $categories[] = array(
	                'category' => $moodCategory,
	                'moods'    => $moodCategory->findDependentRowset('Model_Moods')
	            );
	        }
	        if ($useCache) {
	            $cache->save($categories,'m_moodCategories');
	        }
        }
        
        $this->view->moodForm = $this->_getMoodForm();
        $this->view->moodCategories = $categories;
        
        $currentMood = $this->view->moodForm->getMoodIdElement()->getValue();
        $this->view->currentMood = $modelMoods->getMoodById($currentMood);
        
        // get moodspin tweets count
        $model = Model_Manager::getModel("ReportUsers");
    }
    
    /**
     * Updates user status
     */
    public function updateAction()
    {
        if (!$this->_identity || $this->_request->isPost() == false) {
            $this->_redirect('/');
        }
        
        $form = new Moodspin_Form_MoodStatus();
        $flash = $this->getHelper("PageStatus");
        
        if (!$form->isValid($_POST)) {
            $flash->setMessage($this->view->translate('moodchooser.form.error'));
            $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_ERROR);
            $this->_redirect('/');
        }
        
        $params = $form->getValues();
        
        $statusesModel = Model_Manager::getModel('Statuses');
        $userId = $this->_identity->getUserId();
      
        $message = $params['message'];
        $moodId  = (int)$params['mood_id'];

        try {
            Moodspin_Avatar_Manager::getInstance()->addMoodForUser($this->_identity->getScreenName(), $moodId);
            $imagePath = realpath(
                            Moodspin_Avatar_Manager::getInstance()->getModifyedAvatarPathForUser(
                                $this->_identity->getScreenName()
                            )
                         );

            $responses = Moodspin_Service::getInstance()->updateStatus($this->_identity, $message, $imagePath);
            $errors = '';
            foreach ($responses as $name=>$response) {
                if (empty($response)) {
                    $errors .= 'Fail to update status on ' . $name . "\n";
                }
            }

            if (!empty($errors)) {
                throw new Exception($errors);
            }

            $statusesModel->updateStatus($userId, $moodId, $message);

            $flash->setMessage($this->view->translate('moodchooser.form.updated'));
            $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_MESSAGE);

            $this->_identity->setCurrentMood($moodId);
        } catch (Exception $e) {
            $flash->setMessage($this->view->translate('moodchooser.form.error'));
            $flash->setType(Moodspin_Action_Helper_PageStatus::TYPE_ERROR);
            $flash->setStatusMessage($message);
            Moodspin_Log::log("Exception ". get_class($e). " With message ". $e->getMessage() . " was raised during status update");
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->_redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->_redirect('/');
        }
    }

    /**
     * Initialize form for mood choosing with current or default mood mood
     * 
     * @return Moodspin_Form_MoodStatus
     */
    protected function _getMoodForm()
    {
        $flash = $this->getHelper("PageStatus");
        $form = new Moodspin_Form_MoodStatus();
        
        if ($this->_identity && !$this->_identity instanceof Moodspin_AdminUser) {
            $userId = $this->_identity->getUserId();
            if ($userId) {
                $modelStatuses = Model_Statuses::getInstance();
                $status = $modelStatuses->getCurrentStatus($userId);
                if ($status) {
                    $form->getMoodIdElement()->setValue($status->mood_id);
                    if($flash->getType() == Moodspin_Action_Helper_PageStatus::TYPE_ERROR) {
                        $form->getMessageElement()->setValue($flash->getStatusMessage());
                    }
                } else {
                    $modelMoods = Model_Moods::getInstance(); 
                    $mood = $modelMoods->getEmptyMood();
                    $form->getMoodIdElement()->setValue($mood->id);
                    $form->getMessageElement()->setValue($mood->default_message);
                }
            }
        } else {
            $modelMoods = Model_Moods::getInstance(); 
            $mood = $modelMoods->getDefaultMood();
            $form->getMoodIdElement()->setValue($mood->id);
        }

        return $form;
    }

}