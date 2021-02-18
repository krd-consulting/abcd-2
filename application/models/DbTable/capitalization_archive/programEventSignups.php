<?php

class Application_Model_DbTable_ProgramEventSignups extends Zend_Db_Table_Abstract
{
    protected $_name = 'programEventSignups';

    public function getList($column,$id) /* $column = 'events' or 'volunteers | vols | staff' */ 
    {
        switch ($column) {
            case 'events' : 
                $select     = "userID = " . (int)$id;
                $colname    = "eventID";
                break;
            case 'volunteers' :
            case 'vols':
            case 'staff':
                $select     = "eventID = " . (int)$id;
                $colname    = "userID";
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
        
    public function addRecord($eventID,$userID) {
        $data = array(
            'eventID'     => (int)$eventID,
            'userID'      => (int)$user
        );
        $this->insert($data);
    }
    
    public function delRecord($eventID,$userID) {
        $select = "eventID = " . (int)$eventID . " and userID = " . (int)$userID;
        $this->delete($select);
    }
    
//    public function delJobfromevent($eventID, $jobID) {
//        $select = "eventID = " . (int)$eventID . " and jobID = " . (int)$jobID;
//        return $this->delete($select); 
//    }
    
    public function getRecord ($eventID,$userID) {
        $record = $this->fetchRow("eventID = $eventID AND userID = $userID");
        return $record->toArray();
    }
    
    public function countSignups($eventID) {
        $list = $this->getList('vols',$eventID);
        return count($list);
    }
    
}