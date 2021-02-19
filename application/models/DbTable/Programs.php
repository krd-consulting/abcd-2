<?php

class Application_Model_DbTable_Programs extends Zend_Db_Table_Abstract
{

    protected $_name = 'programs';

    public function programAllowed($userID,$programID) {
        $list = $this->getStaffPrograms($userID);
        if (in_array($programID,$list)) { 
            return TRUE; 
        } else {
            return FALSE;
        }
    }
    
    public function getStaffPrograms($userID) {
        $root = Zend_Registry::get('root');
        $evaluator = Zend_Registry::get('evaluator');
        $mgr = Zend_Registry::get('mgr');
        
        
        if ($root || $evaluator) {        //if root or evaluator, list all programs
            $progIDs = $this->getIDs();
        } elseif ($mgr) {   //if manager, list all programs in my dept(s)
            $progIDs = array();
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $myDepts = $userDepts->getList('depts',$userID);
            foreach ($myDepts as $deptID) {
                $deptProgs = $this->getProgByDept($deptID);
                foreach ($deptProgs as $progRecord) {
                    array_push($progIDs,$progRecord['id']);
                }
            }
        } else {            //if neither, list all programs i'm explicitly ass'd with
            $userPrograms = new Application_Model_DbTable_UserPrograms;
            $progIDs = $userPrograms->getList('progs', $userID);
        }
                
        return $progIDs; //return IDs
    }
    
    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $userRecord) {
            array_push($result, $userRecord['id']);
        }
        return $result;
    }
    
    public function getRecord($id) {
        $row = $this->getProg($id);
        return $row;
    }
    
    public function getName($id) {
        $prog = $this->getProg($id);
        $name = $prog['name'];
        return $name;
    }
    
    public function getProg($id) 
    {
    	$id = (int)$id;
	$row = $this->fetchRow('id = ' . (int)$id);
	  if (!$row) {
		throw new Exception("Could not find program #$id");
	  }
	return $row->toArray();
    }

    public function getProgByDept($deptID) 
    {
        $id = (int)$deptID;
        $result = $this->fetchAll('deptID = ' . $id);
        return $result->toArray();
    }
    
    public function addProg($name,$deptID,$volType) 
    {
	$data = array(
		'name' => $name,
                'deptID' => $deptID,
                'volunteerType' => $volType
		);
	return $this->insert($data);
    }

    public function updateProg($id,$name,$deptID,$volType)
    {
    	$data = array(
		'name' => $name,
                'deptID' => $deptID,
                'volunteerType' => $volType
		);
	$this->update($data, 'id = ' . (int)$id);
    }

    public function deleteProg($id)
    {
	$this->delete('id = ' . (int)$id);
    }
    
    public function getAllForms($id) {
        //get Form IDs
            //first, for the program itself
                $programForms = new Application_Model_DbTable_ProgramForms;
                $progFormIDs = $programForms->getList('forms',$id);
            //then, any required for my department
                $deptForms = new Application_Model_DbTable_DeptForms;
                $myProg = $this->getRecord($id);
                $deptID = $myProg['deptID'];
                $deptFormIDs = $deptForms->getSpecial('required',$deptID);
            //also, any required for my funders
                $funderPrograms = new Application_Model_DbTable_ProgramFunders;
                $funderIDs = $funderPrograms->getList('funders', $id);
                $funderForms = new Application_Model_DbTable_FunderForms;
                $funderFormIDs = array();
                foreach ($funderIDs as $fID) {
                    $thisFunderForms = $funderForms->getRequired($fID);
                    if (count($thisFunderForms) > 0) {
                        foreach ($thisFunderForms as $formID) {
                            array_push($funderFormIDs, $formID);
                        }
                    }
                }
                
            //combine and get rid of duplicates
                $allFormIDs = array_merge($progFormIDs, $deptFormIDs, $funderFormIDs);
                $formIDs = array_unique($allFormIDs);
        
        //$formIDs now has the correct list
        //pull actual form data
                
        $formsTable = new Application_Model_DbTable_Forms;
        $forms = array();
        foreach ($formIDs as $formID) {
            $formMain = $formsTable->getRecord($formID);
            
            //We pull different requirement and frequency information 
            //depending where we pulled the form from.
            //Priority is direct (programs), then departments, then funders.
            
            if (in_array($formID, $progFormIDs)) {
                $formRel  = $programForms->getRecord($formID, $id);
            } elseif (in_array($formID, $deptFormIDs)) {
                $formRel = $deptForms->getRecord($formID,$deptID);
                $forms[$formID]['inherit'] = 'department';
            } elseif (in_array($formID, $funderFormIDs)) {
                //if this is inherited from a funder, see which funders have this form
                $funderIDs = $funderForms->getList('funders',$formID);
                //if there's only one, just set the $formRel variable.
                if (count($funderIDs) == 1) {
                    $funderID = $funderIDs[0];
                    $formRel = $funderForms->getRecord($formID, $funderID);
                } else {
                //otherwise, check which has the highest frequency and use that one.
                    $freqRating = array ('0'            => 0,
                                         'monthly'      => 1,
                                         'quarterly'    => 2,
                                         'semi-annual'  => 3,
                                         'annual'       => 4
                        );
                    $freqArray = array();
                    foreach ($funderIDs as $funderID) {
                        $myForm = $funderForms->getRecord($formID, $funderID);
                        $myFreq = $myForm['frequency'];
                        if (isset($myFreq)) {
                            $myFreqValue = $freqRating[$myFreq];
                        } else {
                            $myFreqValue = 0;
                        }
                        $freqArray[$myFreqValue] = $funderID;
                    }
                    $maxValue = max(array_keys($freqArray));
                    $wantedID = $freqArray[$maxValue];
                    $formRel = $funderForms->getRecord($formID, $wantedID);
                }
                $forms[$formID]['inherit'] = 'funder';
            } 
            $forms[$formID]['id'] = $formID;
            $forms[$formID]['name'] = $formMain['name'];
            $forms[$formID]['table'] = $formMain['tableName'];
            $forms[$formID]['target'] = $formMain['target'];
            $forms[$formID]['required'] = $formRel['required'];
            $forms[$formID]['frequency'] = $formRel['frequency'];
        }
        
        return $forms;
    }
}