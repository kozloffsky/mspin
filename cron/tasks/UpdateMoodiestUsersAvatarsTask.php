<?php
/**
 * Reupload avatars for moodiest users
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 1.0
 */
class UpdateMoodiestUsersAvatarsTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    const MOODIEST_PEOPLE_COUNT = 10;
    const TOP_PEOPLE_COUNT = 100;
    
	private $_db;
	private $_service;
	private $_avatarManager;
	private $_monthMoodiest;
	private $_lastMoodiest;
	
	private $_basePath;
	
    public function __construct()
    {
    	$this->_basePath = realpath(dirname(__FILE__)) . "/../public";
    	$this->_db = Zend_Db_Table::getDefaultAdapter();
    	$this->_service = Moodspin_Service::getInstance()->getService('Twitter');
    	$this->_avatarManager = Moodspin_Avatar_Manager::getInstance();
    }
    
    public function run()
    {
    	$this->_setupUsers();
    	if ($this->_monthMoodiest) {
    		foreach ($this->_monthMoodiest as $user) {
    			$filePath = $this->_basePath . $user['avatar'];
    			if (!file_exists($filePath)) {
    				$this->_createAvatar($user);
    			}
    		}
    	}
    	if ($this->_lastMoodiest) {
    		foreach ($this->_lastMoodiest as $user) {
    			$filePath = $this->_basePath . $user['avatar'];
    			if (!file_exists($filePath)) {
    				$this->_createAvatar($user);
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
	 * setup users who should have avatars
	 */
    private function _setupUsers()
    {
    	
    	$monthMoodiest = array();
        $lastMoodiest = array();
        
        $query = $this->_db->select();
        $query->from(array('ru' => 'report_users'),array('twitter_id','login'))
              ->where('creation_date IS NOT NULL')
              ->order('status_count DESC')
              ->limit(self::MOODIEST_PEOPLE_COUNT);
        $monthMoodiest = $this->_db->fetchAll($query);
        foreach ($monthMoodiest as &$user) {
            $avatar = Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($user['login']);
            $user['avatar'] = $avatar;
        }
        $this->_monthMoodiest = $monthMoodiest;
        
        $query = $this->_db->select();
        $query->from(array('ru' => 'report_users'),array('twitter_id','login'))
              ->where('creation_date IS NOT NULL')
              ->order('creation_date DESC')
              ->limit(self::TOP_PEOPLE_COUNT);
        $lastMoodiest = $this->_db->fetchAll($query);
        foreach ($lastMoodiest as &$user) {
            $avatar = Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($user['login']);
            $user['avatar'] = $avatar;
        }
        $this->_lastMoodiest = $lastMoodiest;
    }
}