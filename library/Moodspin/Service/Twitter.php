<?php
require_once ('EpiCurl.php');
require_once ('EpiOAuth.php');
require_once ('EpiTwitter.php');

class Moodspin_Service_Twitter implements Moodspin_Service_Interface
{
    
    const RESULTS_PER_PAGE = 50;
    
    protected $_customerKey; // = 'kFWudpTv3iUTobtKcYsq7A';
    protected $_customerSecret; // = 'XaUd7XJQE78IePu1mnb6b8yK9UDCdjpaA6b38TDg';
    protected $_connector;
    protected $_secureConnector;
    protected $_nextPageId = null;
    protected $_session;
    protected $_systemFollowerKey;
    protected $_systemFollowerSecret;
    
    protected $_systemJoinUsername;
    protected $_systemJoinPassword;
    protected $_systemFollowerUsername;
    protected $_systemFollowerPassword;

    protected $_systemUserId;

    protected $_followingIds;
    protected $_followersIds;

    public function __construct ()
    {
        $this->_session = new Zend_Session_Namespace('Twitter_Service');
        //$this->_session->token = null;
        $this->_connector = new EpiTwitter();
    }
    
    public function init ()
    {
        if ($this->_session->token != null) {
            $token = $this->_session->token;
            $this->_secureConnector = new EpiTwitter($this->_customerKey, $this->_customerSecret, $token->oauth_token, $token->oauth_token_secret);
            Moodspin_Log::log('User Tokens are token : ' . $token->oauth_token . ' and secret :' . $token->oauth_token_secret);
        } else {
            $this->_secureConnector = new EpiTwitter($this->_customerKey, $this->_customerSecret);
        }
    }

    public function getSystemId() {
        if(empty($this->_systemUserId)) {
            $info = $this->getUserInfo($this->_systemFollowerUsername);
            $this->_systemUserId = $info->id;
        }

        return $this->_systemUserId;
    }
    
    public function getLatestStatuses ($mood)
    {
        $results = $this->_connector->search_search(array('q' => $mood->search_criteria , 'rpp' => self::RESULTS_PER_PAGE , 'page' => 1));
        return $results->results;
    }
    
    public function authenticate ($oauthToken)
    {
        $this->_session->token = null;
        //$this->_secureConnector = new EpiTwitter($this->_customerKey, $this->_customerSecret);
        $this->_secureConnector->setToken($oauthToken);
        $token = $this->_secureConnector->getAccessToken();
        $this->_secureConnector->setToken($token->oauth_token, $token->oauth_token_secret);
        $this->_session->token = $token;
        return new Moodspin_Service_Twitter_Auth_Result($this->_secureConnector->get_accountVerify_credentials(), $token);
    }
    
    public function getAuthenticateUrl ()
    {
        return $this->_secureConnector->getAuthenticateUrl();
    }
    
    /**
     * Logout user
     *
     */
    public function logout ()
    {
        $this->_session->token = null;
        $this->_secureConnector->post_accountEndSession();
    }
    
    public function setCustomerKey ($key)
    {
        $this->_customerKey = $key;
    }

    public function setCustomerSecret ($secret)
    {
        $this->_customerSecret = $secret;
    }
    
    public function updateStatus ($identity, $text, $imagePath)
    {
        $response = true;

        if (trim($text) != '') {
            // added url to user moodline on moodspin host
            $str = " " . BASE_URL . "/" . $identity->getScreenName();
            if (false === stripos($text, $str)) {
                $text .= $str;
            }
            $response = $this->_secureConnector->post_statusesUpdate(array('status' => urldecode($text)));
        }

        try {
            $this->updateProfileImage($imagePath);
        } catch (Exception $e) {
            Moodspin_Log::log('Fail to upload image. exception is' . $e);
            $response = null;
        }

        return $response;
    }
    
    public function getUserTimeline($userId,$count=200)
    {
        return $this->_connector->getUserTimeline($userId,$count);
    }
    
    public function updateProfileImage ($path)
    {
        Moodspin_Log::log("Updating image from path " . $path);
        $res = $this->_secureConnector->post_accountUpdate_profile_image(array('@image' => '@' . $path));
        Moodspin_Log::log('Upload result is ' . var_export($res->status, true));
    }
    
    public function setSystemFollowerKey ($value)
    {
        $this->_systemFollowerKey = $value;
    }
    
    public function getSystemFollowerKey ()
    {
        return $this->_systemFollowerKey;
    }

    public function setSystemFollowerSecret ($value)
    {
        $this->_systemFollowerSecret = $value;
    }

    public function getSystemFollowerSecret ()
    {
        return $this->_systemFollowerSecret;
    }

    public function isFollowingSystem($twitterId) {
        $ids = $this->getFollowingIds($twitterId);
        $systemId = $this->getSystemId();

        if(in_array($systemId, $ids)) {
            Moodspin_Log::log('User ' . $twitterId . ' already follows moodspin');
            return true;
        }

        return false;
    }

    public function followingSystem($twitterId) {
        if (!$this->isFollowingSystem($twitterId)) {
            $result = $this->_secureConnector->follow($this->getSystemId());
            Moodspin_Log::log('>>> Following system ' . $this->getSystemId());
            try {
                $result->status;
                Moodspin_Log::log('<<< Following result is ' . var_export($result, true));
            } catch (Exception $e) {
                Moodspin_Log::log($e->getMessage());
            }

            return $result->status;
        }

        return true;
    }

    public function unFollowingSystem($twitterId) {
        if($this->isFollowingSystem($twitterId)) {
            $result = $this->_secureConnector->unfollow($this->getSystemId());

            try {
                Moodspin_Log::log('Unfollowing result is ' . var_export($result->status, true));
            } catch (Exception $e) {
                Moodspin_Log::log($e->getMessage());
            }

            return $result->status;
        }

        return true;
    }

    public function followSystem ($twitterId)
    {
        $connector = new Zend_Service_Twitter(
            $this->_systemFollowerUsername, $this->_systemFollowerPassword);
        Moodspin_Log::log('create friendship systemFollowerUsername >>>');
        $result = $connector->friendship->create($twitterId);
        $result->status;
        Moodspin_Log::log('<<< Result is ' . var_export($result, true));
        unset($connector);
        $connector = new Zend_Service_Twitter(
            $this->_systemJoinUsername, $this->_systemJoinPassword);
        Moodspin_Log::log('create friendship systemJoinUsername >>>');
        $result = $connector->friendship->create($twitterId);
        $result->status;
        Moodspin_Log::log('<<< Result is ' . var_export($result, true));
    }
    
    public function unFollowSystem ($twitterId)
    {
        if (is_null($this->_systemFollowerKey) || is_null($this->_systemFollowerSecret)) {
            return;
        }
        
        $connector = new EpiTwitter($this->_customerKey, $this->_customerSecret, $this->_systemFollowerKey, $this->_systemFollowerSecret);
        
        Moodspin_Log::log('_sysKeyIs ' . $this->_customerKey . ' ' . $this->_customerSecret . ' ' . $this->_systemFollowerKey . ' ' . $this->_systemFollowerSecret);
        Moodspin_Log::log("Removing user " . $twitterId . " from friends of system follower");
        
        $result = $connector->unfollow($twitterId);
        
        try {
            Moodspin_Log::log('Result is ' . var_export($result->status, true));
        } catch (Exception $e) {
            Moodspin_Log::log($e->getMessage());
        }
        
        $result->status;
        
        unset($connector);
        $connector = new Zend_Service_Twitter($this->_systemJoinUsername, $this->_systemJoinPassword);
        $result = $connector->friendship->destroy($twitterId);
        
        return $result->status;
    }
    
    public function reTweetBySystem($twitterName, $message)
    {
        $connector = new Zend_Service_Twitter($this->_systemJoinUsername, $this->_systemJoinPassword);

        Moodspin_Log::log("Sending message to user " . $twitterName . ", message : " . $message);
        
        $text = "@" . $twitterName . " " . $message;
        $response = $connector->status->update(urldecode($text));

        return $response->getStatus();
    }
    
    public function getUserInfo($screenName, $isId = false)
    {
        return $this->_connector->getUserInfo($screenName, $isId);
    }
    
    public function getFollowersIds($twitterId)
    {
        if(empty($this->_followersIds)) {
            $followersIds = $this->_secureConnector->get_followersIds(array('user_id' => $twitterId));

            foreach ($followersIds as $id) {
                $this->_followersIds[] = $id;
            }
        }

        return $this->_followersIds;
    }

    /**
     * 
     * @param array $user [twitter_id,token,secret]
     */
    public function getSecureFollowersIds($user)
    {
    	$connector = new EpiTwitter(
    	   $this->_customerKey, $this->_customerSecret,
    	   $user['token'], $user['secret']
        );

        $result = $connector->get_followersIds(array('user_id'=>$user['twitter_id']));
        $followersIds = array();
        foreach ($result as $id) {
            $followersIds[] = $id;
        }
        return $followersIds;
    }
    
    /**
     * 
     * @param array $user [twitter_id,token,secret]
     */
    public function getSecureFollowingsIds($user)
    {
        $connector = new EpiTwitter(
           $this->_customerKey, $this->_customerSecret,
           $user['token'], $user['secret']
        );

        $result = $connector->get_friendsIds(array('user_id'=>$user['twitter_id']));
        $followingIds = array();
        foreach ($result as $id) {
            $followingIds[] = $id;
        }

        return $followingIds;
    }
    
    public function getFollowingIds($twitterId)
    {
        if(empty($this->_followingIds)) {
            $followingIds = $this->_secureConnector->get_friendsIds(array('user_id' => $twitterId));

            foreach ($followingIds as $id) {
                $this->_followingIds[] = $id;
            }
        }

        return $this->_followingIds;
    }
    
    public function oldLogin($username, $password){
        $service = new Zend_Service_Twitter($username, $password);
        return $service->accountVerifyCredentials();
    }
    
    public function setSystemJoinUsername($_username) {
        $this->_systemJoinUsername = $_username;
    }
    
    public function setSystemJoinPassword($_password) {
        $this->_systemJoinPassword = $_password;
    }
    
    public function setSystemFollowerUsername($_username) {
        $this->_systemFollowerUsername = $_username;
    }
    
	public function setSystemFollowerPassword($_password) {
		$this->_systemFollowerPassword = $_password;
	}

    public function search($query, $params) {
        $search = new Zend_Service_Twitter_Search();
        return $search->search($query, $params);
    }

    /**
     * Return service name
     *
     * @return string
     */
    public function getServiceName() {
        return 'Twitter';
    }

}