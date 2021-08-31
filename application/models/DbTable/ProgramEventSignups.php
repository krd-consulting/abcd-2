<?php

class Application_Model_DbTable_ProgramEventSignups extends Zend_Db_Table_Abstract
{
    protected $_name = 'programEventSignups';

    public function getVolEvents($volID) {
        $eventTable = new Application_Model_DbTable_ProgramEvents;
        $events = array();
        
        $eventIDs = $this->getList('events',$volID);
        
        foreach ($eventIDs as $eID) {
            $event = $eventTable->getEvent($eID);
            array_push($events,$event);
        }
        
        return $events;
    }
    
    public function getUpcomingEvents($volID) {
        $eventTable = new Application_Model_DbTable_ProgramEvents;
        $jobsTable = new Application_Model_DbTable_VolunteerJobs;
        
        $now = strtotime(date("Y-m-d"));
        $list = array_unique($this->getList('events',$volID));
        $myEvents = array();
        
        
        foreach ($list as $eventID) {
            $record = $eventTable->getEvent($eventID);            
            if (strtotime($record['startDate']) < $now) {
                continue;
            } else {
                $jobs = $this->fetchAll("userID = $volID AND eventID = $eventID")->toArray();
                $jobNames = array();
                foreach ($jobs as $jobRow) {
                    $jobID = $jobRow['jobID'];
                    $jobName = $jobsTable->getName($jobID);
                    array_push($jobNames,$jobName);
                }
                
                $origDate = $record['startDate'];
                $printDate = date("l, F j",strtotime($origDate));
                $printTime = date("g:i a", strtotime($origDate));
                
                $eventInfo = array(
                    'id'        => $eventID,
                    'printDate' => $printDate,
                    'date'      => $origDate,
                    'name'      => $record['name'],
                    'job'       => implode(", ", $jobNames),
                    'location'  => $record['location'],
                    'printTime' => $printTime
                );
                
                $myEvents[strtotime($origDate)] = $eventInfo;
            }
        }
        ksort($myEvents);
        return $myEvents;
    }
    
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
        
    public function addRecord($eventID,$userID,$jobID) {
        $data = array(
            'eventID'     => (int)$eventID,
            'userID'      => (int)$userID,
            'jobID'       => (int)$jobID
        );
        $this->insert($data);
    }
    
    public function delRecord($eventID,$userID,$jobID) {
        $select = "eventID = " . (int)$eventID . " and userID = " . (int)$userID . " and jobID = " . (int)$jobID;
        $this->delete($select);
    }
    
    public function getRecord ($eventID,$userID) {
        $record = $this->fetchRow("eventID = $eventID AND userID = $userID");
        return $record->toArray();
    }
    
    public function countTotalSignups($eventID) {
        $list = $this->getList('vols',$eventID);
        return count($list);
    }
    
    public function getSignupsByType($eventID,$jobID='',$type='event') {
        switch ($type) {
            case 'event' : $select = "eventID = $eventID"; break;
            case 'job' : 
            case 'jobs': $select = "eventID = $eventID AND jobID = $jobID"; break;
            default: throw new exception ("Unknown signup type $type passed to event signups model.");
        }
        
        $result = $this->fetchAll($select)->toArray();
        return $result;
    }
    
    
    
}