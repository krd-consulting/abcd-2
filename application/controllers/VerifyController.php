<?php

class VerifyController extends Zend_Controller_Action
{
    private $root = FALSE;
    private $mgr = FALSE;
    private $evaluator = FALSE;
    
    public function init()
    {
        
        $this->auth = Zend_Auth::getInstance();
        $this->uid = $this->auth->getIdentity()->id;
        $this->role = $this->auth->getIdentity()->role;
        
        switch ($this->role) {
            case '4' : $this->root = TRUE;
                        $this->mgr = TRUE;
                        break;
            case '3' : $this->mgr = TRUE;
                        break;
            case '1' : $this->evaluator = TRUE;
                        break;
        }
        
    }

    public function indexAction()
    {
        //should never be accessed directly:
        $this->_redirect($_SERVER['HTTP_REFERER']);
    }

    public function emailAction() 
    {
        $jsonResult = array();
        $jsonResult['success'] = 'no';
        $email = $_GET['value'];
        $query = ("eMail = '$email'");
        $table = new Application_Model_DbTable_Users;
        $result = $table->fetchRow($query);
        if (count($result) == 0) {
            $jsonResult['success'] = 'yes';
        }
        
        $this->_helper->json($jsonResult);
    }
    
    public function usernameAction()
    {
        $jsonResult = array();
        $jsonResult['success'] = 'no';
        $username = $_GET['value'];
        $query = ("userName = '$username'");
        $table = new Application_Model_DbTable_Users;
        $result = $table->fetchRow($query);
        if (count($result) == 0) {
            $jsonResult['success'] = 'yes';
        }
        
        $this->_helper->json($jsonResult);
    }
    
    public function groupnameAction()
    {
        $jsonResult = array();
        $jsonResult['success'] = 'no';
        $groupname = $_GET['value'];
        $query = ("name = '$groupname'");
        $table = new Application_Model_DbTable_Groups;
        $result = $table->fetchRow($query);
        if (count($result) == 0) {
            $jsonResult['success'] = 'yes';
        }
        
        $this->_helper->json($jsonResult);
    }
    
    public function duplicateAction()
    {
        // Check for duplicates in the database
        
        $type = $_GET['type'];
        $fname = trim($_GET['fname']);
        $lname = trim($_GET['lname']);
        
        $jsonResult = array();
        
        switch ($type) {
            case 'participant' : 
                $person = new Application_Model_DbTable_Participants;
                $personDept = new Application_Model_DbTable_ParticipantDepts;
                $dob = $_GET['dob'];        
                
                $query = ('firstName = \'' . $fname . 
                  '\' AND lastName = \'' . $lname . 
                  '\' AND dateOfBirth = \'' . $dob . '\'');
                
                break;
            case 'user' :
                $person = new Application_Model_DbTable_Users;
                $personDept = new Application_Model_DbTable_UserDepartments;
                
                $uname = $_GET['uname'];
                $email = $_GET['email'];
                $pwd = $_GET['pwd'];
                $role = $_GET['role'];

                $query = ('firstName = \'' . $fname . 
                  '\' AND lastName = \'' . $lname . 
                  '\' AND eMail = \'' . $email . '\'');
                
                break;
            default : 
                $msg = NULL;
        }
          
        $test = $person->fetchAll($query);
        $jsonResult['success'] = 'yes';   
        if (count($test) == 0 && $type == 'participant') {
            $pid = $person->addParticipant($fname, $lname, $dob);
            $safeID = new Application_Model_DbTable_PtcpSecureIds;
            $safeID->addRecord($pid);
            $jsonResult['pid'] = $pid;
        }

        if (count($test) == 0 && $type == 'user') {
            $jsonResult['pid'] = $person->addUser($uname, trim($pwd), $email, $fname, $lname, $role);
        }
        
              
        if (count($test) == 1) {
            $jsonResult['pid'] = $test[0]['id'];
        }
        if (count($test) > 1) {
            $jsonResult['success'] = 'no';
        }
        
        $userDepts = new Application_Model_DbTable_UserDepartments;
        $depts = new Application_Model_DbTable_Depts;
        $deptlist = array();
        if (!$this->root) {
            $myDeptIDs = $userDepts->getList('depts', $this->uid);
        } else {
            $myDeptIDs = $depts->getIDs();
        }
        
        foreach ($myDeptIDs as $deptID) {
            $thisdept = $depts->getDept($deptID);
            array_push($deptlist,$thisdept);
        }
        $jsonResult['deptlist'] = $deptlist;
        
        $this->_helper->json($jsonResult);
        
    }

}