<?php

class Application_Model_DbTable_FunderForms extends Zend_Db_Table_Abstract
{
    protected $_name = 'funderForms';

    public function getList($column,$id) /* $column = 'forms' or 'funders' */ 
    {
        switch ($column) {
            case 'forms' : 
                $select     = "funderID = " . (int)$id;
                $colname    = "formID";
                break;
            case 'funders' :
                $select     = "formID = " . (int)$id;
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
    
    public function getRecord($formID, $funderID) {
        $record = $this->fetchRow("formID = $formID AND funderID = $funderID");
        return $record->toArray();
    }
    
    public function addRecord($form,$funder,$freq=0,$req=0) {
        $data = array(
            'formID'        => (int)$form,
            'funderID'      => (int)$funder,
            'frequency'     => (int)$freq,
            'required'      => (int)$req
        );
        $this->insert($data);
    }
    
    public function delRecord($form,$funder) {
        $select = "formID = " . (int)$form . " and funderID = " . (int)$funder;
        $this->delete($select);
    }
    
    public function getRequired($funder) {
                
        $select = "required = 1 and funderID = " . (int)$funder;
        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results,$row['formID']);
        }
        return $results;
    }
    
}