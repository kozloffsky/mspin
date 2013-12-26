<?php
/**
 * Update users UserTwitter table
 * with info from twitter
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 1.0
 */
class UpdateUsersTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    protected $_db;
    private $_count;
    private $_perPage = 100;
    
    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
    }
    
    public function run()
    {
        try {
	        $this->_createTmpTable();
	        $this->_getUsersCount();
	        
	        if ($this->_count) {
	            $twitter  = Moodspin_Service::getInstance()->getService('Twitter');
	            $modelUsersTwitter = new Model_UsersTwitter();
	            
	            $pages = ceil($this->_count / $this->_perPage);
	            
	            for ($i = 1; $i <= $pages; $i++) {
	                try {
		                $users = $this->_getUsers($i);
		                
			            foreach ($users as $user) {
			                try {
	                            $_user = Moodspin_Service::getInstance()->getService('Twitter')->getUserInfo($user['twitter_id'],true);
			                
				                $twitterUser = $modelUsersTwitter->fetchRow($modelUsersTwitter->select()->where('twitter_id=?',$user['twitter_id']));
				                
				                $twitterUser->followers_num = $_user->followers_count;
				                $twitterUser->following_num = $_user->friends_count;
				                $twitterUser->url = $_user->url;
				                $twitterUser->bio = $_user->description;
				                
				                $twitterUser->save();
				                
				                unset($twitterUser);
				                unset($_user);
			                } catch(Exception $e) {
	                            $this->getLogger()->info("Can't update twitter_users : " . $e->getMessage());
	                            continue;
	                        }
			            }
			            unset($users);
		            } catch(Exception $e) {
		                $this->getLogger()->info("Can't update twitter_users : " . $e->getMessage());
		                continue;
		            }
                }
	            $this->_dropTmpTable();
	        }
        } catch(Exception $e) {
            $this->getLogger()->info("Can't update twitter_users : " . $e->getMessage());
        }
    }
    /**
     * get users count from temporary table
     */
    private function _getUsersCount()
    {
        $query = $this->_db->select()
                      ->from(
                            array('ttu' => 'temp_twitter_users'),
                            array('count' => new Zend_Db_Expr('count(ttu.twitter_id)'))
                      );        
        $this->_count = (int)$this->_db->fetchRow($query,null,ZEND_DB::FETCH_OBJ)->count;
    }
    /**
     * get users from temporary table by offset
     * 
     * @param int $offset
     * @return Zend_Db_Table_Rowset
     */
    private function _getUsers($offset=1)
    {
        $query = $this->_db->select()
                      ->from(
	                        array('temp_twitter_users'),
	                        array('twitter_id')
                      )
                      ->limitPage($offset,$this->_perPage);        
        
        return $this->_db->fetchAll($query);
    }
    /**
     * create temporary table
     */
    private function _createTmpTable()
    {
        $this->_dropTmpTable();
        $query = "CREATE TABLE `temp_twitter_users` SELECT twitter_id FROM users_twitter WHERE NOT followers_num OR NOT following_num;";
        $this->_db->query($query);
    }
    /**
     * drop temporary table
     */
    private function _dropTmpTable()
    {
        $query = "DROP TABLE IF EXISTS `temp_twitter_users`;";
        $this->_db->query($query);
    }
}