<?php

class Application_Model_DbTable_ScheduledEvents extends Zend_Db_Table_Abstract {
    protected $_name = 'scheduledEvents';
        
    public function getApptsBySet($setID,$uID,$root=FALSE) /* */ 
    {
        $where = "setID = $setID AND doNotDisplay = 0";
        $results = $this->fetchAll($where)->toArray();
        
        //permissions need to be added
        
        
        return $results;
    }
    
    public function getApptsByResource($resourceType,$resourceID,$uID,$root=FALSE) {
        $where = "resourceType = $resourceType AND resourceID = $resourceID";
        $results = $this->fetchAll($where)->toArray();
        
        //permissions need to be added
        
        return $results;
    }
       
    public function addAppointment(array $data) {
       $recordID = $this->insert($data);
       return $recordID;
    }
    
    public function archiveRecord($id) {
        $data = array (
            'doNotDisplay' => 1
        );
        $this->update($data,"id = $id");
    }
    
}