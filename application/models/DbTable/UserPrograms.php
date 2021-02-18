<?php

class Application_Model_DbTable_UserPrograms extends Zend_Db_Table_Abstract {
    protected $_name = 'userPrograms';
    
    public function getList($column,$id) /* $column = 'users' or 'progs' or 'volunteers' */ 
    {
        switch ($column) {
            case 'users' : 
                $select     = "programID = " . (int)$id;
                $colname    = "userID";
                break;
            case 'progs' :
                $select     = "userID = " . (int)$id;
                $colname    = "programID";
                break;
            default : throw new Exception("Invalid column \"$column\" passed to user-program model.");
        }

        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row[$colname]);
        }
        return $results;
    }
        
    public function addRecord($user,$prog) {
        
        $data = array(
            'userID'        =>  (int)$user,
            'programID'     =>  (int)$prog,
        );
        
        $this->insert($data);
    }
    
    public function delRecord($user,$prog) {
        $this->delete('userID = ' . $user . ' and programID = ' . $prog);
    }

    public function getRecord ($user,$prog) {
        $record = $this->fetchRow("userID = $user AND programID = $prog");
        return $record->toArray();
    }
    
    public function getLead($prog) {
        $lead = $this->fetchRow('programID = ' . $prog . ' and lead=1');
        if (count($lead) == 1) {
            $leadID = $lead->userID;
        } else {
            $leadID = 0;
        }
        
        return $leadID;
    }
    
    public function setLead($prog,$id) {
        $data = array(
            'programID' => $prog,
            'userID'    => $id,
            'lead'      => 1
        );
        
        $this->update($data);
        
    }
}