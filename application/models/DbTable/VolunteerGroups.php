<?php

class Application_Model_DbTable_VolunteerGroups extends Zend_Db_Table_Abstract {
    protected $_name = 'volunteerGroups';
    
    public function getList($column,$id) /* $column = 'vol' or 'groups' */ 
    {
        switch ($column) {
            case 'users' :
            case 'vol'  :
            case 'vols' :
                $select     = "doNotDisplay = 0 AND groupID = " . (int)$id;
                $colname    = "volunteerID";
                break;
            case 'groups' :
                $select     = "doNotDisplay = 0 AND volunteerID = " . (int)$id;
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
    
    public function getRecord($volID,$groupID) {
        $select = $this->select()
                       ->where("groupID = '$groupID' and volunteerID = '$volID'")
                       ->order('enrollDate desc');
        $rows = $this->fetchAll($select)->toArray();
        return $rows[0];
    }
        
    public function enroll($vol,$group) {
        $now = date("Y-m-d");
        
        $data = array(
            'volunteerID' =>  (int)$vol,
            'groupID'     =>  (int)$group,
            'enrollDate'    =>  $now
        );
        $this->insert($data);
    }
    
    public function end($vol,$group) {
        $now = date("Y-m-d");
        $data = array(
            'endDate' => (int)$group
        );
        $this->update($data,"volunteerID = $vol AND groupID = $group");
    }
    
    public function archive($vol,$group) {
        $data = array(
            'doNotDisplay => 1'
        );
        
        $this->update($data,"volunteerID = $vol AND groupID = $group");
    }
    
    
}