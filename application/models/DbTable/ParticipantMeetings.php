<?php

class Application_Model_DbTable_ParticipantMeetings extends Zend_Db_Table_Abstract {
    protected $_name = 'participantMeetings';
    
    public function getPtcpMeetings($pid) {
        $registry = Zend_Registry::getInstance();
        
        //get all meeting records from this table - meetingID, role, notes
        //get duration from groupMeetings
        
        $meetings=array();
        $groupMeetings = $this->fetchAll("participantID = $pid")->toArray();
        $grpMtgTable = new Application_Model_DbTable_GroupMeetings;
        $groupTable = new Application_Model_DbTable_Groups;
        $staffTable = new Application_Model_DbTable_Users;
        
        foreach ($groupMeetings as $record) {
            $groupRecord = $grpMtgTable->getRecord($record['meetingID']);
            $group = $groupTable->getRecord($groupRecord['groupID']);
            $groupName = $group['name'];
            $duration = $groupRecord['duration'];
            $date = $groupRecord['date'];
            
            $record['duration'] = $duration;
            $record['date'] = $date;
            $record['groupName'] = $groupName;
            $record['groupID'] = $group['id'];
            
            array_push($meetings, $record);
        }
        
        //get all activity records from activities
        $activitiesTable = new Application_Model_DbTable_Activities;
        $ptcpActivities = $activitiesTable->getTypeActivities('participant', $pid);
        foreach ($ptcpActivities as $record) {
            //get Staff Name into array:
            $staffPerson = $staffTable->getRecord($record['userID']);
            $name = $staffPerson['firstName'] . ' ' . $staffPerson['lastName'];
            $record['staffName'] = $name;
            
            //check permissions
            if ($registry['mgr']) {
                array_push($meetings,$record);
            } else { //if staff, only keep notes from userIDs in my depts
                $userDepts = new Application_Model_DbTable_UserDepartments;
                $myDepts = $userDepts->getList('depts',$registry['uid']);
                $staffIDs = array();
                foreach ($myDepts as $deptID) {
                    $fellowStaff = $userDepts->getList('users', $deptID);
                    foreach ($fellowStaff as $userID) {
                        if (!in_array($userID, $staffIDs)) {
                            array_push($staffIDs, $userID);
                        }
                    }
                }
               
                if (in_array($record['userID'], $staffIDs)) {
                    array_push($meetings,$record);
                }
            } // end permission-check -> appropriate records are in $meetings
        }
        //sort by date
        
        function sortByDate($a,$b){
            return(strtotime($b['date']) - strtotime($a['date']));
        }
        
        usort($meetings,'sortByDate');
        
        return $meetings;
    }
    
    
    
    public function getList($column,$id) /* $column = 'ptcp' or 'meetings*/ 
    {
        switch ($column) {
            case 'ptcp' : 
                $select     = "meetingID = " . (int)$id;
                $colname    = "participantID";
                break;
            case 'meetings' :
                $select     = "participantID = " . (int)$id;
                $colname    = "meetingID";
                break;
            default : throw new Exception("\"$column\" is not a valid option.");
        }

        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row[$colname]);
        }
        return $results;
    }
    
    public function getRecord($mid,$pid) {
        $rows = $this->fetchRow("meetingID = $mid and participantID = $pid");
        return $rows;
    }
        
    public function addRecord($meetingID, $ptcpID, $level='passive', $note='') {
        $allowedLevels = array('passive','contrib','leadrole');
        
        if (!in_array($level, $allowedLevels)) {
            throw new exception ("Can't set a level $level");
        }
        
        $data = array(
            'meetingID'              =>  (int)$meetingID,
            'participantID'          =>  (int)$ptcpID,
            'participationLevel'     =>  $level,
            'note'                   => $note
        );
        
        $this->insert($data);
    }
    
    
}