<?php
class Moodspin_Db_Table_Row extends Moodspin_Db_Trigger_Row
{
    protected $_locale;
    protected $_i18nData;
    
    
    public function __construct (array $config = array())
    {
        parent::__construct($config);
        if (isset($this->_data[$this->_primary[1]])) {
            $i18n = $this->_table->getI18nTable();
            $parent = $this->_table->getIdentityRefColumn();
            $select = $i18n->select();
            $select->where($parent . '=?', $this->{$this->_primary[1]});
            $this->_i18nData = $this->_normalizeI18nData($i18n->fetchAll($select));
        }
    }
    
    public function init ()
    {
        /*if (! ($this->_table instanceof Moodspin_Db_Table)) {
            //TODO Create Archer_Db_Table_Exception and throw it
            throw new Exception('Moodspin_Db_Table_Row shoud be used only with Moodspin_Db_Table');
        }*/
    }
    
    public function __get ($columnName)
    {
        $columnName = $this->_transformColumn($columnName);
        
        if (array_key_exists($columnName, $this->_data)) {
            return $this->_data[$columnName];
        } else {
            $current = $this->_getLocaleCode(Zend_Locale::getBrowser());
            $default = $this->_getLocaleCode(Zend_Locale::getDefault());
            $i18nRow = $this->getForLocale($current);

            if ($i18nRow != null) {
                if (isset($i18nRow[$columnName])) {
                    return $i18nRow[$columnName];
                }
            } else {
                $i18nRow = $this->getForLocale($default);
                if ($i18nRow != null) {
                    if (isset($i18nRow[$columnName])) {
                        return $i18nRow[$columnName];
                    }
                }
            }
            
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is not in the row");
        }

    }
    public function __set ($columnName, $value)
    {
        $columnName = $this->_transformColumn($columnName);

        if (! array_key_exists($columnName, $this->_data)) {
            $current = $this->_getLocaleCode(Zend_Locale::getBrowser());

            if (! isset($this->_i18nData[$current])) {
                $row = $this->_table->getI18nTable()->createRow();
                $row[$this->_table->getLocaleRefColumn()] = $current;
                $row[$this->_table->getIdentityRefColumn()] = $this[$this->_primary[1]];
                $this->_i18nData[$current] = $row;
            }

            $row = $this->_i18nData[$current];
            $row[$columnName] = $value;
            return;
        } else {
            require_once 'Zend/Db/Table/Row/Exception.php';
            throw new Zend_Db_Table_Row_Exception("Specified column \"$columnName\" is not in the row");
        }
        
        $this->_data[$columnName] = $value;
        $this->_modifiedFields[$columnName] = true;
    }
    
    public function getForLocale ($locale = 'en')
    {
        if (isset($this->_i18nData[$locale])) {
            return $this->_i18nData[$locale];
        }
        return null;
    }

    protected function _getLocaleCode ($locale)
    {
        $locale = array_keys($locale);
        if (sizeof($locale) == 1) {
            return $locale[0];
        } else {
            return $locale[1];
        }
    }

    protected function _normalizeI18nData (Zend_Db_Table_Rowset $data)
    {
        $refField = $this->_table->getLocaleRefColumn();
        $i18nData = array();
        foreach ($data as $row) {
            if (isset($row[$refField])) {
                $i18nData[$row[$refField]] = $row;
            }
        }
        return $i18nData;
    }

    public function save ()
    {
        if (sizeof($this->_i18nData) == 0) {
            require_once ('Moodspin/Db/Table/Row/Exception.php');
            throw new Moodspin_Db_Table_Row_Exception('Needs at least one translation!');
        }
        parent::save();
        foreach ($this->_i18nData as $locale => $row) {
            $row->save();
        }
    }
       
    protected function _postDelete ()
    {
        parent::_postDelete();
        foreach ($this->_i18nData as $key => $row) {
            try {
                $row->delete();
            } catch (Exception $e) {}
        }
        $this->_i18nData = array();
        $this->_trigger('postDelete');
    }

}