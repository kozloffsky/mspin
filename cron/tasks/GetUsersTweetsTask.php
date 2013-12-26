<?php
/**
 * Get users tweets from twitter
 * and save them in db
 * if they're not there
 * 
 * @author Dmitry Gordienko <dmitry.gordienko@gmail.com>
 * @version 1.0
 */
class GetUsersTweetsTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    protected $_db;
    protected $_modelStatuses;
    protected $_tmpTblName = 'tmp_users_tweets';
    
    protected $_tweeterService;
    // by tweeter API
    private $_tweetsCount = 200;
    private $_count;
    private $_perPage = 100;
    
    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
        $this->_tweeterService = Moodspin_Service::getInstance()->getService('Twitter');
    }
    
    public function run()
    {
        try {
	        $this->_createTmpTable();
	        $this->_getUsersCount();
	        
	        if ($this->_count) {
	            $twitter  = Moodspin_Service::getInstance()->getService('Twitter');
	            
	            $pages = ceil($this->_count / $this->_perPage);
	            
	            for ($i = 1; $i <= $pages; $i++) {
	                try {
		                $users = $this->_getUsers($i);
			            foreach ($users as $user) {
			                try {
	                            $tweets = $this->_getUserTweets($user['twitter_id']);
	                            if ($tweets) {
	                                $this->_modelStatuses = Model_Manager::getModel('Statuses');
	                                foreach ($tweets as $tweet) {
	                                    try {
			                                // moodspin in tweet source
			                                if (preg_match("~moodspin~is",$tweet->source)) {
			                                    $this->getLogger()->info("From moodspin : " . var_export($tweet,true));
			                                // we got this tweet in db
			                                } elseif (true === $this->_isTweetExist($user['id'],$tweet->created_at)) {
			                                    $this->getLogger()->info("Status exists : " . var_export($tweet,true));
			                                // insert new tweet
			                                } else {
			                                    $created = new Zend_Date(strtotime($tweet->created_at));
			                                    $data = array(
                                                    'message' => $tweet->text,
                                                    'user_id' => $user['id'],
			                                        'mood_id' => Model_Moods::EMPTY_MOOD_ID,
			                                        'date'    => $created->toString('Y-M-d H:m:s')
			                                    );
			                                    $data['mood_id'] = $this->_findIdForMood($data);
			                                    $this->_createNewStatus($data);
			                                    $this->getLogger()->info("Inserted new status : " . var_export($data,true));
	                                        }
	                                    } catch(Exception $e) {
			                                $this->getLogger()->info("Can't get tweets : " . $e->getMessage());
			                                continue;
			                            }
	                                }
	                            }
			                } catch(Exception $e) {
	                            $this->getLogger()->info("Can't get tweets : " . $e->getMessage());
	                            continue;
	                        }
			            }
		            } catch(Exception $e) {
		                $this->getLogger()->info("Can't get tweets : " . $e->getMessage());
		                continue;
		            }
                }
	            $this->_dropTmpTable();
	        }
        } catch(Exception $e) {
            $this->getLogger()->info("Can't get tweets : " . $e->getMessage());
        }
    }
    /**
     * get user tweets from twitter
     * 
     * @param int $userId
     */
    private function _getUserTweets($userId)
    {
        return $this->_tweeterService
            ->getUserTimeline($userId,$this->_tweetsCount);
    }
    /**
     * save status to db
     * 
     * @param string $data
     */
    private function _createNewStatus($data)
    {
        $row = $this->_modelStatuses->createRow($data)->save();
    }
    /**
     * check if tweet already exists in db
     * 
     * @param int $userId
     * @param string $tweetDate
     */
    private function _isTweetExist($userId,$tweetDate)
    {
        return $this->_modelStatuses->isTweetExist($userId,$tweetDate);
    }
    /**
     * count users in tenporary table
     */
    private function _getUsersCount()
    {
        $query = $this->_db->select()
                      ->from(
                            array('ttu' => $this->_tmpTblName),
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
                      ->from(array($this->_tmpTblName))
                      ->limitPage($offset,$this->_perPage);        
        
        return $this->_db->fetchAll($query);
    }
    /**
     * create temporary table
     */
    private function _createTmpTable()
    {
        $this->_dropTmpTable();
        $query = "CREATE TABLE `{$this->_tmpTblName}` SELECT `u`.`id`, `ut`.`twitter_id` FROM `users_twitter` AS `ut` LEFT JOIN `users` AS `u` ON `u`.`id` = `ut`.`user_id` WHERE `u`.`creation_date` IS NOT NULL;";
        $this->_db->query($query);
    }
    /**
     * drop temporary table
     */
    private function _dropTmpTable()
    {
        $query = "DROP TABLE IF EXISTS `{$this->_tmpTblName}`;";
        $this->_db->query($query);
    }
    
    private function _findIdForMood($mood) {
        return $this->_modelStatuses->findIdForMood($mood);
    }

}