<?php

class UsersController extends Zend_Controller_Action
{
private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $evaluator = FALSE;
    private $db = NULL;
    
    public function init()
    {
        /* Get user credentials */
        $this->auth = Zend_Auth::getInstance();
        if (!$this->auth->hasIdentity()) {
            throw new Exception("You are not logged in.");
        }
        
        /* Set UID */
        $this->uid = $this->auth->getIdentity()->id;
        
        /* Set role vars*/
        if ($this->auth->getIdentity()->role == '4') {$this->root = TRUE; $this->mgr = TRUE;}
        if ($this->auth->getIdentity()->role == '3') {$this->mgr = TRUE;}
        if ($this->auth->getIdentity()->role == '1') {$this->evaluator = TRUE;}

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
            $list = $users->getIDs();
        
        //If manager, get list of staff in my depts            
        } elseif ($this->mgr) {
            $list = $users->getAllowedStaffIDs($this->uid);
            array_push($list,$this->uid);
        //If staff, show myself only
        } else {
            $list=array($this->uid);
        }
        
        //at this point $list is a one-dimensional array of unique user IDs
        //now we get the user objects to pass to the view
        
        $list = array_unique($list);
        
        $number = count($list);
        $userlist = array();
        $meFirstList = array();
        
        foreach ($list as $uid) {
            $user = $users->getRecord($uid);
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
                '<script type="text/javascript" src="/js/userCreate.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>' .
                '<script type="text/javascript" src="/js/unlock.js"></script>' .
                '<script type="text/javascript" src="/js/filter.js"></script>'; 
                
        $form = new Application_Form_AddUser;
        $unlockForm = new Application_Form_UnlockUser;
        
        $this->view->count = $number;
        $this->view->list = $viewList;
        $this->view->form = $form;
        $this->view->unlockForm = $unlockForm;
        $this->view->admin = $this->root;
        $this->view->mgr = $this->mgr;
        $this->view->myID = $this->uid;
    }

    public function profileAction()
    {
      $id = $this->_getParam('id');
      $users = new Application_Model_DbTable_Users;
      $user = $users->getRecord($id);
     
      //make sure I'm allowed to see this page!
      $allowed = FALSE;
      if ($this->root) {
        $allowed = TRUE;
      } elseif ($this->mgr) {
        $allowedUsers = $users->getAllowedStaffIDs($this->uid);
        if (in_array($id,$allowedUsers)) $allowed = TRUE;
      } 
      
      //always see my own, except evaluators who do not use this functionality
      if (!$this->evaluator) {
          if ($this->uid == $id) $allowed = TRUE;
      }
      
      if (!$allowed) {
          throw new exception ("Sorry - this profile is confidential.");
      }
      
      //get my case load
      $caseLoadTable = new Application_Model_DbTable_ParticipantUsers;
      $ptcpIDs = $caseLoadTable->getList('ptcp',$id);
      
       //fill out records
        $ptcpTable = new Application_Model_DbTable_Participants;
        $progTable = new Application_Model_DbTable_Programs;
        
        $participants = array();
        foreach ($ptcpIDs as $pID) {
            $ptcp = $ptcpTable->getRecord($pID);
            $ptcpRel = $caseLoadTable->getRecord($pID, $id);
            $participants[$pID]['name'] = $ptcp['firstName'] . ' ' . $ptcp['lastName'];
            $participants[$pID]['dob'] = $ptcp['dateOfBirth'];
            $participants[$pID]['status'] = $ptcpRel['status'];
                //get program name
                    $progID =  $ptcpRel['programID'];
                    $progName = $progTable->getName($progID);
            $participants[$pID]['statusProg'] = $progName;
            $participants[$pID]['statusProgID'] = $progID;
            
                //format date
                    $sqlDate = strtotime($ptcpRel['statusDate']);
                    $sinceDate = date("M j, Y",$sqlDate);
            $participants[$pID]['since'] = $sinceDate;
            $participants[$pID]['statusNote'] = $ptcpRel['statusNote'];
        }
      
        $statusFilterForm = new Application_Form_FilterForm;
        $statusForm = new Application_Form_StatusUpdate;
      
      //get program list
        $progUserTable = new Application_Model_DbTable_UserPrograms;
        $programs = array();
        
        $profileProgIDs = $progUserTable->getList('progs',$id);
        $allowedProgIDs = $progUserTable->getList('progs',$this->uid);
        $progIDs = array_intersect($profileProgIDs,$allowedProgIDs);
        
        foreach ($progIDs as $progID) {
            $record = $progTable->getProg($progID);
            array_push($programs,$record);
        }
        
        //get dept list
        $deptTable = new Application_Model_DbTable_Depts;
        $deptUserTable = new Application_Model_DbTable_UserDepartments;
        $depts = array();
        $profileDeptIDs = $deptUserTable->getList('depts',$id);
//        $allowedDeptIDs = $deptUserTable->getList('depts',$this->uid);
//        print_r($profileDeptIDs); print_r($allowedDeptIDs); die();
//        $deptIDs = array_intersect($profileDeptIDs,$allowedDeptIDs);
        
        foreach ($profileDeptIDs as $deptID) {
            $deptRecord = $deptTable->getDept($deptID);
            array_push($depts,$deptRecord);
        }
        
        //get home depts
        $homeDepts = $deptUserTable->getHomeDepts($id);
      
      $this->view->root = $this->root;
      $this->view->mgr = $this->mgr;
      $this->view->uid = $this->uid;
      $this->view->evaluator = $this->evaluator;
      $this->view->user = $user;
      $this->view->ptcps = $participants;
      $this->view->programs = $programs;
      $this->view->depts = $depts;
      $this->view->homeDepts = $homeDepts;
      $this->view->filterForm = $statusFilterForm;
      $this->view->statusForm = $statusForm;

      $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' . 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/ptcpNoteCaseLoad.js"></script>' . 
                '<script type="text/javascript" src="/js/statusFilter.js"></script>' . 
                '<script type="text/javascript" src="/js/filter.js"></script>' .
                '<script type="text/javascript" src="/js/userFunctions.js"></script>'
            ;      
    }
    
    public function deleteAction()
    {
        $id = $this->_getParam('id');
        $users = new Application_Model_DbTable_Users;
        $users->deleteUser($id);
        
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

    public function archiveAction()
    {
        $id = $this->_getParam('id');
        $userTable = new Application_Model_DbTable_Users;
        $userTable->archiveUser($id);

        $this->_redirect($_SERVER['HTTP_REFERER']);

    }
    
    public function associateAction()            {
        
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
        $ptcpProgTable = new Application_Model_DbTable_ParticipantPrograms;
        
        $ptcpUserTable = new Application_Model_DbTable_ParticipantUsers;
        
        $deptTable     = new Application_Model_DbTable_Depts;
        $userDeptTable = new Application_Model_DbTable_UserDepartments;
        
        $thisUser = $userTable->getRecord($id);
        
        if ((!$this->mgr) && ($type != 'ptcp')) {
            throw new exception("You don't have sufficient access control privileges for this functionality.");
        }
        
        switch ($type) {
            case 'prog' : 
                $assocTable = $userProgTable;
                $secondaryAssoc = $userDeptTable;
                $records    = $progTable;
                $columnType = 'progs';
                $header = "Add Program to ";
                break;
            
            case 'ptcp' : 
                $assocTable = $ptcpUserTable;
                //$secondaryAssoc = new Application_Model_DbTable_ParticipantDepts;
                $records    = new Application_Model_DbTable_Participants;
                $columnType = 'ptcp';
                $header = "Case load for ";
                break;
            
            case 'dept' :
                $assocTable = $userDeptTable;
                $columnType = 'depts';
                $header = "Departments for ";
                break;
            
            default: throw new Exception("Can only add Departments, Participants and Programs to Staff Files.");
        }
        
        $currentRecords = array();
        $addRecords     = array();
        $requiredIDs    = array();
        
        
        
        $currentRecordIDs = array_unique($assocTable->getList($columnType, $id));
        
        
        if ($type == 'ptcp') {
            //**organized by program**
            
            //show only programs which are both permissible to current user 
            //and associated with requested staff
            
            $allUserProgIDs = $userProgTable->getList('progs',$id);
            $allowedProgIDs = $userProgTable->getList('progs',$this->uid);
            $userProgIDs = array_intersect($allUserProgIDs,$allowedProgIDs);
            
            foreach ($userProgIDs as $progID) {
                $progName = $progTable->getName($progID);
                $currentRecords[$progID] = array('name' => $progName, 'ptcps' => array());
                $addRecords[$progID] = array(
                    'name' => $progName, 
                    'progID' => $progID,
                    'ptcps' => array());
                
                //get all current prog participants
                $progParticipants = array_unique($ptcpProgTable->getList('ptcp',$progID));
                //make a record for each
                //just status and date
                   foreach($progParticipants as $ppID) {
                       $pRecord = $records->getRecord($ppID);
                       $enrollRecord = $ptcpProgTable->getRecord($ppID, $progID);
                       $pRecord['status'] = $enrollRecord['status'];
                       $pRecord['statusDate'] = $enrollRecord['statusDate'];
                       $pRecord['disableClass'] = FALSE;

                       //if on staff caseload currently, add to current
                       //otherwise add to add
                       $currentCheck = $ptcpUserTable->getRecord($ppID, $id, $progID);
                       if (count($currentCheck) == 0) {
                           //check if it belongs to someone else
                           $ptcpCheck = $ptcpUserTable->fetchAll("participantID = $ppID AND programID = $progID")->toArray();
                           if (count($ptcpCheck) > 0) {
                               $pRecord['disableClass'] = TRUE;
                           }
                           //add it to Available Records 
                           //unless concluded - we are not making those available
                           if ($pRecord['status'] != 'concluded') {
                                array_push($addRecords[$progID]['ptcps'],$pRecord);
                           }
                       } else {
                           //check my own status, if not 'concluded', can't disassociate here
                           if ($currentCheck['status'] != 'concluded') {
                               $pRecord['disableClass'] = TRUE;
                           }
                           //add it to Current Records
                           array_push($currentRecords[$progID]['ptcps'],$pRecord);
                       }

                   }
                $existing = count($currentRecords[$progID]['ptcps']);
                $adding = count($addRecords[$progID]['ptcps']);
                $currentRecords[$progID]['count'] = $existing;
                $addRecords[$progID]['count'] = $adding;
            }
        }
        
        if ($type == 'prog') {
            //$deptTable = new Application_Model_DbTable_UserDepartments();
            $allDeptIDs = $userDeptTable->getList('depts',$id);
            $allowedDeptIDs = $userDeptTable->getList('depts',$this->uid);
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
        
        if ($type == 'dept') {
            
            if (!$this->root) {
                $allowedDeptIDs = $userDeptTable->getList('depts',$this->uid);            
            } else {
                $allowedDeptIDs = $deptTable->getIDs();
            }
            
            $homeDepts = $userDeptTable->getHomeDepts($id);
            
            foreach ($allowedDeptIDs as $deptID) {
                $deptRecord = $deptTable->getRecord($deptID);
                $deptRecord['name'] = $deptRecord['deptName'];
                
                if (in_array($deptID,$currentRecordIDs)) {
                    array_push($currentRecords,$deptRecord);
                } else {
                    array_push($addRecords,$deptRecord);
                }
                
                if (in_array($deptID,$homeDepts)) {
                    array_push($requiredIDs,$deptID);
                }
            }
        }
        
        //print_r($currentRecords);
        //print_r($addRecords);
        //print_r($requiredIDs);
        
        $this->view->currentRecords = $currentRecords;
        $this->view->addRecords = $addRecords;
        $this->view->thisUser = $thisUser;
        
        $this->view->required = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;
    
    }
    
}







