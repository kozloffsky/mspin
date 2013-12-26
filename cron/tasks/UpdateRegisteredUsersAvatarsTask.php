<?php
/**
 * Reupload avatars for registered users
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 1.0
 */
class UpdateRegisteredUsersAvatarsTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
	const USERS_PER_PAGE = 100;
	
    private $_db;
    private $_service;
    private $_basePath;
    private $_avatarManager;
    
    private $_count;
    private $_users;
    
    /**
     * initialize db, twitter service, avatar manager
     */
    public function __construct()
    {
        $this->_basePath = realpath(dirname(__FILE__)) . "/../public";
        $this->_db = Zend_Db_Table::getDefaultAdapter();
        $this->_service = Moodspin_Service::getInstance()->getService('Twitter');
        $this->_avatarManager = Moodspin_Avatar_Manager::getInstance();
    }
    /**
     * run current task
     */
    public function run()
    {
    	$this->_count = $this->_getUsersCount();
    	if ($this->_count) {
    		$pages = ceil($this->_count / self::USERS_PER_PAGE);
    		for ($i = 1; $i <= $pages; $i++) {
		        $this->_setupUsers($i,self::USERS_PER_PAGE);
		        if ($this->_users) {
		            foreach ($this->_users as $user) {
		                $this->_createAvatar($user);
		            }
		        }
    		}
    	}
    }
    /**
     * upload user avatar
     */
    private function _createAvatar($user)
    {
        Moodspin_Log::log("getting info for user " . $user['login'] . "\n");
        try {
            $result = $this->_service->getUserInfo($user['login']);
            $imageUrl = $result->profile_image_url;
            if ($this->_avatarManager->isImageOriginal($imageUrl)) {
                // update status
                $modelStatuses = Model_Manager::getModel('Statuses');
                $modelUsersTwitter = Model_Manager::getModel('UsersTwitter');
                $modelStatuses->updateStatus(
                   $modelUsersTwitter->getUserIdByTwiterName($user['login']),
                   Model_Moods::EMPTY_MOOD_ID, '');
            }
        } catch (Exception $e) {
            Moodspin_Log::log("ERROR!!! getting info for user " . $user['login'] . " failed\n");
            return;
        }
        try {
            Moodspin_Log::log("upload avatar for user " . $user['login'] . "\n");
            $this->_avatarManager->saveOriginalAvatar($user['login'], $this->_avatarManager->getLargeImageUrl($imageUrl),'png',true);
        } catch (Exception $e) {
            Moodspin_Log::log("ERROR!!! upload avatar for user " . $user['login'] . " failed\n");
            Moodspin_Log::log($e->getMessage());
        }
    }
    /**
     * get users count
     */
    private function _getUsersCount()
    {
    	$query = $this->_db->select();
        $query->from(
                  array('ru' => 'report_users'),
                  array('usersCount' => new Zend_Db_Expr('COUNT(*)'))
              )
              ->where('creation_date IS NOT NULL');
        $result = $this->_db->fetchRow($query);
        return (int)$result['usersCount'];
    }
    /**
     * setup users who should have avatars
     */
    private function _setupUsers($offset = 1,$count = self::USERS_PER_PAGE)
    {
        $users = array();
        
        $query = $this->_db->select();
        $query->from(array('ru' => 'report_users'), array('twitter_id','login'))
              ->where('creation_date IS NOT NULL')
              ->limitPage($offset,$count);
        $users = $this->_db->fetchAll($query);
        $this->_users = $users;
    }
}