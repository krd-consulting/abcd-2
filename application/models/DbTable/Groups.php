<?php

class Application_Model_DbTable_Groups extends Zend_Db_Table_Abstract
{

    protected $_name = 'groups';
    
    public function getName($groupID) {
        $record = $this->fetchRow("id = $groupID")->toArray();
        return $record['name'];
    }
    
    public function addGroup($name, $desc, $programID) 
    {
	$now = date("Y-m-d");
        $data = array(
                'name'              => $name,
                'programID'         => $programID,
                'description'       => $desc,
                'beginDate'         => $now
		);
        
	return $this->insert($data);
    }

    public function updateGroup($id,$name,$desc,$programID)
    {
    	$data = array(
                'id'                => $id,
		'name'              => $name,
                'description'       => $desc,
                'programID'         => $programID
		);
	$this->update($data, 'id = ' . (int)$id);
    }
    
    public function deleteGroup($id)
    {
	$this->delete('id = ' . (int)$id);
    }
    
    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $record) {
            array_push($result, $record['id']);
        }
        return $result;
    }

    public function getRecord ($id) {
        $id = (int)$id;
        $row = $this->fetchRow('id = ' . $id);
        if (!$row) {
               throw New Exception("Could not find a group with ID $id.");
        }
        return $row->toArray();
    }
    
    public function getProgramGroups($progID) {
        $result = $this->fetchAll("programID = $progID")->toArray();
        return $result;
    }
    
    public function search($key) {
        $name = $key;    
        $select = $this->select()->where('name like \'%' . $name . '%\'');
        $rowset = $this->fetchAll($select);
        
        return $rowset;
    }
    
    public function getStaffGroups($userID) {
        $result = array();
        $root = Zend_Registry::get('root');
        $mgr = Zend_Registry::get('mgr');
        $evaluator = Zend_Registry::get('evaluator');
        $uid = Zend_Registry::get('uid');
        
        //if mgr, get all programs in dept
        if ($mgr) {
            $userDepartments = new Application_Model_DbTable_UserDepartments;
            $programs = new Application_Model_DbTable_Programs;
            $staffProgs = array();
            
            $myDepts = $userDepartments->getList('depts', $uid);
            foreach ($myDepts as $deptID) {
                $myProgs = $programs->getProgByDept($deptID);
                foreach ($myProgs as $prog) {
                    array_push($staffProgs, $prog['id']);
                }
            }
        } else { //otherwise get staff programs
            $userPrograms = new Application_Model_DbTable_UserPrograms;
            $staffProgs = $userPrograms->getList('progs', $userID);
        }
        
        //get all group IDs in those programs
        foreach ($staffProgs as $progID) {
            $groups = $this->getProgramGroups($progID);
            foreach ($groups as $record) {
                array_push($result, $record['id']);
            }
        }
        
        if ($root || $evaluator) {$result = $this->getIDs();}
        
        return $result;
    }
}