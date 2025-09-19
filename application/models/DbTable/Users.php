<?php

class Application_Model_DbTable_Users extends Zend_Db_Table_Abstract
{

    protected $_name = 'users';

    protected function _isDeleted($testID) {
        $record = $this->fetchRow("id = $testID");
        return $record['doNotDisplay'];
    }

    protected function _getStaffListByType($type,$uid) {
        $validTypes = array('progs','depts');
        if (!in_array($type,$validTypes)) {
            throw new exception ("Invalid type $type passed to getStaffListByType");
        }
        
        switch ($type) {
            case 'progs': $assocTable = new Application_Model_DbTable_UserPrograms;
                break;
            case 'depts': $assocTable = new Application_Model_DbTable_UserDepartments;
                break;
            default: break;
        }
        $result = array();
        //get my groupings
            $myGps = $assocTable->getList($type,$uid);
            
        //get userIDs for each grouping
            foreach ($myGps as $gid) {
                $userIDs = $assocTable->getList('users',$gid);
                foreach ($userIDs as $sid) {                    
                    if (!$this->_isDeleted($sid) && !in_array($sid,$result) && ($sid != $uid)) {
                        array_push($result,$sid);
                    }
                }
            }           
        return $result;
    }
    

    public function getName($id) {
        $row = $this->fetchRow("id = $id");
        $name = $row['firstName'] . ' ' . $row['lastName'];
        return $name;
    }
    
    public function getStaffUsers() {
        $root = Zend_Registry::get('root');
        $mgr = Zend_Registry::get('mgr');
        $uid = Zend_Registry::get('uid');
        
        //if root, get all
        if ($root) {
            $result = $this->getIDs();
        } else { //for others, get by dept
            $userDepts = new Application_Model_DbTable_UserDepartments;
            
            $myDepts = $userDepts->getList('depts', $uid);
            $result = array();
            foreach ($myDepts as $deptID) {
                $deptList = $userDepts->getList('users', $deptID);
                foreach ($deptList as $userID) {
                    array_push($result, $userID);
                }
            }
        }
        return array_unique($result);
        
    }
    
    public function search($key) {
//        $names = split(' ', $key);
//        $fname = $names[0];
//        $lname= $names[1];
//
//        if (!is_null($fname) && !is_null($lname)) {        
//            $select = $this->select()->where('firstName like \'%' . $fname . '%\' 
//                                       AND lastName like \'%' . $lname . '%\'');
//        } else {
//          $select = $this->select()->where('firstName like \'%' . $key . '%\' 
//                                       OR lastName like \'%' . $key . '%\'');  
//        }

        $selectText = "CONCAT_WS (' ', firstName, lastName) like '%$key%'";
        $select = $this->select()->where($selectText);
        $rowset = $this->fetchAll($select);
        return $rowset;
    }

    public function getAllowedStaffIDs($uid='') {
        //returns list of user IDs logged in UID has access to
        $result = array();
        $root = Zend_Registry::get('root');
        $mgr = Zend_Registry::get('mgr');
        
        //if no uid passed, use currently logged in user
        if (strlen($uid) == 0) {
            $uid = Zend_Registry::get('uid');
        }
        
        if ($root) { //get all users
            $result = $this->getIDs();
        } elseif ($mgr) { //get all users in my departments
            $result = $this->_getStaffListByType('depts',$uid); 
        } else { //get all users in my programs
            $result = $this->_getStaffListByType('progs',$uid);
        }
        
        //always return at least myself (some installs don't use programs)
        //if (count($result) == 0) {
            array_push($result,$uid);
        //}
        
        return array_unique($result);
    }


    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $userRecord) {
            if ($userRecord['doNotDisplay'] == 0) {
                array_push($result, $userRecord['id']);
            }
        }
        return $result;
    }
    
    public function getRecord($id)
    {
       $id = (int)$id;
       $row = $this->fetchRow('id = ' . $id);
	if (!$row) {
	 throw new Exception("Could not find user with UID $id");
	}
       return $row->toArray();
    }

    public function addUser($name,$password,$email,$firstName,$lastName,$role=1) 
    {
       $now  =  date("Y-m-d");
       $encPwd = md5($password);
       
       $data = array(
	'userName' => $name,
	'password' => $encPwd,
	'eMail' => $email,
	'firstName' => $firstName,
	'lastName' => $lastName,
	'createdDate' => $now,
	'lastLogin' => NULL,
	'role' => $role
       );

      return $this->insert($data);
    }

    public function updateUser($id, $name,$password,$email,$firstName,$lastName,$createdDate,$lastLogin='NULL',$role='staff') 
    {
       $data = array(
	'userName' => $name,
	'password' => md5('$password'),
	'eMail' => $email,
	'firstName' => $firstName,
	'lastName' => $lastName,
	'createdDate' => $createdDate,
	'lastLogin' => $lastLogin,
	'role' => $role
       );

      $this->update($data, 'id = ' . (int)$id);
    }

    public function deleteUser($id)
    {
       $this->delete('id = ' . (int)$id);
    }

    public function archiveUser($id) {
        $data = array('doNotDisplay' => 1);
        $this->update($data, "id = $id");
    }

    public function lockUser($id) {
        $data = array('lock' => 1,
                      'password' => 'NOLOGIN');
        $this->update($data, "id = $id");
    }
    
    public function unlockUser($id, $pwd) {
        $setPwd = md5($pwd);
        $data = array('lock' => 0,
                       'password' => $setPwd);
        
        $this->update($data, "id = $id");
    }
}

