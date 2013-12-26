<?php
/**
 * Bootstraps classes which controlls handles functionality
 *
 */
class Moodspin_Bootstrap_Resource_Admin extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * resource name in config
     *
     * @var string
     */
    public $_explicitType = "admin";
    /**
     * Bootstrap
     *
     */
    public function init ()
    {
        if (! isset($this->_options['password'])) {
            throw new Exception('Admin password shoud be! please reinstall system');
        }
        Moodspin_Auth_AdminAdapter::setOriginalPassword($this->_options['password']);
    }
}
?>