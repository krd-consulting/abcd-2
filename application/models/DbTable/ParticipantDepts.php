<?php

/**
 * Description of ParticipantDepts
 *
 * @author roman
 * Associates Participants (clients) with Departments
 * 
 * $participantID
 * $deptID
 */
class Application_Model_DbTable_ParticipantDepts extends Zend_Db_Table_Abstract {
    protected $_name = 'participantDepts';
    
    public function getList($column,$id) /* $column = 'ptcp' or 'depts' */ 
    {
        switch ($column) {
            case 'ptcp' : 
                $select     = "deptID = " . (int)$id;
                $colname    = "participantID";
                break;
            case 'depts' :
                $select     = "participantID = " . (int)$id;
                $colname    = "deptID";
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
        
    public function addRecordToDept($ptcp,$dept) {
        $data = array(
            'participantID' => (int)$ptcp,
            'deptID' => (int)$dept
        );
        $this->insert($data);
    }
    
    public function delRecordFromDept($ptcp,$dept) {
        $select = "participantID = " . (int)$ptcp . " and deptID = " . (int)$dept;
        $this->delete($select);
    }
}