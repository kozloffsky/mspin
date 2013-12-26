<?php
/**
 * Logger configurations
 *
 */
class Moodspin_Bootstrap_Resource_Logger extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * resource name in config
     *
     * @var string
     */
    public $_explicitType = "logger";
    
    /**
     * Bootstrap
     *
     */
    public function init ()
    {
        foreach ($this->_options as $key => $val) {
            switch ($key) {
                case 'active':
                    Moodspin_Log::setActive($val == "active");
                    break;
                case 'path':
                    Moodspin_Log::setPath($val);
                    break;
            }
        }
        
    }
}
?>