<?php
class ApiController extends Moodspin_Api_Controller   
{
    
    public function doAction(){
        
        $config = array(
            'accept_schemes' => 'basic',
            'realm'          => 'moodspin.com',
            'digest_domains' => '/',    
            'nonce_timeout'  => 3600,
        );
        
        $service = $this->getRequest()->getParam(1);
        $method  = $this->getRequest()->getParam(2);
        
        try{
            $this->_setData($this->getService(ucfirst($service))->{$method}($this->getRequest()->getParams()));
        }catch (Exception $e){
            $this->_setData(new Moodspin_Api_Response(Moodspin_Api_Response::STATUS_ERROR,1));
        }
    }
    
}


class TwitterResolver implements Zend_Auth_Adapter_Http_Resolver_Interface {
    
    public function resolve($username, $realm){
        $result = Moodspin_Service::getInstance()->getService('Twitter')->oldLogin($username, $realm);
        Moodspin_Log::log('Attempt to login to api with username '.$username . ' and password '.$realm);
        if (isset($result->error)){
            return false;
        }
        Moodspin_Log::log('Attempt success');
        return true;
        //return $result;
    }
}

class Moodspin_Auth_Adapter_Http_Twitter extends Zend_Auth_Adapter_Http {
    
/**
     * Basic Authentication
     *
     * @param  string $header Client's Authorization header
     * @throws Zend_Auth_Adapter_Exception
     * @return Zend_Auth_Result
     */
    protected function _basicAuth($header)
    {
        Moodspin_Log::log('We Got user to auth!');
        if (empty($header)) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('The value of the client Authorization header is required');
        }
        if (empty($this->_basicResolver)) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('A basicResolver object must be set before doing Basic '
                                                . 'authentication');
        }

        // Decode the Authorization header
        $auth = substr($header, strlen('Basic '));
        $auth = base64_decode($auth);
        if (!$auth) {
            /**
             * @see Zend_Auth_Adapter_Exception
             */
            require_once 'Zend/Auth/Adapter/Exception.php';
            throw new Zend_Auth_Adapter_Exception('Unable to base64_decode Authorization header value');
        }

        // See ZF-1253. Validate the credentials the same way the digest
        // implementation does. If invalid credentials are detected,
        // re-challenge the client.
        if (!ctype_print($auth)) {
            return $this->_challengeClient();
        }
        // Fix for ZF-1515: Now re-challenges on empty username or password
        $creds = array_filter(explode(':', $auth));
        if (count($creds) != 2) {
            return $this->_challengeClient();
        }

        Moodspin_Log::log('We Got user to auth!');
        $password = $this->_basicResolver->resolve($creds[0], $creds[1]);
        if ($password == true) {
            $identity = array('username'=>$creds[0], 'realm'=>$this->_realm);
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $identity);
        } else {
            return $this->_challengeClient();
        }
    }
    
}