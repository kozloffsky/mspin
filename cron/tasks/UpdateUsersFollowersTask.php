<?php
/**
 * update users followers after
 * user has logged in
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 1.0
 */
class UpdateUsersFollowersTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    const USERS_PER_PAGE = 100;
    
    protected $_db;
    protected $_service;
    protected $_modelFollowers;
    
    private $_count;
    private $_users;

    /**
     * 
     */
    public function __construct()
    {
        $this->_db = Model_Manager::getModel('UsersTwitterLoggedIn');
    }
    /**
     * run task
     */
    public function run()
    {
        try {
        	$this->_count = $this->_db->getUsersCount();
            if ($this->_count) {
                $this->_service = Moodspin_Service::getInstance()->getService('Twitter');
                $this->_modelFollowers = Model_Manager::getModel('UsersTwitterFollowers');
                
                $pages = ceil($this->_count / self::USERS_PER_PAGE);
                
                for ($i = 1; $i <= $pages; $i++) {
                	try {
	                    $this->_users = $this->_db->getUsers($i,self::USERS_PER_PAGE);
		                foreach ($this->_users as $user) {
		                	try {
		                		$userArray = $user->toArray();
                                $this->_getUsersFollowersAndFollowings($userArray);
                                $this->_db->deleteByTwitterId($userArray['twitter_id']);
		                	} catch (Exception $e) {
		                        $this->getLogger()->info("Can't update users : " . $e->getMessage());
		                    }
		                }
                	} catch (Exception $e) {
                		$this->getLogger()->info("Can't update users : " . $e->getMessage());
                	}
                }
            }
        } catch (Exception $e) {
            $this->getLogger()->info("Can't update users : " . $e->getMessage());
        }
    }
    private function _getUsersFollowersAndFollowings($user)
    {
    	$twitterId = $user['twitter_id'];
    	// get followers
        $followersIds = $this->_service->getSecureFollowersIds($user);
        // delete existing followers
        $where = $this->_modelFollowers->getAdapter()->quoteInto('twitter_id = ?',$twitterId);
        $this->_modelFollowers->delete($where);
        $where = $this->_modelFollowers->getAdapter()->quoteInto('follower_id = ?',$twitterId);
        $this->_modelFollowers->delete($where);
        // insert new followers
        echo "Followers:\n";
        foreach ($followersIds as $id) {
        	echo $id . "\n";
            $row = $this->_modelFollowers->createRow();
            $row->twitter_id  = $twitterId;
            $row->follower_id = $id;
            try {
                $row->save();
            } catch (Exception $e) {
                continue;
            }
        }
        unset($followersIds);
        // get followings
        $followingIds = $this->_service->getSecureFollowingsIds($user);
        echo "Friends:\n";
        foreach ($followingIds as $id){
        	echo $id . "\n";
            $row = $this->_modelFollowers->createRow();
            $row->follower_id = $twitterId;
            $row->twitter_id  = $id;
            try {
                $row->save();
            } catch(Exception $e) {
                continue;
            }
        }
        unset($followingIds);
    }    
}