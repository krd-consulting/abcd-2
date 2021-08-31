<?php

class Application_Model_DbTable_DeptForms extends Zend_Db_Table_Abstract
{
    protected $_name = 'deptForms';

    public function getRecord($formID, $deptID) 
    {
        $select = "deptID = $deptID and formID = $formID";
        $row = $this->fetchRow($select)->toArray();
        return $row;
    }
    
    public function getList($column,$id) /* $column = 'forms' or 'depts' */ 
    {
        switch ($column) {
            case 'forms' : 
                $select     = "deptID = " . (int)$id;
                $colname    = "formID";
                break;
            case 'depts' :
                $select     = "formID = " . (int)$id;
                $colname    = "deptID";
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
        
    public function addRecord($form,$dept,$freq = NULL,$req = 0,$default = 0) {
        $data = array(
            'formID'        => (int)$form,
            'deptID'        => (int)$dept,
            'frequency'     => (int)$freq,
            'required'      => (int)$req,
            'defaultForm'   => (int)$default
        );
        $this->insert($data);
    }
    
    public function delRecord($form,$dept) {
        $select = "formID = " . (int)$form . " and deptID = " . (int)$dept;
        $this->delete($select);
    }
    
    public function getSpecial($type,$dept) {
        
        $allowedTypes = array('required','defaultForm'); //column names, 'default' not allowed
        if (!in_array($type, $allowedTypes)) {
            throw new exception("Faulty special type '$type' passed to database.");
        }
        
        $select = $type . " = 1 and deptID = " . $dept;
        $rowset = $this->fetchAll($select);
        
        
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results,$row->formID);
        }
        
        return $results;
    }
    
}