<?php
require_once ('Moodspin/Controller/Action/Json/Serializable.php');
class Moodspin_Api_Response implements Moodspin_Controller_Action_Json_Serializable
{
    protected $_status;
    protected $_code;
    
    const STATUS_OK    = "OK";
    const STATUS_ERROR = "ERROR";
    
    const CODE_UNKNOWN_ERROR = 1;//unknown error
    const CODE_WRONG_PARAMS = 2;// parameters a wrong
    const CODE_WRONG_CREDENTIALS = 3;//users login or password a wrong
    const CODE_USER_NOT_FOUND = 4;//User not found in database

    public function __construct($status, $code = null){
        $this->setStatus($status);
        $this->setCode($code);
    }
    
    public function setStatus($value){
        // TODO check value. shoud be one of statuses
        $this->_status = $value;
    }
    
    public function getStatus(){
        return $this->_status;
    }
    
    public function setCode($value){
        $this->_code = $value;
    }
    
    public function getCode(){
        return $this->_code;
    }
    
    public function toArray ()
    {
        $res = array();
        $res['status'] = $this->_status;
        if($this->_code != null){
            $res['code'] = $this->_code;
        }
        
        return $res;
    }
}
?>