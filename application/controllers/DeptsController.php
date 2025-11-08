<?php

class DeptsController extends Zend_Controller_Action
{

    private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
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

        /* Set Database */
        $this->db = $this->getInvokeArg('bootstrap')->getResource('db');

    }

    public function indexAction()
    {
	$this->_helper->redirector('list','depts');
    }

    public function listAction()
    {
        $deptTable = new Application_Model_DbTable_Depts();
        
        $this->view->layout()->customJS = 
                 '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                 '<script type="text/javascript" src="/js/filter.js"></script>'; 
	if ($this->mgr) {
	  $this->view->layout()->customJS .=         
         '<script type="text/javascript" src="/js/editDataWithModal.js"></script>';
	} 
	
	if ($this->root){
	  $this->view->layout()->customJS .=         
                 '<script type="text/javascript" src="/js/deptCreate.js"></script>';
         
	$depts = $deptTable->fetchAll()->toArray();
        } else {
            $depts = array();
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $deptIDs = $userDepts->getList('depts', $this->uid);
            foreach ($deptIDs as $deptID) {
                $curdept = $deptTable->getDept($deptID);
//                $deptObj = (object) $curdept;
                array_push($depts,$curdept);
            }
        }
        
        $deptCount = count($depts);
                
        $this->view->count = $deptCount;        
        $this->view->depts = $depts;
        $this->view->admin = $this->root;
        
    }

    public function addAction()
    {
	
        $newdept = $_GET['name'];
        
        $depts = new Application_Model_DbTable_Depts;
        $dept = $depts->addDept($newdept);
               
        $this->_helper->json($dept);
	
    }

    public function deleteAction()
    {
	if ($this->getRequest()->isPost())
	{
	  $del = $this->getRequest()->getPost('del');
	  if ($del == 'Yes') 
	  {
		$id = $this->getRequest()->getPost('id');
		$dept = new Application_Model_DbTable_Depts();
		$dept->deleteDept($id);

		$this->_helper->redirector('list');
	  }
	} else {
		$id = $this->_getParam('id', 0);
		$dept = new Application_Model_DbTable_Depts();
		$this->view->dept = $dept->getDept($id);
	}
    }

    public function profileAction()
    {
	$deptTable = new Application_Model_DbTable_Depts();
        $id = $this->_getParam('id');
        $dept = $deptTable->getDept($id);
        
        $db = $this->getInvokeArg('bootstrap')->getResource('db');
        
        $userSelect = $db->query('SELECT * from users as u, userDepartments as ud
                                  WHERE u.id = ud.userID AND
                                  ud.deptID = ' . $id);
        
        $ptcpSelect = $db->query('SELECT * from participants as p, participantDepts as pd
                                  WHERE p.id = pd.participantID AND
                                  pd.deptID = ' . $id);
        
        $progTable = new Application_Model_DbTable_Programs;
        $progs = $progTable->getProgByDept($id);
        
        
        $formSelect = $db->query('SELECT * from forms as f, deptForms as df 
                                  WHERE f.id = df.formID AND
                                  df.deptID = ' . $id);
        
        $ptcpsAlerts = new Application_Model_DbTable_AlertsParticipants;
        
        $users = $userSelect->fetchAll();
        $ptcps = $ptcpSelect->fetchAll();
        
        foreach ($ptcps as $key => $ptcp) {
            $pid = $ptcp['id'];
            $flags = $ptcpsAlerts->getPtcpAlertStatus($pid);
            $ptcp['flag'] = $flags;
            $ptcps[$key] = $ptcp;
        }
        $forms = $formSelect->fetchAll();
        
        $groupTable = new Application_Model_DbTable_Groups;
        $totalGroups = 0;
        foreach ($progs as $program) {
            $pid = $program['id'];
            $groups = $groupTable->getProgramGroups($pid);
            $numGroups = count($groups);
            $totalGroups += $numGroups;
        }
        
        $deptForms = new Application_Model_DbTable_DeptForms;
        $defaultForms = $deptForms->getSpecial('defaultForm', $id);
        
        $createProgForm = new Application_Form_AddProgram;
        
        $this->view->dept = $dept;
        $this->view->users = $users;
        $this->view->ptcps = $ptcps;
        $this->view->progs = $progs;
        $this->view->forms = $forms;
        $this->view->numGroups = $totalGroups;
        $this->view->defaultForm = $defaultForms;
        $this->view->addProgForm = $createProgForm;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/progCreate.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>'
        ;
    }
    
    public function addmemberAction() {
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/genericSort.js"></script>' .
                '<script type="text/javascript" src="/js/filterLi.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>'
        ;
        
        $id = $this->_getParam('id');
        $type = $this->_getParam('type');
        $depts = new Application_Model_DbTable_Depts;
        $dept = $depts->getDept($id);
                
        switch ($type) {
            case 'user' : 
                $peopleDepts = new Application_Model_DbTable_UserDepartments;
                $people = new Application_Model_DbTable_Users;
                $managerID = $peopleDepts->getManager($id);
                $columnType = 'users';
                $header = "Add Staff to ";
                $requiredIDs = NULL;
                break;
            
            case 'ptcp' : 
                $peopleDepts = new Application_Model_DbTable_ParticipantDepts;
                $people = new Application_Model_DbTable_Participants;
                $managerID = NULL;
                $requiredIDs = NULL;
                $columnType = 'ptcp';
                $header = "Add Participants to ";
                break;
            
            case 'form' : 
                $peopleDepts = new Application_Model_DbTable_DeptForms;
                $people = new Application_Model_DbTable_Forms;
                $managerID = NULL;
                $requiredIDs = $peopleDepts->getSpecial('required', $id);
                $columnType = 'forms';
                $header = "Add forms to ";
                break;
                
            default: throw new Exception("Can only add Staff, Participants or Forms.");
        }
        
        $currentPeople = array();
        $addPeople     = array();
        
        $currentPeopleIDs = $peopleDepts->getList($columnType, $id);
        $allPeopleIDs = $people->getIDs();
        $addPeopleIDs = array_diff($allPeopleIDs,$currentPeopleIDs);
        
        foreach ($currentPeopleIDs as $cid) {
            $currentRecord = $people->getRecord($cid);
            array_push($currentPeople,$currentRecord);
        }
        
        foreach ($addPeopleIDs as $aid) {
            $addRecord = $people->getRecord($aid);
            array_push($addPeople,$addRecord);
        }
        
        $this->view->currentRecords = $currentPeople;
        $this->view->addRecords = $addPeople;
        $this->view->dept = $dept;
        $this->view->manager = $managerID;
        $this->view->requiredIDs = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;
    }
}











