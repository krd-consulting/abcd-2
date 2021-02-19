<?php

class Application_Model_DbTable_GroupForms extends Zend_Db_Table_Abstract
{
    protected $_name = 'groupForms';

    public function getRecord($formID, $groupID) 
    {
        $select = "groupID = $groupID and formID = $formID";
        $row = $this->fetchRow($select)->toArray();
        return $row;
    }
    
    
    public function getList($column,$id) /* $column = 'forms' or 'groups' */ 
    {
        switch ($column) {
            case 'forms' : 
                $select     = "groupID = " . (int)$id;
                $colname    = "formID";
                break;
            case 'groups' :
                $select     = "formID = " . (int)$id;
                $colname    = "groupID";
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
        
    public function addRecord($form,$group,$freq=0,$req=0) {
        $data = array(
            'formID'        => (int)$form,
            'programID'     => (int)$group,
            'frequency'     => (int)$freq,
            'required'      => (int)$req
        );
        $this->insert($data);
    }
    
    public function delRecord($form,$group) {
        $select = "formID = " . (int)$form . " and groupID= " . (int)$group;
        $this->delete($select);
    }
    
    public function getRequired($group) {
                
        $select = "required = 1 and groupID = " . (int)$group;
        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results,$row['formID']);
        }
        return $results;
    }
    
}