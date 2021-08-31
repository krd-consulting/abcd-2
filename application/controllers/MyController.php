<?php

class MyController extends Zend_Controller_Action
{
    private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $db = NULL;
    
    public function init() {
        /* Get user credentials */
        $this->auth = Zend_Auth::getInstance();
        if (!$this->auth->hasIdentity()) {
            throw new Exception("You are not logged in.");
        }
        
        /* Set UID and roles */
        $this->uid = Zend_Registry::get('uid');
        $this->root = Zend_Registry::get('root');
        $this->mgr = Zend_Registry::get('mgr');
        $this->evaluator = Zend_Registry::get('evaluator');
        $this->volunteer = Zend_Registry::get('volunteer');

        /* Set Database */
        $this->db = $this->getInvokeArg('bootstrap')->getResource('db');

    }

    public function indexAction() {
        if (!$this->volunteer) {
            $this->_helper->redirector('index','dash');
        } else {
            $this->_helper->redirector('profile');
        }
    }

    public function calendarAction() {
        if (!$this->volunteer) throw new exception("You do not have the right access level for this calendar (volunteers only).");
        
        $volID = $this->uid;
        $usersTable = new Application_Model_DbTable_Users;
        $this->view->name = $usersTable->getName($volID);
        $this->view->enrollVolForm = new Application_Form_EnrollVolunteer;
        $this->view->volID = $volID;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/dhtmlxscheduler.js"></script>' .
                '<script type="text/javascript" src="/js/abcdVolScheduler.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_cookie.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_recurring.js"></script>'
        ;
        
    }
    
    public function profileAction()
    {
        if (!$this->volunteer) {
            $this->_helper->redirector('index','dash');
        }
        $progTable = new Application_Model_DbTable_Programs;
        $progIDs = $progTable->getStaffPrograms($this->uid);
        $programs = array();
        $progNamesArray = array();
        foreach ($progIDs as $progID) {
            $record = $progTable->getProg($progID);
            array_push($programs,$record);
            array_push($progNamesArray,$record['name']);
        }
        
        $userTable = new Application_Model_DbTable_Users;
        $userName = $userTable->getName($this->uid);
        $activityTable = new Application_Model_DbTable_VolunteerActivities;
        
        $addActivityForm = new Application_Form_AddVolActivity(array('vol' => $this->uid, 'user' => $this->uid, 'progs' => $programs));
        
        $this->view->userName = $userName;
        $this->view->id = $this->uid;
        $this->view->activityForm = $addActivityForm;
        $this->view->progNames = implode(", ",$progNamesArray);
        
        $first = date('Y-m-1');
        $firstOfYear = date('Y-1-1');
        $this->view->monthlyHours = $activityTable->hoursReport('vol', $this->uid,$first);
        $this->view->yearlyHours = $activityTable->hoursReport('vol', $this->uid,$firstOfYear);
        $this->view->progCount = count($progNamesArray);

       
       
        
        $this->view->layout()->customJS = 
                 '<script type="text/javascript" src="/js/ptcpFormTable.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' . 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/statusFilter.js"></script>' . 
                '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                '<script type="text/javascript" src="/js/timepicker/jquery.timepicker.min.js"></script>' .
                '<script type="text/javascript" src="/js/alertCreate.js"></script>' .
                '<script type="text/javascript" src="/js/activityVolCreate.js"></script>' .
                //'<script type="text/javascript" src="/js/uploadFileCreate.js"></script>' .
                '<script type="text/javascript" src="/js/ptcpGroupNotes.js"></script>' .
                //'<script type="text/javascript" src="/js/ac2.js"></script>' .
                '<script type="text/javascript" src="/js/ac.js"></script>' .
                '<script type="text/javascript" src="/js/jQuery/jquery.ui.widget.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.iframe-transport.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.fileupload.js"></script>' .

                '<script type="text/javascript" src="/js/filter.js"></script>' 
            ;
        
        
    }

    public function activitiesAction()
    {
        // action body
    }


}





