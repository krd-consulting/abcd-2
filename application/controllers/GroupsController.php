<?php

class GroupsController extends Zend_Controller_Action
{
private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $db = NULL;
    
    public function init()                       {
        /* Get user credentials */
        /* Get user credentials */
        $this->auth = Zend_Auth::getInstance();
        if (!$this->auth->hasIdentity()) {
            throw new Exception("You are not logged in.");
        }
        
        /* Set UID */
        $this->uid = Zend_Registry::get('uid');
        $this->root = Zend_Registry::get('root');
        $this->mgr = Zend_Registry::get('mgr');
        $this->evaluator = Zend_Registry::get('evaluator');
        $this->volunteer = Zend_Registry::get('volunteer');
        /* Set Database */
        $this->db = $this->getInvokeArg('bootstrap')->getResource('db');

    }

    protected function _filterUsersToVolunteers(array $userIDs) {
        $usersTable = new Application_Model_DbTable_Users;
        $volRecords = array();
        
        foreach ($userIDs as $uID) {
            $user = $usersTable->getRecord($uID);
            if ($user['role'] == '15') {
                array_push($volRecords,$user);
            } else {
                continue;
            }
        }
        
        return $volRecords;
        
    }
    public function indexAction()
    {
        $this->_helper->redirector('list');
    }

    public function addAction()
    {
        $gName  =   $_POST['gname'];
        $pID    =   $_POST['pid'];
        $desc   =   $_POST['desc'];
        
        $groupTable = new Application_Model_DbTable_Groups;
        $result = $groupTable->addGroup($gName, $desc, $pID);
        
        if ($result) {
            $jsonReturn['success'] = TRUE;
        }
        
        $this->_helper->json($jsonReturn);
        
    }

    public function listAction()
    {
        $addGroupForm = new Application_Form_AddGroup;
        $progField    = new Zend_Form_Element_Select('progField');
        $progField->setLabel('Program');
        
        // groups are listed by program
        $programTable = new Application_Model_DbTable_Programs;
        //if root, get list of all program IDs
        if ($this->root) {
            $programIDs = $programTable->getIDs();
        } elseif ($this->mgr) { //if mgr, get list of my depts, and programs in them
            $programIDs=array();
            $deptTable = new Application_Model_DbTable_UserDepartments;
            $myDepts = $deptTable->getList('depts', $this->uid);
            foreach ($myDepts as $deptID) {
                $progs = $programTable->getProgByDept($deptID);
                if (count($progs) > 0) {
                    foreach ($progs as $program) {
                        array_push($programIDs,$program['id']);
                    }
                }
            }
        } else { //otherwise, get list of my programs
            $progUsers = new Application_Model_DbTable_UserPrograms;
            $programIDs = $progUsers->getList('progs', $this->uid);
        }
        
        if (count($programIDs) == 0) {
            throw new exception("You don't have any valid programs associated with your account. Please visit the 'Programs' tab for help.");
        }
        
        //$programIDs now contains all the programs we need
        //get program name and all daughter groups
        $groupList = array();
        $totalCount = (int)0;
        $groupTable = new Application_Model_DbTable_Groups;
        foreach ($programIDs as $progID) {
            //Get program ID and name
            
            $p = $programTable->getRecord($progID);
            $pName = $p['name'];
            
            //add these as select options to our form element;
            $progField->addMultiOption($progID, $pName);
            
            //get group data and organize it into 3-d array
            $groups = $groupTable->getProgramGroups($progID);
            $gCount = count($groups);
            $totalCount+=$gCount;
            
            $groupList[$progID] = array();
            $groupList[$progID]['pName'] = $pName;
            $groupList[$progID]['count'] = $gCount;
            
            foreach ($groups as $group) {
                $gID = $group['id'];
                $groupList[$progID][$gID] = $group;
            }
        }
        
        $addGroupForm->addElement($progField);
        
        $this->view->list = $groupList;
        $this->view->count = $totalCount;
        $this->view->mgr = $this->mgr;
        $this->view->addGroupForm = $addGroupForm;
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/groupCreate.js"></script>' . 
                '<script type="text/javascript" src="/js/collapseTr.js"></script>' . 
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>' . 
                '<script type="text/javascript" src="/js/filter.js"></script>' 
            ;
    }

    public function enrollAction()
    {
        $this->view->layout()->customJS = 
        '<script type="text/javascript" src="/js/filterLi.js"></script>' . 
        '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
        '<script type="text/javascript" src="/js/genericSort.js"></script>' ; 

        $id = $this->_getParam('id');
        $groups = new Application_Model_DbTable_Groups;
        $group = $groups->getRecord($id);
        $type = $this->_getParam('type');
        
        $allowedTypes = array('ptcp','vol');
        
        if (!in_array($type,$allowedTypes)) {
            throw new exception ("Sorry - only participants can be enrolled (you passed $type).");
        }
        
        switch ($type) {
            case 'ptcp':
                $assocTable     =   new Application_Model_DbTable_ParticipantGroups;
                $secondaryAssoc =   new Application_Model_DbTable_ParticipantDepts;
                $records        =   new Application_Model_DbTable_Participants;
                $columnType     =   'ptcp';
                $header         =   "Participant Enrollment in ";
                    break;
            case 'vol':
                $assocTable     =   new Application_Model_DbTable_VolunteerGroups;
                $secondaryAssoc =   new Application_Model_DbTable_UserPrograms;
                $records        =   new Application_Model_DbTable_Users;
                $columnType     =   'users';
                $header         =   "Volunteer Enrollment in ";
                    break;
        }
        
        
        $currentRecords = array();
        $addRecords     = array();
        //$requiredIDs    = array();
        
        $currentRecordIDs = $assocTable->getList($columnType, $id);
        $currentRecordIDs = array_unique($currentRecordIDs);
        //**For participants: 
            //  get list of people in this group's program's department.
            //  
            //**This holds regardless of role, so we don't wind up 
            //  with participants in programs who are not in departments.
        
        //**For volunteers:
            // get list of volunteers in group's program
        
            $programTable = new Application_Model_DbTable_Programs;
            $thisProg = $programTable->getRecord($group['programID']);

        if ($type == 'ptcp') {            
            $deptTable = new Application_Model_DbTable_Depts;
            $myDept = $thisProg['deptID'];
            $viewDept = $deptTable->getRecord($myDept);
            $secondaryID = $myDept;
        }
        if ($type == 'vol') {
            $secondaryID = $group['programID'];   
        }
        
        $allRecordIDs = $secondaryAssoc->getList($columnType,$secondaryID);
        //die("Firing to userprograms: $columnType column, $secondaryID id.");
            //Trim out existing records from the full list.
        $addRecordIDs     = array_diff($allRecordIDs,$currentRecordIDs);
        
        if($type == 'vol') {
            $myVolList = $records->getAllowedVolIDs();
            $currendRecordIDs = array_intersect($currentRecordIDs,$myVolList);
            $addRecordIDs = array_intersect($addRecordIDs,$myVolList);
        }
        
            foreach ($currentRecordIDs as $cid) {
                $currentRecord = $records->getRecord($cid);
                array_push($currentRecords,$currentRecord);    
            }

            foreach ($addRecordIDs as $aid) {
                $addRecord = $records->getRecord($aid);
                array_push($addRecords,$addRecord);
            }
            
        $this->view->currentRecords = $currentRecords;
        $this->view->addRecords = $addRecords;
        $this->view->group = $group;
        $this->view->prog = $thisProg;
        $this->view->dept = $viewDept;
        
        $this->view->header = $header;
        $this->view->type = $type;

    }

    public function profileAction()
    {
        if ($this->_getParam('id')) {
            $id = $this->_getParam('id');
            $permission = FALSE;
        } else {
            throw new exception ("Can't pull group profile - no ID given.");
        }
        
        $groupTable     = new Application_Model_DbTable_Groups;
        $programStaff   = new Application_Model_DbTable_UserPrograms;
        $programTable   = new Application_Model_DbTable_Programs;
        $groupMeetings  = new Application_Model_DbTable_GroupMeetings;
        $groupPtcps     = new Application_Model_DbTable_ParticipantGroups;
        $participants   = new Application_Model_DbTable_Participants;
        $forms          = new Application_Model_DbTable_Forms;
        $groupForms     = new Application_Model_DbTable_GroupForms;
        $ptcpAlerts     = new Application_Model_DbTable_AlertsParticipants;
        $groupVols      = new Application_Model_DbTable_VolunteerGroups;
        $usersTable     = new Application_Model_DbTable_Users;
        
        $group      = $groupTable->getRecord($id);
        $progID     = $group['programID'];
        $program = $programTable->getRecord($progID);
        $programName = $program['name'];
        
        $permittedProgs = $programStaff->getList('progs',$this->uid);
        $permittedVols = $usersTable->getAllowedVolIDs($this->uid);
        
        if ((in_array($progID, $permittedProgs)) || ($this->mgr)) {
            $permission = TRUE;
        } else {
            throw new exception ("Only staff associated with $programName can work with its groups.");
        }
        
        //for tabs:
        //Meeting History
        $meetings = $groupMeetings->getGroupMeetings($id);
                
        //Participant List
        $ptcps = array();
        $ptcpIDs = $groupPtcps->getList('ptcp', $id);
        foreach ($ptcpIDs as $pID) {
            $record = $participants->getRecord($pID);
            $flags = $ptcpAlerts->getPtcpAlertStatus($pID);
            $record['flag'] = $flags;
            array_push($ptcps, $record);
        }
        //Vol List
        $vols = array();
        $myVols = $groupVols->getList('vols',$id);
        $myAllowedVols = array_intersect($myVols,$permittedVols);
        
        foreach ($myAllowedVols as $volID) {
            $vrecord = $usersTable->getRecord($volID);
            array_push($vols,$vrecord);
        }
        
        
        //Form List
        $myForms = $groupForms->getList('forms',$id);
        
        //getting inherited records from program will include dept and funder
        $inheritedForms = $programTable->getAllForms($progID);
        $allForms = array();
        foreach ($inheritedForms as $formID => $iForm) {
            if (!array_key_exists('inherit', $iForm)) {
                $iForm['inherit'] = 'program';
            }
            $allForms[$formID] = $iForm;
        }
        
        //Adding own forms to inherited forms
        foreach($myForms as $formID) {
            $formRecord = $forms->getRecord($formID);
            $formRelRecord = $groupForms->getRecord($formID, $id);
            $allForms[$formID]['name'] = $formRecord['name'];
            $allForms[$formID]['required'] = $formRelRecord['required'];
            $allForms[$formID]['frequency'] = $formRelRecord['frequency'];
        }
        
        //pass variables to view
        $this->view->group      = $group;
        $this->view->meetings   = $meetings; 
        $this->view->ptcps      = $ptcps;
        $this->view->vols       = $vols;
        $this->view->forms      = $allForms;
        $this->view->mgr        = $this->mgr;
        $this->view->progName   = $programName;
        
        //pass javascript to view
        $this->view->layout()->customJS = 
                "<script type='text/javascript' src='/js/filterLi.js'></script>" .
                "<script type='text/javascript' src='/js/jquery.jeditable.js'></script>" .
                "<script type='text/javascript' src='/js/meetingList.js'></script>" .
                "<script type='text/javascript' src='/js/disable.js'></script>" 
        ;
        
    }
    
    public function meetingsAction()
    {
        if ($this->_getParam('id')) {
            $id = $this->_getParam('id');
            $permission = FALSE;
        } else {
            throw new exception ("Can't pull group profile - no ID given.");
        }
        
        $groupTable     = new Application_Model_DbTable_Groups;
        $programStaff   = new Application_Model_DbTable_UserPrograms;
        $programTable   = new Application_Model_DbTable_Programs;
        $groupPtcpTable = new Application_Model_DbTable_ParticipantGroups;
        $ptcpTable      = new Application_Model_DbTable_Participants;
        $groupVolTable  = new Application_Model_DbTable_VolunteerGroups;
        $usersTable = new Application_Model_DbTable_Users;
                
        $group      = $groupTable->getRecord($id);
        $progID     = $group['programID'];
        
        $permittedProgs = $programStaff->getList('progs',$this->uid);
        
        if ((in_array($progID, $permittedProgs)) || ($this->mgr)) {
            $permission = TRUE;
        } else {
            $program = $programTable->getRecord($progID);
            $programName = $program['name'];
            
            throw new exception ("Only staff associated with $programName can work with its groups.");
        }
        
        //get 2 volunteer lists - program based (if no group enrollment) and group based
        $groupVolIDs = $groupVolTable->getList('vols',$id);
        $groupVols = $this->_filterUsersToVolunteers($groupVolIDs);

        $progVolIDs = array_diff($programStaff->getList('users', $progID),$groupVolIDs); //pull only program IDs of those unenrolled
        //$progVolIDs = $programStaff->getList('users', $progID); 
        $progVols = $this->_filterUsersToVolunteers($progVolIDs);
        

        
        $mtgDate = new Zend_Form_Element_Text('date');
        $mtgDate->setAttrib('class','entrydaypicker required')
                ->setLabel('Meeting Date *');
        $this->view->dateElement = $mtgDate->render();
        
        $mtgName = new Zend_Form_Element_Text('eventname');
        $mtgName->setAttrib('class','top-middle')
                ->setLabel('Event Name');
        $this->view->nameElement = $mtgName->render();
        
        $duration = new Zend_Form_Element_Text('duration');
        $duration->setAttrib('class', 'numeric required')
                 ->setLabel('Duration in hours *');
        $this->view->durationElement = $duration->render();
        
        $numUnenrolled = new Zend_Form_Element_Text('unenrolled');
        $numUnenrolled->setAttrib('class', 'numeric')
                      ->setLabel('Number of guests');
        $this->view->unenrolledElement = $numUnenrolled->render();
        
        $numExtraVols = new Zend_Form_Element_Text('guestVols');
        $numExtraVols->setAttrib('class', 'numeric')
                      ->setLabel('Guest volunteers');
        $this->view->volunteersElement = $numExtraVols->render();
        
        $notes = new Zend_Form_Element_Textarea('notes');
        $notes->setAttribs(array(
                            'rows' => '5',
                            'cols' => '120',
                            'class' => 'centred'
                          ))
              ->setLabel('Notes');
        $this->view->notesElement = $notes->render();
        
        $allParticipants = $groupPtcpTable->getList('ptcp', $id);
        $enrolledList = array();
        foreach ($allParticipants as $pid) {
            $ptcpRecord = $ptcpTable->getRecord($pid);
            array_push($enrolledList, $ptcpRecord);
        }
        $this->view->enrolledList = $enrolledList;
        $this->view->volunteerListByGroup = $groupVols;
        $this->view->volunteerListByProg = $progVols;
        $this->view->group = $group;
        $this->view->progid = $progID;
        
        
        $this->view->layout()->customJS = 
                "<script type='text/javascript' src='/js/setHeight.js'></script>" .
                "<script type='text/javascript' src='/js/datePicker.js'></script>" .
                "<script type='text/javascript' src='/js/filter.js'></script>" .
                "<script type='text/javascript' src='/js/jquery.alphanumeric.js'></script>" .
                "<script type='text/javascript' src='/js/jquery.jeditable.js'></script>" .
                "<script type='text/javascript' src='/js/timepicker/jquery.timepicker.min.js'></script>" .
                "<script type='text/javascript' src='/js/meetingCreate.js'></script>"
        ;
    }
}







