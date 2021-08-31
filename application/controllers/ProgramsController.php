<?php

class ProgramsController extends Zend_Controller_Action
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

    public function indexAction()
    {
        $this->_helper->redirector('list');
    }

    public function calendarAction() {
        $programID = $this->_getParam('id');
        $progTable = new Application_Model_DbTable_Programs;
        $name = $progTable->getName($programID);
        
        if ($this->evaluator || $this->volunteer) {
            $readOnly = TRUE;
        } else {
            $readOnly = FALSE;
        }
        
        $perm = $progTable->programAllowed($this->uid, $programID);
        if (!$perm) {
            throw new exception("Your account does not have access to this program's calendar.");
        }
        
        //get volunteer jobs
        $vJobsTable = new Application_Model_DbTable_VolunteerJobs;
        $vJobs = $vJobsTable->getJobsByProgram($programID);
        $displayJobs = array();
        
        foreach ($vJobs as $job) {
            array_push($displayJobs,array(
                $job['id'] => $job['name']
            ));
        }
        
        $eventTable = new Application_Model_DbTable_ProgramEvents;
        $events = $eventTable->getProgEvents($programID,$this->uid,date('Y-m-d'));
        $numNeeded = 0;
        $numWorking = 0;
        $numEvents = count($events);
        
        
        
        foreach ($events as $event) {
            $eventID = $event['id'];
            $needArray = $eventTable->getEventNeeds($eventID,$this->uid);
            
            $numNeeded += (int)$needArray['numberNeeded'];
            $numWorking += (int)$needArray['numberSignedUp'];
            }
        
            
        $program = array(
            'numEvents' => $numEvents,
            'numVolunteers' => $numWorking,
            'numNeeded' => $numNeeded,
            'id' => $programID,
            'name' => $name
        );
        
        
        $this->view->program = $program;
        $this->view->vJobs = $displayJobs;
        $this->view->enrollVolForm = new Application_Form_EnrollVolunteer;
        $this->view->readOnly = $readOnly;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/dhtmlxscheduler.js" charset="utf-8"></script>' .
                '<script type="text/javascript" src="/js/abcdProgScheduler.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_cookie.js" charset="utf-8"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_recurring.js" charset="utf-8"></script>' .

'';
        
        
    }
    
    public function addAction()
    {
        $name = $_GET['name'];
        $did = $_GET['dept'];
        $vType = $_GET['voltype'];
        
        if ($did == 0) {$did = NULL;}
        
        $progTable  =   new Application_Model_DbTable_Programs;
        $progTable->addProg($name,$did,$vType);
        $program = $progTable->fetchRow('name = \'' . $name . '\'');
        $pid = $program->id;
        
        if ($pid) {
            $jsonResult['success'] = 'yes';
        } else {
            $jsonResult['success'] = 'no';
            $jsonResult['message'] = 'Could not add program ';
        }
        
        $userPrograms = new Application_Model_DbTable_UserPrograms;
        $userPrograms->addRecord($this->uid, $pid);
        
        $this->_helper->json($jsonResult);
    }
    
    public function listAction()
    {
        $progTable = new Application_Model_DbTable_Programs;
        //Get list of all programs if root
        if ($this->root) {
            $list = $progTable->fetchAll();
        }
        //Get list of all programs in my dept if manager
        elseif ($this->mgr) {
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $myDepts = $userDepts->getList('depts', $this->uid);
            $list = array();
            foreach ($myDepts as $deptID) {
                $programs = $progTable->getProgByDept($deptID);
                foreach ($programs as $program) {
                    array_push($list,$program);
                }
            }    
        }         
        //Get list of all programs for my uid if staff
        else {
            $userProgs = new Application_Model_DbTable_UserPrograms;
            $progIDs = $userProgs->getList('progs', $this->uid);
            $list = array();
            foreach ($progIDs as $progID) {
                $program = $progTable->getProg($progID);
                array_push($list,$program);
            }
        }
        
        $newList = array();
        $depts = new Application_Model_DbTable_Depts;
        foreach ($list as $program) {
            $deptName = $depts->getDept($program['deptID']);         
            $program['deptID'] = $deptName['deptName'];
            array_push($newList, $program);
        }
        
        //'program add' form
        $form = new Application_Form_AddProgram;
        
        //provide javascript to pop up form and table listings
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/progCreate.js"></script>' . 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/filter.js"></script>'; 
        
        if ($this->mgr) {
            $this->view->layout()->customJS .=
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>';                 
        }
        
        //pass everything to view
        $this->view->mgr      = $this->mgr;
        $this->view->count    = count($list);
        $this->view->programs = $newList;
        $this->view->form     = $form; 
    } 
    
    public function profileAction()
    {
        $id = $this->_getParam('id');
        $programTable = new Application_Model_DbTable_Programs;
        $program = $programTable->getRecord($id);  
        $deptID = $program['deptID'];
        $deptTable = new Application_Model_DbTable_Depts;
        $dept = $deptTable->getRecord($deptID);
        $deptName = $dept['deptName'];
        
        $volStatus = $program['volunteerType'];
        
        //get Staff and volunteers list
        $userPrograms = new Application_Model_DbTable_UserPrograms;
        $userIDs = $userPrograms->getList('users', $id);
        $usersTable = new Application_Model_DbTable_Users;
        $users = array();
        $vols = array();
        
        foreach ($userIDs as $uID) {
            $user = $usersTable->getRecord($uID);
            if ($user['role'] == '15') {
                array_push($vols,$user);
            } else {
                array_push($users,$user);
            }
        }
        
       
        
        //get Participant list
        $ptcpPrograms = new Application_Model_DbTable_ParticipantPrograms;
        $ptcpIDs = $ptcpPrograms->getList('ptcp', $id);
        $ptcpTable = new Application_Model_DbTable_Participants;
        $ptcpStaffTable = new Application_Model_DbTable_ParticipantUsers;
        
        
        $participants = array();
        foreach ($ptcpIDs as $pID) {
            $ptcp = $ptcpTable->getRecord($pID);
            $ptcpRel = $ptcpPrograms->getRecord($pID, $id);
            $participants[$pID]['name'] = $ptcp['firstName'] . ' ' . $ptcp['lastName'];
            $participants[$pID]['dob'] = $ptcp['dateOfBirth'];
            $participants[$pID]['status'] = $ptcpRel['status'];
                //format date
                $sqlDate = strtotime($ptcpRel['statusDate']);
                $sinceDate = date("M j, Y",$sqlDate);
            $participants[$pID]['since'] = $sinceDate;
            $participants[$pID]['statusNote'] = $ptcpRel['statusNote'];
            
                //check if assigned to staff
                
                $psRow = $ptcpStaffTable->fetchAll("participantID = $pID AND programID = $id")->toArray();
                
                if (count($psRow) != 0) {
                    $psRecord = $psRow[0];
                    $participants[$pID]['caseload'] = TRUE;
                    $participants[$pID]['assignedTo'] = $usersTable->getName($psRecord['userID']);
                    $participants[$pID]['assignedToID'] = $psRecord['userID'];
                    $participants[$pID]['assignedToDate'] = $psRecord['enrollDate'];
                } else {
                    $participants[$pID]['caseload'] = FALSE;
                }
               
                
        }
        
        //order Participants by reverse statusDate (currently passed from db as DESC)
        $participants = array_reverse($participants,TRUE);

        //get Group list
        $groupTable = new Application_Model_DbTable_Groups;
        $groups = $groupTable->getProgramGroups($id);
        
        //get Funders
        $funderPrograms = new Application_Model_DbTable_ProgramFunders;
        $funderIDs = $funderPrograms->getList('funders', $id);
        $funderTable = new Application_Model_DbTable_Funders;
        
        $funders = array();
        foreach ($funderIDs as $fID) {
            $funder = $funderTable->getRecord($fID);
            array_push($funders, $funder);
        }
        
        //get volunteer jobs
        $vJobsTable = new Application_Model_DbTable_VolunteerJobs;
        $volJobs = $vJobsTable->getJobsByProgram($id);
        
        //get forms
        $forms = $programTable->getAllForms($id);
        
        $statusForm = new Application_Form_StatusUpdate;
        $statusFilterForm = new Application_Form_FilterForm;
        
        $this->view->program = $program;
        $this->view->deptName = $deptName;
        $this->view->mgr = $this->mgr;
        
        $this->view->users      = $users;
        $this->view->ptcps      = $participants;
        $this->view->groups     = $groups; 
        $this->view->funders    = $funders;
        $this->view->forms      = $forms;
        $this->view->volunteers = $vols;
        $this->view->vJobs      = $volJobs;
        
        $this->view->statusForm = $statusForm;
        $this->view->filterForm = $statusFilterForm;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' . 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/ptcpNote.js"></script>' . 
                '<script type="text/javascript" src="/js/statusFilter.js"></script>' . 
                '<script type="text/javascript" src="/js/filter.js"></script>' . 
                '<script type="text/javascript" src="/js/updateDB3.js"></script>' ; 
        
        $this->view->layout()->customJS .= 
                '<script type="text/javascript" src="/js/scheduler/dhtmlxscheduler.js"></script>' .
                '<script type="text/javascript" src="/js/abcdProgScheduler.js"></script>' .
'';

        
        if (!$this->mgr) {
            $this->view->layout()->customJS .=
                 '<script type="text/javascript" src="/js/disable.js"></script>';
        }
    }
    
    public function associateAction()            {
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/genericSort.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/filterLi.js"></script>' 
                ;
        
        $id       = $this->_getParam('id');
        $type     = $this->_getParam('type');
        $programs = new Application_Model_DbTable_Programs;
        $thisProg = $programs->getRecord($id);
        
        if ((!$this->mgr) && ($type != 'ptcp')) {
            throw new exception("You don't have sufficient access control privileges for this functionality.");
        }
        
        switch ($type) {
            case 'user' :
            case 'volunteer' :
                $assocTable = new Application_Model_DbTable_UserPrograms;
                $secondaryAssoc = new Application_Model_DbTable_UserDepartments;
                $records    = new Application_Model_DbTable_Users;
                $columnType = 'users';
                if ($type =='user') {
                    $header = "Add Staff to ";
                } else {
                    $header = "Add Volunteer to ";
                }
                break;
            
            case 'vjob' :
                $assocTable = new Application_Model_DbTable_ProgramJobs;
                $records    = new Application_Model_DbTable_VolunteerJobs;
                $columnType = 'jobs';
                $header = "Manage Volunteer Jobs for ";
                break;
                
            case 'funder' : 
                $assocTable = new Application_Model_DbTable_ProgramFunders;
                $records    = new Application_Model_DbTable_Funders;
                $columnType = 'funders';
                $header = "Add Funders to ";
                break;
            
            case 'form' : 
                $assocTable = new Application_Model_DbTable_ProgramForms;
                $records    = new Application_Model_DbTable_Forms;
                $columnType = 'forms';
                $header = "Add Forms to ";
                break;
            
            case 'ptcp' : 
                $assocTable = new Application_Model_DbTable_ParticipantPrograms;
                $secondaryAssoc = new Application_Model_DbTable_ParticipantDepts;
                $records    = new Application_Model_DbTable_Participants;
                $columnType = 'ptcp';
                $header = "Participant Enrollment in ";
                break;
            
            default: throw new Exception("Can only add Staff, Participants, Forms and Funders.");
        }
        
        $currentRecords = array();
        $addRecords     = array();
        $requiredIDs    = array();
        
        $currentRecordIDs = $assocTable->getList($columnType, $id);
        $currentRecordIDs = array_unique($currentRecordIDs);
        
        
        //differentiate between staff and volunteers 
        switch ($type) {
            case 'user':
            case 'volunteer':
                $curVolIDs = array();
                $curStaffIDs = array();
                $userTable = new Application_Model_DbTable_Users;
                foreach ($currentRecordIDs as $idToTest) {
                    $isVol=$userTable->isVolunteer($idToTest);
                    
                    if ($isVol) {
                        array_push($curVolIDs,$idToTest);
                    } else {
                        array_push($curStaffIDs,$idToTest);
                    } 
                }
                if ($type == 'user') $currentRecordIDs = $curStaffIDs;
                if ($type == 'volunteer') $currentRecordIDs = $curVolIDs;
                break;
            default: continue;
        }
        
        
        //**If type is Staff or Participants, we should only 
        //  get list of people in this program's department.
        //  
        //**This holds regardless of role, so we don't wind up 
        //  with participants in programs who are not in departments.
        
        if (($type == 'user') || ($type == 'ptcp' || $type == 'volunteer')) {
            $myDept = $thisProg['deptID'];
            $allRecordIDs = $secondaryAssoc->getList($columnType, $myDept);            
        } else {
            $allRecordIDs     = $records->getIDs();
        }

        
        //differentiate between staff and volunteers 
        switch ($type) {
            case 'user':
            case 'volunteer':
                $allVolIDs = array();
                $allStaffIDs = array();
                $userTable = new Application_Model_DbTable_Users;
                foreach ($allRecordIDs as $idToTest) {
                    $isVol=$userTable->isVolunteer($idToTest);
                    
                    if ($isVol) {
                        array_push($allVolIDs,$idToTest);
                    } else {
                        array_push($allStaffIDs,$idToTest);
                    } 
                }
                if ($type == 'user') $allRecordIDs = $allStaffIDs;
                if ($type == 'volunteer') $allRecordIDs = $allVolIDs;
                break;
            default: continue;
        }
        
        //Trim out existing records from the full list.
        $addRecordIDs     = array_diff($allRecordIDs,$currentRecordIDs);
        
        foreach ($currentRecordIDs as $cid) {
            $currentRecord = $records->getRecord($cid);
            array_push($currentRecords,$currentRecord);
            $assocRecord = $assocTable->getRecord($cid,$id);
            if (
                 (($type == 'user') && ($assocRecord['lead']== 1)) ||
                 (($type == 'form') && ($assocRecord['required'] == 1)) ||
                 (($type == 'ptcp') && ($assocRecord['status'] != 'concluded'))
               ) 
            {
                    array_push($requiredIDs, $cid);
            }    
        }
        
        foreach ($addRecordIDs as $aid) {
            $addRecord = $records->getRecord($aid);
            array_push($addRecords,$addRecord);
        }
        
        if ($type == 'vjob') {
            $jobForm = new Application_Form_AddJob;
            $this->view->job = TRUE;
            $this->view->jobForm = $jobForm;
            $this->view->layout()->customJS .= '<script type="text/javascript" src="/js/addJob.js"></script>';
        }
        
        $this->view->currentRecords = $currentRecords;
        $this->view->addRecords = $addRecords;
        $this->view->thisProgram = $thisProg;
        
        $this->view->required = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;
    
    }
}
