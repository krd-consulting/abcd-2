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
    
    public function getStaffHomePtcps($id) {
        $sql = "SELECT p.id from participants p, participantDepts pd, userDepartments ud WHERE"
                . " p.id = pd.participantID AND"
                . " pd.deptID = ud.deptID AND"
                . " ud.userID = $id AND ud.homeDept = 1";
        $query=$this->getAdapter()->query($sql);
        $result = $query->fetchAll();
        return array_column($result,'id');
    }
    
    public function getStaffPtcps($id=NULL,$mode='IDONLY') {
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
                case '4' : $root = $mgr = TRUE; break;
                case '3' : $mgr = TRUE; break;
                case '2' : break;
                case '1' : $evaluator = TRUE; break;
                default : throw new exception ("UserID $id has undefined role $role, can't pull participants.");
            }
        }

        
        //if root, get all
        if ($root || $evaluator) {
            if ($mode == 'IDONLY') {
                $result = $this->getIDs();
            } elseif ($mode == 'FULLREC') {
                $result = $this->fetchAll(NULL,"lastName ASC")->toArray();
            } else {
                throw new exception("Invalide mode $mode passed to Participants Model");
            }
        } else { //for others, get by dept
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
            
            $myDepts = $userDepts->getList('depts', $uid);
            $myDeptsString = "(" . implode(",",$myDepts) . ")";
            
            if ($mode == 'IDONLY') {
                $col = "p.id";
            } else {
                $col = "p.*";
            }
            
            $sql = ("SELECT $col from participants p, participantDepts pd WHERE p.id = pd.participantID AND pd.deptID in $myDeptsString ORDER BY p.lastName ASC");
            $query=$this->getAdapter()->query($sql);
            $rawResult = $query->fetchAll();
            
            if ($mode == 'IDONLY') {
                $result = array();
                foreach ($rawResult as $key => $idArray) {
                    array_push($result,$idArray[id]);
                }
            } else {
                $result = $rawResult;
            }
        }
        return $result;
        
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
            $escapedKey = addslashes($key);
            $selectText = "CONCAT_WS (' ', firstName, lastName) like '%$escapedKey%'";
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
            'firstName'     => $fname,
            'lastName'      => $lname,
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

    public function archiveParticipant($id) {
        $data = array('doNotDisplay' => 1);
        $this->update($data, "id = $id");
    }
    
    public function getIDs() {
        $row = $this->fetchAll(NULL,"lastName ASC");
        
        $result = array();
        foreach ($row as $userRecord) {
            array_push($result, $userRecord['id']);
        }
        return $result;
    }
    
    public function getFullRecords($ids) {
        if(!is_array($ids)) {
            throw new Zend_Exception('Expected $ids to be an array of participant IDs.');
        }

        // return nothing when given no ids
        // prevents the query below from throwing an error when given an empty array
        if(count($ids) == 0) {
            return array();
        }

        $select = $this->select()->where('id IN (?)', $ids);
        return $this->fetchAll($select)->toArray();
    }
    
}
