<?php

class Application_Model_DbTable_ProgramFunders extends Zend_Db_Table_Abstract
{
    protected $_name = 'programFunders';

    public function getList($column,$id) /* $column = 'programs' or 'funders' */ 
    {
        switch ($column) {
            case 'programs' : 
                $select     = "funderID = " . (int)$id;
                $colname    = "programID";
                break;
            case 'funders' :
                $select     = "programID = " . (int)$id;
                $colname    = "funderID";
                break;
            default : throw new Exception("Could not get list of $column");
        }

        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results,$row[$colname]);
        }
        return $results;
    }
        
    public function addRecord($program,$funder) {
        $data = array(
            'programID'     => (int)$program,
            'funderID'      => (int)$funder
        );
        $this->insert($data);
    }
    
    public function delRecord($program,$funder) {
        $select = "programID = " . (int)$program . " and funderID = " . (int)$funder;
        $this->delete($select);
    }
    
    public function getRecord ($funder,$prog) {
        $record = $this->fetchRow("funderID = $funder AND programID = $prog");
        return $record->toArray();
    }
    
}