<?php
 
class Application_Model_DbTable_ProgramEvents extends Zend_Db_Table_Abstract {
    protected $_name = 'programEvents';
        
    protected function _checkPerm($uID,$programID) {
        $progTable = new Application_Model_DbTable_Programs;
        $permission = $progTable->programAllowed($uID, $progID);
        
        if (!$permission) {
            $name = $progTable->getName($progID);
            throw new exception("Sorry, your account is not associated with program $name.");
        }
        
        return $permission;
    }
    
    public function getFutureEventsByProg($progID, $uID) {
        $date = date('Y-m-d');
        $where = "programID = $progID AND startDate > $date";
        $list = $this->fetchAll($where)->toArray();
    }
    
    public function getProgEvents($progID,$uID) /* */ 
    {
        $perm = $this->_checkPerm($uID,$progID);
        
        $where = "programID = $progID";
        $list  = $this->fetchAll($where)->toArray();
        
        return $list;
    }
    
    public function getEventNeeds($eventID,$uID) 
    {
        $signUpTable = new Application_Model_DbTable_ProgramEventSignups;
        $event = $this->fetchRow("id = $eventID");
        
        $perm = $this->_checkPerm($uID, $event['programID']);
        
        $numNeeded = $event['volunteersNeeded'];
        $numSignedUp = $signUpTable->countSignups($eventID);
        
        $result = array(
            'id'        => $eventID,
            'needed'    => $numNeeded,
            'signedup'  => $numSignedUp
        );            
        
        return $result;
    }
    
    public function getEventVolunteers($eventID,$uID) {
        $event = $this->fetchRow("id = $eventID");
        $perm = $this->_checkPerm($uID,$event['programID']);
        
        $signUpTable = new Application_Model_DbTable_ProgramEventSignups;
        $volTable = new Application_Model_DbTable_Users;
        $result = array();
        
        $list = $signUpTable->getList('vols',$eventID);
        foreach ($list as $volID) {
            $name = $volTable->getName($volID);
            $result[$volID] = $name;
        }
        
        return $result;
    }
    
    
    public function addEvent($progID, $numNeeded,$startDate,$endDate,$name,$desc,$uID) {
       $perm = $this->_checkPerm($uID,$progID);
        
        $data = array(
           'programID' => $progID,
           'volunteersNeeded' => $numNeeded,
           'startDate' => $startDate,
           'endDate' => $endDate,
           'createdBy' => $uID,
           'name' => $name,
           'description' => $desc
       );
        
       $eventID = $this->insert($data);
       return $eventID;
    }
    
    public function updateEvent($id, $data, $uid) {
        $event = $this->fetchRow("id = $id");
        $perm = $this->_checkPerm($uID,$event['programID']);
        
        $result = $this->update($data,"id = $id");
        return $result;
    }
    
    public function archiveRecord($id,$uid,$root=false) {
        $event = $this->fetchRow("id = $id");
        $perm = $this->_checkPerm($uID,$event['programID']);
        
        $data = array (
            'doNotDisplay' => 1,
            'updatedBy' => $uid
        );
        
        $result = $this->update($data,"id = $id");
        return $result;
    }
    
}