<?php

class Application_Model_DbTable_Activities extends Zend_Db_Table_Abstract {
    protected $_name = 'activities';
    
    
    public function formatActivities(array $activityArray) {
        //$activityArray is numeric array of associative arrays:
        //[id]
        //[userID]
        //[eventID]
        //[meetingID]
        //[participantID]
        //[date]
        //[duration]
        //[note]
        
        
        $formatted = array();
        $mtgTable = new Application_Model_DbTable_GroupMeetings;
        $ptcpTable = new Application_Model_DbTable_Participants;  
        $userTable = new Application_Model_DbTable_Users;
                
        foreach ($activityArray as $activity) {
            $clean = array_filter($activity); //removes empty columns, so we can check type easily
            unset($userName,$entID,$entName,$entType);
            
            $userName = $userTable->getName($clean['userID']);
            
            if (array_key_exists('meetingID',$clean)) {
                $entType = 'group';
                $mtgID = $clean['meetingID'];
                $group = $mtgTable->getGroup($mtgID);
                $entID = $group['id'];
                $entName = $group['name'];                
            } elseif (array_key_exists('participantID', $clean)) {
                $entType = 'participant';
                $entID = $clean['participantID'];
                $entName = $ptcpTable->getName($entID);                
            } else {
                throw new exception("Neither participant nor group meeting found in activity record id " . $clean['id'] . ". ");
            }
            
            $activityRecord = array(
                'actID'     => $clean['id'],
                'userID'    => $clean['userID'],
                'userName'  => $userName,
                'entity'    => $entType,
                'entityID'  => $entID,
                'name'      => $entName,
                'date'      => $clean['date'],
                'duration'  => $clean['duration'],
                'note'      => $clean['note']
            );
            array_push($formatted,$activityRecord);
        }
        return $formatted;
    }
    
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
       
       //$select .= " ORDER BY date DESC";
       
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