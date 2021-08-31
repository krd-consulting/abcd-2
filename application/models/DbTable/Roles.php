<?php

class Application_Model_DbTable_Roles extends Zend_Db_Table_Abstract
{

    protected $_name = 'roles';

    public function getRole($id) 
    {
	$row = $this->fetchRow('id = \'' . $id . '\'');
	  if (!$row) {
		throw new Exception("Could not find role ID $id!");
	  }
	return $row;
    }
}

