<?php
class Moodspin_Bootstrap_Resource_Mailer extends Zend_Application_Resource_ResourceAbstract
{
      const OPTION_TRANSPORT    = 'transport';
      const OPTION_HOST         = 'host';
      const OPTION_PORT         = 'port';
      const OPTION_ENC          = 'encryption';
      const OPTION_LIB          = 'libPath';
      const OPTION_USER         = 'user';
      const OPTION_PASS         = 'password';
      const OPTION_BATCH_SIZE   = 'batchSize';

    
      public $_explicitType = "mailer";
      
      public function init()
      {
          $mailer = Moodspin_Mailer::getInstance();
          foreach ($this->_options as $key => $value) {
              
              switch ($key){
                  case self::OPTION_TRANSPORT:
                      $mailer->setTransport($value);
                      break;
                      
                  case self::OPTION_HOST:
                      $mailer->setHost($value);
                      break;

                  case self::OPTION_PORT:
                      $mailer->setPort($value);
                      break;
                      
                  case self::OPTION_ENC:
                      $mailer->setEncription($value);
                      break;
                      
                  case self::OPTION_LIB:
                      $mailer->setMailerPath($value);
                      break;

                  case self::OPTION_USER:
                      $mailer->setUserName($value);
                      break;
                      
                  case self::OPTION_PASS:
                      $mailer->setPassword($value);
                      break;

                  case self::OPTION_BATCH_SIZE:
                      $mailer->setBatchSize($value);
                      break;
              }
              
          }
      }
    
}
?>