<?php
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Tool/Framework/Loader/IncludePathLoader/RecursiveFilterIterator.php';

class Moodspin_Config_Ini extends Zend_Config_Ini
{
    public function __construct ($filename, $section = null, $options = array())
    {
        //FIXME Refactor it!

        if (is_dir($filename)) {
            $filterAllowDirectoryPattern = '*(/|\\\\).ini';
            $filterDenyDirectoryPattern = '.*(/|\\\\).svn';

            $iterator = new RecursiveDirectoryIterator($filename);
            $iniArray = array();
            
            foreach ($iterator as $item) {
                if ($item->getType() == 'file') {
                    $iniArray = $this->deepMerge($iniArray, $this->_loadIniFile($item->getRealPath()));
                }
            }
            
            $dataArray = array();
            foreach ($iniArray as $sectionName => $sectionData) {
                if (! is_array($sectionData)) {
                    $dataArray = array_merge_recursive($dataArray, $this->_processKey(array(), $sectionName, $sectionData));
                } else {
                    $dataArray[$sectionName] = $this->_processSection($iniArray, $sectionName);
                }
            }
            
            $this->_loadedSection = null;
            $this->_index = 0;
            $this->_data = array();
            foreach ($dataArray as $key => $value) {
                if (is_array($value)) {
                    $this->_data[$key] = new Zend_Config($value, $this->_allowModifications);
                } else {
                    $this->_data[$key] = $value;
                }
            }
            
            return;
        }
        parent::__construct($filename, $section, $options);
    }
    
    protected $_section;
    
    public function setSection ($section)
    {
        $this->_section = $section;
    }
    
    public function toArray ()
    {
        if (isset($this->_data[$this->_section])) {
            $data = parent::toArray();
            return $data[$this->_section];
        }
        return null;
    }
    protected function deepMerge ($array1, $array2)
    {
        $keys = array_keys($array1);
        $keys = array_unique(array_merge($keys, array_keys($array2)));
        foreach ($keys as $key) {
            if (array_key_exists($key, $array1) && array_key_exists($key, $array2)) {
                
                if (is_array($array1[$key]) && is_array($array2[$key])) {
                    $res[$key] = $this->deepMerge($array1[$key], $array2[$key]);
                } else {
                    $res[$key] = $array2[$key];
                }
                
            } elseif (array_key_exists($key, $array1)) {
                $res[$key] = $array1[$key];
            } elseif (array_key_exists($key, $array2)) {
                $res[$key] = $array2[$key];
            }
        }
        
        return $res;
    }
}