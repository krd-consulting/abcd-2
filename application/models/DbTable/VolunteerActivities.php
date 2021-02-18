<?php

class Application_Model_DbTable_VolunteerActivities extends Zend_Db_Table_Abstract {
    protected $_name = 'volunteerActivities';
    
    protected function _checkValidArgs($target) {
        $validTargets = array(
            'vol',
            'prog',
            'ind',
            'group',
            'supe',
            ''
        );
        
        if (!in_array($target,$validTargets)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    
    protected function _buildQuery($target,$targetID) {
        $primaryID = (int)$targetID;
        
        switch ($target) {
            case 'vol':
                $select = "`volunteerID` = $primaryID";
                break;
            case 'prog':
                $select = "`programID` = $primaryID";
                break;
            case 'ind' :
            case 'group' :
            case 'supe' :
                $select = "`type` = $target AND `typeID` = $primaryID";
        }
        
        return $select;
    }
    
    public function hoursReport($target,$targetID,$startDate='',$endDate='') {
        $sqlText = "SELECT sum(`duration`) as total FROM volunteerActivities WHERE doNotDisplay = 0";
        switch ($target) {
            case 'vol' :
            case 'volunteer' : $sqlText .= " AND volunteerID=$targetID";
                break;
            case 'group' : 
            case 'participant' : $sqlText .= " type='$target' AND typeID='$targetID'";
                break;
            default: throw new exception ("Invalid target type $target passed to VolunteerActivities model.");
        }
        
        if (strlen($startDate) > 0) {
            $sqlText .= " AND date >= '$startDate'";
        }
        
        if (strLen($endDate) > 0) {
            $sqlText .= " AND date <= '$endDate'";
        }
        
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $query = $db->query($sqlText);
        $row = $query->fetchAll();
//        print_r($row);
        $hours = $row[0]['total'];
        if (strlen($hours) == 0) {$hours = "0";}
        return $hours;
    }
    
    public function getTimeTotals($target,$targetID,$startDate,$endDate) {
        
    }
    
    public function getActivitiesTimeRange($target, $targetID,
                                           $secTarget='', $secTargetID='', 
                                           $startDate='', $endDate='') {
        
        //check we have sensible arguments
        if ( !($this->_checkValidArgs($target)) ) {
            throw new Exception("Invalid target $target passed to Volunteer Activity search"); 
        }
        
        if ( !($this->_checkValidArgs($secTarget)) ) {
            throw new Exception("Invalid second target $secTarget passed to Volunteer Activity search"); 
        }
        
        //get appropriate records
        $records = $this->getTypeActivities($target, $targetID, $secTarget, $secTargetID);
            
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
    
    public function getTypeActivities($target, $targetID, $secTarget='', $secTargetID='')
    {
        $primaryID = (int)$targetID;
        
        //check we have sensible arguments
        if ( !($this->_checkValidArgs($target)) ) {
            throw new Exception("Invalid target $target passed to Volunteer Activity search"); 
        }
        
        if ( !($this->_checkValidArgs($secTarget)) ) {
            throw new Exception("Invalid second target $secTarget passed to Volunteer Activity search"); 
        }
        
        $select = $this->_buildQuery($target,$targetID);

        if (strlen($secTarget) > 0) {
            $secSelect = $this->_buildQuery($secTarget,$secTargetID);
            $select .= " AND " . $secSelect;
        }
        
        $select .= " AND doNotDisplay=0";
        
        
        $return = array();
        $groupTable = new Application_Model_DbTable_Groups;
        $ptcpTable = new Application_Model_DbTable_Participants;
        
        $order = "date DESC";
        
        $rowset = $this->fetchAll($select,$order);
        $result = $rowset->toArray();
        $goodResult = array();
        foreach ($result as $key => $row) {
            unset($nameTable);
            //get name
            switch ($row['type']) {
                case 'group'        : $nameTable = $groupTable; break;
                case 'participant'  : $nameTable = $ptcpTable; break;
                default: throw new exception ("Unknown entity type " . $row['type'] . "passed to VolunteerActivities model.");
            }
            
            if($row['type'] == 'group' && $row['typeID'] == 0) {
                continue;
            }
            $typeName = $nameTable->getName($row['typeID']);
            $row['typeName'] = $typeName;
            array_push($goodResult,$row);
            }
        
        return $goodResult;
    }
    
    public function getRecord($id) {
        $rows = $this->fetchRow("id = $id")->toArray();
        return $rows;
    }
        
    public function addRecord(array $data) {
        $keys = array( //required keys
            'volunteerID',
            'programID',
            'type',
            'typeID',
            'date',
            'fromTime',
            'toTime',
            'duration',
            'description'
        );
        
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) throw new exception ("Data array for Volunteer Activities missing requried $key key."); 
        }                
        
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
            $code = $this->update($data, "id = $id");
        }
        
        return $code;
    }
}