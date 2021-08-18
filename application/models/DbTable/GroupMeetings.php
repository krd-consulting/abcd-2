<?php
class Application_Model_DbTable_GroupMeetings extends Zend_Db_Table_Abstract {
    protected $_name='groupMeetings';
    
    /*FIELDS:
     *  id
     *  groupID
     *  enrolledIDs (list of participants at this meeting)
     *  unenrolledCount (how many who're not enrolled)
     *  date
     *  duration
     *  notes
     */
    
    protected function _dateFilter($rowset = array(), $startDate, $endDate) {
        $results = $rowset;
        
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
            foreach ($results as $key => $meeting) {
                $myDate = strtotime($meeting['date']);
                if (!($myDate >= $begin)) {
                    unset($results[$key]);
                }
            }
        }
        
        if ($searchEnd) {
            foreach ($results as $key => $meeting) {
                $myDate = strtotime($meeting['date']);
                if (!($myDate <= $end)) {
                    unset($results[$key]);
                }
            }
        }
        
        return $results;
    }
    
    public function getPtcpMtgRecord($groupID, $ptcpID, $startDate='', $endDate='') {
        $meetings = array();
        $mtgPtcps = new Application_Model_DbTable_ParticipantMeetings;
        
        $groupMeetings = $this->getGroupMeetings($groupID, $startDate, $endDate);
        
        //print_r($groupMeetings);
        
        foreach ($groupMeetings as $key => $instance) {
            $attending = explode(',', $instance['enrolledIDs']);
            if (!in_array($ptcpID, $attending)) {
                unset($groupMeetings[$key]);
            } else {
                $mid = $instance['id'];
                $mtgRecord = $mtgPtcps->getRecord($mid, $ptcpID);
                $meetings[$mid] = array(
                  'id'          => $mid,
                  'date'        => $instance['date'],
                  'duration'    => $instance['duration'],
                  'level'       => $mtgRecord['participationLevel'],
                  'vol'         => $mtgRecord['volunteer'],
                  'note'        => $mtgRecord['note']
                );
            }
        }        
        return $meetings;
    }
    
    public function getGroupMeetings($groupID,$startDate='',$endDate='') {
        $results = $this->fetchAll("groupID = $groupID", "date desc")->toArray();
        $results = $this->_dateFilter($results, $startDate, $endDate);
        return $results;
    }
    
    public function getRecord($id='',$groupID='',$date='') {
        if (strlen($id) > 0) {
            $row = $this->fetchRow("id = $id");
        } elseif ((strlen($groupID) > 0) && (strlen($date) > 0)) {
            $row = $this->fetchRow("groupID = $groupID and date = '$date'");
        } else {
            throw new exception ("Need either a meeting ID or a group ID with date to get meeting record.");
        }
        return $row;
    }
    
    public function getAttendance($id) {
        $row = $this->fetchRow($id);
        if (strlen($row['enrolledIDs']) > 0) {
            $enrolledPeeps = explode(',', $row['enrolledIDs']);
            $enrolledNum = count($enrolledPeeps);
        } else {
            $enrolledNum = 0;
        }    
        
        $unenrolledNum = (int)$row['unenrolledCount'];
        
        $totalAtt = $enrolledNum + $unenrolledNum;
        
        return $totalAtt;
    }
    
    public function getTotalAttendance($groupID, $startDate='',$endDate='') {
        $total = 0;
        $totalUnenrolled = 0;
        $allEnrolled = array();
        //get meetings
        $meetings = $this->getGroupMeetings($groupID, $startDate, $endDate);
        //foreach meeting, get both kinds of attendance and add them.
        foreach ($meetings as $meeting) {
            $myEnrolled = explode(',', $meeting['enrolledIDs']);
            foreach( $myEnrolled as $pID) {
                array_push($allEnrolled, $pID);
            }
            $totalUnenrolled += $meeting['unenrolledCount'];
        }
        
        $allEnrolled = array_unique($allEnrolled);
        
        $total = count($allEnrolled) + (int)$totalUnenrolled;
        
        return $total;
    }
    
    public function addRecord($data=array()) {
        $id = $this->insert($data);
        return $id;
    } 
    
    public function updateRecord ($id, $data=array()) {
        $this->update($data, "id = $id");
    }
}
    