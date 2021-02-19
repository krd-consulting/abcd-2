<?php

class Application_Model_DbTable_AlertsVolunteers extends Zend_Db_Table_Abstract {
    protected $_name = 'alertsVolunteers';
    
    protected function _processText($volID,$formID,$date,$text) {
        //$type is 1 (MISSING) or 2 (DUE)
        
        //get form name
        $formQuery = "SELECT name FROM forms WHERE id = $formID";
        $formName = $this->getAdapter()->fetchOne($formQuery);
        
        
        //get participant name
        $volQuery = "SELECT firstName FROM users WHERE id = $volID";
        $volName = $this->getAdapter()->fetchOne($volQuery);
        
        //alter text.
        
        $v1Text = str_replace('{FORMNAME}', $formName, $text);
        $v2Text = str_replace('{NAME}', $volName, $v1Text);
        $returnText = str_replace('{DATE}', $date, $v2Text);
        
        return $returnText;
        
    }
    
//    public function confirmRemoval($entity,$entityID,$formID,$ptcpToRemove) {
//        $allowedEntities = array('dept','prog','fund');
//        if (!in_array($entity,$allowedEntities)) {
//            throw new exception("Illegal entity '$entity' passed to database");
//        }
//        
//        $formsModel = new Application_Model_DbTable_Forms;
//        
//        if ($formID == 'all') {    //MULTIPLE FORMS
//            $entityForms = $formsModel->getAssociatedForms($entity,$entityID);            
//        } else {                   //SINGLE FORM
//            $entityForms = array($formID => NULL);
//        }
//
//        $ptcps = array();
//        if ($ptcpToRemove != 'all') {  //SINGLE PTCP
//            array_push($ptcps,$ptcpToRemove);           
//        } else {                       //Many PTCP
//            //get all participants associated with current entity
//            
//            if ($entity == 'fund') {
//                //get funder participants
//                
//                //this doesn't work because the program and funder are 
//                //already disassociated by this time.
//                
//                $query = "SELECT DISTINCT participantID 
//                          FROM participantPrograms as p, programFunders as f
//                          WHERE p.programID = f.programID
//                          AND f.funderID = $entityID";
//                $ptcpTemp = $this->getAdapter()->fetchAll($query);
//                foreach ($ptcpTemp as $p) {
//                    $id = $p['participantID'];
//                    array_push($ptcps,$id);
//                }
//            } else {
//                switch ($entity) {
//                    case 'dept' : 
//                        $model = new Application_Model_DbTable_ParticipantDepts;
//                        break;
//                    case 'prog' :
//                        $model = new Application_Model_DbTable_ParticipantPrograms;
//                        break;
//                    case 'group' :
//                        $model = new Application_Model_DbTable_ParticipantGroups;
//                        break;
//                    default: break;
//                }
//                $ptcps = $model->getList('ptcp',$entityID);
//            }
//        }
//        print "relevant participants are";
//        print_r($ptcps);
//        //for each, check if given form is still required:
//        $alertsRemoved = 0;
//        
//        foreach ($entityForms as $fid => $freq) {
//            foreach ($ptcps as $ptcpID) {
//                $requiredForms = $formsModel->getAssociatedForms('ptcp', $ptcpID);  
//                if (array_key_exists($fid,$requiredForms)) { //if yes, do nothing
//                    print "Form id $fid is still required.\n";
//                } else { //if no, find and remove alert
//                    print "Form id $fid is no longer required, removing alert.";
//                    $alert = $this->checkFormPtcpAlert($fid, $ptcpID);
//                    $alertID = $alert['id'];
//                    print "\nFound alert $alertID";
//                    $this->unsetFormPtcpAlert($alertID);
//                    $alertsRemoved++;
//                } 
//            }
//        }
//       return $alertsRemoved;
//    }
//    
//    public function confirmRequirements($entity,$entityID,$pid) {
//        $allowedEntities = array('dept','prog','group', 'fund');
//        $ptcps = array();
//        
//        if (!in_array($entity,$allowedEntities)) {
//            throw new exception("Illegal entity '$entity' passed to database");
//        }
//        
//        if($pid != 'all') {
//            array_push($ptcps,$pid);
//        } else {
//            if ($entity == 'fund') {
//                //get funder participants
//                $query = "SELECT DISTINCT participantID 
//                          FROM participantPrograms as p, programFunders as f
//                          WHERE p.programID = f.programID
//                          AND f.funderID = $entityID";
//                $ptcpTemp = $this->getAdapter()->fetchAll($query);
//                
//                foreach ($ptcpTemp as $p) {
//                    $id = $p['participantID'];
//                    array_push($ptcps,$id);
//                }
//                
//            } else {
//                switch ($entity) {
//                    case 'dept' : 
//                        $model = new Application_Model_DbTable_ParticipantDepts;
//                        break;
//                    case 'prog' :
//                        $model = new Application_Model_DbTable_ParticipantPrograms;
//                        break;
//                    case 'group' :
//                        $model = new Application_Model_DbTable_ParticipantGroups;
//                        break;
//                    default: break;
//                }
//                $ptcps = $model->getList('ptcp',$entityID);
//            }
//        }
//        
//        $formsModel = new Application_Model_DbTable_Forms;
//        $recordsModel = new Application_Model_DbTable_DynamicForms;
//
//        //pull required forms
//        $forms = $formsModel->getAssociatedForms($entity,$entityID);
//        //process each form
//        $numAffected = 0;        
//        foreach ($ptcps as $ptcpID) {
//            foreach ($forms as $id => $frequency) {
//                $formRecord = $formsModel->getRecord($id);
//                $tableName  = $formRecord['tableName'];
//                $ptcpRecords = $recordsModel->getRecords($ptcpID,$tableName);
//                
//                if (count($ptcpRecords) == 0) {
//                    //check for existing alert first
//                    $alert = $this->checkFormPtcpAlert($id, $ptcpID);
//                    if ($alert['id'] == 0) {
//                        $this->setFormPtcpAlert($ptcpID, $id, '1');
//                        $numAffected ++;
//                    } // only adding if no alert already exists.
//                } else {
//                    
//                    switch ($frequency) {
//                        case 'monthly'      : $numFreq = 30; $warning =  7; break;
//                        case 'quarterly'    : $numFreq = 120;$warning = 14; break;
//                        case 'semi-annual'  : $numFreq = 180;$warning = 30; break;
//                        case 'annually'     : $numFreq = 365;$warning = 30; break;
//                        default             : $numFreq = 10000;  $warning = 0;
//                    }
//                    $today = time();
//                    $latest= strtotime($ptcpRecords[0]['responseDate']);
//                    $modifier = $numFreq * 24 * 60 * 60; // frequency in seconds
//                    $warning  = $warning * 24 * 60 * 60; // warning period in seconds
//
//                    if (($today + $warning) >= ($latest + $modifier)) {
//                        $dueDate = date("Y-m-d", $latest + $modifier);
//                        $this->setFormPtcpAlert($ptcpID, $id, '2', $dueDate);
//                        $numAffected++;
//                    } else {
//                        continue;
//                    }
//                }
//            }
//        }
//        
//        return $numAffected;
//    }
//    
//    public function setFormPtcpAlert($ptcpID, $formID, $type, $showAfter=''){
//        //$type: 1 == missing, 2 == due
//        if (strlen($showAfter) == 0) {
//            $showAfter = time();
//        }
//        
//        $data = array(
//            'participantID' => (int)$ptcpID,
//            'alertID' => (int)$type,
//            'formID'  => (int)$formID,
//            'startDate' => $showAfter
//        );
//        
//        $this->insert($data);
//    }
//    
//    public function alterFormPtcpAlert($id, $newType, $newDate){
//        $data = array(
//            'alertID'     =>  (int)$newType,
//            'startDate'   =>  (int)$newDate
//        );
//        
//        $this->update($data, "id = '$id'");        
//    }
//    
//    public function unsetFormPtcpAlert($id){
//        $this->delete("id = '$id'");
//    }
//
//    public function checkFormPtcpAlert($formID, $ptcpID) {
//        $record = $this->fetchRow("formID = $formID AND participantID = $ptcpID");
//        
//        if ($record) {
//            return $record->toArray();
//        } else {
//            return 0;
//        }
//    }
    
    public function getVolAlertStatus($pid, $type='all') {
        //type is one of 'all', 'forms', 'custom';
        
        switch ($type) {
          case 'all' : $whereAddOn = ' AND doNotDisplay != 1';
              break;
          case 'forms' : $whereAddOn = ' AND formID IS NOT NULL AND doNotDisplay != 1';
              break;
          case 'custom': $whereAddOn = ' AND formID IS NULL AND doNotDisplay !=1';
              break;
        };
        
        $today = date("Y-m-d",time());
        
        $queryText = "SELECT COUNT(*) FROM alertsVolunteerss
                      WHERE volID = $pid
                      AND startDate <= '$today'";
        
        $queryText .= $whereAddOn;
        
        $alerts = $this->getAdapter()->fetchOne($queryText);

        if ($alerts > 0) {
            $return = TRUE;
        } else {
            $return = FALSE;
        }
        
        return $return;
    }
    
    public function getVolAlerts($pid) {
      //Returns array of arrays.
      //Each alert array consists of 'type' (custom or system), 
      //                             'id' (alertID),
      //                             'formID',
      //                             'text'
        
        
      $alertTable = new Application_Model_DbTable_Alerts;  
      $alerts = array();
      
      $alertRows = $this->fetchAll("volID = $pid AND (`doNotDisplay` IS NULL OR `doNotDisplay` != '1')")->toArray();
      
      if (count($alertRows) > 0) {
          foreach ($alertRows as $alertInstance) {
          //check whether alert is for some future date
          $startDate = $alertInstance['startDate'];
          $compareDate = strtotime($startDate);
          
          $now = time();
          if ($compareDate <= $now) {
              $alertID    = $alertInstance['alertID'];
              $alertText = $alertTable->getRecord($alertID);
              
              
              if ($alertID < 3) { // ' Alerts 1 and 2 are reserved for system
                  if ($alertID == 2) {
                      $printDate = date("M j, Y", $compareDate);
                  } else {
                      $printDate = "";
                  }
                  $formID = $alertInstance['formID'];
                  $volID = $alertInstance['volID'];
                  $alertText = $this->_processText($volID,$formID,$printDate,$alertText);
                  $alertType = 'system';
              } else {
                  $alertType = 'custom';
                  $formID = NULL;
              }
              
              $thisAlert = array(
                  'type'  => $alertType,
                  'id'    => $alertID,
                  'formID'=> $formID,
                  'text'  => $alertText
              );
              
              array_push($alerts,$thisAlert);
          } else {
              continue;
          }
        }
        return $alerts;
      
      }
    }
    
    public function getList($column,$id) /* $column = 'vol' or 'alerts' */ {
        switch ($column) {
            case 'vol' : 
                $select     = "alertID = " . (int)$id;
                $colname    = "volID";
                break;
            case 'alerts' :
                $select     = "volID = " . (int)$id;
                $colname    = "alertID";
                break;
            default : throw new Exception("\"$column\" is not a valid option.");
        }
        
        $select .= " AND (doNotDisplay IS NULL or doNotDisplay = 0)";
        
        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row[$colname]);
        }
        return $results;
    }
        
    public function addAlert($alert,$vol,$startDate='') {
        if (strlen($startDate) == 0) {
            $startDate = time();
        }
        
        $data = array(
            'volID' => (int)$vol,
            'alertID' => (int)$alert,
            'startDate' => $startDate
        );
        $result = $this->insert($data);
        return $result;
    }
    
    public function unsetAlert($alert,$vol) {
        $select = "volID = " . (int)$vol . " and alertID = " . (int)$alert;
        $data = array (
            doNotDisplay => 1
        );
        
        return $this->update($data,$select);
    }
}