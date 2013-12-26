<?php
require_once ('Moodspin/Api/Service.php');
class Moodspin_Api_Service_Status extends Moodspin_Api_Service
{
    
    protected function _update($params){
       if(($userInfo = $this->validateAuthData($params)) === false){
           return Moodspin_Api_Response::CODE_WRONG_CREDENTIALS;
       }
       
       if( false == isset($params['user_id']) ||
           false == isset($params['mood_id']) ||
           false == isset($params['message']) ||
           false == isset($params['date'])    ||
           false == strtotime($params['date'])||
           strtotime($params['date']) < strtotime('-1 day') ||
           strtotime($params['date']) >= strtotime('+1 day')
       ){
           return Moodspin_Api_Response::CODE_WRONG_PARAMS;
       }

       $usersModel = new Model_UsersTwitter();
       $users = $usersModel->fetchAll($usersModel->select()->where('twitter_id=?',$params['user_id']));
       
       if($users->count() == 0){
           return Moodspin_Api_Response::CODE_USER_NOT_FOUND;
       }

       $date = date('Y-m-d H:i:s',strtotime($params['date']));
       
       $userId = $users->current()->user_id;

       $usersModel = new Model_Users();
       $usersModel->useIPhone($userId, true);

       $statuses = new Model_Statuses();
       
       $statuses->updateStatus($userId, $params['mood_id'], $params['message'], $date);
       
       Moodspin_Avatar_Manager::getInstance()->addMoodForUser($params[self::PARAM_USERNAME], $params['mood_id']);
        
    }
    
}