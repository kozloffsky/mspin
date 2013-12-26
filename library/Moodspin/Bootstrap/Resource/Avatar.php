<?php
/**
 * Configurations of Moodspin_Avatar_Manager
 *
 */
class Moodspin_Bootstrap_Resource_Avatar extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * resource name in config
     *
     * @var string
     */
    public $_explicitType = "avatar";
    
    /**
     * Bootstrap
     *
     */
    public function init ()
    {
        $manager = Moodspin_Avatar_Manager::getInstance();
        
        foreach ($this->_options as $key => $value) {
            switch ($key) {
                case 'basePath':
                    $manager->setBasePath($value);
                    break;
                case 'baseUrl':
                    $manager->setBaseUrl($value);
                    break;
                case 'defaultAvatarFileName':
                    $manager->setDefaultAvatarFileName($value);
                    break;
                case 'originalAvatarsDirectoryName':
                    $manager->setOriginalAvatarsDirectoryName($value);
                    break;
                case 'modifiedAvatarsDirectoryName':
                    $manager->setModifiedAvatarsDirectoryName($value);
                    break;
                default:
                    break;
            }
        }
        
    }
}
?>