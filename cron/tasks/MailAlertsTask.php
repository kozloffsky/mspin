<?php

require_once './mailer/swift_required.php';

class MailAlertsTask extends Moodspin_Cron_Task implements Moodspin_Cron_Task_Interface 
{
    
    /**
     * Send alerts to users class
     *
     * @var Swift_Mailer
     */
    protected $_mailer;
    
    const MAIL_FROM = "nobody@moodspin.com";
    const MAIL_SUBJECT = "Moodspin Alerts";
    
    public function run()
    {
        $this->_logger->info('Mailer started');

        $tasks = $this->_getTasks();
        foreach ($tasks as $task) {
            $this->_logger->info('found message for ' . $task->recipient);
            if ($task->recipient == Moodspin_Mailer::KILLER) {
                $task->delete();
                return;
            }
            if ($this->_send($task) != 0) {
                $task->delete();
            }
        }

        $this->_logger->info('Mailer stopped');
    }
    
    protected function _getTasks()
    {
        return Model_Manager::getModel('MailerTasks')->fetchAll(null, null, $this->getMailer()->getBatchSize());
    }
    
    protected function _send($task)
    {
        $message = $this->getMailer()->getNewMessage();
        $message->setBody($task->body,'text/html');
        $message->setTo(array($task->recipient));       
        $message->setFrom(array(self::MAIL_FROM));
        $message->setSubject(self::MAIL_SUBJECT);
        
        $this->_logger->info('Sending mail');
        $result = $this->getMailer()->send($message);
        $this->_logger->info('Mail send');
        
        return $result;
    }

    /**
     * Get mailer
     *
     * @return Moodspin_Mailer
     */
    public function getMailer()
    {
        if ($this->_mailer == null) {
        	$this->_mailer = Moodspin_Mailer::getInstance();
        }
        
        return $this->_mailer;
            
    }
}