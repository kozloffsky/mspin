<?php
/**
 * Adapter for admin authorization
 * 
 * Usage: 
 * 
 * <code>
 * // in controller
 * Zend_Auth::getInstance()->authenticate(new Moodspin_Auth_AdminAdapter("adminpassword"));
 * </code>
 *
 */
class Moodspin_Auth_AdminAdapter implements Zend_Auth_Adapter_Interface
{
    /**
     * Password to validate
     *
     * @var string
     */
    protected $_password;
    
    /**
     * Admin password for authentification. stored in config.
     *
     * @var string
     */
    protected static $_origPasswod;
    
    /**
     * Static setter for admin password. Shoud be setted during configuration 
     *
     * @param unknown_type $value
     */
    public static function setOriginalPassword ($value)
    {
        self::$_origPasswod = $value;
    }
    
    /**
     * Constructroe
     *
     * @param string $password - password to validate
     */
    public function __construct ($password)
    {
        $this->_password = $password;
    }
    
    /**
     * Implementation of authorization.
     *
     * @return Zend_Auth_Result
     */
    public function authenticate ()
    {
        if ($this->_password == self::$_origPasswod) {
            return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, new Moodspin_AdminUser());
        }
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
    }
}
?>