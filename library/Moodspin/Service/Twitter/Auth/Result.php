<?php
class Moodspin_Service_Twitter_Auth_Result
{
    
    protected $_data;
    
    protected $_tokens;
    
    public function __construct($data, $tokens)
    {
        Moodspin_Log::log(var_export($data->screen_name, true));
        $this->_data = $data;
        $this->_tokens = $tokens;
    }
    
    public function getData()
    {
        return $this->_data;   
    }
    
    public function getToken()
    {
        return $this->_tokens->oauth_token;
    }
    
    public function getTokenSecret()
    {
        return $this->_tokens->oauth_token_secret;
    }
    
}
?>