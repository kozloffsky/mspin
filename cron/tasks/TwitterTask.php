<?php

class TwitterTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    
    public function __construct()
    {
        $model = Model_Statuses::getInstance();
    }
    
    public function run()
    {        
        $moodsModel = new Model_Moods();
        
        $moods = $moodsModel->fetchAll();
        
        $twitter  = Moodspin_Service::getInstance()->getService('Twitter');
        
        foreach ($moods as $mood) {
        	if ($mood->search_criteria == "") {
        		continue;
        	}
        	
            $statuses = $twitter->getLatestStatuses($mood);
            
            foreach ($statuses as $status) {
            	if($this->_parseStatus($status,$mood) == false){
            	    //break;
            	}
            }
        }
        
    }
    
    protected function _parseStatus($status, $mood)
    {
        try{
            $_user = Moodspin_Service::getInstance()->getService('Twitter')->getUserInfo($status->from_user);
            $twitterId = $_user->id;
            $avatar = $_user->profile_image_url;
        }catch (Exception $e){
            
            $this->getLogger()->info("Username ".$status->from_user." was cached by twitter and changed by owner, igniring");
            
            return;
        }
        $updateStatusFlag = false;
        $created = new Zend_Date(strtotime($status->created_at));
        
        $modelUsersTwitter = new Model_UsersTwitter();
        $modelUsers = new Model_Users();
        
        $twitterUser = $modelUsersTwitter->fetchRow($modelUsersTwitter->select()->where('twitter_id=?',$twitterId));
      
        if ($twitterUser != null){
            $this->getLogger()->info("User ".$twitterUser->user_id." was found!");
            $userId = $twitterUser->user_id;
        }else{
            $this->getLogger()->info("User was not found, creating new");
            $user = $modelUsers->createRow();
            $user->login = $status->from_user;
            $user->save(); 
            $userId = $user->id;
            
            $twitterUser = $modelUsersTwitter->createRow();
            $twitterUser->user_id = $user->id;
            $twitterUser->twitter_id = $twitterId;
            $updateStatusFlag = true;
        }
        
        $twitterUser->name = $status->from_user;
        $twitterUser->avatar = $avatar;
        $twitterUser->followers_num = $_user->followers_count;
        $twitterUser->following_num = $_user->friends_count;
        $twitterUser->url = $_user->url;
        $twitterUser->bio = $_user->description;
        $twitterUser->save();
        try{
            Moodspin_Avatar_Manager::getInstance()->saveAvatarToModified($status->from_user, $avatar);
            if($updateStatusFlag){
                $row = Model_Manager::getModel('Statuses')->createRow();
                $row->mood_id = 1;
                $row->user_id = $userId;
                $row->date = new Zend_Db_Expr('NOW()');
            }
        }catch (Exception $e){
            $this->getLogger()->warn('Cant create user image. Twitter did not update url');
        }
        
        $oldStatus = Model_Statuses::getInstance()->fetchRow(
                Model_Statuses::getInstance()->select()
                ->where('user_id=?',$userId)
                ->where('mood_id='.$mood->id)
                ->where('date=?',$created->toString('Y-M-d H:m:s'))
            );
        
        if ($oldStatus != null){
            $this->getLogger()->info('Status was already registered');
            return false;
        }
            
        $newStatus = Model_Statuses::getInstance()->createRow();
        $newStatus->message = htmlspecialchars_decode($status->text);
        $newStatus->user_id = $userId;
        $newStatus->date = $created->toString('Y-M-d H:m:s');
        $newStatus->mood_id = $mood->id;
        $newStatus->save();
        
        $this->getLogger()->info('status was created at '.$status->created_at.' or '.var_export($created->toArray(), true) . ' or '. $created->toString('Y-M-d H:m:s'));
        
        $this->getLogger()->info('new status created!');
        
        return true;
    }
    
}


