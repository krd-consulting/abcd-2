<?php

class Application_Model_DbTable_Participants extends Zend_Db_Table_Abstract {
    protected $_name='participants';
    
    protected function _calculateAge($dob) {
        $age = date('Y') - substr($dob,0,4);
        if (strtotime(date('Y-m-d')) - strtotime(date('Y') . substr($dob,4,6)) < 0) {
            $age--;
        }
        return $age;
    }
    
    public function getStaffPtcps($id=NULL) {
        if ($id == NULL) {
            $root = Zend_Registry::get('root');
            $evaluator = Zend_Registry::get('evaluator');
            $mgr = Zend_Registry::get('mgr');
            $uid = Zend_Registry::get('uid');
        } else {
            $uid = $id;
            $userTable = new Application_Model_DbTable_Users;
            $record = $userTable->getRecord($uid);
            $role = $record['role'];
            
            $root = $evaluator = $mgr = 0;
            
            switch ($role) {
                case '40' : $root = $mgr = TRUE; break;
                case '30' : $mgr = TRUE; break;
                case '20' : break;
                case '10' : $evaluator = TRUE; break;
                default : throw new exception ("UserID $id has undefined role $role, can't pull participants.");
            }
        }

        //if root, get all
        if ($root || $evaluator) {
            
            $result = $this->getIDs();
        } else { //for others, get by dept
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
            
            $myDepts = $userDepts->getList('depts', $uid);
            $result = array();
            
            foreach ($myDepts as $deptID) {
                $deptList = $ptcpDepts->getList('ptcp', $deptID);
                foreach ($deptList as $ptcpID) {
                    array_push($result, $ptcpID);
                }
            }
        }
        return array_unique($result);
        
    }
    
    public function getForms($ptcpID,$type=NULL) {
        //returns an array of formIDs that the participant has filled out
        //$type can be prepost or singleuse
        $formIDs = array();
        
        $formsTable = new Application_Model_DbTable_Forms;
        $dForms = new Application_Model_DbTable_DynamicForms;
        
        $allForms = $formsTable->fetchAll()->toArray();
        
        foreach ($allForms as $formInstance) {
            
            if ($type != NULL) {
                if ($formInstance['type'] != $type) continue;
            }
            if ($formInstance['target'] != 'participant') {
                continue;
            }
            $table = $formInstance['tableName'];
            $id = $formInstance['id'];
            $name = $formInstance['name'];
            $records = $dForms->getRecords($ptcpID, $table);
            if (count($records) > 0) {
                $formIDs[$id] = $name;
            }
        }
        return $formIDs;
    }
    
    public function getName($id) {
        $row = $this->fetchRow("id = $id");
        $name = $row['firstName'] . ' ' . $row['lastName'];
        return $name;
    } 
    
    public function search($key) {
        //current functionality does not work for multiple first names, so we will concat db instead
//        $names = split(' ', $key);
//        if (count($names) <= 2) {
//            $fname = $names[0];
//            $lname= $names[1];
//
//            if (!is_null($fname) && !is_null($lname)) {        
//                $select = $this->select()->where('firstName like \'%' . $fname . '%\' 
//                                           AND lastName like \'%' . $lname . '%\'');
//            } else {
//              $select = $this->select()->where('firstName like \'%' . $key . '%\' 
//                                           OR lastName like \'%' . $key . '%\'');  
//            }
//        } else {
            $selectText = "CONCAT_WS (' ', firstName, lastName) like '%$key%'";
            $select = $this->select()->where($selectText);
//        }
        
        $rowset = $this->fetchAll($select);
        return $rowset;
    }

    public function autoCompleter($arg) {
        $select = $this->select()->where('firstName like \'%' . $arg . '%\' 
                                       OR lastName like \'%' . $arg . '%\'');
        $rowset = $this->fetchAll($select);
        return $rowset->toArray();
    }
    
    public function getRecord ($id) {
        $id = (int)$id;
        $row = $this->fetchRow('id = ' . $id);
        if (!$row) {
               throw New Exception("Could not find a participant with that ID.");
        }
        $record = $row->toArray();
        $record['age'] = $this->_calculateAge($record['dateOfBirth']);
        
        return $record;
    }
    
    public function addParticipant($fname, $lname, $dob) {
        
        $now = date("Y-m-d");
        
        $data = array(
            'firstName'     => trim($fname),
            'lastName'      => trim($lname),
            'dateOfBirth'   => $dob,
            'createdOn'     => $now
        ); 
        
        return $this->insert($data);
        
    }
    
    public function getParticipantID($fname,$lname,$dob) {
        $select =   'firstName = \'' . $fname . 
                    '\' AND lastName =\'' . $lname .
                    '\' AND dateOfBirth = \'' . $dob . '\'';
        
        $participant = $this->fetchRow($select);
        $id = $participant->id;
        return $id;
    }
    
    public function editParticipant($id, $fname, $lname, $dob) {
        $data = array(
            'id' => $id,
            'firstName' => $fname,
            'lastName' => $lname,
            'dateOfBirth' => $dob,
        );
        
        $this->update($data, 'id = ' . (int)$id);
    }
    
    public function deleteParticipant($id) {
        $id = (int)$id;
        $this->delete('id = ' . $id);
    }
    
    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $userRecord) {
            array_push($result, $userRecord['id']);
        }
        return $result;
    }
}
