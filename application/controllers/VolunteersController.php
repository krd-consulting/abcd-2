<?php

class VolunteersController extends Zend_Controller_Action
{

    private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $evaluator = FALSE;
    private $volunteer = FALSE;
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

    public function indexAction()
    {
        $this->_helper->redirector('list');
    }

    public function listAction()
    {
        $userDepts = new Application_Model_DbTable_UserDepartments;
        $depts = new Application_Model_DbTable_Depts;
        $users = new Application_Model_DbTable_Users;
        
        //Check if we're getting a list from search
        if ($this->_helper->flashMessenger->getMessages()) {
            $passedList = $this->_helper->flashMessenger->getMessages();
            $list = $passedList['0'];
        
        //If admin, show all users in system     
        } elseif ($this->root) {
            $list = $users->getIDs('vol');
        
        //If manager, get list of volunteers in my dept      
        } elseif ($this->mgr) {
            $list = $users->getAllowedVolIDs($this->uid);
        //If vol, show myself only
        } elseif ($this->volunteer) {
            $list=array($this->uid);
        }
        
        //at this point $list is a one-dimensional array of unique user IDs
        //now we get the user objects to pass to the view
        
        $number = count($list);
        $userlist = array();
        $meFirstList = array();
        
        $alertsLinks = new Application_Model_DbTable_AlertsVolunteers;
        
        foreach ($list as $uid) {
            $user = $users->getRecord($uid);
            if ($alertsLinks->getList('alerts',$uid)) {
                $user['flag'] = 'yes';
            }
            
            
            if ($uid != $this->uid) {
                array_push($userlist, $user);
            } else {
                array_push($meFirstList,$user);
            }
        }
        
        foreach ($userlist as $c => $key) {
            $sortLastName[$c] = $key['lastName'];
        }
        
        array_multisort($sortLastName, SORT_ASC, $userlist);
        $viewList = array_merge($meFirstList,$userlist);
        
        //JS + Form for new user;
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/volCreate.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>' .
                '<script type="text/javascript" src="/js/unlock.js"></script>' .
                '<script type="text/javascript" src="/js/filter.js"></script>'; 
                
        $form = new Application_Form_AddVolunteer;
        $unlockForm = new Application_Form_UnlockVolunteer;
        
        $this->view->count = $number;
        $this->view->list = $viewList;
        $this->view->form = $form;
        $this->view->unlockForm = $unlockForm;
        $this->view->admin = $this->root;
        $this->view->mgr = $this->mgr;
        $this->view->volunteer = $this->volunteer;
        $this->view->myID = $this->uid;
    }

    public function profileAction()
    {
      $id = $this->_getParam('id');
      $users = new Application_Model_DbTable_Users;
      $progTable = new Application_Model_DbTable_Programs;
      $deptTable = new Application_Model_DbTable_Depts;
      $user = $users->getRecord($id);
     
      //make sure I'm allowed to see this page!
      $allowed = FALSE;
      if ($this->root) {
        $allowed = TRUE;
      } elseif ($this->mgr) {
        $allowedVols = $users->getAllowedVolIDs($this->uid);
        if (in_array($id,$allowedVols)) $allowed = TRUE;
      } 
      
      //always see my own, except evaluators and volunteers who do not use this functionality
      if (!$this->evaluator || !$this->volunteer) {
          if ($this->uid == $id) $allowed = TRUE;
      }
      
      if (!$allowed) {
          throw new exception ("Sorry - this profile is confidential.");
      }
      
      //get program list
        $progUserTable = new Application_Model_DbTable_UserPrograms;
        $programsTable = new Application_Model_DbTable_Programs;
        $programs = array();
        $depts = array();
        
        $profileProgIDs = $progUserTable->getList('progs',$id);
        $allowedProgIDs = $programsTable->getStaffPrograms($this->uid);
        $progIDs = array_intersect($profileProgIDs,$allowedProgIDs);
        
        foreach ($progIDs as $progID) {
            $record = $progTable->getProg($progID);
            array_push($programs,$record);
        }
        
      //get dept list
        $deptUserTable = new Application_Model_DbTable_UserDepartments;
        $profileDeptIDs = $deptUserTable->getList('depts',$id);
        $allowedDeptIDs = $deptUserTable->getList(('depts'), $this->uid);
        $deptIDs = array_intersect($profileDeptIDs,$allowedDeptIDs);
        foreach ($deptIDs as $deptID) {
            $record = $deptTable->getRecord($deptID);
            array_push($depts,$record);
        }
        
      //get all forms
           $formTable = new Application_Model_DbTable_Forms;
           $forms = $formTable->getVolForms($id);
           $permittedForms = $formTable->getStaffForms("volunteer");
           
           foreach ($forms as $key=>$formRecord) {
               if (!in_array($formRecord['id'], $permittedForms)) {
                   unset($forms[$key]);
               }
           }
            
           
    //get all files
           $fileTable = new Application_Model_DbTable_Files;
           $files = $fileTable->getFileList('vol', $id);  
    
    //make pop-up forms
    $addAlertForm = new Application_Form_AddAlert(array('type' => 'vol'));
    $addActivityForm = new Application_Form_AddVolActivity(array('vol' => $id, 'user' => $this->uid, 'progs' => $programs));
    
    
    //get stuff for header
        $activityTable = new Application_Model_DbTable_VolunteerActivities;
        $first = date('Y-m-1');
        $firstOfYear = date('Y-1-1');
        $this->view->monthlyHours = $activityTable->hoursReport('vol',$id,$first);
        $this->view->yearlyHours = $activityTable->hoursReport('vol',$id,$firstOfYear);
        $this->view->progCount = count($programs);
           
      $this->view->root = $this->root;
      $this->view->mgr = $this->mgr;
      $this->view->evaluator = $this->evaluator;
      $this->view->volunteer= $this->volunteer;
      $this->view->user = $user;
      $this->view->depts = $depts;
      $this->view->programs = $programs;
      $this->view->forms = $forms;
      $this->view->files = $files;
      $this->view->alertForm  = $addAlertForm;
      $this->view->activityForm = $addActivityForm;

      
      $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/ptcpFormTable.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' . 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/statusFilter.js"></script>' . 
                '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                '<script type="text/javascript" src="/js/timepicker/jquery.timepicker.min.js"></script>' .
                '<script type="text/javascript" src="/js/alertCreate.js"></script>' .
                '<script type="text/javascript" src="/js/activityVolCreate.js"></script>' .
                '<script type="text/javascript" src="/js/volActivityNotes.js"></script>' .
                '<script type="text/javascript" src="/js/uploadFileCreate.js"></script>' .
                '<script type="text/javascript" src="/js/ac2.js"></script>' .
                '<script type="text/javascript" src="/js/ac.js"></script>' .
                '<script type="text/javascript" src="/js/jQuery/jquery.ui.widget.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.iframe-transport.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.fileupload.js"></script>' .

                '<script type="text/javascript" src="/js/filter.js"></script>' 
                 
            ;      
    }
    
    public function calendarAction() {
        if (!$this->mgr) throw new exception("You do not have the right access level for this calendar.");
        
        $volID = $this->_getParam('id');
        $usersTable = new Application_Model_DbTable_Users;
        $this->view->name = $usersTable->getName($volID);
        $this->view->enrollVolForm = new Application_Form_EnrollVolunteer;
        $this->view->volID = $volID;
        $this->view->mode = "rw";
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/dhtmlxscheduler.js"></script>' .
                '<script type="text/javascript" src="/js/abcdVolScheduler.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_cookie.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_recurring.js"></script>'
        ;
        
    }
    
    public function deleteAction()
    {
        $id = $this->_getParam('id');
        $users = new Application_Model_DbTable_Users;
        if ($this->root) {
            $users->deleteUser($id);
        } else {
            throw new exception("Please do not attempt to delete users. Locking accounts is the recommended security operation.");
        }
        
        $this->_redirect($_SERVER['HTTP_REFERER']);
        
    }
    
    public function deptremoveAction()
    {
        $uid = $this->_getParam('id');
        $did = $this->_getParam('deptID');
        $userDepts = new Application_Model_DbTable_UserDepartments;
        $userDepts->delRecordfromDept($uid, $did);
        
        $this->_redirect($_SERVER['HTTP_REFERER']);
        
    }

    public function deptaddAction()
    {
        $uid = $_GET['uid'];
        $did = $_GET['did'];
        
        $userDepts = new Application_Model_DbTable_UserDepartments;
        $userDepts->addRecordToDept($uid, $did);
        
        $json=array('success' => 'yes');
        $this->_helper->json($json);
    }

    public function lockAction()
    {
        $id = $this->_getParam('id');
        $userTable = new Application_Model_DbTable_Users;
        $userTable->lockUser($id);

        $this->_redirect($_SERVER['HTTP_REFERER']);
    }
    
    public function unlockAction()
    {
        $id = $_POST['uid'];
        $pwd = $_POST['pwd'];
        
        $userTable = new Application_Model_DbTable_Users;
        $userTable->unlockUser($id, $pwd);
        
        $this->_helper->json("Success");
        
    }
    
    public function associateAction() {
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/genericSort.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/filterLi.js"></script>' 
                ;
        
        $id       = $this->_getParam('id');
        $type     = $this->_getParam('type');
        $userTable = new Application_Model_DbTable_Users;
        $userProgTable = new Application_Model_DbTable_UserPrograms;
        $progTable = new Application_Model_DbTable_Programs;
        
        $thisUser = $userTable->getRecord($id);
        
        if ((!$this->mgr) && ($type != 'ptcp')) {
            throw new exception("You don't have sufficient access control privileges for this functionality.");
        }
        
        switch ($type) {
            case 'prog' : 
            case 'program' :
                $assocTable = $userProgTable;
                $secondaryAssoc = new Application_Model_DbTable_UserDepartments;
                $records    = $progTable;
                $columnType = 'progs';
                $header = "Add Program to ";
                break;
            
            default: throw new Exception("Can only add Programs to Staff Files.");
        }
        
        $currentRecords = array();
        $addRecords     = array();
        $requiredIDs    = array();
        
        $currentRecordIDs = array_unique($assocTable->getList($columnType, $id));
        
        if ($type == 'prog' || $type == 'program') {
            $deptTable = new Application_Model_DbTable_UserDepartments();
            $allDeptIDs = $deptTable->getList('depts',$id);
            $allowedDeptIDs = $deptTable->getList('depts',$this->uid);
            $deptIDs = array_intersect($allDeptIDs,$allowedDeptIDs);
        
            foreach ($deptIDs as $deptID) {
                $progRecords = $progTable->getProgByDept($deptID);
                
                foreach ($progRecords as $progRecord) {
                    $progID = $progRecord['id'];
                    if (in_array($progID,$currentRecordIDs)) {
                        array_push($currentRecords,$progRecord);
                    } else {
                        array_push($addRecords,$progRecord);
                    } 
                }
            }
        }
        
        $this->view->currentRecords = $currentRecords;
        $this->view->addRecords = $addRecords;
        $this->view->thisUser = $thisUser;
        
        $this->view->required = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;
    
    }
    
}







