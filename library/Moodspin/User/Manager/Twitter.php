<?php

class Moodspin_User_Manager_Twitter extends Moodspin_User_Manager_Abstract {

    /**
     * Instance of Moodspin_User_Manager_Twitter
     *
     * @var Moodspin_User_Manager_Twitter
     */
    protected static $_instance;

    /**
     * Instance of Moodspin_Service_Twitter
     *
     * @var Moodspin_Service_Twitter
     */
    protected $_serviceInstance;

    /**
     * Get manager instance
     *
     * @return Moodspin_User_Manager_Twitter
     */
    public static function getInstance()
    {
        if(self::$_instance == null){
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Get twitter service
     *
     * @return Moodspin_Service_Twitter
     */
    public function getServiceInstance() {
        if($this->_serviceInstance === null) {
            $this->_serviceInstance = Moodspin_Service::getInstance()->getService('Twitter');
        }

        return $this->_serviceInstance;
    }

    /**
     * @return Model_UsersTwitter
     */
    public function getUsersServiceModel()
    {
        if($this->_usersServiceModel == null){
            $this->_usersServiceModel = Model_Manager::getModel('UsersTwitter');
        }

        return $this->_usersServiceModel;
    }

	/**
     * @return Model_UsersFollowers
     */
    public function getUsersServiceFriendsModel()
    {
        if($this->_usersServiceFriendsModel == null){
            $this->_usersServiceFriendsModel = Model_Manager::getModel('UsersTwitterFollowers');
        }

        return $this->_usersServiceFriendsModel;
    }

    /**
     * Creates new or loads existing user
     *
     * @param array $data User data
     * @return Moodspin_User
     */
    public function createUser(Array $data)
    {
        $identity = new Moodspin_User($data);
        $usersModel = new Model_Users();
        $usersTwitterModel = new Model_UsersTwitter();
        $twitterUser = $usersTwitterModel->isUserIdRegistered($identity->getId());
        Moodspin_Log::log('User`s twitter id is ' . $identity->getId());
        
        if ($twitterUser->user_id == 0) {
            $user = $usersModel->createUser($identity->getScreenName());
            Moodspin_Log::log('Creating new user to database ' . $identity->getScreenName());
        } else {
            Moodspin_Log::log('Updating user ' . $identity->getScreenName() . ' in database');
            $user = $usersModel->changeLogin($identity->getScreenName(), $twitterUser->user_id);
            $this->registerUser($user);
        }

        $identity->userId           = $user->id;

        $this->loadAvatarImage($identity);

        $twitterUser->user_id       = $user->id;
        $twitterUser->twitter_id    = $identity->getId();
        $twitterUser->bio           = $identity->getBio();
        $twitterUser->name          = $identity->getScreenName();
        $twitterUser->followers_num = $identity->getFollowersCount();
        $twitterUser->following_num = $identity->getFollowingCount();
        $twitterUser->url           = $identity->getUrl();
        $twitterUser->avatar        = $identity->getImageUrl();
        $twitterUser->save();

        $this->fetchUserSettings($identity);
        $this->saveTokens($data, $identity);
        $this->followSystem($identity);

        return $identity;
    }

    private function saveTokens($data, Moodspin_User $identity) {
        if (isset($data['tokens'])) {
            $model = Model_Manager::getModel('UsersTwitterLoggedIn');
            
            $row = $model->fetchRow('twitter_id = ' . (int)$identity->getId());
            if ($row) {
                $row->delete();
            }
            
            $row = $model->createRow();
            $row->twitter_id = $identity->getId();
            $row->token = $data['tokens']->getToken();
            $row->secret = $data['tokens']->getTokenSecret();
            $row->save();
        }
    }

    /**
     * Return url of user's profile image
     *
     * @param Moodspin_User $identity User
     * @return string
     */
    public function getProfileImageUrl(Moodspin_User $identity)
    {
        $result = $this->getServiceInstance()->getUserInfo($identity->getScreenName());
        return (string)$result->profile_image_url;
    }

    /**
     * Load and bind to user avatar image
     *
     * @param Moodspin_User $identity
     */
    private function loadAvatarImage(Moodspin_User $identity)
    {
        if ($identity->imageUrl == null && $identity->screenName != null) {
            $identity->imageUrl = $this->getProfileImageUrl($identity);
        }

        $avatarManager = Moodspin_Avatar_Manager::getInstance();

        if ($identity->imageUrl != null) {
            try {
                $identity->imageUrl = $avatarManager
                    ->saveOriginalAvatar(
                        $identity, $this->getLargeImage($identity));
                if ($avatarManager->isImageOriginal($identity->profileImage)) {
                    // update status
                    $modelStatuses = Model_Manager::getModel('Statuses');
                    $modelStatuses->updateStatus(
                       $identity->getUserId(),
                       Model_Moods::EMPTY_MOOD_ID, '');
                }
            } catch (Exception $e) {
                Moodspin_Log::log('ERROR load avatar image!! '.$e->getMessage());
                $identity->imageUrl = $avatarManager->getDefaultAvatarUrl();
            }
        } else {
            $identity->imageUrl = $avatarManager->getDefaultAvatarUrl();
        }
    }

    private function followSystem(Moodspin_User $identity)
    {
        $service = $this->getServiceInstance();
        $model = Model_Manager::getModel('Users');

        try {
            $service->followingSystem($identity->getId());
            $model->updateUserFollowingMoodspin($identity->getUserId(), 1);
        } catch(Exception $e) {
            Moodspin_Log::log('followingSystem : Error in Moodspin_User_Manager_Twitter::followSystem() line '.__LINE__.'. Exception: ' . $e->getMessage());
        }

        try {
            $service->followSystem($identity->getId());
        } catch(Exception $e) {
            Moodspin_Log::log('followSystem : Error in Moodspin_User_Manager_Twitter::followSystem() line '.__LINE__.'. Exception: ' . $e->getMessage());
        }
    }

    /**
     * Get big image url (or path) from user's info
     *
     * @param Moodspin_User $identity
     */
    public function getLargeImage(Moodspin_User $identity)
    {
        return preg_replace("~normal(\.\w{3,4})$~is", "bigger\\1", $identity->getImageUrl());
    }
}