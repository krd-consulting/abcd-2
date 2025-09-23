<?php

class FormsController extends Zend_Controller_Action
{
private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $db = NULL;
    private $numericFormID = NULL;
    private $target = NULL;
    
    public function init()                       {
        /* Get user credentials */
        $this->auth = Zend_Auth::getInstance();
        if (!$this->auth->hasIdentity()) {
            throw new Exception("You are not logged in.");
        }
        
        /* Set UID */
        $this->uid = $this->auth->getIdentity()->id;
        
        /* Set role vars*/
        $this->role = $this->auth->getIdentity()->role;
        switch ($this->role) {
            case '4' : $this->root = TRUE; $this->mgr = TRUE; break;
            case '3' : $this->mgr = TRUE; break;
            case '1' : $this->evaluator = TRUE; break;
            default: break;
        }

        /* Set Database */
        $this->db = $this->getInvokeArg('bootstrap')->getResource('db');

    }
       
    protected function _setDoNotDisplay($tableName,$oldVersion) {
        $dataTable = new Application_Model_DbTable_DynamicForms();
        $record = $dataTable->getRecordByID($tableName, $oldVersion);
        if ($record) {
            $doNotDisplaySet = array(
                'doNotDisplay' => 1
            );
            $dataTable->update($doNotDisplaySet,"id=$oldVersion");
        }
    }
    
    protected function _dupcheck($name)          {
        $forms = new Application_Model_DbTable_Forms;
        $test = $forms->fetchRow("name = '$name'");
        
        if ($test) {
            return $test;
        } else {
            return 'yes';
        }
       
    }
    
    protected function _setOptionForm($type)     {     
        $referenceDiv = '';
        $validTypes = array('text','num','date','radio','check','matrix','textarea', 'upload');
        
        if (!in_array($type, $validTypes)) {
            throw new exception("Invalid element type $type passed to AJAX call.");
        }
        
        $optionForm = new Application_Form_DynamicFormOptions(array('type'=>$type));
        $optionForm->setView(new Zend_View());
        $formHTML = $optionForm->render();
        return $formHTML;
        
    }

    protected function _processForm($formData)   {
        //Get latest Form's ID from database
        $select = $this->db->query('select max(id) from forms');
        $highID = $select->fetchAll();
        $myID = $highID[0]['max(id)'] + 1;
        
        //Set Form MetaData variables
        
        $formName = trim($formData[0]['name']);
        $type = trim($formData[0]['type']);
        $target = trim($formData[0]['target']);
        $dept = trim($formData[0]['dept']);
        $desc = trim($formData[0]['desc']);
        $editHtml = trim($formData[0]['editHtml']);
        $displayHtml = trim($formData[0]['cleanHtml']);
        $tableName = 'form_' . $myID;
        if ($myID < 10) {$tableName = 'form_0' . $myID;}
                       
        //Register Form in Database
        $regForm = new Application_Model_DbTable_Forms;
        $realID = $regForm->addForm($myID, $formName, $tableName, $desc, $type, $target);
        //Register default department
        $regDept = new Application_Model_DbTable_DeptForms;
        $regDept->addRecord($myID, $dept, NULL);
                
        //Store HTML
        $regHTML = new Application_Model_DbTable_FormsHTML;
        $regHTML->addFormHTML($myID, $editHtml, $displayHtml);

        if ($myID != $realID) {
            throw new Exception("Real Form ID ($realID) doesn't match expected Form ID ($myID).");
        }
        
        //Create beginning of sql string
        $sqlCreateDataTable = "
                    CREATE TABLE IF NOT EXISTS $tableName (
                    id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    doNotDisplay boolean NOT null default FALSE,
                    uID int NOT NULL,
                    enteredOn timestamp NOT NULL default now(),
                    UNIQUE KEY entryid (uID, enteredOn),
                    enteredBy int NOT NULL,
                    responseDate date NOT NULL,
                    deptID int NULL default NULL";
        
        //If prepost form, add corresponding column
        if ($type == 'prepost') {
           $sqlCreateDataTable .= ",
              prePost varchar (10) NOT NULL";
        }
        
        //Process elements
        $zElement = new Application_Model_DbTable_CustomFormElements;
        
        foreach ($formData as $index => $element) {
            
            //Skip the first array: it isn't an element;
            if ($index == 0) { continue; }
        if ($element == 'undefined') { continue; }            

            $eid      =   'field_' . $element['id'];
            $type     =   $element['type'];
            $elName   =   $element['name'];
            $options  =   array();
            
            switch ($type) {
                case 'text'     : $colType = 'varchar'; $optionList='(140)';    break;
                case 'num'      : $colType = 'float';   $optionList='';         break;
                case 'date'     : $colType = 'date';    $optionList='';         break;
                case 'radio'    : $colType = 'varchar'; $optionList='(200)';    break;
                case 'checkbox' : $colType = 'varchar'; $optionList='(200)';    break;
                case 'matrix'   : $colType = 'varchar'; $optionList='(200)';    break;
                case 'textarea' : $colType = 'text';                            break;
                case 'upload'   : $colType = 'varchar';  $optionList='(140)';   break;                
            }
             
            if ($type != 'matrix') {
                if (isset ($element['options'])) {
                    $options    = $element['options'];
                 }
                //add to elements description table
                $zElement->addElement($eid, $myID, $elName, $type, $options);
                //add to SQL call for new table
                $sqlCreateDataTable .= ",
                    $eid $colType $optionList NULL default NULL";
                                
            } else {
                //$numRows    = $element['numRows'];
                //$numCols    = $element['numCols'];
                $cols       = $element['cols'];
                $i = 1;
                foreach ($element['rows'] as $rowValue) {
                    $matrixEID = $eid.'_'.$i;
                    $matrixName = $rowValue;
                    $matrixType = 'radio';
                    $matrixOptions = $cols;
                    $zElement->addElement($matrixEID, $realID, $matrixName, $matrixType, $matrixOptions);
                    $i++;
                                        
                    $sqlCreateDataTable .= ",
                        $matrixEID $colType $optionList NULL default NULL";
                } //end small foreach   
            } //end if-else
            
        }//end big foreach                
        
        $sqlCreateDataTable .= ");";
        //print($sqlCreateDataTable);
        $this->db->query($sqlCreateDataTable);
                
        $message = "Form $formName added successfully.";
        
        return $message;
        
    }
    
    protected function _setFormID($table) {
        $numericFormID = str_replace('form_','',$table);
        if ($numericFormID[0] == '0') {
            $numericFormID = str_replace('0','',$numericFormID);
        }
        $this->numericFormID = $numericFormID;
        return true;
    }
    
    protected function _htmlToSql($data) {
        //Get HTTP data into a usable format (indexed array);
        $pairArray = array();
        parse_str($data, $pairArray);                
               
        $formTarget = $pairArray['formTarget'];
        $this->target = $formTarget;
        
        //Set appropriate tables for verification
        switch ($formTarget) {
            case 'participant':
                $dbTable = new Application_Model_DbTable_Participants;
                break;
            case 'staff':
                $dbTable = new Application_Model_DbTable_Users;
                break;
            case 'group':
                $dbTable = new Application_Model_DbTable_Groups;
                break;
            default: throw new exception("Bad form target passed as AJAX call.");
        }
        
        //Verify that target identity is valid
        $record = $dbTable->getRecord($pairArray['targetID']);
        
        if ($formTarget == 'group') {
            $verifyName = $record['name'];
        } else {
            $verifyName = $record['firstName'] . " " . $record['lastName'];
        }
        
        if ($pairArray['name'] != $verifyName) {
            throw new exception ("Form name and ID are mismatched. Looking at .$verifyName. and ." . $pairArray['name'] . ".");
        }
        
        //Set initial values
        $sqlArray = array(
            'uid'           => $pairArray['targetID'],
            'responseDate'  => $pairArray['responseDate'],
            'enteredBy'     => $this->uid,
        );
        
        //set number of non-dynamic fields to slice out:
        //formTarget, targetID, name, responseDate, dept
        //some forms have no dept, so just first 4 of the above
        
        if (array_key_exists('ptcpDept', $pairArray)) {
            $sqlArray['deptID'] = $pairArray['ptcpDept'];
            $skelCols = 5;
        } else {
            //$sqlArray['groupID'] = NULL;
            $skelCols = 4;
        }
        
        //Pre-post forms have an additional non-dynamic field
        
        if (array_key_exists('prepost', $pairArray)) {
            $sqlArray['prepost'] = $pairArray['prepost'];
            $skelCols++;
        }
        
        //slice them out
        
        $pairArray = array_slice($pairArray, $skelCols, NULL, true);
                
        //Get column names and add new pairs to sql-ready array;
        foreach ($pairArray as $k => $v) {
            
            $searchKey    = str_replace("_", " ", $k);
            $safeKey      = $this->db->quote($searchKey);
            $sqlText =  "SELECT elementID 
                        FROM customFormElements
                        WHERE elementName = $safeKey
                        AND formID = $this->numericFormID";
            
            $colNameQuery = $this->db->query($sqlText);
            $colName = $colNameQuery->fetchAll();
          
          if (empty($colName)) {
        $column = '';
          } else {
            $column = $colName[0]['elementID'];
              }

              if (is_array($v)) {$v = implode(' , ', $v); }
            
              if ((strlen($v) == 0) || (!isset($v)) || strlen($column) == 0) {
                continue;
              }
            
              $sqlArray["$column"] = $v;
        }
        return $sqlArray;
        
    }
    
    protected function _isFcssForm() {
        $forms = new Application_Model_DbTable_Forms();
        $currentForm = $forms->getRecord($this->numericFormID);
        if ($currentForm['fcssID'] > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    protected function _fcssSubmit($data) {
        $fcssConnect = new ABCD_Soap;
        $result = $fcssConnect->submitForm($data,$this->numericFormID);
    //$result = "Down for maintenance.";
        return $result;
    }
    
    protected function _updateReminder($sqlArray) {
        $formTarget = $this->target;    
        if ($formTarget == 'participant') {
            $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
            $alert = $ptcpAlerts->checkFormPtcpAlert($this->numericFormID, $sqlArray['uid']);

            //if current alert exists, remove it.
            if (is_array($alert)) { //if no alert, previous function returns 0
                $ptcpAlerts->unsetFormPtcpAlert($alert['id']);
            }

            //find out if this participant has this form on a required frequency
            $formsModel = new Application_Model_DbTable_Forms;
            $ptcpReqs = $formsModel->getAssociatedForms('ptcp', $sqlArray['uid']);

            if (count($ptcpReqs) > 0) {
               if (array_key_exists($this->numericFormID, $ptcpReqs)) { 
                  $frequency = $ptcpReqs[$this->numericFormID];
               } else {
                  $frequency = 'null';
               }
                if (($frequency) && ($frequency != 'null')) {
                    $numFrequency = array(
                    'monthly'     => 30,
                    'quarterly'   => 90,
                    'semi-annual' => 180,
                    'annual'      => 365
                    );
                    $daysTill = $numFrequency[$frequency];
                    $dueDate = $daysTill * 24 * 60 * 60  + 
                            strtotime($sqlArray['responseDate']);

                    $ptcpAlerts->setFormPtcpAlert(
                            $sqlArray['uid'], 
                            $this->numericFormID, 
                            '2', 
                            date('Y-m-d',$dueDate));
                }
            }
        }   
    }
    
    protected function _insertData($table,$rawdata) {
        $this->_setFormID($table);
        $dataArray = $this->_htmlToSql($rawdata);
        
        if ($this->_isFcssForm()) {
            //will only proceed to enter data locally
            //if FCSS entry is successful
             $proceed = $this->_fcssSubmit($dataArray);
             //$proceed = "FCSS back-end is down for maintenance.";
        } else {
            //for non-FCSS forms, go straight to local entry
            $proceed = 1;
        }
        
        if ($proceed == 1) {
            $dynamicModel = new Application_Model_DbTable_DynamicForms;
            $result = $dynamicModel->insertData($table, $dataArray);
            //on success, update reminder alert status
            if ($result > 0) {
                $this->_updateReminder($dataArray);
            }
            return 1;
        } else {
           return $proceed;
        }
}

    protected function _setRequired($data=array()){

        $type       = $data['type'];
        $action     = $data['action'];
        $parentID   = $data['to'];
        $formID     = $data['who'];
        
        switch ($type) {
            case 'depts' : 
                $table = new Application_Model_DbTable_DeptForms;
                $column = 'deptID';
                $alerts = 'dept';
                break;
            case 'progs' : 
                $table = new Application_Model_DbTable_ProgramForms;
                $column = 'programID';
                $alerts = 'prog';
                break;
            case 'funders' : 
                $table = new Application_Model_DbTable_FunderForms;
                $column = 'funderID';
                $alerts = 'fund';
                break;
            default: throw new exception ("Can only work with depts, programs and funders");
        }
        
        
        switch ($action) {
            case 'add':
                $required = 1;
                break;
            case 'remove':
                $required = 0;
                break;
        }
        
        $tdata = array('required' => $required);
        
        $numRowsUpdated = $table->update($tdata, "formID = $formID and $column = $parentID");
        
        if ($numRowsUpdated != 1) {
            throw new exception ("Fatal Error: non-unique matching combinations in Forms/Ajax");
        } else {
            if ($required == 1) {
            //add alerts for current participants
                $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
                $numAffected = $ptcpAlerts->confirmRequirements($alerts,$parentID,'all');
            } else { //remove alerts for those who no longer need them (not all)
                $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
                $numAffected = $ptcpAlerts->confirmRemoval($alerts,$parentID,$formID,'all');
            }
            
            $success = $numAffected;
        }
        
        return $success;
    }
    
    protected function _setDeptElements()        {
        $deptModel = new Application_Model_DbTable_Depts;
        $deptStaff = new Application_Model_DbTable_UserDepartments;
        
        if ($this->root) {
            $deptIDs = $deptModel->getIDs();
        } else {
            $deptIDs = $deptStaff->getList('depts', $this->uid);
        }
        
        $depts = array();
        foreach ($deptIDs as $did) {
            $record = $deptModel->getRecord($did);
            $name = $record['deptName'];
            $depts[$did] = $name;
        }
        return $depts;
    }
    
    protected function _setGroupElements()       {
        // groups are listed by program
        $programTable = new Application_Model_DbTable_Programs;
        //if root, get list of all program IDs
        if ($this->root) {
            $programIDs = $programTable->getIDs();
        } elseif ($this->mgr) { //if mgr, get list of my depts, and programs in them
            $programIDs=array();
            $deptTable = new Application_Model_DbTable_UserDepartments;
            $myDepts = $deptTable->getList('depts', $this->uid);
            foreach ($myDepts as $deptID) {
                $progs = $programTable->getProgByDept($deptID);
                if (count($progs) > 0) {
                    foreach ($progs as $program) {
                        array_push($programIDs,$program['id']);
                    }
                }
            }
        } else { //otherwise, get list of my programs
            $progUsers = new Application_Model_DbTable_UserPrograms;
            $programIDs = $progUsers->getList('progs', $this->uid);
        }
        
        //$programIDs now contains all the programs we need
        //get daughter groups
        $groupList = array('0' => 'None');
        $groupTable = new Application_Model_DbTable_Groups;
        foreach ($programIDs as $progID) {
            
            //get group data and put it into options array
            $groups = $groupTable->getProgramGroups($progID);
            foreach ($groups as $group) {
                $gID = $group['id'];
                $groupList[$gID] = $group['name'];
            }
        }
        
        return $groupList;
        
    }
    
    protected function _setReminder($data=array()) {
        
        foreach ($data as $k => $v) {
             $$k = $v; //$type, $parentID, $myID, $value
        };
        
        switch ($type) {
            case 'depts' : 
                $table = new Application_Model_DbTable_DeptForms;
                $column = 'deptID';
                break;
            case 'progs' : 
                $table = new Application_Model_DbTable_ProgramForms;
                $column = 'programID';
                break;
            case 'funders' : 
                $table = new Application_Model_DbTable_FunderForms;
                $column = 'funderID';
                break;
            default: throw new exception ("Can only work with depts, forms and funders");
        }
        
        $tdata = array(
            'frequency' => $value 
        );
        
        $num = $table->update($tdata, "formID = $myID AND $column = $parentID");
       
    if (($value == 'null') || ($value == NULL)) {
        $value = 'None';
    }
        return $value;
        
        
    }
    
    protected function _getProgramIDs($d=array()){
    $pids = array();
    $pTable = new Application_Model_DbTable_Programs;
    
    foreach ($d as $deptID) {
       $programList = $pTable->getProgbyDept($deptID);
       foreach ($programList as $p) {
        $pID = $p['id'];
        array_push($pids, $pID);
       }
        }
    
    return $pids;
    }
    
    public function indexAction()                {
    $this->_helper->redirector('list');
    }

    public function listAction()                 {        
        $formDB = new Application_Model_DbTable_Forms;
        $forms = $formDB->fetchAll(null, 'name');
        $formsArray = $forms->toArray();
        $num = count($forms);
        $enabledForms = array();
        $disabledForms = array();
        
        //check for entries and sort disabled
        foreach ($formsArray as $form) {
            $entriesSelect =  $this->db->query("SELECT * FROM " . $form['tableName']);
            $numEntries = count($entriesSelect->fetchAll());
            $form['numEntries'] = $numEntries;
        if ($form['enabled'] == 1) {
                array_push($enabledForms,$form);
        } else {
        array_push($disabledForms,$form);
        }
        }
        $this->view->permittedIDs = $formDB->getStaffForms();
        $this->view->forms = $enabledForms;
        $this->view->dForms = $disabledForms;
        $this->view->count = $num;
        
        //get Staff departments list to pass to form creator
        $deptsDB = new Application_Model_DbTable_Depts;
        $staffDeptsDB = new Application_Model_DbTable_UserDepartments;
        $deptNameList = array();
        
        if (!$this->root) {
            $myDepts = $staffDeptsDB->getList('depts', $this->uid);
        } else {
            $myDepts = $deptsDB->getIDs();
        }
        
        foreach ($myDepts as $did) {
            $deptName = $deptsDB->getDept($did);
            $deptNameList[$did] = $deptName['deptName'];
        }
        
        //config form creator
        $formCreator = new Application_Form_FormCreator(array('depts' => $deptNameList));
        $this->view->formCreator = $formCreator;
        
        
        
        $this->view->manager = $this->mgr;
        $this->view->admin = $this->root; 
        $this->view->uid = $this->uid;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/formCreate.js"></script>' . 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' . 
                '<script type="text/javascript" src="/js/enableForm.js"></script>' . 
                '<script type="text/javascript" src="/js/collapseTr.js"></script>' . 
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>' . 
                '<script type="text/javascript" src="/js/filter.js"></script>';
    }
    
    protected function _getEntriesByDept($table,$deptID) {
        $dataTable = new Application_Model_DbTable_DynamicForms;
        
        if ($this->root || $this->mgr) {
            //filter by dept only
            $filterArray = array(
              'deptID' => $deptID  
            );
        } else {
            //filter by dept and enteredBy
            $filterArray = array(
                'deptID' => $deptID,
                'enteredBy' => $this->uid
            );
        }
        
        $data = $dataTable->getRecordsAdHocFilter($table,$filterArray);
        
        return $data;
    }
    
    public function profileAction()              {
        $formTable = new Application_Model_DbTable_Forms;
        $id = $this->_getParam('id');
        $thisForm = $formTable->getRecord($id);
        $table = $thisForm['tableName'];

        $deptArray = array();
        $progArray = array();
        $fundArray = array();
        $entryArray = array();
        
        $deptTable = new Application_Model_DbTable_Depts;
        $userDeptTable = new Application_Model_DbTable_UserDepartments;
        
        //get form departments
        $deptForms = new Application_Model_DbTable_DeptForms;
        $deptIDs = $deptForms->getList('depts', $id);
        
        //get list of departments I'm allowed
        $myDeptIDs = array();
        if ($this->root) {
            $myDeptIDs = $deptTable->getIDs();
        } else {
            $myDeptIDs = $userDeptTable->getList('depts',$this->uid);
        }
        
        //fill dept data at intersection
        $count = 0;
        foreach ($deptIDs as $deptID) {
            if (in_array($deptID,$myDeptIDs)) {
                $dept = $deptTable->getDept($deptID);
                $record = $deptForms->getRecord($id, $deptID);
                $deptArray[$deptID]['name'] = $dept['deptName'];
                $deptArray[$deptID]['default'] = $record['defaultForm'];
                $deptArray[$deptID]['required'] = $record['required'];
                $deptArray[$deptID]['frequency'] = $record['frequency'];
                
                $entryArray[$count]['id'] = $id;
                $entryArray[$count]['deptID'] = $deptID;
                $entryArray[$count]['name'] = $dept['deptName'];
                $entryArray[$count]['frequency'] = '';
                $entryArray[$count]['data'] = $this->_getEntriesByDept($table,$deptID);
                $count++;
            }
        }

        
        
        //get programs
        $programForms = new Application_Model_DbTable_ProgramForms;
        $progIDs = $programForms->getList('programs', $id);
        
        $programTable = new Application_Model_DbTable_Programs;
        foreach ($progIDs as $progID) {
            $prog = $programTable->getProg($progID);
            $record = $programForms->getRecord($id, $progID);
            
            $progArray[$progID]['name'] = $prog['name'];
            $progArray[$progID]['required'] = $record['required'];
            $progArray[$progID]['frequency'] = $record['frequency'];
        }

        //get funders
        $funderForms = new Application_Model_DbTable_FunderForms;
        $fundIDs = $funderForms->getList('funders', $id);
        
        $funderTable = new Application_Model_DbTable_Funders;
        foreach ($fundIDs as $fundID) {
            $fund = $funderTable->getRecord($fundID);
            $record = $funderForms->getRecord($id, $fundID);
            
            $fundArray[$fundID]['name'] = $fund['name'];
            $fundArray[$fundID]['required'] = $record['required'];
            $fundArray[$fundID]['frequency'] = $record['frequency'];
        }
        
        
        
        $this->view->thisForm= $thisForm;
        $this->view->depts   = $deptArray;
        $this->view->progs   = $progArray;
        $this->view->funders = $fundArray;
        $this->view->entries = $entryArray;
        $this->view->mgr     = $this->mgr;
        //if (!$this->root) {
        //$this->view->ptcpIDs = $permittedPtcpIDs;
        //}
        if ($this->mgr) {
            $this->view->layout()->customJS = 
                    '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' .
                    '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                    '<script type="text/javascript" src="/js/ptcpFormTable.js"></script>' .
                    '<script type="text/javascript" src="/js/disable.js"></script>' .
                    '<script type="text/javascript" src="/js/updateDB.js"></script>' ;
        } else {
            $this->view->layout()->customJS = 
                    '<script type="text/javascript" src="/js/disable.js"></script>' .
                    '<script type="text/javascript" src="/js/ptcpFormTable.js"></script>' .
                    '<script type="text/javascript" src="/js/setHeight.js"></script>';
        }
    }

    public function enableAction()               {
    $formID = $_POST['id'];
        $formTable = new Application_Model_DbTable_Forms;
        if ($formTable->enable($formID)) {
            $jsonReturn = 'Success';
        } else {
            $jsonReturn = 'No records could be updated.';
        }
    $this->_helper->json($jsonReturn);
    }
    
    public function associateAction()            {
        if (!$this->mgr) {
            throw new exception("You don't have sufficient access control privileges for this functionality.");
        }
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/filterLi.js"></script>' .
                '<script type="text/javascript" src="/js/genericSort.js"></script>';
        
        $id         = $this->_getParam('id');
        $type       = $this->_getParam('type');
    $userRecords    = new Application_Model_DbTable_UserDepartments;
        $forms      = new Application_Model_DbTable_Forms;
        $thisForm   = $forms->getRecord($id);
                
        switch ($type) {
            case 'dept' : 
                $assocTable = new Application_Model_DbTable_DeptForms;
                $records    = new Application_Model_DbTable_Depts;
        $myRecordIDs= $userRecords->getList('depts',$this->uid);
                $columnType = 'depts';
                $header = "Add Department to ";
                break;
            
            case 'prog' : 
                $assocTable = new Application_Model_DbTable_ProgramForms;
                $records    = new Application_Model_DbTable_Programs;
        $myDeptIDs  = $userRecords->getList('depts',$this->uid);
        $myRecordIDs= $this->_getProgramIDs($myDeptIDs);
                $columnType = 'programs';
                $header = "Add Program to ";
                break;
            
            case 'funder' : 
                $assocTable = new Application_Model_DbTable_FunderForms;
                $records    = new Application_Model_DbTable_Funders;
                $columnType = 'funders';
                $header = "Add Funder to ";
                break;
                
            default: throw new Exception("Can only add Departments, Programs and Funders.");
        }
        
        $currentRecords = array();
        $addRecords     = array();
        $requiredIDs    = array();
       
        $currentRecordIDs = $assocTable->getList($columnType, $id);
        $allRecordIDs     = $records->getIDs();
        $addRecordIDs     = array_diff($allRecordIDs,$currentRecordIDs);

    if ((!$this->root) && ($type != 'funder')) {
       $notMyRecordIDs   = array_diff($allRecordIDs,$myRecordIDs);
           $addRecordIDs     = array_diff($addRecordIDs,$notMyRecordIDs);      
       $currentRecordIDs = array_diff($currentRecordIDs,$notMyRecordIDs);
        }
        foreach ($currentRecordIDs as $cid) {
            $currentRecord = $records->getRecord($cid);
            if (is_array($currentRecord) && !$currentRecord['doNotDisplay']) {
                array_push($currentRecords,$currentRecord);
            }
            $assocRecord = $assocTable->getRecord($id, $cid);
            if ($assocRecord['required'] == 1) {
                array_push($requiredIDs, $cid);
            }
        }
        
        foreach ($addRecordIDs as $aid) {
            $addRecord = $records->getRecord($aid);
            if (is_array($addRecord) && !$addRecord['doNotDisplay']) {
                array_push($addRecords,$addRecord);
            }
        }
        
        $this->view->currentRecords = $currentRecords;
        $this->view->addRecords = $addRecords;
        $this->view->thisForm = $thisForm;
        
        $this->view->required = $requiredIDs;
        $this->view->header = $header;
        $this->view->type = $type;
    
    }
    
    public function ajaxAction()                 {
      $this->_helper->viewRenderer->setNoRender();
    $this->getHelper('layout')->disableLayout();
    $j = TRUE;
        $task = $_POST['task'];
        
        switch ($task) {
            case 'dupcheck': 
                $name = $_POST['name'];
                $success = $this->_dupcheck($name);
                
                $jsonReturn = array('success' => $success);
                break;
            
            case 'referenceList':
                $type = $_POST['type'];
                if ($type == 'formlist') {
                    $formDropDown = new Zend_Form_Element_Select('formList');
                    $formTable = new Application_Model_DbTable_Forms;
                    //$where = "target = '$filterTarget' and type = '$dataType'";
        
                    $myForms = $formTable->fetchAll()->toArray();
                    $permittedForms = $formTable->getStaffForms();
        
                    foreach ($myForms as $form) {
                        $fID = $form['id'];
                        if (in_array($fID,$permittedForms)) {
                            $formDropDown->addMultiOption($form['id'], $form['name']);
                        }
                    }
                    $formDropDown->setLabel('Choose a form');
                    $formHtml = $formDropDown->render();
                } else if ($type == 'fieldList') {
                    $formID = $_POST['formID'];
                    $fieldDropDown = new Zend_Form_Element_Select('fieldList');
                    $elementTable = new Application_Model_DbTable_CustomFormElements;
                    $elementList = $elementTable->getElementNames($formID,"text");
                    
                    foreach ($elementList as $element) {
                        $fieldDropDown->addMultiOption($element['field'], $element['name']);
                    }
                    $fieldDropDown->setLabel('Choose Field');
                    $formHtml = $fieldDropDown->render();
                }
                
                $jsonReturn = array('form' => $formHtml); 

                
                break;
            
            case 'optionset':
                $formType = $_POST['type'];
                $getForm = $this->_setOptionForm($formType);
                $jsonReturn = array('form' => $getForm);
                break;
            
            case 'processform':
                $formData = $_POST['data'];
                $processForm = $this->_processForm($formData);
                $jsonReturn = array('message' => $processForm);
                break;
            
            case 'submit' :
                $tableName = $_POST['id'];
                $formData = $_POST['data'];
                $oldVersion = $_POST['oldVersion'];
                
                
                
                $dataResult = $this->_insertData($tableName, $formData);
                
                if($dataResult == 1) {
                    if ($oldVersion != 0) {
                        $this->_setDoNotDisplay($tableName,$oldVersion);
                    }
                    
                    $jsonReturn = array('success' => 'yes');
                } else {
                    $jsonReturn = array('success' => $dataResult);
                }

                break;
                
            case 'required' :
                $success = $this->_setRequired($_POST);
                $jsonReturn = array('success' => $success);
                break;
            
            case 'reminder' :
                $value = $this->_setReminder($_POST);
                print ucfirst($value);
                $jsonReturn = '';
        $j = FALSE;
                break;
            
            case 'getform':
                $formID = $_POST['fid'];
                $formsHTML = new Application_Model_DbTable_FormsHTML;
                $html = $formsHTML->getHTML($formID, 'display');
                $jsonReturn = array('content' => $html);
                break;
            
            case 'getdepts':
                $pid = $_POST['pid'];
                $pType = $_POST['type'];
                
                $allowedTypes = array('staff','ptcp','participant');
                if (!in_array($pType, $allowedTypes)) {
                    throw new exception ("Invalid form type $pType passed to department lister.");
                }
                
                //get list of departments into a suitable array
                
                switch ($pType) {
                    case 'ptcp' : 
                    case 'participant': $typeDepts = new Application_Model_DbTable_ParticipantDepts;
                        break;
                    case 'staff': $typeDepts = new Application_Model_DbTable_UserDepartments;
                        break;
                }
                
                $sourceIsForm = FALSE;
                $referer = $_SERVER['HTTP_REFERER'];
                if (strpos($referer, "forms/dataentry") == TRUE) {
                    $sourceIsForm = TRUE;
                    $s = explode("/",$referer);
                    $formID = $s[6];
                    $formDeptTable = new Application_Model_DbTable_DeptForms;
                    $formDepts = $formDeptTable->getLIst('depts',$formID);
                }
                
                

                $deptIDs = $typeDepts->getList('depts', $pid);
                
                if($sourceIsForm) {
                    $deptIDs = array_intersect($deptIDs,$formDepts);
                }
                
                $deptTable = new Application_Model_DbTable_Depts;
                $i = 1;
                //$jsonReturn['deptlist'][0]['id'] = 0;
                //$jsonReturn['deptlist'][0]['name'] = 'None';
                
                foreach ($deptIDs as $deptID) {
                    $deptRecord = $deptTable->getRecord($deptID);
                    $jsonReturn['deptlist'][$i]['id'] = $deptRecord['id'];
                    $jsonReturn['deptlist'][$i]['name'] = $deptRecord['deptName'];
                    $i++;
                }
                break;
            
            default: throw new exception('Invalid task passed to FORMS/AJAX.');
        }
               
       if ($j) $this->_helper->json($jsonReturn);
       
    }

    public function addAction()                  {
        $formName = $_GET['formName'];
        $formType = $_GET['formType'];
        $dept     = $_GET['dept'];
        $formWhom = $_GET['formTarget'];
        $formDesc = $_GET['description'];
        
        if (!$formName || !$formType || !$formWhom) {
            throw new Exception ("Didn't get sufficient options to start Form Generator.");
        }
              
        
        $dynamicForm = new Application_Form_DynamicFormSkeleton(array(
                                'target' => $formWhom, 
                                'type'   => $formType, 
                                'dept'   => $dept));
        
        if ($formWhom == 'participant' || $formWhom == 'staff') {
            $elementsArray = $this->_setDeptElements();
            
            $deptField = new Zend_Form_Element_Select('ptcpDept');
            $deptField->setLabel('Department:');
            $deptField->addMultiOptions($elementsArray);
            $dynamicForm->addElement($deptField);
            
            $dynamicForm->clearDecorators();
            $dynamicForm->addDecorator('FormElements')
                         ->addDecorator('HtmlTag', array('tag' => '<ul>'))
                         ->addDecorator('Form');
       
            $dynamicForm->setElementDecorators(array(
                        array('ViewHelper'),
                        array('Errors'),
                        array('Description'),
                        array('Label', array('separator' => ' ')),
                        array('HtmlTag', array('tag' => '<li>', 'class' => 'skeletonElements'))
            ));
        }
        
        
        $this->view->dynamicForm = $dynamicForm;
        
        $this->view->formName = $formName;
        $this->view->formDesc = $formDesc;
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/formGenerator.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.jeditable.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/datePicker.js"></script>' 
                ;
    }

    public function deleteAction()               {
        if ($this->_getParam('id')) {
            $formDB = new Application_Model_DbTable_Forms;
            $id = $this->_getParam('id');
            
            $formDB->deleteForm($id);
            $this->_helper->redirector('list');
        } else {
            throw new exception("Can't delete form - no ID received.");
        }
    }

    public function dataentryAction()            {
        if (!$this->_getParam('id')) {
            throw new exception("Can't enter data for form - no ID received.");
        } else {
            $id = $this->_getParam('id');
        }
        $formTable = new Application_Model_DbTable_Forms;
        $permittedForms = $formTable->getStaffForms();
        if (!in_array($id,$permittedForms)) {
            throw new exception("You don't have access to this form (id $id)");
        }
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/ac-communities.js"></script>' .
                '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/ac2.js"></script>' .
                '<script type="text/javascript" src="/js/ac-reference.js"></script>' .
                '<script type="text/javascript" src="/js/formOptions.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.alphanumeric.js"></script>' .
                '<script type="text/javascript" src="/js/jQuery/jquery.ui.widget.js"></script>' .
                '<script type="text/javascript" src="/js/jquery.fileupload.js"></script>' .
                '<script type="text/javascript" src="/js/dataEntry.js"></script>';
        
        if ($this->getRequest()->isPost()) {
            //process data
            //insert
        } else {
            //fetch HTML
            $htmlDB = new Application_Model_DbTable_FormsHTML;      
            $html = $htmlDB->getHTML($id, 'display');
            
            //set form params
            $formDB = new Application_Model_DbTable_Forms;  
            $formData = $formDB->getRecord($id);
            //$formDataArray = $formData->toArray();
            $tableName = $formData['tableName'];
            
            $formWrapperTop = "<form id='$tableName' class='dataEntry'>";
            $formWrapperBottom = "</form>";
            
            $formHTML = $formWrapperTop . $html . $formWrapperBottom;
            
            $this->view->formData = $formData;
            $this->view->formHTML = $formHTML;
        }
        
    }
    
    public function setdefaultAction()           {
        $form = $this->_getParam('form');
        $dept = $this->_getParam('dept');
        
        $relationTable = new Application_Model_DbTable_DeptForms;
        
        $currentDefault = $relationTable->getSpecial('defaultForm',$dept);
        
        $clearData = array('defaultForm' => 0);
        foreach ($currentDefault as $formID) {
            $relationTable->update($clearData, "formID = $formID and deptID = $dept");
        }
        
        $newDefault = array('defaultForm' => 1, 'required' => 1);
        $relationTable->update($newDefault, "formID = $form and deptID = $dept");
        
    //$jsonReturn['attempt'] = $newDefault;
    //$jsonReturn['sql'] = "formID = $form and deptID = $dept";
    //$this->_helper->json($jsonReturn);
        $this->_redirect('/depts/profile/id/' . $dept . '#dept-frag-4');
        
    }
    
    public function cleardefaultAction()         {
        $form = $this->_getParam('form');
        $dept = $this->_getParam('dept');
        
        $relationTable = new Application_Model_DbTable_DeptForms;  
        $clearData = array('defaultForm' => 0);
        
        $relationTable->update($clearData, "formID = $form and deptID = $dept");
         
        $this->_redirect('/depts/profile/id/' . $dept . '#dept-frag-4');
        
    }
    
   
}