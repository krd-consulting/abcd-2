<?php

class Application_Model_DbTable_Depts extends Zend_Db_Table_Abstract
{

    protected $_name = 'departments';

    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $userRecord) {
            array_push($result, $userRecord['id']);
        }
        return $result;
    }
    
    public function getRecord($id) {
        $row = $this->getDept($id);
        return $row;
    }
    
    public function getName($id) {
        $dept = $this->getDept($id);
        $name = $dept['deptName'];
        return $name;
    }
    
    public function getDept($id) 
    {
    	$id = (int)$id;
	$row = $this->fetchRow('id = ' . (int)$id);
	  if (!$row) {
		throw new Exception("Could not find department #$id");
	  }
	return $row->toArray();
    }

    public function addDept($name) 
    {
	$data = array(
		'deptName' => $name,
		);
	$this->insert($data);
        $dept = $this->fetchRow('deptName = \'' . $name . '\'');
        return $dept->toArray();
    }

    public function updateDept($id,$name)
    {
    	$data = array(
		'deptName' => $name,
		);
	$this->update($data, 'id = ' . (int)$id);
    }

    public function deleteDept($id)
    {
	$this->delete('id = ' . (int)$id);
    }
}

