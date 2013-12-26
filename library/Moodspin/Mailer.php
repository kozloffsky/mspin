<?php
class Moodspin_Mailer
{
    
    protected static $_instance;
    /**
     * Singleton instance of the Mailer
     *
     * @return Moodspin_Mailer
     */
    public static function getInstance()
    {
        if (self::$_instance == null){
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    const TRANSPORT_SMTP = 'smtp';
    const TRANSPORT_SENDMAIL = 'sendmail';
    const TRANSPORT_PHPMAIL = 'phpmail';
    
    const KILLER = 'kill.mailer@moodspin.com';
    
    protected $_transport;
    protected $_transportObj;
    protected $_host;
    protected $_port;
    protected $_encryption;
    protected $_userName;
    protected $_password;
    protected $_mailerPath;
    protected $_batchSize;

    protected $_swift;
    
    public function getNewMessage()
    {
        $this->getSwift();
        return Swift_Message::newInstance();
    }
    
    public function send($message)
    {
        return $this->_swift->send($message);
    }
    
    public function getSwift()
    {
        if ($this->_swift == null) {
        	$this->_initSwift();
        }
        
        return $this->_swift;
    }
    
    protected function _initSwift()
    {
        if (
            $this->_mailerPath == null || 
            is_dir($this->_mailerPath) == false || 
            is_file($this->_mailerPath . '/swift_required.php') == false
           ) {
        	throw new Exception('Path to swift mailer is invalid');
        }
        
        require_once $this->_mailerPath . '/swift_required.php';
        
        if (! class_exists('Swift_Mailer')) {
        	throw new Exception('Swift mailer was not found');
        }
        
        $this->_swift = Swift_Mailer::newInstance($this->getTransportObject());
    }
    
    public function getTransportObject()
    {
        if ($this->_transportObj == null) {
            switch($this->_transport){
                case self::TRANSPORT_PHPMAIL:
                default:
                    $this->_transportObj = $this->_createPHPMailTransport();
                    break;
                
                case self::TRANSPORT_SENDMAIL:
                    $this->_transportObj = $this->_createSendmailTransport();
                    break;
                
                case self::TRANSPORT_SMTP:
                    $this->_transportObj = $this->_createSmtpTransport();
                    break;                    
            }
            
        }
        return $this->_transportObj;
    }
    
    protected function _createPHPMailTransport()
    {
        return Swift_MailTransport::newInstance();
    }
    
    protected function _createSendmailTransport()
    {
        return Swift_SendmailTransport::newInstance();
    }
    
    protected function _createSmtpTransport()
    {
        $transport = Swift_SmtpTransport::newInstance();
        if ($this->_host != null) {
            $transport->setHost($this->_host);
        }
        if ($this->_port != null) {
            $transport->setPort($this->_port);
        }
        if ($this->_encryption != null) {
            $transport->setEncryption($this->_encryption);
        }
        if ($this->_userName != null) {
            $transport->setUsername($this->_userName);
        }
        if ($this->_password != null) {
            $transport->setPassword($this->_password);
        }
        return $transport;
    }
    
    public function getTransport()
    {
        if ($this->_transport == null) {
            $this->_transport = self::TRANSPORT_PHPMAIL;
        }
        return $this->_transport;
    }
    
    /**
     * set transport
     *
     * @param string $value
     * @return Moodspin_Mailer
     */
    public function setTransport($value)
    {
        $this->_transport = $value;
        return $this;
    }
    
    public function getHost()
    {
        return $this->_host;
    }
    
    /**
     * set host
     *
     * @param string $value
     * @return Moodspin_Mailer
     */
    public function setHost($value)
    {
        $this->_host = $value;
        return $this;
    }
    
    public function getPort()
    {
        return $this->_port;
    }
    
    /**
     * set port
     *
     * @param int $value
     * @return Moodspin_Mailer
     */
    public function setPort($value)
    {
        $this->_port = $value;
        return $this;
    }
    
    public function getEncription()
    {
        return $this->_encryption;
    }
    
    /**
     * set encryption
     *
     * @param string $value
     * @return Moodspin_Mailer
     */
    public function setEncription($value)
    {
        $this->_encryption = $value;
        return $this;
    }
    
    public function getUserName()
    {
        return $this->_userName;
    }
    
    /**
     * set username
     *
     * @param string $value
     * @return Moodspin_Mailer
     */
    public function setUserName($value)
    {
        $this->_userName = $value;
        return $this;
    }
    
    public function getPassword()
    {
        return $this->_password;
    }
    
    public function setPassword($value)
    {
        $this->_password = $value;
        return $this;   
    }
    
    /**
     * set mailer path
     *
     * @param string $value
     * @return Moodspin_Mailer
     */
    public function setMailerPath($value)
    {
        $this->_mailerPath = $value;
        return $this;
    }

    /**
     * Set batch size
     *
     * @param  int $size
     * @return void
     */
    public function setBatchSize($size)
    {
        $this->_batchSize = (int)$size;
    }

    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize()
    {
        return $this->_batchSize;
    }
    
}