<?php

class Moodspin_Api_Service_User extends Moodspin_Api_Service 
{
    
    const PARAM_TWITTER_ID = 'twitter_id';
    
    protected function _create($params)
    {
        if(false == isset($params[self::PARAM_TWITTER_ID])) {
            return Moodspin_Api_Response::CODE_WRONG_PARAMS;
        }

        if(!empty($params[self::PARAM_USERNAME]) && !empty($params[self::PARAM_PASSWORD]) && !$this->validateAuthData($params)) {
            return Moodspin_Api_Response::CODE_WRONG_CREDENTIALS;
        }

        $twitterId = $params[self::PARAM_TWITTER_ID];
        $data = Moodspin_Service::getInstance()->getService('Twitter_Api')->getUserInfo($twitterId, true);
       
        try {
            Moodspin_Log::log('Create new user from API');
            $userManager = Moodspin_User_Manager_Twitter_Api::getInstance();

            !empty($params[self::PARAM_USERNAME]) ? $userManager->setUsername($params[self::PARAM_USERNAME]) : false;
            !empty($params[self::PARAM_PASSWORD]) ? $userManager->setPassword($params[self::PARAM_PASSWORD]) : false;

            $res = $userManager->createUser(
                        array(
                            'id'                => $twitterId,
                            'bio'		        => $data->description,
                            'url'               => $data->url,
                            'screenName'        => $data->screen_name,
                            'followersCount'    => $data->followers_count,
                            'followingCount'    => $data->friends_count,
                            'profileImage'      => $data->profile_image_url,
                            'imageUrl'          => $data->profile_image_url,
                        ), true
                   );

            $usersModel = new Model_Users();
            $usersModel->useIPhone($res->userId, true);
            Moodspin_Log::log('API: creation result is successful');
        } catch (Exception $e) {
            Moodspin_Log::log('API: creation result is fail');
            Moodspin_Log::log('EROR!! '.$e->getMessage());
            return Moodspin_Api_Response::CODE_UNKNOWN_ERROR;
        }
        
        return 0;    
    }
}