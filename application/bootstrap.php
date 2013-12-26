<?php
/**
 * MoodSpin Project
 *
 * @author
 * @version
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    // to init something use <code>protected _initSomething()</code>
    protected function _initLoaders ()
    {
        $loader = new Zend_Loader_Autoloader_Resource(
            array(
                'basePath' => APPLICATION_PATH , 
                'namespace' => ''
            ));

        $loader->addResourceTypes(
            array(
                'dbtable' => array(
                    'namespace' => 'Model_DbTable' ,
                     'path' => 'models/DbTable'
                ) ,
                 'form' => array(
                     'namespace' => 'Form' , 
                     'path' => 'forms'
                ),
                'model' => array(
                    'namespace' => 'Model' , 
                    'path' => 'models'
                ) , 
                'plugin' => array(
                    'namespace' => 'Plugin' , 
                    'path' => 'plugins'
                ) , 
                'service' => array(
                    'namespace' => 'Service' , 
                    'path' => 'services'
                ) , 
                'viewhelper' => array(
                    'namespace' => 'View_Helper' ,
                    'path' => 'views/helpers') , 'viewfilter' => array('namespace' => 'View_Filter' , 'path' => 'views/filters')));
        $loader->setDefaultResourceType('model');

        Zend_Loader_Autoloader::getInstance()->registerNamespace('Moodspin');
    }
    
    protected function _initTranslations ()
    {
        $locale = new Zend_Locale();
        $locale->setLocale('en_US');
        Zend_Registry::set('Zend_Locale', $locale);
        $translate = new Zend_Translate('ini', APPLICATION_PATH . '/../locale', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        Zend_Registry::set('Zend_Translate', $translate);
        date_default_timezone_set('America/Los_Angeles');
        setlocale(LC_ALL, 'en_US');
    }
    
    protected function _initHelpers ()
    {
        $this->bootstrap('View');
        $this->getResource('View')->addHelperPath('Moodspin/View/Helper', 'Moodspin_View_Helper');
        Zend_Controller_Action_HelperBroker::addPrefix('Moodspin_Action_Helper');
    }
    
    protected function _initServices ()
    {
        require_once ('Moodspin/Service.php');
        Moodspin_Service::getInstance()->useService('Twitter_Api');
        Moodspin_Service::getInstance()->useService('Twitter');
    }
    
    protected function _initPlugins()
    {
        require_once ('Moodspin/Controller/Plugin/ClearStatus.php');
        Zend_Controller_Front::getInstance()->registerPlugin(new Moodspin_Controller_Plugin_ClearStatus());
    }

}
