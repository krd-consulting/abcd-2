<?php

class Application_Model_DbTable_CustomValues extends Zend_Db_Table_Abstract
{

    protected $_name = 'customValues';

    public function getValue($key) 
    {
    	$row = $this->fetchRow('descriptor = \'' . $key . '\'');
	  if (!$row) {
		return FALSE;
	  }
	$arrayRow=$row->toArray();
        $result=$arrayRow['value'];
        
        return $result;
    }

}

