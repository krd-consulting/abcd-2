<?php

class Application_Model_DbTable_Resources extends Zend_Db_Table_Abstract
{

    protected $_name = 'aclResources';

    public function getResource($name) 
    {
	$row = $this->fetchRow('name = \'' . $name . '\'');
	  if (!$row) {
		throw new Exception("Could not find a resource called $name");
	  }
	return $row->toArray();
    }
}

