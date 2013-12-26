<?php
if (!defined('APPLICATION_PATH')) {
    define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application/default'));
}

if (!defined('APPLICATION_ENV')) {
    define('APPLICATION_ENV', 'cron');
}

set_include_path(implode(PATH_SEPARATOR, array (
    realpath(APPLICATION_PATH . '/../../library'),
    get_include_path(),
)));

require_once 'Moodspin/Cron.php';

require_once 'Moodspin/Config/Ini.php';

$conf = new Moodspin_Config_Ini(APPLICATION_PATH . '/../configs');

$conf->setSection(APPLICATION_ENV);

$application = new Moodspin_Cron(APPLICATION_ENV, $conf);
$application->bootstrap()->run();
