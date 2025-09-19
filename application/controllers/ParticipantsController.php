<?php

class ParticipantsController extends Zend_Controller_Action
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
        
        /* Set UID */
        $this->uid = $this->auth->getIdentity()->id;
        
        /* Set role vars*/
        if ($this->auth->getIdentity()->role == '4') {$this->root = TRUE; $this->mgr = TRUE;}
        if ($this->auth->getIdentity()->role == '3') {$this->mgr = TRUE;}

        /* Set Database */
        $this->db = $this->getInvokeArg('bootstrap')->getResource('db');

    }

    public function indexAction() {
        $this->_helper->redirector('list');
    }
    
    public function listAction() {
        $userDepts = new Application_Model_DbTable_UserDepartments;
        $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
        $participants = new Application_Model_DbTable_Participants;
        $depts = new Application_Model_DbTable_Depts;
        $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
        
        
        //check if sub-list is being passed
        if ($this->_helper->flashMessenger->getMessages()) {
            $passedList = $this->_helper->flashMessenger->getMessages();
            $idList = $passedList['0'];
            $neededIDs = implode(",", $idList);
            $list = $participants->getFullRecords($neededIDs);
        } else {
            $list = $participants->getStaffPtcps(NULL,"FULLREC");
        }
        
        $homePtcpIDs = $participants->getStaffHomePtcps($this->uid);
        
        $number = count($list);
        $content = array();
//        $rawDeptList = $depts->fetchAll()->toArray();
//        $deptList = array();
//        foreach ($rawDeptList as $dept) {
//                $deptId = $dept['id'];
//                $deptName = $dept['deptName'];
//                $deptList[$deptId] = $deptName;
//        }
                
        foreach ($list as $record) {
            $deptNames = array();
            $id = $record['id'];
            //$ptcpDept = $ptcpDepts->getList('depts', $id);
            $flagTest = $ptcpAlerts->getPtcpAlertStatus($id, 'all');
            
//            foreach ($ptcpDept as $did) {
//                $deptName = $deptList[$did];
//                $deptNames[$did] = $deptName;
//            }
            
            $content[$id] = $record;
//            $content[$id]['depts'] = $deptNames;
            $content[$id]['flag'] = $flagTest;
        }
        
        
        $this->view->list = $content;
        $this->view->homeIDs = $homePtcpIDs;
        $this->view->count = $number;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                '<script type="text/javascript" src="/js/ptcpCreate.js"></script>' . 
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>' . 
                '<script type="text/javascript" src="/js/filter.js"></script>'; 
                
        $form = new Application_Form_AddParticipant;
        $this->view->form = $form;
        
}

    public function addAction() {
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/datePicker.js"></script>';
        $form = new Application_Form_AddParticipant;
        $this->view->form = $form;
        
        if ($this->getRequest()->isPost()) 
	{
	  $formData = $this->getRequest()->getPost();
	  if ($form->isValid($formData)) 
	  {
		    $fn  = $form->getValue('fname');
                $ln  = $form->getValue('lname');
                $dob = $form->getValue('dob');
		
                $newPart = new Application_Model_DbTable_Participants();
		    $newPart->addParticipant($fn, $ln, $dob);
                
                $id = $newPart->getParticipantID($fn, $ln, $dob);
                
		$this->_helper->redirector('profile', 'participants', 'default', array('id'=> $id));
                
	  } else {
		$form->populate($formData);
	  }
	}
        
    }

    public function profileAction() {
      if ($this->_getParam('id')) {
            $id = $this->_getParam('id');
            $permission = FALSE;
            
            $ptcpTable = new Application_Model_DbTable_Participants;
            $pidValues = $ptcpTable->getStaffPtcps();
          
            //if (array_search($id,array_column($pidValues,'id')) || $this->root == TRUE) {
            if ((in_array($id,$pidValues)) || ($this->root == TRUE)) {
                $permission = TRUE;
            } 
            
            if ($permission == TRUE) {
            //Get list of all departments for this participant
                $part = new Application_Model_DbTable_Participants;
                $partDeptsTable = new Application_Model_DbTable_ParticipantDepts;
                $deptTable = new Application_Model_DbTable_Depts;
                $partDepts =  array();
                $deptIDs = $partDeptsTable->getList('depts', $id);
                foreach ($deptIDs as $deptID) {
                    $curdept = $deptTable->getDept($deptID);
                    array_push($partDepts, $curdept);
                }
           //Get list of all departments for current user     
                if ($this->root == TRUE) {
		  $myDepts = $partDepts;
		} else {
		  $myDepts = array();
                  $userDeptsTable = new Application_Model_DbTable_UserDepartments;
                  $myDeptIDs = $userDeptsTable->getList('depts', $this->uid);
                  foreach ($myDeptIDs as $did) {
                      if (in_array($did, $deptIDs)) {
                          $curdept = $deptTable->getDept($did);
                          array_push($myDepts, $curdept);
                      }
                  }
		}
           //Get participant record     
                $participant = $part->getRecord($id);
           
           //get participant programs to which current user has access
           //use programs to get groups
                $allowedPrograms = array();
                $allowedGroups = array();
                
                $programTable = new Application_Model_DbTable_Programs;
                $groupTable = new Application_Model_DbTable_Groups;
                
                $ptcpPrograms = new Application_Model_DbTable_ParticipantPrograms;
                $ptcpProgIDs = $ptcpPrograms->getList('progs', $id);
                
                $ptcpGroups = new Application_Model_DbTable_ParticipantGroups;
                $ptcpGroupIDs = $ptcpGroups->getList('groups', $id);
                
                $groupMeetings = new Application_Model_DbTable_GroupMeetings;
                
                if ($this->root) {
                    $staffProgIDs = $programTable->getIDs();
                } else {
                    $userPrograms = new Application_Model_DbTable_UserPrograms;
                    $staffProgIDs = $userPrograms->getList('progs', $this->uid);
                }
                               
                foreach ($ptcpProgIDs as $pid) {
                    if (in_array($pid, $staffProgIDs)) {
                        $thisProg = $programTable->getRecord($pid);
                        $enrollRecord = $ptcpPrograms->getRecord($id, $pid);
                        $thisProg['enroll'] = $enrollRecord;
                        $allowedPrograms[$pid] = $thisProg;
                        
                        $groups = $groupTable->getProgramGroups($pid);
                        
                        foreach ($groups as $group) {
                            $gid = $group['id'];
                            
                            if (in_array($gid, $ptcpGroupIDs)) {
                                                                
                                $mtgRecord = $groupMeetings->getPtcpMtgRecord($gid, $id);
                                if (count($mtgRecord) > 0) {
                                    $group['meetings']  = $mtgRecord;
                                }
                            $allowedGroups[$gid] = $group;                                
                            }                            
                        }
                    }
                }
           
           //get all forms
           $formTable = new Application_Model_DbTable_Forms;
           $forms = $formTable->getPtcpForms($id);
           $permittedForms = $formTable->getStaffForms("participant");
           
           foreach ($forms as $key=>$formRecord) {
               if (!in_array($formRecord['id'], $permittedForms)) {
                   unset($forms[$key]);
               }
           }
                
           //for javascripts
           $statusForm = new Application_Form_StatusUpdate();
           $addAlertForm = new Application_Form_AddAlert;
           $addActivityForm = new Application_Form_AddActivity(array('ptcp' => $id, 'user' => $this->uid));
           
           
           //Passing everything to view     
                $this->view->layout()->customJS = 
                    //'<script type="text/javascript" src="/js/editData.js"></script>' .
                    '<script type="text/javascript" src="/js/ac-communities.js"></script>' .
                    '<script type="text/javascript" src="/js/ptcpNote.js"></script>' .
                    '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                    '<script type="text/javascript" src="/js/ptcpFormTable.js"></script>' .
                    '<script type="text/javascript" src="/js/ac2.js"></script>' .
                    '<script type="text/javascript" src="/js/alertCreate.js"></script>' .
                    '<script type="text/javascript" src="/js/activityCreate.js"></script>' .
                    '<script type="text/javascript" src="/js/disable.js"></script>' .
                    '<script type="text/javascript" src="/js/statusFilter.js"></script>' .
                    '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' .
                    '<script type="text/javascript" src="/js/ptcpGroupNotes.js"></script>' ;
                    
                
                $this->view->statusForm = $statusForm;
                $this->view->alertForm  = $addAlertForm;
                $this->view->activityForm = $addActivityForm;
                $this->view->participant = $participant;
                $this->view->partDepts = $partDepts;
                $this->view->depts = $myDepts;
                $this->view->programs = $allowedPrograms;
                $this->view->groups = $allowedGroups;
                $this->view->forms = $forms;
                $this->view->db = $this->db;
                
            } else {
                throw new Exception("You don't have permission to access userID $id.");
            }
      } else {
            throw new exception("Can't pull profile: no id received.");
      }
    }

    public function associateAction() { //MESSY!
    
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/genericSort.js"></script>' .
                '<script type="text/javascript" src="/js/filterLi.js"></script>' 
                ;
        
        $id       = $this->_getParam('id');
        $type     = $this->_getParam('type');
        $ptcpTable= new Application_Model_DbTable_Participants;
        $thisPtcp = $ptcpTable->getRecord($id);
        $deptTable = new Application_Model_DbTable_Depts;
        $programTable = new Application_Model_DbTable_Programs;
        $groupTable = new Application_Model_DbTable_Groups;
                
        switch ($type) {
            case 'group' : 
                $assocTable = new Application_Model_DbTable_ParticipantGroups;
                $secondaryAssoc = new Application_Model_DbTable_ParticipantDepts;
                $records    = $groupTable;
                $columnType = 'groups';
                $header = "Enrollment for ";
                break;
            
            case 'program' : 
                $assocTable = new Application_Model_DbTable_ParticipantPrograms;
                $secondaryAssoc = new Application_Model_DbTable_ParticipantDepts;
                $records    = $programTable;
                $columnType = 'progs';
                $header = "Enrollment for ";
                break;
            
            case 'dept' : 
                $assocTable = new Application_Model_DbTable_ParticipantDepts;
                $secondaryAssoc = $assocTable;
                $records = $deptTable;
                $columnType = 'depts';
                $header = "Enrollment for ";
                break;
            
            default: throw new Exception("Can only enroll in departments, programs and groups.");
        }
        
        $currentRecords = array();
        $addRecords     = array();
        $requiredIDs    = array();
        
        $currentRecordIDs = $assocTable->getList($columnType, $id);
        $currentRecordIDs = array_unique($currentRecordIDs);


        //**Only 
        //  get list of programs-to-add in this ptcp's department.
        //  
        //**This holds regardless of role, so we don't wind up 
        //  with participants in programs who are not in departments.
        
        $myDepts = $secondaryAssoc->getList('depts',$id);
        
        $allRecordIDs = array();
        $allGroupIDs = array();
        $allProgramIDs = array();
        $allDeptIDs = array();
        
        $userPrograms = new Application_Model_DbTable_UserPrograms;
        $staffPrograms = $userPrograms->getList('progs', $this->uid);
        $staffGroups = $groupTable->getStaffGroups($this->uid);
        
        if ($type != 'dept') {
            foreach ($myDepts as $deptID) {
            $deptProgs = $programTable->getProgByDept($deptID);
                        
            foreach ($deptProgs as $deptProg) {
                $progID = $deptProg['id'];
                //check against staff programs 
                
                if((in_array($progID, $staffPrograms)) || ($this->root)) {
                    array_push($allProgramIDs, $progID);
                    
                    $progGroups = $groupTable->getProgramGroups($deptProg['id']);
                    if (count($progGroups) > 0) {
                        foreach ($progGroups as $progGroup) {
                            array_push($allGroupIDs, $progGroup['id']);
                        }
                    }
                    
                } else {
                    if (in_array($progID, $currentRecordIDs)) {
                        $k = array_search($progID, $currentRecordIDs);
                        unset($currentRecordIDs[$k]);
                    }
                }
                }
            } 
        }
        
        if ($type == 'dept') {
            $currentRecordIDs = $myDepts;
            $allRecordIDs = $deptTable->getIDs();
            if ($this->root) {
                $staffRecords = $allRecordIDs;
            } else {
                $userDepts = new Application_Model_DbTable_UserDepartments;
                $staffRecords = $userDepts->getList('depts',$this->uid);
                $allRecordIDs = $staffRecords;
                print_r($allRecordIDs); 
            }
            
        }
        
        if ($type == 'program') {
            $allRecordIDs = $allProgramIDs;
            $staffRecords = $staffPrograms;
        }
        if ($type == 'group') {
            $allRecordIDs = $allGroupIDs;
            $staffRecords = $staffGroups;
        }
        
        //Trim out existing records from the full list.
        $addRecordIDs     = array_diff($allRecordIDs,$currentRecordIDs);
        
        //Get display data for current records
        foreach ($currentRecordIDs as $cid) {
        $currentRecord = $records->getRecord($cid);
        array_push($currentRecords,$currentRecord);
        
        //Set 'required' flag on current records
            if ($type != 'dept') {
                $assocRecord = $assocTable->getRecord($id,$cid);
            }
            if  (
                     (($type == 'program') && ($assocRecord['status'] != 'concluded')) ||
                     ((!$this->mgr) && (!in_array($cid,$staffRecords)))
                ) 
            {
               array_push($requiredIDs, $cid);
            }    
            
        }
        
        //Get display data for additional records
        foreach ($addRecordIDs as $aid) {
            $addRecord = $records->getRecord($aid);
            array_push($addRecords,$addRecord);
        }
        
//        print_r($currentRecords);
//        print_r($addRecords);
//        print_r($requiredIDs);
//        die("Last call.");
        
        //Pass everything to view
        $this->view->currentRecords = $currentRecords;
        $this->view->addRecords = $addRecords;
        $this->view->thisPtcp = $thisPtcp;
        
        $this->view->required = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;  
    }
    
    public function deptremoveAction() {
        $pid = $this->_getParam('id');
        $did = $this->_getParam('deptID');
        $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
        $ptcpDepts->delRecordfromDept($pid, $did);
        
        //REFERER url + div id of second tab        
        $goback = $_SERVER['HTTP_REFERER'] . "#dept-frag-2";
        
        $this->_redirect($goback);
        
    }
    
    public function deptaddAction() {
        $pid = $_GET['pid'];
        $did = $_GET['did'];
        
        $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
        $ptcpDepts->addRecordToDept($pid, $did);
        
        //create alerts if necessary
        $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
        $ptcpAlerts->confirmRequirements('dept', $did, $pid);
        
        $json=array('success' => 'yes');
        $this->_helper->json($json);
    }

    public function archiveAction()
    {
        $id = $this->_getParam('id');
        $participantTable = new Application_Model_DbTable_Participants;
        $participantTable->archiveParticipant($id);

        $this->_redirect($_SERVER['HTTP_REFERER']);

    }

}







