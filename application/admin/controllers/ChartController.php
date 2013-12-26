<?php
require_once 'OFC/OFC_Chart.php';

/**
 * Admin_ChartController - shows chart with numberes of regirtered users
 *
 * @author
 * @version
 */

class Admin_ChartController extends Moodspin_Action_AdminController 
{
    const DATE_FORMAT = 'YYYY-MM-dd';
    const PERIOD_DURATION = 28;
    const STEP = 1;
    
    public function preDispatch()
    {
        parent::preDispatch();
        $layout = Zend_Layout::getMvcInstance();
        $layout->getView()
               ->setScriptPath(
                   array_merge(
                       $layout->getView()->getScriptPaths(),
                       array(
                           APPLICATION_PATH . '/views/scripts',
                           APPLICATION_PATH . '/../admin/views/scripts',
                       )
                   )
        );
        $layout->getView()->assign('messageType','admin');
    }
    
    /**
     * Displays chart with number of registered users
     */
    public function indexAction()
    {
        $form = $this->_getForm();
        if ($this->_request->isPost()) {
            if ($form->isValid($this->_getAllParams())) {
                ;
            }
        }
        $this->view->form = $form;
        
        $values = $form->getValues();
    }
    
    public function moodsAction()
    {
        
    }
    
    public function moodsDataAction()
    {
        $this->view->layout()->disableLayout();
        $modelMoods = new Model_Moods();
        $moods = $modelMoods->getUsersMoodsReport();
        $this->view->moods = $moods;
    }
    
    /**
     * Returns data with number of registered users for chart
     */
    public function usersDataAction()
    {
        $this->view->layout()->disableLayout();

        $modelUsers = new Model_Users();

        // default date range
        $fromDate = Zend_Date::now()->addDay(self::STEP * (2 - self::PERIOD_DURATION));
        $toDate = Zend_Date::now()->addDay(self::STEP * (3 - self::PERIOD_DURATION));

        $total = 0;
        $labels = array();
        $people = array();
        $totalPeople = array();

        // date range from user form
        $fromDateVal = $this->_getParam('fromDate',$fromDate->toString(self::DATE_FORMAT));
        if (!$fromDateVal)
            $fromDateVal = $fromDate->toString(self::DATE_FORMAT);
        $toDateVal = $this->_getParam('toDate',$toDate->toString(self::DATE_FORMAT));
        if (!$toDateVal)
            $toDateVal = $toDate->toString(self::DATE_FORMAT);

        $fromDateZend = new Zend_Date();
        $fromDateZend->setDate($fromDateVal,self::DATE_FORMAT);
        $toDateZend = new Zend_Date();
        $toDateZend->setDate($toDateVal,self::DATE_FORMAT);

        // users within date range
        $users = $modelUsers->getUsersRegistrationReport(
                $fromDateZend->toString(self::DATE_FORMAT),
                $toDateZend->toString(self::DATE_FORMAT));

        foreach ($users as $user) {
            $labels[] = $user['date'];
            $people[] = (int)$user['registered_today'];
            $totalPeople[] = (int)$user['registered_total'];
        }

        $this->view->total = (count($totalPeople) > 0) ? max(array_merge($totalPeople,$people)) : 0;
        $this->view->labels = $labels;
        $this->view->people = $people;
        $this->view->totalPeople = $totalPeople;
    }
    
    private function _getForm()
    {
        $dateValidate = new Zend_Validate_Date(self::DATE_FORMAT);
        $toDate = Zend_Date::now()->addDay(self::STEP);
        $fromDate = Zend_Date::now()->subDay(self::STEP);
        
        $form = new Zend_Form();
        $form->addElement('text', 'from_date', array(
            'value' => $fromDate->toString(self::DATE_FORMAT),
            'validators' => array($dateValidate),
            'required' => true
        ));
        $form->addElement('text', 'to_date', array(
            'value' => $toDate->toString(self::DATE_FORMAT),
            'validators' => array($dateValidate),
            'required' => true
        ));
        return $form;
    }

}