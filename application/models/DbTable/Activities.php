<?php

class Application_Model_DbTable_Activities extends Zend_Db_Table_Abstract {
    protected $_name = 'activities';
    
    public function getActivitiesTimeRange($target, $targetID,
                                           $secTarget='', $secTargetID='', 
                                           $startDate='', $endDate='') {
        
        $possibleTargets    = array('user', 'event', 'meeting', 'participant');
        $possibleSecTargets = array('event', 'meeting', 'participant');
        
        //check we have sensible arguments
        if ( (!in_array($target,$possibleTargets)) || 
             ((strlen($secTarget) > 0) && (!in_array($secTarget, $possibleSecTargets)))
           ) {
            throw new exception ("Invalid target passed for reporting.");
        }
        
        //get appropriate records
        if ($target == 'user') {
            $records = $this->getUserActivities($targetID, $secTarget, $secTargetID);
        } else {
            $records = $this->getTypeActivities($target, $targetID);
        }
            
        //filter them with received dates
        if (strlen($startDate > 0)) {
            $begin = strtotime($startDate);
            $searchBegin = TRUE;
        } else {
            $searchBegin = FALSE;
        }   

        if (strlen($endDate > 0)) {
            $end = strtotime($endDate);
            $searchEnd = TRUE;
        } else {
            $searchEnd = FALSE;
        }
        
        if ($searchBegin) {
            foreach ($records as $key => $activity) {
                $myDate = strtotime($activity['date']);
                if (!($myDate > $begin)) {
                    unset($records[$key]);
                }
            }
        }
        
        if ($searchEnd) {
            foreach ($records as $key => $activity) {
                $myDate = strtotime($activity['date']);
                if (!($myDate < $end)) {
                    unset($records[$key]);
                }
            }
        }
        
        return $records;
        
    }
    
    
    public function getUserActivities($userID, $type='', $typeID='') /* */ 
    {
       $id = (int)$userID;
       $cid = (int)$typeID;
       
       if (strlen($type) > 0) {
            $entityList = array('event', 'meeting', 'participant');
            if (!in_array($type, $entityList)) {
                throw new exception ("Invalid activity type $type passed.");
            }
            $column = $type . "ID";
            $select = "userID = $id and $column = $cid";
       } else {
            $select = "userID = $id";
       }
       
       $rowset = $this->fetchAll($select)->toArray();
       return $rowset;
    }
    
    public function getTypeActivities($type, $typeID)
    {
        $id = (int)$typeID;
        $entityList = array('event', 'meeting', 'participant');
        if (!in_array($type, $entityList)) {
            throw new exception ("Invalid activity type $type passed.");
        }
        $column = $type . "ID";
        $select = "$column = $id";
        $rowset = $this->fetchAll($select);
        return $rowset->toArray();
    }
    
    public function getRecord($id) {
        $rows = $this->fetchRow("id = $id")->toArray();
        return $rows;
    }
        
    public function addRecord($userID, $type, $typeID, $date, $duration, $description='') {
       $id  = (int)$userID;
       $cid = (int)$typeID;

       $entityList = array('event', 'meeting', 'participant');
       if (!in_array($type, $entityList)) {
           throw new exception ("Invalid activity type $type passed.");
       }
       
       $column = $type . "ID";
        
       $data = array(
            'userID'        =>  $id,
            $column         =>  $cid,
            'date'          =>  $date,
            'duration'      => $duration,
            'note'   => $description
        );
        
        $recordID = $this->insert($data);
        return $recordID;
    }
    
    public function updateRecord($id, $duration='', $description='')
    {
        $data = array();
        
        if (strlen($duration) > 0) {
            $data['duration'] = $duration;
        }
        
        if (strlen($description) > 0) {
            $data['description'] = $description;
        }
        
        if (count($data) == 0) {
            throw new exception ("Nothing to update activity record with!");
        } else {
            $this->update($data, "id = $id");
        }
    }
}