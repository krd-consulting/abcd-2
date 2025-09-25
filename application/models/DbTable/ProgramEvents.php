<?php
  
class Application_Model_DbTable_ProgramEvents extends Zend_Db_Table_Abstract {
    protected $_name = 'programEvents';
        
    protected function _checkPerm($uID,$programID) {
        $progTable = new Application_Model_DbTable_Programs;
        $permission = $progTable->programAllowed($uID, $programID);
        
        if (!$permission) {
            $name = $progTable->getName($programID);
            throw new exception("Sorry, your account is not associated with program $name.");
        }
        
        return $permission;
    }
    
    protected function _filterByDates($records=array(), $timefield, $filterStartDate, $filterEndDate) {
        if (strlen($filterStartDate) > 0) {
            $begin = strtotime($filterStartDate);
            $searchBegin = TRUE;
        } else {
            $searchBegin = FALSE;
        }   
        if (strlen($filterEndDate) > 0) {
            $end = strtotime($filterEndDate);
            $searchEnd = TRUE;
        } else {
            $searchEnd = FALSE;
        }
        
        if ($searchBegin) {
            foreach ($records as $key => $meeting) {
                $myDate = strtotime($meeting[$timefield]);
                
                if (($myDate < $begin)) {
                    unset($records[$key]);
                }
            }
        }
        
        if ($searchEnd) {
            foreach ($records as $key => $meeting) {
                $myDate = strtotime($meeting[$timefield]);
                if ($myDate > $end) {
                    unset($records[$key]);
                }
            }
        }
        
        return $records;
    
    }
    
    public function getProgEvents($progID,$uID,$filterStartDate='',$filterEndDate='') /* */ 
    {
        $perm = $this->_checkPerm($uID,$progID);
        
        $where = "programID = $progID AND doNotDisplay = 0";
        $list  = $this->fetchAll($where)->toArray();
        
        if (strlen($filterStartDate) > 0 || strlen($filterEndDate) > 0) {
            
            $list = $this->_filterByDates($list,'startDate',$filterStartDate,$filterEndDate);
        }
        
        return $list;
    }
    
    public function getEventNeeds($eventID,$uID) 
    {
        $signUpTable = new Application_Model_DbTable_ProgramEventSignups;
        $jobsTable = new Application_Model_DbTable_VolunteerJobs;
        $usersTable = new Application_Model_DbTable_Users;
        
        $event = $this->fetchRow("id = $eventID")->toArray();
        $perm = $this->_checkPerm($uID, $event['programID']);
                
        $jobNeedsLine = $event['jobsNeeded'];
        $jobNeeds = json_decode($jobNeedsLine,TRUE); //"TRUE" = assoc option
        
        
        
        $totalNeeded = 0;
        $totalSignedUp = 0;
        
        $jobs = array();
        
        //iterate through jobNeeds
        foreach ($jobNeeds as $jobArray) {
            $jobID = $jobArray['name'];
            $numNeeded = $jobArray['value'];
            
            
            $jobVolunteers = array();
            $jobName = $jobsTable->getName($jobID);
            $signups = $signUpTable->getSignupsByType($eventID, $jobID, 'jobs');
            
            
            $numberSignedUp = count($signups);
            
            $totalNeeded += $numNeeded;
            $totalSignedUp += $numberSignedUp;
            
            foreach ($signups as $signupRow) {
                $volID = $signupRow['userID'];
                $volName = $usersTable->getName($volID);
                $activeVol = array($volID,$volName,$jobName);
                array_push ($jobVolunteers,$activeVol);
            }
            
            $jobArray= array(
                'jobID' => $jobID,
                'jobName' => $jobName,
                'neededCount' => $numNeeded,
                'signedUpCount' => $numberSignedUp,
                'resources' => $jobVolunteers
            );
            array_push($jobs,$jobArray);
        }
        
        $result = array(
            'eventID' => $eventID,
            'eventName' => $event['name'],
            'needDetails' => $jobs,
            'numberNeeded' => $totalNeeded,
            'numberSignedUp' => $totalSignedUp            
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
    
    public function addAppointment($data=array()) {
        $eventID = $this->insert($data);
        //print_r($data);
        return $eventID;
    }
    
    public function getEvent($eventID) {
        $result = $this->fetchRow("id=$eventID","startDate ASC");
        return $result->toArray();
    }
    
    public function addEvent($progID,$jobsLine,$location,$startDate,$endDate,$name,$desc,$uID) {
       $perm = $this->_checkPerm($uID,$progID);
        
       //$jobsLine = json_encode($jobsNeeded);
       
        $data = array(
           'programID' => $progID,
           'jobsNeeded' => $jobsLine,
           'startDate' => $startDate,
           'endDate' => $endDate,
           'location' => $location,
           'createdBy' => $uID,
           'name' => $name,
           'description' => $desc
       );
        
       $eventID = $this->insert($data);
       return $eventID;
    }
    
    public function updateEvent($id, $data, $uid) {
        $event = $this->fetchRow("id = $id");
        $perm = $this->_checkPerm($uid,$data['programID']);
        $data['updatedBy'] = $uid;
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