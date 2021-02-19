<?php

class Application_Model_DbTable_ScheduleSets extends Zend_Db_Table_Abstract {
    protected $_name = 'scheduleSets';
        
    public function getList($uID,$root=FALSE,$mode='all') /* */ 
    {
        $deptSchedulesTable = new Application_Model_DbTable_ScheduleDepts;
        $deptUsersTable = new Application_Model_DbTable_UserDepartments;
        $ids = array();
        $list = array();
        
        if (!$root) {
            $myDepts = $deptUsersTable->getList("depts",$uID);

            foreach ($myDepts as $deptID) {
                $setIDs = $deptSchedulesTable->getList('sets',$deptID);
                $ids = array_unique(array_merge($ids,$setIDs));
            }
        } else {
            $rows = $this->fetchAll()->toArray();
            foreach ($rows as $record) {
                array_push($ids,$record['id']);
            }
        }
        
        if (count($ids) > 0) {
            $idString = "(" . implode(",",$ids) . ")";
            $where = "id IN $idString AND doNotDisplay = 0";
            $list=$this->fetchAll($where)->toArray();
        }       
        switch ($mode) {
            case 'all': return $list; break;
            case 'ids': return $ids; break;
            default: throw new exception("Unknown mode $mode passed to Schedule Sets model."); break;
        }
    //        return $list;
    }
    
    public function getSet($id) 
    {
            $scheduleSet=$this->fetchRow("id = $id")->toArray();
            return $scheduleSet;
    }
        
    public function addSet(array $data) {
       $recordID = $this->insert($data);
       return $recordID;
    }
    
    public function archiveRecord($id,$uid,$root=false) {
        $data = array (
            'doNotDisplay' => 1,
            'createdBy' => $uid
        );
        
        $result = $this->update($data,"id = $id");
        return $result;
    }
    
}