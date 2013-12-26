<?php
class PrepareAlertsTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    const BASE_URL = "http://moodspin.com";
    const NETWORK_THEY_FOLLOW = 1;
    const NETWORK_I_FOLLOW = 2;
    const NETWORK_BOTH_FOLLOW = 3;
    
    const USERS_LIMIT = 1000;
    // 1 day for cron task
    const CRON_PERIOD = 1;
    
    protected $_db;
    protected $_modelUsersSettings;
    protected $_mailerTable = 'mailer_tasks';
    private $_view;
    private $_moodsHash;
    /**
	  *  ["userId"]
	  *      ["email"] = string
	  *      ["moods"] = array
	  *      ["includeType"] = int(1|2|3)
      */
    protected $_users;
    
    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
        $this->_modelUsersSettings = Model_Manager::getModel('UsersSettings');
        $this->_view = new Zend_View();
        $this->_view->setScriptPath(APPLICATION_PATH . '/../../cron/tasks/alerts');
    }
    
    public function run()
    {
        try {
            $users = $this->_getUsersSettings();
            $this->_parseUsers($users);
            unset($users);
            $this->_getMoodsHash();
            foreach ($this->_users as $key => $val) {
                $statuses = $this->_getStatusesByMoodIds(
                    $val['moods'],
                    $val['twitter_id'],
                    $val['includeType']
                );
                if ($statuses) {
	                $this->_createAlerts(
	                    array(
	                        'login'   => $val['login'],
	                        'email'   => $val['email'],
	                        'user_id' => $key
	                    ),
	                    $statuses);
                }/* else {
                    $this->_updateLastSentDate($key);
                }*/
            }
        } catch(Exception $e) {
            $this->getLogger()->info("error : " . $e->getMessage());
        }
    }
    /**
     * 
     * @param array $user
     * @param array $statuses
     */
    private function _createAlerts($user,$statuses)
    {
        $orderedStatuses = array();
        foreach ($statuses as $status) {
        	$orderedStatuses[$status['mood']][] = array('login' => $status['login'], 'latest_status_message' => $status['latest_status_message']);
        }
        unset($statuses);
        $tmp = '';
        foreach ($orderedStatuses as $key => $val) {
            $tmp .= $this->_makeLetter($user,$key,$val);
        }
        $body = $this->_view(
            'body',
            array(
                'userLogin' => $user['login'],
                'data'      => $tmp,
                'baseUrl'   => self::BASE_URL
            )
        );
        $this->_insertToDb($user['email'],$body);
        $this->_updateLastSentDate($user['user_id']);
    }
    /**
     * 
     * @param array $user [login,email,user_id]
     * @param int $mood
     * @param array $users [email]
     */
    private function _makeLetter($user,$mood,$users)
    {
        $moodName = $this->_moodsHash[$mood];
        $data = array();
        $data['moodName'] = $moodName;
        $data['moodId'] = $mood;
        $data['users'] = array();
        foreach ($users as $userLoop) {
            $tmp = array();
            $tmp['login'] = $userLoop['login'];
            $tmp['latest_status_message'] = $userLoop['latest_status_message'];
            //$tmp['href'] = self::BASE_URL . '/' . $userLoop['login'];
            $tmp['imgSrc'] =
                Moodspin_Avatar_Manager::getInstance()->getModifiedAvatarUrlForUser($userLoop['login']);
            $data['users'][] = $tmp;
        }
        return $this->_getDataLoop($data);
    }
    /**
     * get parsed template for users
     * with defined mood
     * $data[
     *     moodname
     *     users[
     *         login
     *         href
     *         imgSrc
     *     ]
     * ]
     * 
     * @param array $data
     * @return string
     */
    private function _getDataLoop($data)
    {
        return $this->_view('body-loop',array('data' => $data, 'baseUrl' => self::BASE_URL));
    }
    /**
     * store emails to db
     * for mailer
     * 
     * @param string $userEmail
     * @param string $letter
     */
    private function _insertToDb($userEmail,$letter)
    {
        $this->_db->insert(
            $this->_mailerTable,
            array(
                'recipient' => $userEmail,
                'body' => $letter
            )
        );
    }
    /**
     * update lastSendDate to user with userId
     * 
     * @param int $userId
     */
    private function _updateLastSentDate($userId)
    {
        $this->_modelUsersSettings->updateLastSentDate($userId);
    }
    /**
     * get parsed template $template
     * with params $data
     * from Zend_View
     * 
     * @param string $template
     * @param array $data
     * @return string
     */
    private function _view($template,$data=null)
    {
        if ($data) {
            foreach ($data as $name => $var ) {
                $this->_view->$name = $var;
            }
        }
        return $this->_view->render($template . '.phtml');
    }
    /**
     * get all moods array
     * store all moods names and ids
     * in $this->_moodsHash array
     */
    private function _getMoodsHash()
    {
        if ($this->_users) {
            $moodsModel = Model_Manager::getModel('Moods');
            $moods = $moodsModel->fetchAll();
            if ($moods) {
                foreach ($moods->toArray() as $mood) {
                    $this->_moodsHash[$mood['id']] = $mood['name'];
                }
            }
        }
    }
    /**
     * get users that have moods
     * described in moodIds
     * to user with userId
     * that are following|followers|both
     * 
     * @param array $moodIds
     * @param int $userId
     * @param int $followings [1|2|3]
     * @return Zend_Db_Rowset
     */
    private function _getStatusesByMoodIds($moodIds,$userId,$followings=self::NETWORK_BOTH_FOLLOW)
    {
        if (!$moodIds) {
            return null;
        }
        if (is_array($moodIds)) {
            $moodStr = "`latest_mood_id` IN (" . implode(",",$moodIds) . ")";
        } else {
            $moodStr = "`latest_mood_id` = {$moodIds}";
        }
        
        switch ($followings) {
            case self::NETWORK_THEY_FOLLOW:
                $where1 = "`ru`.`twitter_id` = `utf`.`twitter_id`";
                $where2 = "`utf`.`follower_id` = {$userId}";
                break;
            case self::NETWORK_I_FOLLOW:
                $where1 = "`ru`.`twitter_id` = `utf`.`follower_id`";
                $where2 = "`utf`.`twitter_id` = {$userId}";
                break;
            case self::NETWORK_BOTH_FOLLOW:
            default:
                $where1 = "`ru`.`twitter_id` = `utf`.`follower_id` OR
                         `ru`.`twitter_id` = `utf`.`twitter_id`";
                $where2 = "`utf`.`follower_id` = {$userId} OR
                         `utf`.`twitter_id` = {$userId}";
                break;
        }
        $sql = "
            SELECT
                ru.login,
                ru.latest_mood_id as mood,
                ru.latest_status_message
            FROM
                (
                SELECT
                    ru.login,
                    ru.latest_mood_id,
                    ru.twitter_id,
                    ru.latest_status_message
                FROM
                    `users_twitter_followers` AS `utf`
                    JOIN `report_users` AS `ru` ON
                        {$where1}
                WHERE
                    {$where2}
                GROUP BY
                    `ru`.`id`
                ) AS `ru`
            WHERE
                {$moodStr}
               AND `ru`.`twitter_id` != {$userId}
        ";
        return $this->_db->fetchAll($sql,null,Zend_Db::FETCH_ASSOC);
    }
    /**
     * parse users array so to get
     * plain array of users settings
     * 
     * @param array $users
     */
    private function _parseUsers($users)
    {
        $this->_users = array();
        $modelUsersTwitter = Model_Manager::getModel('UsersTwitter');
        foreach ($users as $user) {
            try {
	            if ($user['name'] == 'alerts') {                
	                $val = unserialize($user['value']);
	                $this->_users[$user['user_id']]['moods'] = $val['moods'];
	                $this->_users[$user['user_id']]['includeType'] = $val['includeType'];
	                
	            }
	            if ($user['name'] == 'emailField') {
	                $this->_users[$user['user_id']]['email'] = $user['value'];
	            }
	            if (!isset($this->_users[$user['user_id']]['login'])) {
	                $this->_users[$user['user_id']]['login'] = $this->_getLoginToUsersInfo($user['user_id']);
	                $this->_users[$user['user_id']]['twitter_id'] = 
                        $modelUsersTwitter->getTwitterIdByUserId($user['user_id']);
	            }
            } catch (Exception $e) {
                Moodspin_Log::Log("Error : " . $e->getMessage());
                continue;
            }
        }
    }
    /**
     * get user login by their userId
     * from users table
     * 
     * @param int $userId
     * @return string
     */
    private function _getLoginToUsersInfo($userId)
    {
        $model = Model_Manager::getModel('Users');
        return $model->find((int)$userId)->current()->login;
    }
    /**
     * get users settings from DB
     * we use only users which
     * had checked "alerts" from moodspin
     * and who had lastSentDate within a range
     */
    private function _getUsersSettings()
    {
        $query = "
			SELECT
			    `user_id`,`name`,`value`
			FROM
			    `users_settings`
			    WHERE `user_id` IN (
			        SELECT
			            `user_id`
			        FROM
			            `users_settings`
			        WHERE
			            `name` = 'lastSendDate' AND DATEDIFF(DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'),`value`) > " . self::CRON_PERIOD . "
			            AND user_id IN
			            (
			                SELECT
			                    `user_id`
			                FROM
			                    `users_settings`
			                WHERE
			                    user_id IN (
			                        SELECT
			                            user_id
			                        FROM
			                            users_settings
			                        WHERE
			                            `name` = 'sendAlerts' AND `value` = 1
			                )
			            )
			) AND (
			    `name` = 'emailField' OR
			    `name` = 'alerts'
			) LIMIT " . self::USERS_LIMIT;
        return $this->_db->query($query)->fetchAll();
    }
    
}