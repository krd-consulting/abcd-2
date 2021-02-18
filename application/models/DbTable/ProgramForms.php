<?php

class Application_Model_DbTable_ProgramForms extends Zend_Db_Table_Abstract
{
    protected $_name = 'programForms';

    public function getRecord($formID, $progID) 
    {
        $select = "programID = $progID and formID = $formID";
        $row = $this->fetchRow($select)->toArray();
        return $row;
    }
    
    
    public function getList($column,$id) /* $column = 'forms' or 'programs' */ 
    {
        switch ($column) {
            case 'forms' : 
                $select     = "programID = " . (int)$id;
                $colname    = "formID";
                break;
            case 'programs' :
                $select     = "formID = " . (int)$id;
                $colname    = "programID";
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
        
    public function addRecord($form,$program,$freq=0,$req=0) {
        $data = array(
            'formID'        => (int)$form,
            'programID'     => (int)$program,
            'frequency'     => (int)$freq,
            'required'      => (int)$req
        );
        $this->insert($data);
    }
    
    public function delRecord($form,$program) {
        $select = "formID = " . (int)$form . " and programID= " . (int)$program;
        $this->delete($select);
    }
    
    public function getRequired($program) {
                
        $select = "required = 1 and programID = " . (int)$program;
        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results,$row['formID']);
        }
        return $results;
    }
    
}