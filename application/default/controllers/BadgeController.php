<?php
class BadgeController extends Moodspin_Controller_Action_Json
{
    
    public function indexAction()
    {
        $username = $this->_getParam('username');
        if($username == null){
            $this->view->data = array('error');
            return ;
        }
        
        $userModel = Model_Manager::getModel('UsersTwitter');
        $statusesModel = Model_Manager::getModel('Statuses');
        
        $user = $userModel->fetchRow($userModel->select()->where('name=?',$username));

        if($user == null){
            $this->view->data = array('error');
            return ;
        }
        
        $status = $statusesModel->fetchRow($statusesModel->select()->where('user_id=?',$user->user_id)->order('date DESC'));
        
        $this->view->data = array('user'=>$user->toArray(), 'status'=>$status->toArray());
    }
    
}