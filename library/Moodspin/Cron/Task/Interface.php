<?php
interface Moodspin_Cron_Task_Interface
{
    function run();
    function setLogger(Zend_Log $logger);
}
?>