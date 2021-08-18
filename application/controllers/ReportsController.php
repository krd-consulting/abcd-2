<?php

class ReportsController extends Zend_Controller_Action
{
    public $reg='';
    public $uid='';
    
    public function init()
    {
        $this->reg = Zend_Registry::getInstance();
        $this->uid = Zend_Registry::get('uid');
    }

    public function indexAction() {
        $form = new Application_Form_ReportOptions;
        $this->view->optionsForm = $form;
        
        $mgr  = $this->reg['mgr'];
        $root = $this->reg['root'];
        $evaluator = $this->reg['evaluator'];
        $uid  = $this->reg['uid'];
        
        $srTable = new Application_Model_DbTable_StoredReports();
        $reports = $srTable->fetchAll();
        $ownedReports = $srTable->getStaffReportIDs($uid,$mgr,$root); //currently only returns own reports unless root
        
        $depts = new Application_Model_DbTable_Depts;
        $progs = new Application_Model_DbTable_Programs;
        $users = new Application_Model_DbTable_Users;
        $ptcps = new Application_Model_DbTable_Participants;
        $groups= new Application_Model_DbTable_Groups;
        
        $this->view->deptCount = count($depts->getIDs());
        $this->view->progCount = count($progs->getIDs());
        $this->view->userCount = count($users->getIDs());
        $this->view->ptcpCount = count($ptcps->getIDs());
        $this->view->groupCount= count($groups->getIDs());
        $this->view->mgr = $mgr;
        $this->view->uid = $uid;
        $this->view->admin = $root;
        $this->view->evaluator = $evaluator;
        
        
        $this->view->reports = $reports;
        $this->view->allowedIDs = $ownedReports;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                //'<script type="text/javascript" src="/js/jquery.dataTables.min.js"></script>' .
                '<script type="text/javascript" src="/js/datatables.min.js"></script>' .
                '<script type="text/javascript" src="/js/formReport.js"></script>' .
                '<script type="text/javascript" src="/js/progReport.js"></script>' .
                '<script type="text/javascript" src="/js/ptcpReport.js"></script>' .
                '<script type="text/javascript" src="/js/staffReport.js"></script>' .
                '<script type="text/javascript" src="/js/statusFilter.js"></script>' .
                '<script type="text/javascript" src="/js/highcharts.js"></script>' .
                '<script type="text/javascript" src="/js/exporting.js"></script>' .
                '<script type="text/javascript" src="/js/reportCreate.js"></script>' . //controls Smart Report popup and makes a button for Stored Report Generator
                '<script type="text/javascript" src="/js/groupReport.js"></script>' 
        ;   
        
        if (!$evaluator) {
            $this->view->layout()->customJS .= '<script type="text/javascript" src="/js/ac2.js"></script>';
        }
    }

    public function buildAction() {
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/reportBuilder.js"></script>' . 
                '<script type="text/javascript" src="/js/jquery.dataTables.min.js"></script>' .
                '<script type="text/javascript" src="/js/highcharts.js"></script>' .
                '<script type="text/javascript" src="/js/exporting.js"></script>' .
                '<script type="text/javascript" src="/js/datePicker.js"></script>'  
        ;
        
        $filterTarget = $_GET['filterTarget'];
        $filterType = $_GET['filterType'];
        $dataType = $_GET['dataFrom']; //'singleuse' or 'prepost'
        
        //make data drop down
        $dataDropDown = new Zend_Form_Element_Select('dataForm');
        $formTable = new Application_Model_DbTable_Forms;
        $where = "target = '$filterTarget' and type = '$dataType'";
        
        $myForms = $formTable->fetchAll($where)->toArray();
        $permittedForms = $formTable->getStaffForms();
        
        foreach ($myForms as $form) {
            $fID = $form['id'];
            if (in_array($fID,$permittedForms)) {
                $dataDropDown->addMultiOption($form['id'], $form['name']);
            }
        }
        $dataDropDown->setLabel('Choose a form');
        
        //make filter drop down
        $filterDropDown = new Zend_Form_Element_Select('filterForm');
        
        switch ($filterType) {
            case 'group':
                //get groups available to this user
                $groupTable = new Application_Model_DbTable_Groups;
                $myGroups = $groupTable->getStaffGroups($this->uid);
                foreach ($myGroups as $groupID) {
                    $groupRecord = $groupTable->getRecord($groupID);
                    $filterDropDown->addMultiOption($groupRecord['id'], $groupRecord['name'])
                                   ->setLabel('Choose a group');
                }
                break;
            case 'form' :
                //get full list of forms -- permissions handled later
                $formTable = new Application_Model_DbTable_Forms;
                $forms = $formTable->fetchAll("target = '$filterTarget'")->toArray();
                foreach ($forms as $form) {
                    if (in_array($form['id'],$permittedForms)) {
                    $filterDropDown->addMultiOption($form['id'], $form['name']);
                    }
                     $filterDropDown->setLabel('Choose a form');
                }
                break;
            case 'prog' :
                //get progs available to this user
                $progTable = new Application_Model_DbTable_Programs;
                $myProgs = $progTable->getStaffPrograms($this->uid);
                foreach ($myProgs as $progID) {
                    $progRecord = $progTable->getRecord($progID);
                    $filterDropDown->addMultiOption($progRecord['id'],$progRecord['name'])
                                   ->setLabel('Choose a Program');
                }
                break;
                
            case 'staff' :
                //get staff available to the user
                $staffTable = new Application_Model_DbTable_Users;
                $myStaff = $staffTable->getAllowedStaffIDs();
                foreach ($myStaff as $staffID) {
                    $sName = $staffTable->getName($staffID);
                    $filterDropDown->addMultiOption($staffID,$sName);
                }
                $filterDropDown->setLabel('Choose a staff member');
                break;
                
            default: throw new exception ("Invalid option $filterType passed to report builder."); 
        }
        
        $filterDropDown->setAttribs(array(
            'data-filterType' => $filterType,
            'data-filterTarget' => $filterTarget,
            'data-dataType' =>$dataType
        ));
        $this->view->filterForm = $filterDropDown->render();
        $this->view->dataForm = $dataDropDown->render();
        
        
        
    }

    public function addstoredAction() {   
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/reportStore.js"></script>'
        ;
        
        $mgr = $this->reg['mgr'];
        $admin = $this->reg['root'];
        $uid = $this->reg['uid'];
        $userTable = new Application_Model_DbTable_Users;
        $uName = $userTable->getName($uid);
        
 
        
        //make recips dropdown form - including choices based on permissions
        $recipsDropDown = new Zend_Form_Element_Select('recipsDropDown');
        $recipsDropDown->addMultiOption('single','Individually');
        if ($mgr) {
            //$recipsDropDown->addMultiOption('grps','By Group');
            $recipsDropDown->addMultiOption('prgs','By Program');
        }
        if ($admin) {
            $recipsDropDown->addMultiOption('depts','By Dept');
        }
        
        $recipsDropDown->setLabel('Choose additional recipients');
        
        //make frequency options form
        $freqDropDown = new Zend_Form_Element_Select('freqDropDown');
        $freqDropDown->addMultiOptions(array(
            'daily'     => 'Daily',
            'weekly'    => 'Weekly',
            'monthly'   => 'Monthly'
        ));
        
        $freqDropDown->setLabel('Report frequency');
        
        //make name element
        $reportName = new Zend_Form_Element_Text('reportName');
        $reportName->setLabel('Report Name');
        
        //make type dropdown form - including content choices based on permissions
        $typeDropDown = new Zend_Form_Element_Select('typeDropDown');
        $typeDropDown->addMultiOptions(array(
            'prgs' => 'Programs',
            'grps'=> 'Groups',
            'forms' => 'Forms'
        ));
        
        $typeDropDown->setLabel("Include information about");
        
        $this->view->recipForm  = $recipsDropDown->render();
        $this->view->freqForm   = $freqDropDown->render();
        $this->view->typeForm   = $typeDropDown->render();
        $this->view->nameForm   = $reportName->render();
        $this->view->user       = array('id'=>$uid,'name'=>$uName);
    }
}





