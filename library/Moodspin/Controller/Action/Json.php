<?php
require_once ('Zend/Controller/Action.php');
class Moodspin_Controller_Action_Json extends Zend_Controller_Action
{
    
    public function postDispatch(){
        $this->renderScript('_jsonResponse.phtml');
    }
    
    protected function _setData(Moodspin_Controller_Action_Json_Serializable $value){
      $this->view->data = $value->toArray();   
    }
    
}
?>