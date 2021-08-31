<?php

class Application_Model_DbTable_Communities extends Zend_Db_Table_Abstract
{

    protected $_name = 'communities';

    public function getRecord($name) 
    {
	$row = $this->fetchRow("name = '$name'");
	return $row->toArray();
    }
    
    public function getByQuadrant($quad) {
        $validQuads = array('downtown', 'northwest', 'northeast', 'southwest', 'southeast');
        if (!in_array($quad, $validQuads)) {
            throw new Exception("Invalid quadrant $quad passed to database.");
        }
        
        $results = $this->fetchAll("quadrant = $quad")->toArray();
        return $results;
    }
}

