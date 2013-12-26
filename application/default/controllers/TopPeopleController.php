<?php

/**
 * TopPeopleController - displays Top 3 users list
 *
 * @author
 * @version
 */

class TopPeopleController extends Zend_Controller_Action  
{
    const TOP_USERS_COUNT      = 10;
    const MOODIEST_USERS_COUNT = 10;
    const LAST_USERS_COUNT     = 100;
    
    /**
     * Displays Top users list
     */
    public function moodiestAction()
    {
        $users = new Model_Users();
        
        $query = $users->getAdapter()->select();
        $query->from(array('ru' => 'report_users'),array(
                    'totalStatuses' => 'status_count',
                    'login'         => 'login',
                    'twitter_id'    => 'twitter_id'
                ))
              ->where('creation_date IS NOT NULL')
              ->order('status_count DESC')
              ->limit(self::TOP_USERS_COUNT);
        $users = $users->getAdapter()->fetchAll($query);
        
        foreach ($users as &$user){
            $user['image'] = Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($user['login']) . '?rnd='.rand(0,10000);
        }
        
        $this->view->users = $users;
    }
    
    public function indexAction()
    {
        $this->_helper->layout->getLayoutInstance()->setLayout('page');

        $model = new Model_UsersTwitter();
        
        $this->view->monthMoodiest = Moodspin_Avatar_Manager::getInstance()->assignAvatar(
                    $model->getMonthMoodiestsUsers(self::MOODIEST_USERS_COUNT)
                );

        $this->view->lastMoodiest = Moodspin_Avatar_Manager::getInstance()->assignAvatar(
                    $model->getRecentMoodsUsers(self::LAST_USERS_COUNT)
                );
    }
}
