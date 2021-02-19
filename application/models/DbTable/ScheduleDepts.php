<?php

/**
 * Description of ParticipantDepts
 *
 * @author roman
 * Associates Participants (clients) with Departments
 * 
 * $setID
 * $deptID
 */
class Application_Model_DbTable_ScheduleDepts extends Zend_Db_Table_Abstract {
    protected $_name = 'scheduleDepts';
    
    public function getList($column,$id) /* $column = 'sets' or 'depts' */ 
    {
        switch ($column) {
            case 'sets' : 
                $select     = "deptID = " . (int)$id;
                $colname    = "setID";
                break;
            case 'depts' :
                $select     = "setID = " . (int)$id;
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
        
    public function addRecordToDept($set,$dept) {
        $data = array(
            'setID' => (int)$set,
            'deptID' => (int)$dept
        );
        $this->insert($data);
    }
    
    public function delRecordFromDept($set,$dept) {
        $select = "setID = " . (int)$set . " and deptID = " . (int)$dept;
        $this->delete($select);
    }
}