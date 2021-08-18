<?php

class Application_Model_DbTable_Alerts extends Zend_Db_Table_Abstract {
    protected $_name = 'alerts';
    
    public function getRecord($id) {
        $row = $this->fetchRow("id = " . $id);
        $row = $row->toArray();
        return $row['alert'];
    }
       
    public function addRecord($alert) {
       $data = array(
            'alert'         =>  $alert
        );
        $recordID = $this->insert($data);
        return $recordID;
    }
    
    public function updateRecord($id, $alert)
    {
        $data = array(
            'alert' => $alert
        );
        $this->update($data, "id = $id");
    }
}