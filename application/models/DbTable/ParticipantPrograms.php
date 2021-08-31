<?php

class Application_Model_DbTable_ParticipantPrograms extends Zend_Db_Table_Abstract {
    protected $_name = 'participantPrograms';
    
    public function getList($column,$id) /* $column = 'ptcp' or 'progs' */ 
    {
        switch ($column) {
            case 'ptcp' : 
                $select     = "programID = " . (int)$id;
                $colname    = "participantID";
                break;
            case 'progs' :
                $select     = "participantID = " . (int)$id;
                $colname    = "programID";
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
    
    public function getRecord($ptcpID,$progID) {
        $where = "programID = '$progID' and participantID = '$ptcpID'";
        $order = "statusDate desc";
                       
        $rows = $this->fetchAll($where, $order)->toArray();

        return $rows[0];
    }
    
    public function getWithStatus($progID, $status) {
        $validStatusTypes = array('active', 'waitlist', 'concluded', 'leave');
        if (!in_array($status, $validStatusTypes)) {
            throw new Exception ("Invalid search for status '$status'.");
        }
        $rowset = $this->fetchAll("programID = $progID and status = '$status'")->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row['participantID']);
        }
        return $results;
    }
    
    public function enroll($ptcp,$prog,$status='active',$statusNote='') {
        $now = date("Y-m-d");
        
        $data = array(
            'participantID' =>  (int)$ptcp,
            'programID'     =>  (int)$prog,
            'enrollDate'    =>  $now,
            'status'        =>  $status,
            'statusNote'    =>  $statusNote
        );
        
        $this->insert($data);
    }
    
    public function changeStatus($ptcp,$prog,$enrollDate,$status,$prevStatus, $statusNote='') {
        $data = array(
            'participantID' =>  (int)$ptcp,
            'programID'     =>  (int)$prog,
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
            $this->getAdapter()->insert('ptcpProgramArchive', $row);
                    //insert('ptcpProgramArchive',$row);
        } 
    } 
    
    
}
