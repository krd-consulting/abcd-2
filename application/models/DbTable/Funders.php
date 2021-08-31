<?php

class Application_Model_DbTable_Funders extends Zend_Db_Table_Abstract
{

    protected $_name = 'funders';

    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $record) {
            array_push($result, $record['id']);
        }
        return $result;
    }
    
    public function getRecord($id) {
       	$id = (int)$id;
	$row = $this->fetchRow('id = ' . (int)$id);
	  if (!$row) {
		throw new Exception("Could not find funder #$id");
	  }
	return $row->toArray();
    }

    public function addRecord($name) 
    {
	$data = array(
		'name' => $name,
		);
	$this->insert($data);
        $dept = $this->fetchRow('name = \'' . $name . '\'');
        return $dept->toArray();
    }

    public function updateRecord($id,$name)
    {
    	$data = array(
		'name' => $name,
		);
	$this->update($data, 'id = ' . (int)$id);
    }

    public function deleteRecord($id)
    {
	$this->delete('id = ' . (int)$id);
    }
}

