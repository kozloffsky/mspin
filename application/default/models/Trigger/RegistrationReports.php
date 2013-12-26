<?php
require_once ('Moodspin/Db/Table/Trigger.php');

class Model_Trigger_RegistrationReports extends Moodspin_Db_Table_Trigger
{
    
    protected function userInsert($data)
    {
        if($data->creation_date == null){
            return;
        }
        
        $this->_reportRegistration();
        
    }
    
    protected function userUpdate($data)
    {
        $creationDate = $data->creation_date;
        if ($creationDate == null) {
        	return;
        }        
        
        $usersModel=Model_Manager::getModel('Users');
        $oldUser = $usersModel->fetchRow($usersModel->select()->where('id=?',$data->id));
        
        if($oldUser->creation_date != null){
            return;
        }
        
        $this->_reportRegistration();
    }
    
    /**
     * Enter description here...
     *
     * @return Zend_Db_Table_Row
     */
    protected function _getReportForToday(){
        $nowExpr = new Zend_Db_Expr('DATE(NOW())');
        
        $reportModel = Model_Manager::getModel('ReportRegistrations');
        $report = $reportModel->fetchRow($reportModel->select()->where('date=?',$nowExpr));
        
        $db = Zend_Db_Table::getDefaultAdapter();
        $max = $db->fetchRow('SELECT MAX(r.registered_total) m FROM report_registrations r;');
        
        if($report == null){
            $report = $reportModel->createRow();
            $report->date = $nowExpr;
            $report->registered_total = (int)$max['m'];
            $report->registered_today = 0;
        }
        
        return $report;
    }
    
    protected function _reportRegistration()
    {
        $report = $this->_getReportForToday();
        
        $report->registered_today+=1;
        $report->registered_total+=1;
        $report->save();
    }
    
}