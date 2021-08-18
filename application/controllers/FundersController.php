<?php

class FundersController extends Zend_Controller_Action
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
	$this->_helper->redirector('list','funders');
    }

    public function listAction()
    {
        $funderTable = new Application_Model_DbTable_Funders();
        
        if ($this->root || $this->mgr){
        $this->view->layout()->customJS = 
                 '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                 '<script type="text/javascript" src="/js/filter.js"></script>' .
                 '<script type="text/javascript" src="/js/funderCreate.js"></script>';
	$funders = $funderTable->fetchAll();
        } else {
            throw new exception("You do not have permission to access this listing.");
        }
        
        $funderCount = count($funders);
                
        $this->view->count = $funderCount;        
        $this->view->funders = $funders;
        $this->view->admin = $this->root;
        
    }

    public function addAction()
    {
	
        $newfunder = $_GET['name'];
        
        $funders = new Application_Model_DbTable_Funders;
        $funder  = $funders->addRecord($newfunder);
               
        $this->_helper->json($funder);
	
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
	$funderTable = new Application_Model_DbTable_Funders;
        $id = $this->_getParam('id');
        $funder = $funderTable->getRecord($id);
        
        $db = $this->db;
               
        $progSelect = $db->query('SELECT * from programs as p, programFunders as pf
                                  WHERE p.id = pf.programID AND
                                  pf.funderID = ' . $id);
        
        $progs = $progSelect->fetchAll();
        
        $funderForms = new Application_Model_DbTable_FunderForms;
        $formIDs = $funderForms->getList('forms', $id);
        $formTable = new Application_Model_DbTable_Forms;
        $formArray = array();
        
        foreach ($formIDs as $formID) {
            $form = $formTable->getRecord($formID);
            $record = $funderForms->getRecord($formID, $id);
            $formArray[$formID]['name'] = $form['name'];
            $formArray[$formID]['required'] = $record['required'];
            $formArray[$formID]['frequency'] = $record['frequency'];
        }
        
        $this->view->funder = $funder;
        $this->view->progs = $progs;
        $this->view->forms = $formArray;
        
        if ($this->mgr) {
            $this->view->layout()->customJS = 
                    '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' .
                    '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                    '<script type="text/javascript" src="/js/updateDB2.js"></script>' ;
        } else {
            $this->view->layout()->customJS = 
                    '<script type="text/javascript" src="/js/disable.js"></script>';
        }
                
    }
    
    public function addmemberAction() {
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/genericSort.js"></script>' .
                '<script type="text/javascript" src="/js/filterLi.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>';
        
        $id = $this->_getParam('id');
        $type = $this->_getParam('type');
        $funders = new Application_Model_DbTable_Funders;
        $funder = $funders->getRecord($id);
        
        switch ($type) {
            case 'form' : 
                $peopleDepts = new Application_Model_DbTable_FunderForms;
                $people = new Application_Model_DbTable_Forms;
                $requiredIDs = $peopleDepts->getRequired($id);
                $columnType = 'forms';
                $header = "Add forms to ";
                break;
                
            case 'prog' : 
                $peopleDepts = new Application_Model_DbTable_ProgramFunders;
                $people = new Application_Model_DbTable_Programs;
                $requiredIDs = '';
                $columnType = 'programs';
                $header = "Add programs to ";
                break;
                
            default: throw new Exception("Can only add Forms or Programs.");
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
        $this->view->funder = $funder;
        $this->view->requiredIDs = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;
    }

}











