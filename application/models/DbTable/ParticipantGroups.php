<?php

class Application_Model_DbTable_ParticipantGroups extends Zend_Db_Table_Abstract {
    protected $_name = 'participantGroups';
    
    public function getList($column,$id) /* $column = 'ptcp' or 'groups' */ 
    {
        switch ($column) {
            case 'ptcp' : 
                $select     = "groupID = " . (int)$id;
                $colname    = "participantID";
                break;
            case 'groups' :
                $select     = "participantID = " . (int)$id;
                $colname    = "groupID";
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
    
    public function getRecord($ptcpID,$groupID) {
        $select = $this->select()
                       ->where("groupID = '$groupID' and participantID = '$ptcpID'")
                       ->order('enrollDate desc');
        $rows = $this->fetchAll($select)->toArray();
        return $rows[0];
    }
        
    public function enroll($ptcp,$group) {
        $now = date("Y-m-d");
        
        $data = array(
            'participantID' =>  (int)$ptcp,
            'groupID'     =>  (int)$group,
            'enrollDate'    =>  $now
        );
        
        $this->insert($data);
        
        //SET RELEVANT PROGRAM STATUS AS ACTIVE
        
        $groupTable = new Application_Model_DbTable_Groups;
        $groupRecord = $groupTable->getRecord($group);
        $programID = $groupRecord['programID'];
        
        $programTable = new Application_Model_DbTable_ParticipantPrograms;
        
        $programTable->changeStatus($ptcp, $programID, $now, 'active', 'Enrolled in ' . $groupRecord['name']);
    }
    
    
}