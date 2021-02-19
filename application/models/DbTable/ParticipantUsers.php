<?php

class Application_Model_DbTable_ParticipantUsers extends Zend_Db_Table_Abstract {
    protected $_name = 'participantUsers';
    
    protected function setName($name) {
        $this->_name = $name;
    }
    
    public function getCaseLoad($id,$date) {
        $result = array();
        $relevantIDs = array();
        $processedResult = array();
        
        $sqlBegin = "select * from ";
        $sqlEnd = " WHERE userID = $id AND statusDate <= '$date 23:59:59' ORDER BY statusDate DESC";
        
        $tables = array(
            'participantUsers','ptcpUserArchive'
        );
        
        foreach ($tables as $table) {
            $sql = $sqlBegin . $table . $sqlEnd;
            $rows = $this->getAdapter()->fetchAll($sql);
            foreach ($rows as $row) {
                array_push($result,$row);
            }
        }

        foreach ($result as $row) {
            $pID = $row['participantID'];
            $sDate = $row['statusDate'];  
            if (!in_array($pID,$relevantIDs)) {
                array_push($relevantIDs,$pID);
                $processedResult[$pID] = $row;
            } else {
                $existingDate = $processedResult[$pID]['statusDate'];
                if ($sDate > $existingDate) {
                    $processedResult[$pID] = $row;
                }
            }
        }
        return $processedResult;
    }
    
    public function getList($column,$id) /* $column = 'ptcp' or 'users' */ 
    {
        switch ($column) {
            case 'ptcp' : 
                $select     = "userID = " . (int)$id;
                $colname    = "participantID";
                break;
            case 'users' :
                $select     = "participantID = " . (int)$id;
                $colname    = "userID";
                break;
            
            default : throw new Exception("\"$column\" is not a valid option.");
        }
        
        $orderBy = "statusDate DESC"; //order is not preserved in 
                                      //subsequent processing unless DESC
        
        $rowset = $this->fetchAll($select,$orderBy)->toArray();
               
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row[$colname]);
        }
        
        return $results;
    }
    
  
    public function getRecord($ptcpID,$userID,$progID=NULL,$asOfDate=NULL) {
        $where = "userID = '$userID' and participantID = '$ptcpID'";
        
        if (strlen($progID)>0) {
            $where .= " and programID = '$progID'";
        }
        
        if (strlen($asOfDate)>0) {
            $date = date('Y-m-d',$asOfDate);
            $where .= " and statusDate <= '$date 23:59:59'";
        }
        
        $order = "statusDate desc";
                               
        $rows = $this->fetchAll($where, $order)->toArray();
                
        if (count($rows) > 0) {
            return $rows[0]; //return latest only
        } else {
            return $blankArray=array();
        }
    }
    
    public function getWithStatus($userID, $status) {
        $validStatusTypes = array('active', 'waitlist', 'concluded', 'leave');
        if (!in_array($status, $validStatusTypes)) {
            throw new Exception ("Invalid search for status '$status'.");
        }
        $rowset = $this->fetchAll("userID = $userID and status = '$status'")->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row['participantID']);
        }
        return $results;
    }
    
    public function enroll($ptcp,$user,$status='active',$statusNote='') {
        $now = date("Y-m-d");
        
        $data = array(
            'participantID' =>  (int)$ptcp,
            'userID'     =>  (int)$user,
            'enrollDate'    =>  $now,
            'status'        =>  $status,
            'statusNote'    =>  $statusNote
        );
        
        $this->insert($data);
    }
    
    public function changeStatus($ptcp,$user,$prog,$enrollDate,$status,$prevStatus, $statusNote='') {
        $data = array(
            'participantID' =>  (int)$ptcp,
            'userID'     =>  (int)$user,
            'programID'  =>  (int)$prog,
            'enrollDate'    =>  $enrollDate,
            'status'        =>  $status,
            'prevStatus'    =>  $prevStatus,
            'statusNote'    =>  $statusNote
        );
        
        $this->insert($data); //always insert new row, MySQL timestamp will track history.
    }
    
    public function archiveRecord($where) {
        $rowsToArchive = $this->fetchAll($where)->toArray();
    
        foreach ($rowsToArchive as $row) {
            $this->getAdapter()->insert('ptcpUserArchive', $row);
            } 
    }     
}
