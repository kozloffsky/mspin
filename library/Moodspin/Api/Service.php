<?php
abstract class Moodspin_Api_Service
{
    
    const PARAM_USERNAME = 'username';
    const PARAM_PASSWORD = 'password';
    
    public function __call($name, $arguments){
        $name = '_'.$name;
        if(!method_exists($this, $name)){
            throw new Exception('Method ' . $name . ' does not exists');
        }
        
        try{
            $result = call_user_func_array(array($this,$name), $arguments);
        }catch (Exception $e){
            Moodspin_Log::log(var_export($e,true));
            return new Moodspin_Api_Response(Moodspin_Api_Response::STATUS_ERROR, $e->getCode());
        }
        
        if($result != null && $result > 0 ){
            return new Moodspin_Api_Response(Moodspin_Api_Response::STATUS_ERROR, $result);
        }elseif ($result instanceof Moodspin_Api_Response){
            return $result;
        }else{
            return new Moodspin_Api_Response(Moodspin_Api_Response::STATUS_OK);
        }
    }
    
    /**
     * 
     *
     * @return Moodspin_Service
     */
    public function getService(){
        return Moodspin_Service::getInstance();
    }
    
    protected function validateAuthData($params){
        if (false == isset($params[self::PARAM_USERNAME]) || false == isset($params[self::PARAM_PASSWORD])) {
            return false;
        }

        
        $result = $this->getService()->getService('Twitter')->oldLogin($params[self::PARAM_USERNAME], $params[self::PARAM_PASSWORD]);
        if (isset($result->error)){
            return false;
        }
        
        return $result;
    }
    
}
?>