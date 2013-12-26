<?php

/**
protected $_triggers = array(
    'update'=>array(
        array('class'=>'Model_StatusReportTrigger','callback'=>'updateStatus'),
    ),
);
*/

class Moodspin_Db_Table extends Moodspin_Db_Trigger_Table 
{
    protected $_isI18n = true;
    protected $_i18nTable;
    protected $_localeRefColumn = 'locale';
    protected $_identityRefColumn = 'parent';
    protected $_locale;
    
    protected $_triggers;

    public function init ()
    {
        if ($this->_isI18n == false)
            return;

        $this->_dependentTables = array($this->_i18nTable);
        $i18nTableName = $this->_name . '_i18n';
        $i18nTableReferenceMap = array('Parent' => array('columns' => $this->_identityRefColumn , 'refTableClass' => get_class($this) , 'refColumns' => 'id'));
        $i18nTable = new Moodspin_Db_Table(array('name' => $i18nTableName , 'referenceMap' => $i18nTableReferenceMap , 'isI18n' => false));

        array_push($this->_dependentTables, $i18nTable);

        $this->setRowClass('Moodspin_Db_Table_Row');
        $this->_i18nTable = $i18nTable;
    }
    
    public function __construct (array $config = null)
    {
        if (isset($config['isI18n'])) {
            $this->_isI18n = $config['isI18n'];
        }
        parent::__construct($config);
    }
    
    public function getI18nFields ()
    {
        return $this->_i18nFields;
    }
    
    public function getI18nTable ()
    {
        return $this->_i18nTable;
    }
    
    public function getLocaleRefColumn ()
    {
        return $this->_localeRefColumn;
    }
    
    public function getIdentityRefColumn ()
    {
        return $this->_identityRefColumn;
    }
}