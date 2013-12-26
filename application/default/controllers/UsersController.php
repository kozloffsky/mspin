<?php

/**
 * UsersController - shows last users moods
 *
 * @author
 * @version
 */

class UsersController extends Zend_Controller_Action
{
    const RESULTS_PER_PAGE = 5;
    
    protected $_moodId;
    protected $_page;
    /**
     * Enter description here...
     *
     * @var Moodspin_User
     */
    protected $_identity;
    
    /**
     * Disable layout if AJAX request
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if(Zend_Auth::getInstance()->hasIdentity()){
            $this->_identity = Zend_Auth::getInstance()->getIdentity();
        }
        
        if ($this->_request->isXmlHttpRequest()) {
            $this->view->layout()->disableLayout();
        }
    }

        /**
     * Return user's moodline
     */
    public function moodlineAction()
    {
       $user = (string)$this->_getParam('username');
       if ($user == null) {
           if ($this->_identity != null) {
              $user = $this->_identity->getScreenName();
              $this->_redirect('/'.$user);
           }

           $this->_redirect('/users/not-found');
       }

       $usersModel = Model_Manager::getModel('Users')
                     ->fetchAll(
                         Model_Manager::getModel('Users')->select()->where('login=?',$user)
                     );

       if ($usersModel->count() == 0){
           $this->_forward('index','index','default',array('unknownUserLogin' => $user));
       }

       $this->view->user = $user;
       
       if($this->_identity == null || $this->_identity->getScreenName() != $user){
           $this->view->showAvatar = Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($user);
       }

    }

    public function notFoundAction()
    {
    }

    /**
     * Return user's friend list
     */
    public function networkAction()
    {              
    }

    /**
     * Return paginated list of people statuses
     */
    public function everyoneAction()
    {
        $this->_moodId = (int)$this->_request->getParam('mood',Model_Moods::DEFAULT_MOOD_ID);
        if ($this->_moodId < 2){
            $this->_moodId = Model_Moods::DEFAULT_MOOD_ID;
        }
        $this->_page = $this->_request->getParam('page',1);
        
        $this->view->searchCriteria = Model_Moods::getInstance()->getSearchCriteriaByMoodId($this->_moodId);
        
        $this->view->selectedMoodId = $this->_moodId;
    }
    
    /**
     * 
     * @param int $mood
     * @param int $limit
     */
    private function _getUsersStatuses($mood,$limit)
    {
    	$model = Model_Statuses::getInstance()->getAdapter();
        $sql = "
            SELECT
                login,
                latest_status_message AS message,
                latest_status_date AS `date`
            FROM
                report_users
            WHERE
                latest_mood_id = {$mood}
            ORDER BY
                `date` DESC
            LIMIT
                {$limit}
            
        ";
        $result = $model->fetchAll($sql,null,Zend_Db::FETCH_OBJ);
        return $this->_statusesToArray($result);
    }
    
    public function getBadgeAction()
    {
      if(!Zend_Auth::getInstance()->hasIdentity()){
          $this->_redirect('/');   
      }
      
      $identity = Zend_Auth::getInstance()->getIdentity();
      $this->view->username = $identity->getScreenName();
    }
    
    protected function _statusesToArray($statuses)
    {
        $res = array();
        
        foreach ($statuses as $status) {
            $s = new stdClass();
            $s->message = $status->message;
            
            if (method_exists($status,'findParentRow')) {
                $s->login = $status->findParentRow('Model_Users')->login;
            } else {
                $s->login = $status->login;
            }
            
            $s->avatar = Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($s->login). '?rnd='.rand(0,1000);
            $s->date = $status->date;
            
            $res[] = $s;
        }
        
        return $res;
    }
    
    protected function _createPaginator($data, $page = 1, $itemCount = 10)
    {
        if ($data == null) {
            $data = array();
        }
        
        $paginator = Zend_Paginator::factory($data) 
                    ->setItemCountPerPage($itemCount)
                    ->setCurrentPageNumber($page);
       
        return $paginator;
    }
    
}