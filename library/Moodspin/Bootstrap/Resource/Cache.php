<?php
/**
 * Bootstraps classes which controlls handles functionality
 * bootstrap memcached
 *
 */
class Moodspin_Bootstrap_Resource_Cache extends Zend_Application_Resource_ResourceAbstract {
    /**
     * resource name in config
     *
     * @var string
     */
    public $_explicitType = "cache";
    
    public static $enabled;
    public static $enabledFront;
    
    protected $_availableCacheTypes = array(
        'Zend_Cache_Backend_Memcached',
        'Zend_Cache_Backend_ZendServer_ShMem'
    );
    protected $_type;
    protected $_host;
    protected $_port;
    protected $_frontLifetime;
    protected $_shortLifetime;
    protected $_mediumLifetime;
    protected $_longLifetime;
    /**
     * Bootstrap
     *
     */
    public function init()
    {
        foreach ($this->_options as $key => $value) {
            switch ($key) {
                case 'enabled' :
                    self::$enabled = $value;
                    break;
                case 'enabledFront' :
                    self::$enabledFront = $value;
                    break;
                case 'type' :
                	if (!in_array($value,$this->_availableCacheTypes) ||
                	    !class_exists($value)) {
                		throw new Zend_Exception("Not supported cache type!");
                	}
                	$this->_type = $value;
                	break;
                case 'host' :
                    $this->_host = $value;
                    break;
                case 'port' :
                    $this->_port = $value;
                    break;
                case 'frontLifetime' :
                    $this->_frontLifetime = $value;
                    break;
                case 'shortLifetime' :
                    $this->_shortLifetime = $value;
                    break;
                case 'mediumLifetime' :
                    $this->_mediumLifetime = $value;
                    break;
                case 'longLifetime' :
                    $this->_longLifetime = $value;
                    break;
            }
        }
        Zend_Registry::set('cacheEnabled',self::$enabled);
        Zend_Registry::set('cacheFEEnabled',self::$enabledFront);
        if (self::$enabled) {
            $this->_initCache();
        }
        if (self::$enabledFront) {
            $this->_initCacheFront();
        }
    }
    /**
     * trying if memcache is available
     * @return void
     */
    protected function _isMemcacheLoaded()
    {
        if ($this->_type == 'Zend_Cache_Backend_Memcached' &&
            !extension_loaded('memcache')) {
            throw new Zend_Exception('memcache extension is not found.'); 
        }
    }
    /**
     * initialize frontend caching
     */
    protected function _initCacheFront()
    {
        $this->_isMemcacheLoaded();
        $cache = Zend_Cache::factory(
                             'Page',
                             'Zend_Cache_Backend_ZendServer_ShMem',
                             array('lifetime' => $this->_frontLifetime),
                             array(),
                             false,true);
        Zend_Registry::set('cacheFE', $cache);
    }
    /**
     * initialize caching
     */
    protected function _initCache()
    {
        $this->_isMemcacheLoaded();
            
        $cache = $this->_setCacheObjects($this->_shortLifetime,'cacheLong');
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        $this->_setCacheObjects($this->_shortLifetime,'cacheMedium');
        $this->_setCacheObjects($this->_shortLifetime,'cacheShort');
    }
    /**
     * setup cache objects
     * @param int $cacheLifetime
     * @param string $cacheName
     * @return Zend_Cache object
     */
    protected function _setCacheObjects($cacheLifetime,$cacheName)
    {
        $cacheHost     = $this->_host;
        $cachePort     = $this->_port;
        $frontendOptions = array(
            'lifetime'                => $cacheLifetime,
            'automatic_serialization' => true
        );
        $backendOptions = array(
            'doNotTestCacheValidity' => true
        );
        if ($this->_type == 'Zend_Cache_Backend_Memcached') {
	        $memcached = new Zend_Cache_Backend_Memcached(
	            array(
	                'servers' => array('host' => $cacheHost),
	                'port'    => $cachePort
	            )
	        );
            $cache = Zend_Cache::factory(
                'Core',
                $memcached,
                $frontendOptions,
                $backendOptions);
        } else {
        	$cache = Zend_Cache::factory(
                'Core',
                $this->_type,
                $frontendOptions,
                $backendOptions,
                false,
                true);
        }
        Zend_Registry::set($cacheName, $cache);
        return $cache;
    }
}
