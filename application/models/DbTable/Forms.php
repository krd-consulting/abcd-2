<?php

class Application_Model_DbTable_Forms extends Zend_Db_Table_Abstract
{

    protected $_name = 'forms';
    
    protected function _collateFormRecords(array $orig, array $new) {
        foreach ($new as $id => $freq) {
            if (!array_key_exists($id, $orig)) {
                $orig["$id"] = $freq;
            }
        }
        return $orig;
    }
    
    protected function _getPtcpFormData($ptcpID,$requiredOnly = 1) {
        $forms = array();
        
        $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
        $ptcpProgs = new Application_Model_DbTable_ParticipantPrograms;
        $ptcpGrps  = new Application_Model_DbTable_ParticipantGroups;
        
        $myDepts = $ptcpDepts->getList('depts',$ptcpID);
        $myProgs = $ptcpProgs->getList('progs',$ptcpID);
        $myGroups= $ptcpGrps->getList('groups',$ptcpID);
        
        foreach ($myDepts as $deptID) {
            $deptForms = $this->_getDeptFormData($deptID,$requiredOnly);
            $forms = $this->_collateFormRecords($forms, $deptForms);
        }
        
        foreach ($myProgs as $progID) {
            $progForms = $this->_getProgFormData($progID,$requiredOnly);
            $forms = $this->_collateFormRecords($forms, $progForms);
        }
        
        foreach ($myGroups as $groupID) {
            $groupForms = $this->_getGroupFormData($groupID,$requiredOnly);
            $forms = $this->_collateFormRecords($forms, $groupForms);
        }
        
        return $forms;
    }
    
    protected function _getDeptFormData($deptID,$req = '1') {
        $forms = array();
        
        if ($req == 1) {
           $sqlAdd = " AND required=1";
        } else {
           $sqlAdd = '';
        }
        
        //using query to avoid instantiating extra objects
        $query = "SELECT formID,frequency 
                  FROM deptForms
                  WHERE deptID = $deptID" . $sqlAdd;
        $results = $this->getAdapter()->fetchAll($query);
        
        foreach ($results as $row) {
            $formID    = $row['formID'];
            $frequency = $row['frequency'];
            $forms["$formID"] = $frequency;
        }
        
        return $forms;        
    }
    
    protected function _getFunderFormData($funderID,$req = '1') {
        $forms = array();
        
        if ($req == 1) {
           $sqlAdd = " AND required=1";
        } else {
           $sqlAdd = '';
        }
        
        //using query to avoid instantiating extra objects
        $query = "SELECT formID,frequency 
                  FROM funderForms
                  WHERE funderID = $funderID" . $sqlAdd;
        $results = $this->getAdapter()->fetchAll($query);

        foreach ($results as $row) {
            $formID    = $row['formID'];
            $frequency = $row['frequency'];
            $forms["$formID"] = $frequency;
        }
   
        return $forms;        
    }
        
    protected function _getProgFormData($progID,$req = '1') {
        $forms = array();

        if ($req == 1) {
           $sqlAdd = " AND required=1";
        } else {
           $sqlAdd = '';
        }
        
        //get my funders
        $funderQuery = "SELECT funderID 
                        FROM programFunders 
                        WHERE programID = $progID";
        
        $funders = $this->getAdapter()->fetchAll($funderQuery);
        
        //get funders' forms
        foreach ($funders as $funder) {
            $fID = $funder['funderID'];
            $funderForms = $this->_getFunderFormData($fID,$req);
            $forms = $this->_collateFormRecords($forms, $funderForms);
        }
        
        //get my own forms
        $programQuery = "SELECT formID,frequency
                         FROM programForms
                         WHERE programID = $progID";
        $progForms = $this->getAdapter()->fetchAll($programQuery);
        foreach ($progForms as $progForm) {
            $id = $progForm['formID'];
            $frequency = $progForm['frequency'];
            if (!array_key_exists($id, $forms)) {
                $forms["$id"] = $frequency;
            }
        }
        return $forms;
    }
    
    protected function _getGroupFormData($groupID){
        $forms = array();
        
        //get my own forms
        $formQuery = "SELECT formID, frequency
                      FROM groupForms
                      WHERE groupID = $groupID";
        $groupForms = $this->getAdapter()->fetchAll($formQuery);

        foreach ($groupForms as $groupForm) {
            $id = $groupForm['formID'];
            $freq = $groupForm['frequency'];

            if (!array_key_exists($id, $forms)) {
                $forms["$id"] = $freq;
            }
        }

        //return array.
        return $forms;

    }
    
    public function getAssociatedForms($type,$typeID) {
        //$type can be one of: ptcp, dept, prog, group
        switch ($type) {
            case 'ptcp' : $result = $this->_getPtcpFormData($typeID);
                break;
            case 'fund' : $result = $this->_getFunderFormData($typeID);
                break;
            case 'dept' : $result = $this->_getDeptFormData($typeID);
                break;
            case 'prog' : $result = $this->_getProgFormData($typeID);
                break;
            case 'group': $result = $this->_getGroupFormData($typeID);
                break;
            default: throw new Exception("Invalid type passed to getAssociatedForms().");
        } 
        return $result;
    }
    
    public function getName($id) {
        $row = $this->fetchRow("id = $id");
        $name = $row['name'];
        return $name;
    }
    
    public function getType($id) {
        $row = $this->fetchRow("id = $id");
        $type = $row['type'];
        return $type;
    }
    
    public function getTypeForms($type) {
        $validTypes = array('prepost','singleuse');
        if (!in_array($type, $validTypes)) {
            throw new exception("Invalid type $type passed to form getter.");
        }
        $forms=$this->fetchAll("type='$type'")->toArray();
        return $forms;
    }
    
    public function getStaffForms($target=FALSE) {
        $root = Zend_Registry::get('root');
        $eval = Zend_Registry::get('evaluator');
        $uid = Zend_Registry::get('uid');
        
        $appropriateIDs = $this->getIDs($target);
        
        if ($root || $eval) {
            $result = $appropriateIDs;
        } else {
            $result = array();
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $deptForms = new Application_Model_DbTable_DeptForms;
            $myDepts = $userDepts->getList('depts', $uid);
            foreach ($myDepts as $dID) {
                $formIDs = $deptForms->getList('forms', $dID);
                foreach ($formIDs as $fid) {
                    if (in_array($fid,$appropriateIDs)) {
                        array_push($result,$fid);
                    };
                }
            }
        }
        return array_unique($result);
    }
    
    public function getPtcpForms($ptcpID) {
        $id = $ptcpID;
        $allForms = $this->_getPtcpFormData($id,0); //returns array keyed by ID
        
        $formData = new Application_Model_DbTable_DynamicForms;
        $finalForms = array();
        
        foreach ($allForms as $fid => $frequency) //frequency not needed, but that's what's in array
        {    
           if ($fid < 10) {
              $table = "form_0" . $fid;
           } else {
              $table = "form_" . $fid;
           }
          
           $name = $this->getName($fid);
           
           $formRecord = array(
              'id' => $fid,
              'frequency' => $frequency,
              'name' => $name
           );
          
           $myData = $formData->getRecords($id, $table);
          
           foreach ($myData as $datapoint) {
              $dataID = $datapoint['id'];
              $formRecord['data'][$dataID] = $datapoint;
           }        
           
           array_push($finalForms,$formRecord);
        }
        
        return $finalForms;
    }
    
    public function addForm($id, $name,$tableName,$desc,$type,$target) 
    {
	$data = array(
		'id'                => $id,
                'name'              => $name,
                'tableName'         => $tableName,
                'description'       => $desc,
                'type'              => $type,
                'target'            => $target
		);
        
	return $this->insert($data);
    }

    public function updateForm($id,$name,$tableName,$desc,$type,$target)
    {
    	$data = array(
                'id'                => $id,
		'name'              => $name,
                'tableName'         => $tableName,
                'description'       => $desc,
                'type'              => $type,
                'target'              => $target
		);
	$this->update($data, 'id = ' . (int)$id);
    }
    
    public function deleteForm($id)
    {
	$data = array(
		'enabled' => 0
	);

	$this->update($data, 'id = '. (int)$id);
        $restoreRow = $this->fetchRow("id = $id")->toArray();
	$this->delete('id = ' . (int)$id);
	$this->insert($restoreRow);

    }
   
    public function enable($id) {
	$data = array ('enabled' => 1);
	return $this->update($data, "id = $id");
    }
 
    public function getIDs($target=FALSE) {
        $condition = "enabled = 1";
        if ($target) {
            $condition .= " AND target = '$target'";
        }
        
        $row = $this->fetchAll($condition);
        $result = array();
        foreach ($row as $formRecord) {
            array_push($result, $formRecord['id']);
        }
        return $result;
    }

    public function getRecord ($id) {
        $id = (int)$id;
        $row = $this->fetchRow('id = ' . $id);
        if (!$row) {
               throw New Exception("Could not find a form with that ID.");
        }

	if ($row['enabled'] != 1) {
		throw new exception("This form is not enabled in the system and cannot be used.");
	}

        return $row->toArray();
    }
    
    public function getTableName($id) {
        $id = (int)$id;
        $record = $this->getRecord($id);
        $table = $record['tableName'];
        return $table;
    }
    
    public function getFormCounts($id) {
        $tableName = $this->getTableName($id);

        //get department count
        $formDepts = new Application_Model_DbTable_DeptForms;
        $deptList = $formDepts->getList('depts',$id);
        $deptCount = count($deptList);
                
        //get unique uIDs
        $dataTable = new Application_Model_DbTable_DynamicForms();
        $uidCount = $dataTable->getEntryCount($tableName,'unique');
        
        //get total entries
        $entryCount = $dataTable->getEntryCOunt($tableName,'total');
  
        return [
            'deptCount' => $deptCount,
            'uidCount'  => $uidCount,
            'entryCount' => $entryCount
        ];
    }
    
}

