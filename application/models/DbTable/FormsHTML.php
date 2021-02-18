<?php

class Application_Model_DbTable_FormsHTML extends Zend_Db_Table_Abstract
{
    protected $_name = 'formsHTML';
    
    public function addFormHTML($id, $editable, $display) {
        
        $data = array(
            'id'        => $id,
            'editable'  => $editable,
            'display'   => $display
        );
        
        $this->insert($data);
    }
    
    public function getHTML($id, $type)
    {
        $validTypes = array('editable', 'display');
        
        if (!(in_array($type, $validTypes))) {
            throw new Exception ("$type is not a valid HTML type for the Forms Model.");
        }
        
        $id = (int)$id;
        $row = $this->fetchRow("id = $id");
        $fullSet = $row->toArray();
        return $fullSet[$type];
    }   
}

