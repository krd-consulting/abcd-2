<?php

class AjaxController extends Zend_Controller_Action
{ 
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $evaluator = FALSE;
    private $volunteer = FALSE;
    private $db = NULL;
    private $status = array();
    
    private $preData    = array();
    private $postData   = array();
    private $intData    = array();
    
    
    
    public function init()
    {
        $this->_helper->viewRenderer->setNoRender(true); //all ajax actions
        
        $this->uid = Zend_Registry::get('uid');
        $this->root = Zend_Registry::get('root');
        $this->mgr = Zend_Registry::get('mgr');
        $this->evaluator = Zend_Registry::get('evaluator');
        $this->volunteer = Zend_Registry::get('volunteer');
       
        $this->db   = $this->getInvokeArg('bootstrap')->getResource('db');
    }

    protected function _getAuthAdapter()
    {
	$dbAdapter = Zend_Db_Table::getDefaultAdapter();
	$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

	$authAdapter	->setTableName('users')
			->setIdentityColumn('userName')
			->setCredentialColumn('password')
			->setCredentialTreatment('md5(?)');

	return $authAdapter;
    }
    
    private function _neatTrim($str, $n, $delim='...') 
    {
        $matches = array();
        $len = strlen($str);
        if ($len > $n) {
            preg_match('/(.{' . $n . '}.*?)\b/', $str, $matches);
            return rtrim($matches[1]) . $delim;
        } else {
            return $str;
        }
    }

    protected function _returnList($type) {
        $return = '';
        switch ($type) {
            case 'single' :
                $dataTable = new Application_Model_DbTable_Users;
                $idList = $dataTable->getAllowedStaffIDs($this->uid);
                break;
                
            case 'grps' :
                $dataTable = new Application_Model_DbTable_Groups;
                $idList = $dataTable->getStaffGroups($this->uid);
                break;
                
            case 'prgs' :
                $dataTable = new Application_Model_DbTable_Programs;
                $idList = $dataTable->getStaffPrograms($this->uid);
                break;
            
            case 'depts' :
                $dataTable = new Application_Model_DbTable_Depts;
                $dataForListTable = new Application_Model_DbTable_UserDepartments;
                $idList = $dataForListTable->getList('depts',$this->uid);
                break;
            
            case 'forms' : 
                $dataTable = new Application_Model_DbTable_Forms;
                $idList = $dataTable->getStaffForms($this->uid);
                break;
        }
                     
        foreach ($idList as $validID) {
            $name = $dataTable->getName($validID);
            $display = "<li class=draggable data-name='$name' data-type='$type' data-id='$validID'>$name</li>";
            $return .= $display;
        }

        return $return;
    }
    
    protected function _collectStatusTotals($eID, $end, $entityType) 
    {
        $activeInst = 0;
        $leaveInst = 0;
        $waitlistInst = 0;
        $concludedInst = 0;
        
        switch ($entityType) {
            case 'prog' :
            case 'program' : 
                $tables = array('participantPrograms', 'ptcpProgramArchive');
                $idCol = "programID";
                break;
            case 'user' :
            case 'staff' :
                $tables = array('participantUsers');
                $idCol = "userID";
                break;
            default:
                throw new Exception("Cannot collect status totals for entity type $entityType");
        }
        
        foreach ($tables as $table) {
            
            $queryText = "
                SELECT p.participantID, p.status,p.statusDate 
                FROM $table AS p 
                INNER JOIN ( 
                        SELECT participantID, max(statusDate) AS status_date 
                        FROM $table 
                        WHERE $idCol = $eID
                        AND statusDate < '$end 23:59:59'
                        GROUP BY participantID ) pmax 
                ON pmax.participantID = p.participantID and pmax.status_date = p.statusDate 
                ORDER BY p.participantID ASC
            ";
            $sql = $this->db->query($queryText);
            $records = $sql->fetchAll();

            //print $queryText;
            //print_r($records);
            
            foreach ($records as $row) {
                switch ($row['status']) {
                    case 'active': $activeInst++;
                        break;
                    case 'leave': $leaveInst++;
                        break;
                    case 'waitlist': $waitlistInst++;
                        break;
                    case 'concluded': $concludedInst++;
                        break;
                }

            }
        }
        $list = array(
            'active' => $activeInst,
            'leave' => $leaveInst,
            'waitlist' => $waitlistInst,
            'concluded' => $concludedInst
        );
        
        return $list;
    }
    
    protected function _processCheckBoxVals(array $currentRecords, $lineToAdd) {
        $result = $currentRecords;
                    $checkBoxVals = explode(", ",$lineToAdd);
                    unset($checkBoxVals[0]);
                    foreach ($checkBoxVals as $valToCount) {
			$valToCount=trim($valToCount);
                        if (!array_key_exists($valToCount,$result)) {
			    $result[$valToCount] = 0;
			}
                        $result[$valToCount] +=1;
                    }
        return $result;
    }
    
    protected function _quartile($rawArray,$quartile){
      switch ($quartile) {
         case '1' : $p = 0.25;
            break;
         case '2' : $p = 0.5;
            break;
         case '3' : $p = 0.75;
            break;
         case '4' : $p = 1.0;
            break;
         default : throw new exception ("Quartile must be an integer between 1 and 4");
      }
      
      $count = count($rawArray);
      sort($rawArray);
      $rankArray = array_filter($rawArray);
      
      $n = $count*$p;
      
      if (ceil($n) == $n) 
         return ($rankArray[$n-1]+$rankArray[$n])/2;
      else 
         return $rankArray[ceil($n)-1];
   }
    
    protected function _removeOutliers($numArray,$type='extreme') {
       switch ($type) {
          case 'extreme': $multiplier = 3; break;
          case 'mild'   : $multiplier = 1.5; break;
       }
       
       //get Q1
       $q1 = $this->_quartile($numArray,"1");
       //get Q3
       $q3 = $this->_quartile($numArray,"3"); 
       //get inter-quartile range
       $iqr = $q3 - $q1;
       //set fences
       $lowerFence = $q1 - $iqr*$multiplier;
       $upperFence = $q3 + $iqr*$multiplier;

       $lowRemoved = array_filter($numArray, 
               function ($x) use ($lowerFence) {
                  return $x > $lowerFence;
               }
          );
          
       $filteredArray = array_filter($lowRemoved,
               function ($x) use ($upperFence) {
                  return $x < $upperFence;
               }
       );
       
       return $filteredArray;
    }
    
    protected function _getMatchPhrase($key,$match) 
    {
        $keywords = array(
            'is' => '=',
            'is not' => '!=',
            'equals' => '=',
            'does not equal' => '!=',
            'is less than' => '<',
            'is greater than' => '>',
            'includes' => 'REGEXP',
            'begins with' => 'like',
            'ends with' => 'like',
            'excludes' => 'not REGEXP',
            'is after' => '>',
            'is before' => '<'
            
        );
        
        
        
        $string = $keywords[$key];
        switch ($key) {
            case 'includes' : 
            case 'excludes' :
                if (is_array($match)) { 
                    $phrase = " '(" . implode("|",$match) . ")' "; 
                } else {
                    $phrase = " '(" . $match . ")' ";
                }
                break;
            case 'begins with': $phrase = " '$match%'"; break;
            case 'ends with'  : $phrase = " '%$match'"; break;
            default:            $phrase = " '$match'";
        }
        
        $returnQuery = $string . $phrase;
        
        return $returnQuery;
    }
    
    protected function _getPtcpIDs() 
    {//make list of participantIDs i have access to
         $myPtcpIDs = array();
         if ((!$this->root) && ($this->mgr)) {
             $userDepts = new Application_Model_DbTable_UserDepartments;
             $ptcpDepts = new Application_Model_DbTable_ParticipantDepts;
             $myDeptIDs = $userDepts->getList('depts', $this->uid);
             foreach ($myDeptIDs as $deptID) {
                 $ptcpList = $ptcpDepts->getList('ptcp',$deptID);
                 foreach ($ptcpList as $pid) {
                     if (!in_array($pid, $myPtcpIDs)) {
                         array_push($myPtcpIDs, $pid);
                     }
                 }
             }
         }
         
         if (!$this->mgr) {
             //list is everyone in my progs
             $userProgs = new Application_Model_DbTable_UserPrograms;
             $ptcpProgs = new Application_Model_DbTable_ParticipantPrograms;
             $myProgIDs = $userProgs->getList('progs', $this->uid);
             foreach ($myProgIDs as $progID) {
                 $ptcpList = $ptcpProgs->getList('ptcp',$progID);
                 foreach ($ptcpList as $pid) {
                     if (!in_array($pid, $myPtcpIDs)) {
                         array_push($myPtcpIDs, $pid);                         
                     }
                 }
             }
         }
         return $myPtcpIDs;
    }
    
    protected function _getStaffIDs() 
    {//make list of staffIDs i have access to 
        $myStaffIDs = array();
        if ((!$this->root) && ($this->mgr)) {
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $myDeptIDs = $userDepts->getList('depts', $this->uid);
            foreach ($myDeptIDs as $deptID) {
                $userList = $userDepts->getList('users',$deptID);
                foreach ($userList as $uid) {
                array_push($myStaffIDs, $uid); 
                }
            }
        }

        if (!$this->mgr) { //only access to myself
            array_push($myStaffIDs, $this->uid);                         
        }

        return array_unique($myStaffIDs);
    }
    
    protected function _getGroupIDs() 
    {//make list of groupIDs i have access to 
        $groupTable = new Application_Model_DbTable_Groups;
        $myGroupIDs = $groupTable->getStaffGroups($this->uid);
        return array_unique($myGroupIDs);
    }
    
    protected function _prepostProcess($data,$fieldID,$formID) {
        foreach ($data as $recID => $record) {
            //legacy check for pre or post
            if ($formID == 8) {
                $ppType = $record['field_1'];
            } else {
                $ppType = $record['prePost'];
            }    
                        
            switch ($ppType) {
                case 'Pre' : array_push($this->preData,$record[$fieldID]); break;
                case 'Interim' : array_push($this->intData,$record[$fieldID]); break;
                case 'Post' : array_push($this->postData,$record[$fieldID]); break;
                default: break;
            }
        }    
    }
    
    protected function _mmmr($array=array(), $output = 'mean'){
    
        switch($output){
            case 'mean':
                $count = count($array);
                $sum = array_sum($array);
                $total = $sum / $count;
            break;
            case 'median':
                rsort($array);
                $middle = round(count($array) / 2);
                $total = $array[$middle-1];
            break;
            case 'mode':
                $v = array_count_values($array);
                arsort($v);
                foreach($v as $k => $v){$total = $k; break;}
            break;
            case 'range':
                sort($array);
                $sml = $array[0];
                rsort($array);
                $lrg = $array[0];
                $total = $lrg - $sml;
            break;
        }
        return $total;
} 
    
    protected function _addUpPrePosts($eID,$formID,$elType) {
        $results = array(
                'name' => '',
                'type' => '',
                'pre' => array(),
                'int' => array(),
                'post' => array()
            );
        
        //get name
        $elTable = new Application_Model_DbTable_CustomFormElements;
        $el = $elTable->getElement($eID,$formID);        
        $elName = $el['elementName'];
        
        $results['name']=$elName;
        $results['type']=$elType;
        
        switch ($elType) {
            case 'num' :
                $cleanPreSet = $this->_removeOutliers($this->preData,'extreme');
                $cleanIntSet = $this->_removeOutliers($this->intData,'extreme');
                $cleanPstSet = $this->_removeOutliers($this->postData,'extreme');
                
                $results['pre']['mean'] = round($this->_mmmr($cleanPreSet,'mean'));
                $results['pre']['median']  = $this->_mmmr($cleanPreSet,'median');
                
                $results['int']['mean'] = round($this->_mmmr($cleanIntSet,'mean'));
                $results['int']['median']  = $this->_mmmr($cleanIntSet,'median');
                
                $results['post']['mean'] = round($this->_mmmr($cleanPstSet, 'mean'));
                $results['post']['median'] = $this->_mmmr($cleanPstSet, 'median');
                break;

            case 'radio': 
                $results['pre'] = array_count_values($this->preData);
                $results['int'] = array_count_values($this->intData);
                $results['post']= array_count_values($this->postData);
                
                break;
            case 'checkbox':
                break;
            case 'text':
            case 'textbox':
            case 'date':
            default:
                break;
        }
        return $results;
    }
    
    
    protected function _getExcelSheet($headers, $data, $name, $creator, $type=NULL)
    {
        $sheet = new PHPExcel;
        $sheet->getProperties()->setCreator($creator);
        $sheet->getProperties()->setTitle('ABCD Report for ' . $name);
        $sheet->getProperties()->setSubject('ABCD Report for ' . $name);
        $maxWidth = 35;
        
        $today = date('F d, Y', time());
        $footer = "Generated by ABCD for $creator on $today";
        $header = "$name report";

        $sheet->setActiveSheetIndex(0);
        //Set print header & footer
        $sheet->getActiveSheet()->getHeaderFooter()->setOddHeader("&L&B$header&R&BPage &P of &N");
        $sheet->getActiveSheet()->getHeaderFooter()->setEvenHeader("&L&B$header&R&BPage &P of &N");
        
        $sheet->getActiveSheet()->getHeaderFooter()->setOddFooter("&L $footer");
        $sheet->getActiveSheet()->getHeaderFooter()->setEvenFooter("&L $footer");
        
        $i = 1; $k = 'A'; // 97 is ASCII code for 'a'
        //Set Column headers
        foreach ($headers as $header) {
            $title = $header['sTitle'];
            $cell = $k . $i;
            $sheet->getActiveSheet()->setCellValue($cell,$title);
            $sheet->getActiveSheet()->getStyle($cell)->applyFromArray(array(
                    'font' => array('bold' => true, 'name' => 'Cambria', 'size' => '13' ),
                    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER),
                    'borders' => array('bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM))
                    )
                    );
//            $sheet->getActiveSheet()->getColumnDimension($k)->setAutoSize(true);
//                    //trying for column size
//                    $sheet->getActiveSheet()->calculateColumnWidths();
//                    $width = $sheet->getActiveSheet()->getColumnDimension($k)->getWidth();
//                    if ($width > $maxWidth) {
                        $sheet->getActiveSheet()->getColumnDimension($k)->setAutoSize(FALSE);
                        $sheet->getActiveSheet()->getColumnDimension($k)->setWidth($maxWidth);
//                    }
                    
            $k++;
        }
        //Add Data
        $i++; //move to the next row
        foreach ($data as $record) {
            $k = 'A';
            foreach ($record as $cellValue) {
                $cellNum = $i;
                $cellLetter = $k; //move through the columns starting with $k ('A');
                $cell = $cellLetter . $cellNum;
                $sheet->getActiveSheet()->setCellValue($cell,$cellValue);
                $sheet->getActiveSheet()->getStyle($cell)->applyFromArray(array(
                    'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                                        'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER),
                    'font' => array('name' => 'Calibri', 'size' => '11'),
                    'borders' => array(
                        'bottom' => array('style' => PHPExcel_Style_Border::BORDER_HAIR),
                        'left' => array('style' => PHPExcel_Style_Border::BORDER_HAIR),
                        'right' => array('style' => PHPExcel_Style_Border::BORDER_HAIR),
                        )
                    ));
                $odd = $i%2;
                if($odd) {
                    $sheet->getActiveSheet()
                        ->getStyle($cell)
                        ->applyFromArray(array(
                            'fill' => array(
                                'type' => PHPExcel_Style_Fill::FILL_SOLID, 
                                'color' => array('rgb' => 'e1e0f7')) 
                        ));
                }
                $k++;
            }
            $i++;
        }

        //remove first column if asked
        if ($type == 'removeOne') {
            $sheet->getActiveSheet()->removeColumn('A');
        }
        
        //set sheet-wide styles
        $sheet->getActiveSheet()->getStyle('A1:Z1000')->getAlignment()->setWrapText(TRUE);
        
        //Save File
        $now = time();
        $fileName = "abcd-report-$now.xlsx";
        $excelFile = new PHPExcel_Writer_Excel2007($sheet);
        $excelFile->save(APPLICATION_PATH . '/../public/files/' . $fileName);
        
        return $fileName;
    }
    
    protected function _getProgramSelect() 
    {
        $progField  =   new Zend_Form_Element_Select('progField');
        $progField  ->  setLabel('Program');
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
        //get program names
        foreach ($programIDs as $progID) {
            //Get program ID and name

            $p = $programTable->getRecord($progID);
            $pName = $p['name'];

            //add these as select options to our form element;
            $progField->addMultiOption($progID, $pName);
        }     
        return $progField;
    }
    
    protected function _addDeptField($form) 
    {
        $depts = new Application_Model_DbTable_Depts;
        
        $userDepts = new Application_Model_DbTable_UserDepartments;
        $deptlist = array();
        $myDeptIDs = $userDepts->getList('depts', $this->uid);
        foreach ($myDeptIDs as $deptID) {
            $thisdept = $depts->getDept($deptID);
            $deptlist[$deptID] = $thisdept['deptName'];
        }
        
        $dept   = $form->deptField;
        $dept   ->addMultiOptions($deptlist);
        
        //$form->addElement($dept);
        return $form;
    }
    
    public function indexAction()
    {
        $this->_helper->redirector('index','dash');
    }

    public function checkifexistsAction() {
        $type = $_POST['type'];
        $column = $_POST['col'];
        $token = $_POST['token'];
        $jsonReturn = array();
        
        switch ($type) {
            case 'scheduleset':
                   $dataTable = new Application_Model_DbTable_ScheduleSets;
                break;
            default:
                throw new exception("Invalid option $type passed to duplicate checker.");
                break;
        }
        
        $count = count($dataTable->fetchAll("`$column` = '$token'")->toArray());
        
        if ($count == 0) {
            $jsonReturn['duplicate'] = "no";
        } elseif ($count >= 1) {
            $jsonReturn['duplicate'] = "yes";
        } else {
            throw new exception("Nonsense returned by duplicate checker.");
        }
        
        $this->_helper->json($jsonReturn);
        
    }
    
    public function addschedulesetAction() {
        $data = array(
            'name'  => $_POST['setName'],
            'startDate'  => $_POST['startDate'],
            'endDate'  => $_POST['endDate'],
            'fromTime'  => $_POST['fromTime'],
            'toTime'  => $_POST['toTime'],
            'createdBy'  => $_POST['createdBy'],
            'resources'  => "{" . $_POST['resources'] . "}"
        );
        
        $jsonReturn = array();
        //$jsonReturn = $data;
        $table = new Application_Model_DbTable_ScheduleSets;
        $result = $table->addSet($data);
        
        if ($result > 1) {
            $deptUserTable = new Application_Model_DbTable_UserDepartments;
            $deptTable = new Application_Model_DbTable_Depts;
            
            
            $jsonReturn['result'] = $result;
            $jsonReturn['pid'] = $result;
            $jsonReturn['deptlist'] = array();
            
            $deptList = $deptUserTable->getList('depts', $this->uid);
            foreach ($deptList as $did) {
                $name = $deptTable->getName($did);
                $thisArray = array('id' => $did, 'deptName' => $name);
                array_push($jsonReturn['deptlist'],$thisArray);
            }
            
        } else {
            $jsonReturn['result'] = 0;
        }
                
        $this->_helper->json($jsonReturn);
    }
    
    public function getbookedresourcesAction() {
//        $sid = $_POST['sid'];
//        $date = $_POST['date'];
//        $from = $_POST['from'];
//        $to = $_POST['to'];
//        $rType = $_POST['rType'];
//        $rID = $_POST['rID'];
        extract($_POST);
        //die ("Found: $sid, $date, $from, $to, $rType, $rID");
        
        if ($rID && $rType) {
            $addlSqlText = " AND resourceType = '$rType' AND resourceID = '$rID'";
        } else {
            $addlSqlText = '';
        }
        
        $sqlText = "SELECT id,resourceType,resourceID FROM `scheduledEvents` " . 
                   "WHERE setID = $sid AND doNotDisplay = 0 " . 
                    "AND '$date $from:00' < `endDate` " .
                    "AND '$date $to:00' > `startDate`";
        
        $sql = $this->db->query($sqlText . $addlSqlText);
        $result = $sql->fetchAll();
        
        $this->_helper->json($result);
    }
    
    public function gettimeboundariesAction() {
        $resourceTable = new Application_Model_DbTable_ScheduleSets;
        $sID = $_POST['sid'];
        
        if ($sID == '') {
            $jsonReturn = array(
                'startTime' => '',
                'endTime'   => ''
            );
        } else {
            $record = $resourceTable->getSet($sID);
            $jsonReturn = array(
                'startTime'  => $record['fromTime'],
                'endTime'    => $record['toTime'],
                'startDate'  => $record['startDate'],
                'endDate'  => $record['endDate']
            );
        }       
        
        $this->_helper->json($jsonReturn);
    }
    
    public function getresourcelistAction() {
        $resourceTable = new Application_Model_DbTable_ScheduleSets;
        $scheduleSetID = $_POST['scheduleSet'];
        $record = $resourceTable->getSet($scheduleSetID);
        
        //have to get hacky because JSON object is not valid: keys repeat (adhoc, vol, etc)
        $tempString = str_replace('{','',str_replace('}','',$record['resources']));
        $resources = explode(",",str_replace('"','',str_replace("\n",'',$tempString)));
        
        $jsonReturn = array();
        
        foreach ($resources as $resource) {
            $rA = explode(":",$resource);
            $type = $rA[0];
            $id = $rA[1];
            
            if ($type == 'adhoc') { 
                $name = $id;
            } else {
                $table = new Application_Model_DbTable_Users;
                $name = $table->getName($id);
            }
            
            $record = array(
                'resourceID' => $id,
                'resourceName' => $name,
                'resourceType' => $type
            );
            
            array_push($jsonReturn,$record);
        }
        
        
        $this->_helper->json($jsonReturn);
        
        
    }
    
    public function geteventneedsAction() {
        $eventTable = new Application_Model_DbTable_ProgramEvents;
        $jsonReturn = array();
        $eventID = $_POST['id'];
        
        $info = $eventTable->getEventNeeds($eventID,$this->uid);
        
        $this->_helper->json($info);
    }
    
    public function enrollvolunteersAction() {
        $jsonReturn = array();
        $enrollTable = new Application_Model_DbTable_ProgramEventSignups;
        
        $info = $_POST['info'];
        
        $infoArray = explode(",",$info);
        $numSubmitted = count($infoArray);
        $i = 0;
        
        //temporary event ID in $eid, job id in $jid
        $temp = explode(":",$infoArray[0]);
        $eid = $temp[0];
        $jid = $temp[1];
        
        //clear existing entry
        $enrollTable->delete("eventID = $eid AND jobID = $jid");

        
        foreach ($infoArray as $dataString) {
            $dataArray = explode(":",$dataString);
            $eventID = $dataArray[0];
            $jobID = $dataArray[1];
            $userID = $dataArray[2];
            
            if ($userID == "X") {
                continue;
            } else {
                $enrollTable->addRecord($eventID, $userID, $jobID);
            }
        }
        
        $numEnrolled = count($enrollTable->getSignupsByType($eventID, $jobID));
        
        if ($numSubmitted == $numEnrolled) {
            $jsonReturn['success'] = 'yes';
        }
        //throw new exception("$userID");
        $this->_helper->json($jsonReturn);
    }
    
    public function scheduleeventAction () {
        $jsonReturn = array();
        $action = $_POST['task'];
        $setID = $_POST['setid'];
        $units = FALSE;
        $ptcpTable = new Application_Model_DbTable_Participants;
        $dataTable = new Application_Model_DbTable_DynamicForms;
        
        switch ($action) {
            case 'getevents':
                $eventTable = new Application_Model_DbTable_ScheduledEvents;
                $events = $eventTable->getApptsBySet($setID,$this->uid);
                $units = TRUE;
                break;
            case 'getprogevents':
                $eventTable = new Application_Model_DbTable_ProgramEvents;
                $events = $eventTable->getProgEvents($setID,$this->uid);
                break;
            case 'getvolevents':
                $eventTable = new Application_Model_DbTable_ProgramEventSignups;
                $events = $eventTable->getVolEvents($setID);
                break;
            default: throw new exception("Could not process task $action in AJAX event schedule action.");
        }
        
        foreach ($events as $event) {
            if ($units) {$unitID = $event['resourceType'][0] . "_" . $event['resourceID'];}
            
            $sdate = date("m/d/Y H:i",strtotime($event['startDate']));
            $edate = date("m/d/Y H:i",strtotime($event['endDate']));
            $text = $event['name'];
            
            if ($event['linkType'] == 'participant') {
                $ptcp = $ptcpTable->getRecord($event['linkID']);
                $dob = $ptcp['dateOfBirth'];
                $profile = $dataTable->getLatestPtcpRecord('form_150',$event['linkID']);
                $phone = $profile['field_5'];
                $text .= "<br><span class='cal-dob'>$dob</span><br>";
                $text .= "<span class='cal-phone'>$phone</span>";
            }
            
            $data = array(
                'id' => $event['id'],
                'text' => $text,
                'desc' => $event['description'],
                'start_date' => $sdate,
                'end_date' => $edate,
                'unit_id' => $unitID,
                'spot' => $event['location']
            );
            array_push($jsonReturn,$data);
        }
        $this->_helper->json($jsonReturn);
    }
    
    public function formeventdeleteAction() {
        extract($_POST); //formid and entryid
        $eventTable = new Application_Model_DbTable_ScheduledEvents;
        $formTable = new Application_Model_DbTable_DynamicForms();
        $eventID = $eventTable->releaseRecordFromForm($formid,$entryid);

        if ($formid < 10) {
            $table = "form_0" . $formid;
        } else {
            $table = "form_" . $formid;
        }
        
        $softDeleteForm = $formTable->updateData($table,array('doNotDisplay' => 1),"id = $entryid");
        $this->_helper->json($eventID);
    }
    
    
    public function archiveformentryAction() {
        extract($_POST); //formid and entryid
        $eventTable = new Application_Model_DbTable_ScheduledEvents;
        $eventID = $eventTable->update(array('doNotDisplay' => 1),"formID = $formid AND formEntryID = $entryid");
        
        $formTable = new Application_Model_DbTable_DynamicForms;
        
        if ($formid < 10 ) {
            $tableName = "form_0" . $formid;
        } else {
            $tableName = "form_" . $formid;
        }
        
        $updatedRecord = $formTable->updateData($tableName, array('doNotDisplay' => 1), "id = $entryid");
        
        $this->_helper->json($updatedRecord);
        
    }
    
    public function eventarchiveAction() {
        $type = $_POST['type'];
        
        switch ($type) {
            case 'set': $eventTable = new Application_Model_DbTable_ScheduledEvents; break;
            case 'program': $eventTable = new Application_Model_DbTable_ProgramEvents; break;
            default: throw new exception("Invalid calendar type $type passed to ajax event archive action.");
        }
        
        $eventID = $_POST['id'];
        $data = array('doNotDisplay' => 1, 'createdBy' => $this->uid);
        $eventTable->update($data,"id = $eventID");
        
        $jsonReturn = array('status' => "SUCCESS");
        
        $this->_helper->json($jsonReturn);
    }
    
    public function getprogjobsAction() {
        $progID = $_POST['progid'];
        $pJobsTable = new Application_Model_DbTable_ProgramJobs;
        $jobsTable = new Application_Model_DbTable_VolunteerJobs;
        
        $jobsList = $pJobsTable->getList('jobs',$progID);
        $jobs = array();
        foreach ($jobsList as $jobID) {
            $name = $jobsTable->getName($jobID);
            $jobs[$jobID] = $name;
        }
        
        $this->_helper->json($jobs);
    }
    
    public function saveeventfromformAction() {
        $eventTable = new Application_Model_DbTable_ScheduledEvents;
        extract($_POST);
        
        $formattedName = "<a href='/participants/profile/id/$targetID'>$name</a>"; 
        
        $data = array(
            'setID' => $scheduleID,
            'startDate' => $date . " " . $from . ":00",
            'endDate' => $date . " " . $to . ":00",
            'name' => $formattedName,
            'resourceType' => $rType,
            'resourceID' => $rID,
            'createdBy' => $this->uid,
            'linkType' => $target,
            'linkID' => $targetID,
            'doNotDisplay' => 0
            );
        try {
            $eventID = $eventTable->addAppointment($data);
        } catch (Exception $e) {
            $result = false;
            $message = $e;
        }
        
        if ($eventID > 0) {
            $result = true;
            $eventid = $eventID;
        } else {
            $result = false;
            $message = "Event save operation failed in database.";
        }
        
        $jsonReturn = array(
            'success' => $result,
            'eventid' => $eventid,
            'message' => $message
        );
        
        $this->_helper->json($jsonReturn);
    }
    
    public function linkformtoeventAction() {
        extract($_POST); //eventID, formID and formEntryID
        $form = explode("_",$formID);
        $formIDnum = $form[1];
        
        $jsonReturn = array();
        
        $eventTable = new Application_Model_DbTable_ScheduledEvents;
        $data = array(
            'formID' => $formIDnum,
            'formEntryID' => $formEntryID
        );
        
        $result = $eventTable->update($data,"id = $eventID");
        
        if ($result) {
            $jsonReturn['successOfThing'] = 'yes';
        } else {
            $jsonReturn['successOfThing'] = 'no';
        }
        
        $this->_helper->json($jsonReturn);
    }
    
    public function eventsaveAction() {
        $type = $_POST['type'];
        $task = $_POST['task'];
        $startDate = $_POST['startdate'];
        $endDate = $_POST['enddate'];
        $name = $_POST['text'];
        $eventID = $_POST['eventid'];
        $desc = $_POST['desc'];
        $location = $_POST['location'];
        $volNeeded = $_POST['volunteersNeeded'];
        $setID = $_POST['setid'];
        
        switch ($type) {
            case 'program': $eventTable = new Application_Model_DbTable_ProgramEvents;
                             $data = array(
                                'programID' => $setID,
                                'jobsNeeded' => $volNeeded,
                                'startDate' => $startDate,
                                'endDate' => $endDate,
                                'location' => $location,
                                'createdBy' => $this->uid,
                                'name' => $name,
                                'description' => $desc
                            );
                break;
            case 'set'    : $eventTable = new Application_Model_DbTable_ScheduledEvents;
                            $rawResourceArray = explode("_",$_POST['unit_id']);
                            $resourceID = $rawResourceArray[1];
                            
                            switch ($rawResourceArray[0]) {
                                case 'v':   $resourceType = 'volunteer'; break;
                                case 's':   $resourceType = 'staff'; break;
                                case 'a':   $resourceType = 'adhoc'; break;
                                default:    throw new exception("Unrecognized Resource Type " . $rawResourceArray[0]. " submitted to event save ajax."); break;            
                            }
                            
                            $data = array(
                                'setID'         => $setID,
                                'startDate'     => $startDate,
                                'endDate'       => $endDate,
                                'resourceID'    => $resourceID,
                                'resourceType'  => $resourceType,
                                'name'          => $name,
                                'createdBy'     => $this->uid,
                                'doNotDisplay'  => 0
                            );
                break;
            default: throw new exception ("Wrong calendar type $type sent to event save ajax action.");
        }
        
        switch ($task) {
            case 'insert': $newRecord = $eventTable->addAppointment($data, $this->uid);
                            break;
            case 'update': $success = $eventTable->update($data,"id = $eventID"); $newRecord = $eventID;
                            break;
            default:       $newRecord = 0; $message = "Wrong task type ($task) passed to event save ajax.";
        }
        
        
        if ($newRecord > 0) {
            $status = 'SUCCESS';
        } else {
            $status = 'FAIL';
        }
        
        $jsonReturn = array('status' => $status, 'eventid' => $newRecord);
        
        
        $this->_helper->json($jsonReturn);
        
    }
       
    
    public function appointmentsinscheduleAction() {
        $id = $_POST['id'];
        $apptsTable = new Application_Model_DbTable_ScheduledEvents;
        $num = count($apptsTable->getApptsBySet($id,$this->uid,$this->root));
        
        $this->_helper->json(array('num' => $num));
    }
    
    public function addalertAction() 
    {
        $alerts = new Application_Model_DbTable_Alerts;
        
        $alertText = $_POST['alertText'];
        $target = $_POST['target'];
        $targetID = $_POST['targetID'];
        $startDate = $_POST['startDate'];
        $ptcpIDs = array();
        $volIDs = array();
        
        $aid = $alerts->addRecord($alertText);
        
        switch ($target) {
            case 'group':
                $groupParticipants = new Application_Model_DbTable_ParticipantGroups;
                $ids = $groupParticipants->getList('ptcp',$targetID);
                $ptcpIDs = $ids;
                break;
            case 'participant':
                array_push($ptcpIDs, $targetID);
                break;
            case 'volunteer':
                array_push($volIDs,$targetID);
                break;
            default: 
                break;
        }
        
        if (count($ptcpIDs) > 0) {
            $alertsPtcps = new Application_Model_DbTable_AlertsParticipants;
            foreach ($ptcpIDs as $pid) {
                $result = $alertsPtcps->addAlert($aid, $pid, $startDate);
                
            }
        }
        
        if (count($volIDs) > 0) {
            $alertsVols = new Application_Model_DbTable_AlertsVolunteers;
            foreach ($volIDs as $vid) {
                $result = $alertsVols->addAlert($aid,$vid,$startDate);
            }
            
        }
        if ($result > 0) {
            $this->_helper->json(array('success' => 'yes'));
        } else {
            throw new exception ("Alert was not created successfully. Result is $result");
        }
        
        
    }
    
    public function sessionrenewAction() {
        
        $namespace = new Zend_Session_Namespace('Zend_Auth');
        $namespace -> setExpirationSeconds(28800);
        $this->_helper->json("OK");
    }
    
    
    public function uploadfileAction() {
        $desc = $_POST['filDescription'];
        $tType = $_POST['targetType'];
        $tID = $_POST['targetID'];
        $file = $_FILES['files'];
        $model = new Application_Model_DbTable_Files;
                   
        $name = $file['name'][0];
        $tmpname = $file['tmp_name'][0];
        $location = APPLICATION_PATH . "/../data/uploaded-files/" . $tID . "-" . time() . "-" . $name;
        
        if(strlen($desc)==0) {
            $description = $name;
        } else {
            $description = $desc;
        }
        
        //store file info in database
        $dbStore = $model->addFile($tType,$tID,$description,$location,$this->uid);
        //move file to permanent location
        move_uploaded_file($tmpname,$location);
        
        $jsonReturn['success'] = 'yes';
        
        $this->_helper->json($jsonReturn);
    }
    
    public function downloadfileAction() {
        $id = $_POST['id'];
        $model = new Application_Model_DbTable_Files;
        $file = $model->getFile($id);
        $location = $file['location'];
        $description = $file['description'];
        $link = APPLICATION_PATH . "/../public/files/links/" . $description;
        symlink($location,$link);
               
        $filename = "/files/links/" . $description;
        
        $jsonReturn['success'] = 'yes';
        $jsonReturn['url'] = $filename;
        $this->_helper->json($jsonReturn);
        
    }
    
    public function archiveAction() {
        $type = $_POST['type'];
        $id = $_POST['id'];
        $fail = FALSE;
        $message = '';
        
        switch ($type) {
            case 'schedule' : $table = new Application_Model_DbTable_ScheduleSets;
                break;
            default: $fail = TRUE; $message = "Right now only schedules can be processed by the generic AJAX archive function.";
                break;
        }
        
        if (!$fail) {
            $result = $table->archiveRecord($id,$this->uid,$this->root);
            if ((int)$result != 1) {$fail = true; $message = "Incoherent attempt to archive record. Database returned error code $result.";}
        }
        
        $fail ? $success = "no" : $success = "yes";
        $this->_helper->json(array('success' => $success, 'message' => $message));
    }
    
    public function archivefileAction() {
        $id = $_POST['id'];
        $model = new Application_Model_DbTable_Files;
        $result = $model->archiveRecord($id);
        $jsonReturn['result'] = $result;
        $this->_helper->json($jsonReturn);
    }
    
    public function deleteactivityAction() {
        $id = $_POST['id'];
        $model = new Application_Model_DbTable_VolunteerActivities;
        $update = array(
            'doNotDisplay' => 1,
            'updatedBy' => $this->uid
        );
        $result = $model->update($update,"id=$id");
        $jsonReturn['result'] = $result;
        $this->_helper->json($jsonReturn);
    }
    
    public function removealertAction() 
    {
        $aid = $_POST['id'];
        $pid = $_POST['pid'];
        $type = $_POST['type'];
        
        $jsonReturn = array();
        
        $alerts = new Application_Model_DbTable_Alerts;
        
        switch ($type) {
            case 'ptcp': $alertsLink = new Application_Model_DbTable_AlertsParticipants;
                break;
            case 'vol': $alertsLink = new Application_Model_DbTable_AlertsVolunteers;
                break;
            default: throw new exception("Unrecognized type '$type' passed to alert remove function.");
                break;
        }
        
        $alertsLink->unsetAlert($aid,$pid);
        $jsonReturn['success'] = 'yes';
        $this->_helper->json($jsonReturn);
        
    }
    
    public function addactAction() 
    {
        $activities = new Application_Model_DbTable_Activities;
        $data = array(
                $_POST['column'] => $_POST['pid'],
                'userID' => $_POST['uid'],
                'date' => $_POST['date'],
                'duration' => $_POST['duration'],
                'note' => $_POST['note']
                );
        $goodID = $activities->insert($data);
        if (strlen($goodID) != 0) {
            $jsonReturn = array('success' => 'yes');
        } else {
            $jsonReturn = array('success' => 'no');
        }
        
        $this->_helper->json($jsonReturn);
    }
    
    public function addactvolAction() {
        $jsonReturn = array();
        //add activity to volActivity table and to ptcp if relevant
        //1. populate variables
        extract($_POST);
        //creates $from, $to, $date, $note, $userID, $volID, $targetID, $targetType, $programID
        //2. calculate duration
//        $fromTime = new DateTime($from);
//        $toTime = new DateTime($to);
//        $hours = $fromTime->diff($toTime)->format("%H");
//        $min   = $fromTime->diff($toTime)->format("%i");
//        if ((int)$min <= 15) {
//            $rounded = 0;
//        } elseif ((int)$min <= 30) {
//            $rounded = 0.25;
//        } elseif ((int)$min <= 45) {
//            $rounded = 0.5;
//        } else {
//            $rounded = 1;
//        }
//        $duration = (int)$hours + $rounded;  
        
        $duration = round(((strtotime($to) - strtotime($from))/3600),1);       
        //3. if ptcp, store in ptcp Activity table
        if ($targetType == 'participant') {
            $ptcpActivityTable = new Application_Model_DbTable_Activities;
            $ptcpData = array(
                'userID' => $volID,
                'participantID' => $targetID,
                'date' => $date,
                'duration' => $duration,
                'note' => $note
            );
            $ptcpActivityTable->insert($ptcpData);
        }
        //4. store in vol activity table
        $volActTable = new Application_Model_DbTable_VolunteerActivities();
        $data = array(
            'volunteerID'   => $volID,
            'programID'     => $programID,
            'type'          => $targetType,
            'typeID'        => $targetID,
            'date'          => $date,
            'fromTime'      => $from,
            'toTime'        => $to,
            'duration'      => $duration,
            'description'   => $note
        );
        
        $record = $volActTable->addRecord($data);
        if ($record > 0) {
            $jsonReturn['success'] = 'yes';
        } else {
            $jsonReturn['success'] = 'no';
        }
        $this->_helper->json($jsonReturn);
    }
    
    public function getformdataActionARCHIVE() {
        $jsonReturn = array();
        
        $tableID = $_POST['formID'];
        $formIDAr = explode("_",$tableID);
        $formID = $formIDAr[1];
        
        $ptcpID = $_POST['ptcpID'];
        
        $dataTable = new Application_Model_DbTable_DynamicForms;
        $elementsTable = new Application_Model_DbTable_CustomFormElements;
        
        $latestRecord = $dataTable->getLatestPtcpRecord($tableID, $ptcpID);
        
        $fillableData = array_slice($latestRecord, 6);
        foreach ($fillableData as $elementID => $value) {
            $elementName = $elementsTable->getElementName($elementID,$formID);
            $jsonReturn[$elementName] = $value;
        }
        
        $this->_helper->json($jsonReturn);
        
    }

    public function getformdataAction() {
        $jsonReturn = array();
        
        $tableID = $_POST['formID'];
        $formIDAr = explode("_",$tableID);
        $formID = $formIDAr[1];
        $recordID = $_POST['recordID'];
        //$ptcpID = $_POST['ptcpID'];
        
        $dataTable = new Application_Model_DbTable_DynamicForms;
        $elementsTable = new Application_Model_DbTable_CustomFormElements;
        
        $latestRecord = $dataTable->getRecordByID($tableID, $recordID);
        
        $jsonReturn['entryID'] = $latestRecord['id'];
        
        $fillableData = array_slice($latestRecord, 7);
        
        foreach ($fillableData as $elementID => $value) {            
            $elementName = $elementsTable->getElementName($elementID,$formID);
            $jsonReturn[$elementName] = $value;
        }


        $this->_helper->json($jsonReturn);
        
    }

    
    public function deletestoredreportAction() {
        $reportID = $_POST['rid'];
        $srTable = new Application_Model_DbTable_StoredReports;
        $result = $srTable->deleteReport($reportID);
        if ($result) {
            $jsonReturn = array('success' => 'yes');
        } else {
            $jsonReturn = array('success' => 'no');
        }
        $this->_helper->json($jsonReturn);
    }
    
    public function updatefrequencyAction() {
        $reportID = $_POST['rid'];
        $newFreq  = $_POST['freq'];
        $srTable = new Application_Model_DbTable_StoredReports;
        $srTable->update(array('frequency' => $newFreq),"id = $reportID");
        $jsonReturn=array('success' => 'yes');
        $this->_helper->json($jsonReturn);
    }
    
    public function getprogvolinfoAction() {
        $error = NULL;
        $progID = $_POST['progid'];
        $progTable = new Application_Model_DbTable_Programs;
        $myProgs=$progTable->getStaffPrograms($this->uid);
        
        if (!in_array($progID,$myProgs)) {
            $error = "You are now allowed to view this program.";
        }
        
        if ($error) {
            $jsonReturn = array( 
                'success' => 'no',
                'errormessage' => 'error'
            );
        } else {
            $progRecord = $progTable->getRecord($progID);
            $volType  = $progRecord['volunteerType'];
            switch ($volType) {
                case 'oneToOne' : $volType='ptcp'; $display = 'participant'; break;
                default: $display = $volType;
            }
            $jsonReturn = array(
                'success' => 'yes',
                'progID'  => $progID,
                'volType' => $volType,
                'displayType' => $display
            );
        }
              
        $this->_helper->json($jsonReturn);  
        
    }
    
    public function deptlistAction() 
    {
        $depts = new Application_Model_DbTable_Depts;
        
        if ($this->root) {
            $deptlist = $depts->fetchAll()->toArray();
        } else {
            $userDepts = new Application_Model_DbTable_UserDepartments;
            $deptlist = array();
            $myDeptIDs = $userDepts->getList('depts', $this->uid);
            foreach ($myDeptIDs as $deptID) {
                $thisdept = $depts->getDept($deptID);
                array_push($deptlist,$thisdept);
            }
        }
        
        $jsonResult['deptlist'] = $deptlist;
        $this->_helper->json($jsonResult);   
    }
    
    public function associaterecordsAction()        
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $action     =   $_POST['what'];      //'add' or 'remove'
        $rType      =   $_POST['rtype'];     //record Type: 'dept', 'program', 'funder', 'user', 'ptcp', 'form', 'volunteer', 'vjob'
        $rid        =   $_POST['rid'];       //Record ID
        $pType      =   $_POST['ptype'];     //parent Type: 'dept', 'program', 'funder', 'user', 'form', 'volunteer'
        $pid        =   $_POST['pid'];       //parent ID
        $addlProgID =   $_POST['addlprogid'];//program ID for staff-ptcp connection

        $rType = trim($rType);
        $pType = trim($pType);
        $rid = trim($rid);
        $pid = trim($pid);
        $now  =  date("Y-m-d");
        
        $alertConfirm = FALSE;
        $alertFor     = FALSE;
        $propagateStatus = FALSE;
        
        if ($rType == 'program') $rType = 'prog';
        if ($pType == 'program') $pType = 'prog';
        
        if ($rType == 'volunteer' || $rType == 'vol') $rType = 'user';
        if ($pType == 'volunteer' || $pType == 'vol') $pType = 'user';
        
        $validPTypes = array('dept', 'prog', 'form', 'funder', 'group', 'ptcp', 'user');
        $validRTypes = array('dept', 'prog', 'form', 'ptcp', 'user', 'funder', 'group', 'vjob');
        
        if ((!in_array($rType, $validRTypes)) || (!in_array($pType, $validPTypes))) {
            throw new exception ("Invalid attempt to add a $rType to a $pType.");
        }
        
        $association = $rType . "-" . $pType;
                
        switch ($association) {
            //dept-program is a one-to-one, so should never occur here.
            case 'vjob-prog' :
                $assocTable = new Application_Model_DbTable_ProgramJobs;
                $idArray = array('programID' => $pid, 'jobID' => $rid);
                $pCol = 'programID';
                $rCol = 'jobID';
                break;
            
            case 'dept-form' : 
                $assocTable = new Application_Model_DbTable_DeptForms;
                $idArray = array(
                  'deptID' => $rid, 'formID' => $pid
                );
                $pCol = 'formID';
                $rCol = 'deptID';
                break;
                        
            case 'form-dept' : 
                $assocTable = new Application_Model_DbTable_DeptForms;
                $idArray = array(
                  'deptID' => $pid, 'formID' => $rid
                );
                $pCol = 'deptID';
                $rCol = 'formID';
                break;
            
            case 'funder-form' :
                $assocTable = new Application_Model_DbTable_FunderForms;
                $idArray = array(
                    'funderID' => $rid, 'formID' => $pid
                );
                $rCol = 'funderID';
                $pCol = 'formID';
                break;
            
            case 'form-funder' :
                $assocTable = new Application_Model_DbTable_FunderForms;
                $idArray = array(
                    'funderID' => $pid, 'formID' => $rid
                );
                $pCol = 'funderID';
                $rCol = 'formID';
                break;
                
            case 'funder-prog' :
                $assocTable = new Application_Model_DbTable_ProgramFunders;
                $idArray = array(
                    'funderID' => $rid, 'programID' => $pid
                );
                $rCol = 'funderID';
                $pCol = 'programID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'prog',
                    'id'   => $pid,
                    'ptcpID' => 'all'
                );  
                break;
            
            case 'prog-funder' :
                $assocTable = new Application_Model_DbTable_ProgramFunders;
                $idArray = array(
                    'funderID' => $pid, 'programID' => $rid
                );
                $pCol = 'funderID';
                $rCol = 'programID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'prog',
                    'id'   => $rid,
                    'ptcpID' => 'all'
                );
                break;
                
            case 'form-prog' :
                $assocTable = new Application_Model_DbTable_ProgramForms;
                $idArray = array(
                    'formID' => $rid, 'programID' => $pid
                );
                $rCol = 'formID';
                $pCol = 'programID';
                break;
            
            case 'prog-form' :
                $assocTable = new Application_Model_DbTable_ProgramForms;
                $idArray = array(
                    'formID' => $pid, 'programID' => $rid
                );
                $pCol = 'formID';
                $rCol = 'programID';
                break;
            
            case 'ptcp-user' :
                $assocTable = new Application_Model_DbTable_ParticipantUsers;
                $idArray = array(
                    'participantID' => $rid,
                    'userID'        => $pid,
                    'programID'     => $addlProgID,
                    'enrollDate'    => $now
                );
                $rCol = 'participantID';
                $pCol = 'userID';
                $alertConfirm = FALSE;
                $propagateStatus = TRUE;
                break;
            
            case 'user-ptcp' : 
                $assocTable = new Application_Model_DbTable_ParticipantUsers;
                $idArray = array(
                    'participantID' => $pid,
                    'userID'        => $rid,
                    'programID'     => $addlProgID,
                    'enrollDate'    => $now
                );
                $rCol = 'userID';
                $pCol = 'ptcpID';
                $alertConfirm = FALSE;
                $propagateStatus = TRUE;
                break;
            
            case 'ptcp-prog' :
                $assocTable = new Application_Model_DbTable_ParticipantPrograms;
                $idArray = array(
                    'participantID' => $rid, 'programID' => $pid, 'enrollDate' => $now
                );
                $rCol = 'participantID';
                $pCol = 'programID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'prog',
                    'id'   => $pid,
                    'ptcpID' => $rid
                );
                break;
            
            case 'prog-ptcp' :
                $assocTable = new Application_Model_DbTable_ParticipantPrograms;
                $idArray = array(
                    'participantID' => $pid, 'programID' => $rid, 'enrollDate' => $now
                );
                $pCol = 'participantID';
                $rCol = 'programID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'prog',
                    'id'   => $rid,
                    'ptcpID' => $pid
                );
                break;
                
            case 'user-group' :
                $assocTable = new Application_Model_DbTable_VolunteerGroups;
                $idArray = array(
                    'volunteerID' => $rid, 'groupID' => $pid
                );
                $rCol = 'volunteerID';
                $pCol = 'groupID';
                break;
            
            case 'group-user' :
                $assocTable = new Application_Model_DbTable_VolunteerGroups;
                $idArray = array(
                    'groupID' => $rid, 'volunteerID' => $pid
                );
                $rCol = 'groupID';
                $pCol = 'volunteerID';
                break;
            
            case 'user-prog' :
                $assocTable = new Application_Model_DbTable_UserPrograms;
                $idArray = array(
                    'userID' => $rid, 'programID' => $pid
                );
                $rCol = 'userID';
                $pCol = 'programID';
                break;
            
            case 'prog-user' :
                $assocTable = new Application_Model_DbTable_UserPrograms;
                $idArray = array(
                    'userID' => $pid, 'programID' => $rid
                );
                $pCol = 'userID';
                $rCol = 'programID';
                break;
             
            case 'ptcp-group' :
                $assocTable = new Application_Model_DbTable_ParticipantGroups;
                $idArray = array(
                    'participantID' => $rid, 'groupID' => $pid
                );
                $rCol = 'participantID';
                $pCol = 'groupID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'group',
                    'id'   => $pid,
                    'ptcpID' => $rid
                );
                break;
            
            case 'group-ptcp' :
                $assocTable = new Application_Model_DbTable_ParticipantGroups;
                $idArray = array(
                    'participantID' => $pid, 'groupID' => $rid
                );
                $pCol = 'participantID';
                $rCol = 'groupID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'group',
                    'id'   => $rid,
                    'ptcpID' => $pid
                );
                break;
             
            case 'user-dept' :
                $assocTable = new Application_Model_DbTable_UserDepartments;
                $idArray = array(
                    'userID' => $rid, 'deptID' => $pid
                );
                $rCol = 'userID';
                $pCol = 'deptID';
                break;
            
            case 'dept-user' :
                $assocTable = new Application_Model_DbTable_UserDepartments;
                $idArray = array(
                    'userID' => $pid, 'deptID' => $rid
                );
                $pCol = 'userID';
                $rCol = 'deptID';
                break;
             
            case 'ptcp-dept' :
                $assocTable = new Application_Model_DbTable_ParticipantDepts;
                $idArray = array(
                    'participantID' => $rid, 'deptID' => $pid
                );
                $rCol = 'participantID';
                $pCol = 'deptID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'dept',
                    'id'   => $pid,
                    'ptcpID' => $rid
                );
                break;
            
            case 'dept-ptcp' :
                $assocTable = new Application_Model_DbTable_ParticipantDepts;
                $idArray = array(
                    'participantID' => $pid, 'deptID' => $rid
                );
                $pCol = 'participantID';
                $rCol = 'deptID';
                $alertConfirm = TRUE;
                $alertFor     = array(
                    'type' => 'dept',
                    'id'   => $rid,
                    'ptcpID'=> $pid
                );
                break;

            default: 
                throw new exception("Unknown AJAX variable passed: $association");
        }
        
        switch ($action) {
            case 'add' : 
                if ($propagateStatus) {
                    $ptcpID = $idArray['participantID'];
                    $progID = $idArray['programID'];
                    
                    //getting status from program and setting it to caseload
                    $statusTable = new Application_Model_DbTable_ParticipantPrograms;
                    $record = $statusTable->getRecord($ptcpID,$progID);
                    $idArray['status'] = $record['status'];
                    print_r($idArray);    
                }
                
                if($association == 'ptcp-group')  {
                    $assocTable->enroll($rid, $pid);
                } elseif ($association == 'group-ptcp') {
                    $assocTable->enroll($pid,$rid);
                } else {
                    $assocTable->insert($idArray);
                }
                
                if($alertConfirm) {                    
                    $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
                    //print_r($alertFor);
                    $ptcpAlerts->confirmRequirements($alertFor['type'], $alertFor['id'], $alertFor['ptcpID']);
                }
                
                $content = "Added successfully.";
                break;
            
            case 'remove' : 
                if ($propagateStatus) {
                    $ptcpID = $idArray['participantID'];
                    $progID = $idArray['programID'];
                    $userID = $idArray['userID'];
                    
                    //getting status from caseload and setting it in program
                    $statusTable = new Application_Model_DbTable_ParticipantUsers;
                    $progTable = new Application_Model_DbTable_ParticipantPrograms;
                    
                    $record = $statusTable->getRecord($ptcpID, $userID, $progID);
                    $status = $record['status'];
                    $oldRecord = $progTable->getRecord($ptcpID,$progID);
                    $prevStatus = $oldRecord['status'];
                    
                    if ($oldRecord) {
                        $progTable->changeStatus($ptcpID,$progID,$oldRecord['enrollDate'],$status,$prevStatus,"Propagated from caseload change.");
                    }
                }
                
                $where = "$pCol = '$pid' and $rCol = '$rid'";
                if (strlen($addlProgID) > 0) {
                    $where .= " and programID = '$addlProgID'";
                }
                
                if (method_exists($assocTable, 'archiveRecord')) {
                    $assocTable->archiveRecord($where);
                    $content = "Archived and cleared.";
                } else {
                    $content = "Deleted.";
                }
                $assocTable->delete($where);
                
                if ($alertConfirm) {
                    $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
                    $ptcpAlerts->confirmRemoval($alertFor['type'], $alertFor['id'], 'all', $alertFor['ptcpID']);
                    //print "alertFor contains ";
                    //print_r($alertFor);
                }
                
                break;
            
            default: throw new exception ("Unknown option passed to AJAX call: $action");
        }
        
        $this->_helper->json($content);
        
        
    }

    public function isformfcssAction()
    {
       $this->_helper->viewRenderer->setNoRender();
       $fID = str_replace('form_','',$_POST['id']);
       $formTable = new Application_Model_DbTable_Forms;
       $formRecord = $formTable->getRecord($fID);
       if ($formRecord['fcssID'] > 0) {
          $response = 'yes';
       } else {
          $response = 'no';
       }
       
       $jsonReturn = array (
           'fcss' => $response
       );
       
       $this->_helper->json($jsonReturn);
    }
    
    public function programstatusAction()           
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $progID =   $_POST['programID'];
        $ptcpID =   $_POST['ptcpID'];
        $status =   $_POST['status'];
        $sNote  =   $_POST['note'];
        
        $dataTable = new Application_Model_DbTable_ParticipantPrograms;
        
        $row = $dataTable->getRecord($ptcpID,$progID);//ensure it's an existing record
        if (count($row) == 0) {
            throw new exception ("Can't update status - non-unique database record.");
        }
        $enrollDate = $row['enrollDate'];
        $prevStatus = $row['status'];
        
        $dataTable->changeStatus($ptcpID, $progID, $enrollDate, $status, $prevStatus, $sNote);
        
        $ajaxReturn='success';
        $this->_helper->json($ajaxReturn);
    }
    
    public function casestatusAction()           
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $userID =   $_POST['userID'];
        $ptcpID =   $_POST['ptcpID'];
        $progID =   $_POST['progID'];
        $status =   $_POST['status'];
        $sNote  =   $_POST['note'];
        
        $dataTable = new Application_Model_DbTable_ParticipantUsers;
        $propagateTable = new Application_Model_DbTable_ParticipantPrograms;
        
        $row = $dataTable->getRecord($ptcpID,$userID,$progID);//ensure it's an existing record
        if (count($row) == 0) {
            throw new exception ("Can't update status - non-unique database record.");
        }
        $enrollDate = $row['enrollDate'];
        $prevStatus = $row['status'];
        
        $dataTable->changeStatus($ptcpID, $userID, $progID, $enrollDate, $status, $prevStatus, $sNote);
        $propagateTable->changeStatus($ptcpID, $progID, $enrollDate, $status, $prevStatus, $sNote);
        
        $ajaxReturn='success';
        $this->_helper->json($ajaxReturn);
    }
    
    public function editformAction()        
    {
        $this->_helper->viewRenderer->setNoRender();
        $type = $_POST['type'];        
        $fid = $_POST['id'];
        
        $validTypes = array('groups', 'forms', 'depts', 'programs', 'users', 'participants', 'volunteers');
        if (!in_array($type,$validTypes)) {
            throw new exception ("Invalid entity type '$type' cannot be edited.");
        }
        
        switch ($type) {
            case 'forms': $dataTable = new Application_Model_DbTable_Forms;
                $form = new Application_Form_FormCreator;
                $formID = new Zend_Form_Element_Hidden('id');
                $form->addElement($formID);
                $form->removeElement('dept');
                $data = $dataTable->fetchRow("id = $fid");
                $formValues = array(
                    'formName' => $data['name'],
                    'description' => $data['description'],
                    'formType' => $data['type'],
                    'formTarget' => $data['target'],
                    'id' => $fid
                );
              break;
          
           case 'participants': 
                $dataTable = new Application_Model_DbTable_Participants;
                $form = new Application_Form_AddParticipant;
                $pID = new Zend_Form_Element_Hidden('id');
                $form->addElement($pID);
                $form->removeElement('dept');
                
                $data = $dataTable->fetchRow("id = $fid");
                
                $formValues = array(
                    'fname'     => $data['firstName'],
                    'lname'     => $data['lastName'],
                    'dob'       => $data['dateOfBirth'],
                    'id'        => $fid
                );
              break;
          
            case 'groups': $dataTable = new Application_Model_DbTable_Groups;
                $form = new Application_Form_AddGroup;
                $data = $dataTable->fetchRow("id = $fid");
                $progField = $this->_getProgramSelect();                           
                $groupid = new Zend_Form_Element_Hidden('id');
                $form->addElements(array($progField,$groupid));

                $formValues = array(
                    'groupName'    => $data['name'],
                    'groupDesc'    => $data['description'],
                    'progField'    => $data['programID'],
                    'id'           => $data['id']
                );
                break;
             case 'depts': 
                $dataTable = new Application_Model_DbTable_Depts;
                $form = new Application_Form_Dept;
                $data = $dataTable->fetchRow("id = $fid");

                $formValues = array(
                    'deptName' => $data['deptName'],
                    'id' => $data['id']
                );
                break;
                       
             case 'programs':
                $dataTable = new Application_Model_DbTable_Programs;
                $form = new Application_Form_AddProgram;
                $data = $dataTable->fetchRow("id=$fid");
                $prgID = new Zend_Form_Element_Hidden('id');
                $form->addElement($prgID);

                $form = $this->_addDeptField($form);

                $formValues = array(
                    'pname'     => $data['name'],
                    'deptField' => $data['deptID'],
                    'voltype'   => $data['volunteerType'],
                    'id'        => $data['id']
                );
               break;
            
           case 'users':
           case 'volunteers':
                $dataTable = new Application_Model_DbTable_Users;
                $form = ($type == 'users' ? new Application_Form_AddUser : new Application_Form_AddVolunteer);
                $data = $dataTable->fetchRow("id=$fid");
                $usrID = new Zend_Form_Element_Hidden('id');
                $form->addElement($usrID);
                
                $userIsRecordsManager = FALSE;
                //get list of deparments for which logged-in user is set as manager
                if ($this->mgr) {
                    $userDepts = new Application_Model_DbTable_UserDepartments;
                    $mgrDeptsList = $userDepts->getManagerDepts($this->uid);
                    $recordDepts = $userDepts->getList('depts',$fid);
                    if (count(array_intersect($mgrDeptsList,$recordDepts)) > 0) {
                        $userIsRecordsManager = TRUE;
                    }
                }
                
                $form->removeElement('userID');
                if ($this->root || $userIsRecordsManager) {
                    $role = $form->role;
                    $role->setAttribs(array('class' => ''));
                } else {
                    $form->removeElement('role');
                }
                
                //$identifier = ($type == 'users' ? "email" : "phone");
                
                //print_r($data);
                
                $formValues = array(
                    'username'     => $data['userName'],
                    'fname'        => $data['firstName'],
                    'lname'        => $data['lastName'],
                    'pwd'          => '',
                    'email'        => $data['eMail'],
                    'role'         => $data['role'],
                    'id'           => $data['id']
                );                 
                break;
            
            default: break;
        }
        
        $form->populate($formValues);
                
        $htmlForm = $form->render();
        
        $jsonReturn['success'] = 'yes';
        $jsonReturn['form'] = $htmlForm;
         
        $this->_helper->json($jsonReturn);
    }
    
    public function updateformAction()      
    {
        $this->_helper->viewRenderer->setNoRender();
        $type = $_POST['type'];
        $data = array();
        parse_str($_POST['data'], $data);
        $id = (int)$data['id'];
        
        switch ($type) {
            case 'forms' :
                $updateData = array(
                    'name' => $data['formName'],
                    'description' => $data['description'],
                    'type' => $data['formType'],
                    'target' => $data['formTarget']
                );
                $table = new Application_Model_DbTable_Forms;
              break;
          
            case 'groups' :
                $updateData = array(
                    'name'              => $data['groupName'],
                    'description'       => $data['groupDesc'],
                    'programID'         => $data['progField']
		);
                $table = new Application_Model_DbTable_Groups;
              break;
          
           case 'depts' :
               $updateData = array(
                   'deptName' => $data['deptName']
               );
               $table = new Application_Model_DbTable_Depts;
               break;
           
           case 'programs' :
               $updateData = array(
                   'name' => $data['pname'],
                   'deptID' => $data['deptField'],
                   'volunteerType' => $data['voltype']
               );
               $table = new Application_Model_DbTable_Programs;
               break;
           
           case 'participants' :
               $updateData = array(
                   'firstName' => $data['fname'],
                   'lastName' => $data['lname'],
                   'dateOfBirth' => $data['dob']
               );
               $table = new Application_Model_DbTable_Participants;
               break;
           
           case 'users' :
           case 'volunteers':
               $updateData = array(
                   'userName'   => $data['username'],
                   'firstName'  => $data['fname'],
                   'lastName'   => $data['lname'],
                   'eMail'      => $data['email'],
                   'password'   => md5($data['pwd'])
               );
               
               if (array_key_exists('role', $data)) {
                   $updateData['role'] = $data['role'];
               }
               
               $table = new Application_Model_DbTable_Users;
               break;
               
            default: 
                break;
        }
        
        $numRows = $table->update($updateData, "id = $id");
        
        if ($numRows != 1) {
            $jsonReturn['success'] = 'no';
            $jsonReturn['num'] = $numRows;
            $jsonReturn['attempted'] = "Failed updating id $id";
        } else {
            $jsonReturn['success'] = 'yes';
        }
        
        $this->_helper->json($jsonReturn);
    }
    
    public function addgroupmeetingAction() 
    {
        //group ID
        $groupID = $_POST['id'];
        //get group data
        $data = $_POST['data'];
        $meetingData = array();
        parse_str($data, $meetingData);
        
        
        //set checkbox array to string, and replace it in the sql array
        if (array_key_exists('attendance', $meetingData)) {
            $enrolledIDs = implode(',', $meetingData['attendance']);
            $meetingData['enrolledIDs'] = $enrolledIDs;
            unset($meetingData['attendance']);
        } else {
            $meetingData['enrolledIDs'] = NULL;
        }    
        
        if (array_key_exists('vol_attendance',$meetingData)) {
            $volIDArray = $meetingData['vol_attendance']; //keep for future processing
            $volunteerIDs = implode(',', $meetingData['vol_attendance']);
            $meetingData['volunteerIDs'] = $volunteerIDs;
            unset($meetingData['vol_attendance']);
        } else {
            $meetingData['volunteerIDs'] = NULL;
        }
        
        $meetingData['guestCount'] = $meetingData['unenrolled'];
        unset($meetingData['unenrolled']);
        
        //register the sum of enrolled and unenrolled volunteers
        if (array_key_exists('volunteers', $meetingData)) {
            $totalVols = (int)$meetingData['guestVols'] + count($meetingData['volunteers']);
        } else {
            $totalVols = (int)$meetingData['guestVols'];
        }
        
        $meetingData['nonVolVols'] = $totalVols;
        unset($meetingData['guestVols']);
        $meetingData['duration'] = (int)$meetingData['duration'];
        $meetingData['groupID'] = $groupID;
        
        //print_r($meetingData);
        //enter group meeting ->get meeting ID
        $groupMeetings = new Application_Model_DbTable_GroupMeetings;
        
        $mID = $groupMeetings->addRecord($meetingData);
        
        //using meeting ID, record activity for staff person (current user)
        $actTable = new Application_Model_DbTable_Activities;
        $actTable->addRecord($this->uid, 'meeting', $mID, $meetingData['date'], $meetingData['duration']);
        
        //using meeting ID, enter attendance data for each participant
        if ($_POST['ptcps'] != '') {
            $ptcps = $_POST['ptcps'];
        } else {
            $ptcps = array();
        }
        $levelKeys = array(
            'In Attendance' => 'passive',
            'Active Contributor' => 'contrib',
            'Leadership Role' => 'leadrole'
        );
        $inserted = array();
        
        if (count($ptcps) > 0 ) {
            $ptcpMtgs = new Application_Model_DbTable_ParticipantMeetings;

            foreach ($ptcps as $p) {
                $pID = $p['id'];
                $levelkey = $p['level'];
                $level = $levelKeys[$levelkey];
                $volunteer = $p['vol'];

                $sqlData = array(
                'meetingID'           => $mID,
                'participantID'       => $pID,
                'participationLevel'  => $level,
                'volunteer'           => $volunteer
                );

                $rid = $ptcpMtgs->insert($sqlData);
                array_push($inserted, $rid);
            }
        }
        
        if ($_POST['vols'] != '') {
            $vols = $_POST['vols'];
        } else {
            $vols = array();
        }
        
        if (count($vols) > 0) {
            $volActivities = new Application_Model_DbTable_VolunteerActivities;
            $groupsTable = new Application_Model_DbTable_Groups;
            $groupRecord = $groupsTable->getRecord($groupID);
            $progID = $groupRecord['programID'];
            
            foreach ($vols as $v) {
                $volID = $v['id'];
                $jobID = $v['jobid'];
                $from= $v['fromTime'];
                $to = $v['toTime'];
                $note = $v['note'];
            
                //calculate duration
//                    $fromTime = new DateTime($from);
//                    $toTime = new DateTime($to);
//                    $hours = $fromTime->diff($toTime)->format("%H");
//                    $min   = $fromTime->diff($toTime)->format("%i");
//                    if ((int)$min <= 15) {
//                        $rounded = 0;
//                    } elseif ((int)$min <= 30) {
//                        $rounded = 0.25;
//                    } elseif ((int)$min <= 45) {
//                        $rounded = 0.5;
//                    } else {
//                        $rounded = 1;
//                    }
//                    $duration = (int)$hours + $rounded;        
                if ((strlen($to) > 0) && (strlen($from) > 0)) {
                    //storing as hours rounded to 1 decimal point
                    $duration = round(((strtotime($to) - strtotime($from))/3600),1); 
                } else {
                    $duration = $meetingData['duration'];
                }
                
            $activityData = array(
                'volunteerID'   => $volID,
                'programID'     => $progID,
                'type'          => 'group',
                'typeID'        => $groupID,
                'groupMeetingID'=> $mID,
                'jobID'         => $jobID,
                'date'          => $meetingData['date'],
                'fromTime'      => $from,
                'toTime'        => $to,
                'duration'      => $duration,
                'description'   => $note,
                'updatedBy'     => $this->uid
            );
            
            $volActivities->addRecord($activityData);
            }
        }
        
        $jsonReturn = array();
        
        if (count($inserted) == count($ptcps)) {
            $jsonReturn['success'] = 'yes';
        }
        
        $this->_helper->json($jsonReturn);
    }
    
    public function addvjobAction() {
        $jobName = $_POST['name'];
        $jobDesc = $_POST['description'];
        
        $sql = $this->db->query("select * from volunteerJobs WHERE `name` = '$jobName'");
        $num = count($sql->fetchAll());
        if ($num > 0) {
            $ajaxReturn['success'] = 'no';
            $ajaxReturn['message'] = "$num record already exists with name $jobName. Please choose another.";
        } else {
            $jobTable = new Application_Model_DbTable_VolunteerJobs;
            $newID=$jobTable->addRecord($jobName,$jobDesc,$this->uid);
            if ($newID > 0) {
                $ajaxReturn['success'] = 'yes';
            } else {
                $ajaxReturn['success'] = 'no';
                $ajaxReturn['message'] = 'Coult not add ' . $jobName . " to database.";
            }
        }
        
        $this->_helper->json($ajaxReturn);
    }
    
    public function checkduplicatesAction()
    {
        $table = $_POST['table'];
        $column = $_POST['column'];
        $value = $_POST['val'];
        
        if ($table == 'depts') {
            $table = 'departments';
            $column = 'deptName';
        }
        
        if ($table == 'users' || $table == 'volunteers') {
            $table = 'users';
            $column = 'userName';
        }
        
        $jsonReturn = array();
        
        $sqlStatement = "SELECT * FROM $table WHERE $column = '$value'";
        
        if (array_key_exists('col2', $_POST)) {
            $column2 = $_POST['col2'];
            $val2 = $_POST['val2'];
            $sqlStatement .= " AND $column2 = '$val2'";
        }
        
        if (array_key_exists('col3', $_POST)) {
            $column3 = $_POST['col3'];
            $val3 = $_POST['val3'];
            $sqlStatement .= " AND $column3 = '$val3'";
        }
        
        $sql = $this->db->query($sqlStatement);
        
        $rows = $sql->fetchAll();
        
        if (count($rows) == 0) {
            $jsonReturn['unique'] = 'yes';
        } else {
            $jsonReturn['unique'] = 'no';
        }
        
        $this->_helper->json($jsonReturn);
    }  
    
    public function updategroupnotesAction()
    {
        $this->getHelper('layout')->disableLayout();
        
        $id = $_POST['id'];
        $note = $_POST['value'];
        
        $data = array(
            'notes' => $note
        );
        
        $groupMeetings = new Application_Model_DbTable_GroupMeetings;
        $groupMeetings->updateRecord($id, $data);
        
        //$this->_helper->json($note);
        print nl2br($note);
    }
    
    public function updatevolnotesAction() 
    {
        $this->getHelper('layout')->disableLayout();
        
        $note = $_POST['value'];
        $passedID = split('-',$_POST['id']);
        if ($passedID[1] == 'vol') {
            $recordID = $passedID[0];
        } else {
            throw new exception("Unexpected id string format - " . $_POST['id']);
        }
        
        $activityTable = new Application_Model_DbTable_VolunteerActivities;
        $returnCode = $activityTable->updateRecord($recordID,'',$note);
        if ($returnCode == 1)
            print(nl2br($note));
    }
    
    public function updateptcpnotesAction()
    {
        $this->getHelper('layout')->disableLayout();
        
        $id = $_POST['id'];
        $note = $_POST['value'];
        
        $data = array(
            'note' => $note
        );
        
        $ids = explode('-',$id);
        $ptcpID = $ids[0];
        $mtgID = $ids[1];
        
        if ($mtgID == 'vol') {
            $id = $ids[0];
            $description = $note;
            $actTable = new Application_Model_DbTable_VolunteerActivities;
            $update = $actTable->updateRecord($id, '', $description);
            print(nl2br($description));
            return true;
        }
        
        if(count($ids) == 3) {
            $userID = $ids[1];
            
            if (($userID == $this->uid) || ($this->mgr)) {
                $activities = new Application_Model_DbTable_Activities();
                $activities->update($data, "userID = $userID and participantID = $ptcpID");
                $return = $note;
            } else {
                $return = "You can't update someone else's meeting notes.";
            }
            
            print nl2br($return);
            return true;
        }
                
        
        $ptcpMeetings = new Application_Model_DbTable_ParticipantMeetings;
        $numSuccess = $ptcpMeetings->update($data, "participantID = $ptcpID and meetingID = $mtgID");

        if ($numSuccess == 1) {
            print nl2br($note);
        } else {
            print ("Update didn't work, please try again.");
        }
    }
    
    public function formreportAction()
    {
        //Set up default variables
        $jsonReturn = array();
        
        //Get passed variables
        $formID     = $_POST['id'];
        $startDate   = $_POST['from'];
        $endDate     = $_POST['to'];
        $format     = $_POST['format'];
        
        if (key_exists('anonymize',$_POST)) {
            $anonymize  = $_POST['anonymize'];
        } else {
            $anonymize = FALSE;
        }
        
        if ($this->evaluator) $anonymize=TRUE;
        
        //check format
        $validFormats = array('table', 'excel');
        if (!in_array($format, $validFormats)) {
            throw new exception("Invalid format $format passed to report builder.");
        }
        
        $forms = new Application_Model_DbTable_Forms;
        $thisForm = $forms->getRecord($formID);
        //get form target and type
        
        $target = $thisForm['target'];
	$type 	= $thisForm['type'];
	
        $numericFields = array();

        //make columnnames array
            if ($anonymize) {
                $nameCol = array('sTitle' => 'File ID');
            } else {
                $nameCol = array('sTitle' => 'Name');
            }
            $dobCol = array('sTitle' => 'Date of Birth');
            $ageCol = array('sTitle' => 'Age');
            $dateCol = array('sTitle' => 'Date');
	    $deptCol = array('sTitle' => 'Department');
	    $survCol = array('sTitle' => 'Survey Type');
            $byCol   = array('sTitle' => 'Entered By');
            
         $columns = array($nameCol); 
         
         if (($target == "ptcp") || ($target == "participant")) {
             array_push($columns, $dobCol);
             array_push($columns, $ageCol);
         }

	 if ($type == 'prepost') {
		array_push($columns, $survCol);
	 }

         array_push($columns,$dateCol);
         array_push($columns,$deptCol);
        
         //add column names from dynamic elements table
         //make sure order matches reality
            $elementsTable = new Application_Model_DbTable_CustomFormElements;
            if ($formID < 10) {
                $tableName = "form_0" . $formID;
            } else {
                $tableName = "form_" . $formID;
            }
            
            $sqlString = "SHOW COLUMNS in `$tableName`;";
            $query = $this->db->query($sqlString);
            
            $cols = array_splice($query->fetchAll(),7); //first seven are not dynamic
            
            foreach ($cols as $col) {
                $elID = $col['Field'];
                $elName = $elementsTable->getElementName($elID,$formID);
                $elType = $elementsTable->getElementType($elID,$formID);
                $column = array('sTitle' => $elName, 'sID' => $elID, 'sCustomType' => $elType);
                array_push($columns, $column);
                
                if ($elType == 'num') {
                    $numericFields[$elID] = array(
                            'fieldName' => $elName, 
                            'values' => array(), 
                            'Total' => 'N/A', 
                            'Average' => 'N/A', );
                }
                
            }
         array_push($columns, $byCol);
         
         //get form data and Records
         $ptcps = new Application_Model_DbTable_Participants;
         $staff = new Application_Model_DbTable_Users;
         $groups = new Application_Model_DbTable_Groups;
	 $depts = new Application_Model_DbTable_Depts;
         $dynamicTables = new Application_Model_DbTable_DynamicForms;
         
         //get permissible arrays, based on target
         
         switch ($target) {
             case 'participant' : 
                 $myAllowedIDs = $this->_getPtcpIDs();
                 break;
             case 'group' : 
                 $myAllowedIDs = $this->_getGroupIDs();
                 break;
             case 'volunteer' : 
             case 'staff' : 
                 $myAllowedIDs = $this->_getStaffIDs();
                 break;
         }
         
         $formTable = $tableName;
         
         $returnData = array();
         
         $dataset = $dynamicTables->getRecords(NULL, $formTable, $startDate, $endDate);
         
         foreach ($dataset as $datarow) {
            //check that we have access to this ptcp
            
            $permission = FALSE;
            if (($this->root || $this->evaluator) || (in_array($datarow['uID'], $myAllowedIDs))) {
                $permission = TRUE;
            }
                                    
            if ($permission == TRUE) {
                $entityID = $datarow['uID'];
		$deptName = 'N/A';
                
                $deptID = $datarow['deptID'];
                if ($deptID != 0) {
                    $dept = $depts->getRecord($deptID);
                    $deptName = $dept['deptName'];
                }
                
                $date = $datarow['responseDate'];

                $staffName = $staff->getName($datarow['enteredBy']);
                
            //get part/group name
                switch($target) {
                    case 'participant' : 
                        $p = $ptcps->getRecord($entityID);
                        if ($anonymize) {
                            $name = $entityID;
                        } else {
                            $name = $p['firstName'] . ' ' . $p['lastName'];
                        }
                        $age = $p['age'];
                        $dob = $p['dateOfBirth'];

                        $myRow = array($name, $dob, $age, $date, $deptName);
                        
                        break;
                    case 'volunteer' :
                    case 'staff' :
                        $name = $staff->getName($entityID);
                        $myRow = array($name,$date,$deptName);
                        break;
                    case 'group' :
                        $name = $groups->getName($entityID);
                        $myRow = array($name,$date,$deptName);
                }
                
                $customData = array_slice($datarow,7);
                
                foreach ($customData as $fieldID => $field) {
                    //get all data into table
                    array_push($myRow, $field);
                    
                    //if numeric also do calculations
                    if (array_key_exists($fieldID,$numericFields) && (strlen($field) > 0)) {
                        array_push($numericFields[$fieldID]['values'],$field);
                        $total = array_sum($numericFields[$fieldID]['values']);
                        $avg = $total/count($numericFields[$fieldID]['values']);
                        $numericFields[$fieldID]['Total'] = $total;
                        $numericFields[$fieldID]['Average'] = $avg;
                    }
                    
                }
                array_push($myRow, $staffName);
                array_push($returnData,$myRow);                
            }
         }
                  
         //make total and average rows
         $totalRow = array();
        $averageRow = array();
        $addIt = FALSE;
         
         foreach ($columns as $count => $column) {
             $total = '';
             $average = '';
             
             $type = $column['sCustomType'];
             $cid  = $column['sID'];
             
             if ($count == 0) {
                 $total = 'Total: ';
                 $average = 'Average: ';
             }
             
             if ($type == 'num') {
                 $total = $numericFields[$cid]['Total'];
                 $average = $numericFields[$cid]['Average'];
                 $addIt = TRUE;
             }
             
             
             array_push($totalRow,$total);
             array_push($averageRow,$average);
             
         }
         
         if ($addIt) {
            array_push($returnData,$averageRow);
            array_push($returnData,$totalRow);
         }          
         //OUTPUT
         
         if ($format == 'table') {
             $jsonReturn['aoColumns'] = $columns;
             $jsonReturn['aaData'] = $returnData;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'excel') {
             //include_once 'PHPExcel.php';
             $creator = $staff->getName($this->uid);
             $name = $forms->getName($formID);
             $fileName = $this->_getExcelSheet($columns, $returnData, $name, $creator);
             $jsonReturn['file'] = $fileName;
             $this->_helper->json($jsonReturn);
         }
    }
    
    public function groupreportAction()
    {
        //Set up default variables
        $jsonReturn = array();
        
        //Get passed variables
        $groupID     = $_POST['id'];
        $startDate   = $_POST['from'];
        $endDate     = $_POST['to'];
        $format     = $_POST['format'];
        
        //check format
        $validFormats = array('table', 'excel', 'attend-graph', 'role-graph');
        if (!in_array($format, $validFormats)) {
            throw new exception("Invalid format $format passed to report builder.");
        }
        
        //make columnnames array
            $dateCol        = array('sTitle' => 'Date');
            $durationCol    = array('sTitle' => 'Duration');
            $totalAttCol    = array('sTitle' => 'Total Attendance');
            $enrolledCol    = array('sTitle' => 'Enrolled Members');
            $guestCol       = array('sTitle' => 'Guests');
            $volunteerCol   = array('sTitle' => 'Volunteers');
            $byCol          = array('sTitle' => 'Staff');
            
            
         $columns = array(  $dateCol, 
                            $durationCol, 
                            $totalAttCol,
                            $enrolledCol,
                            $guestCol,
                            $volunteerCol,
                            $byCol
                        );
         
         //get Records
         $userProgs     = new Application_Model_DbTable_UserPrograms;
         $userDepts     = new Application_Model_DbTable_UserDepartments;
         $programs     = new Application_Model_DbTable_Programs;
         $groups        = new Application_Model_DbTable_Groups;
         $meetings      = new Application_Model_DbTable_GroupMeetings;
         $activities    = new Application_Model_DbTable_Activities;
         $staff = new Application_Model_DbTable_Users;
         
         //make sure we have access to group being requested
         if (!$this->mgr) {
             $myProgs = $userProgs->getList('progs',$this->uid);
         } else {
             $myProgs = array();
             $myDepts = $userDepts->getList('depts',$this->uid);
             foreach ($myDepts as $deptID) {
                 $deptProgs = $programs->getProgByDept($deptID);
                 foreach ($deptProgs as $prog) {
                     array_push($myProgs,$prog['id']);
                 }
             }
         } 
         
         $groupRecord = $groups->getRecord($groupID);
         $groupProg = $groupRecord['programID'];
         $returnData = array();
         if ((in_array($groupProg, $myProgs)) || ($this->root)) {                 
            
            $dataset = $meetings->getGroupMeetings($groupID, $startDate, $endDate);            
            foreach ($dataset as $datarow) {
                $mtgID = $datarow['id'];
                $mtgRec = $activities->getTypeActivities('meeting', $mtgID);
                
                $staffID = $mtgRec[0]['userID'];
                $staffName = $staff->getName($staffID);
                
                if (strlen($datarow['enrolledIDs']) > 0) {
                    $attendees = explode(',',$datarow['enrolledIDs']);
                    $members = count($attendees);
                } else {
                    $members = 0;
                }
                $guests = $datarow['unenrolledCount'];
                $total = (int)$members + (int)$guests;
                
                $myRow = array( $datarow['date'],
                                $datarow['duration'],
                                $total,
                                $members,
                                $guests,
                                $datarow['volunteers'],
                                $staffName
                    );
                array_push($returnData,$myRow);
            } //end foreach
         }
         
         if ($format == 'table') {
             $jsonReturn['aoColumns'] = $columns;
             $jsonReturn['aaData'] = $returnData;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'excel') {
             $name = $groups->getName($groupID);
             $creator = $staff->getName($this->uid);
             $fileName = $this->_getExcelSheet($columns, $returnData, $name, $creator);
             
             $jsonReturn['file'] = $fileName;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format =='attend-graph') {
             $name = $groups->getName($groupID);
             $mtgDates = array();
             $memberNumbers = array();
             $guestNumbers = array();
             $volNumbers = array();
             foreach ($returnData as $recordRow) {
                 array_push($mtgDates, $recordRow[0]);
                 array_push($memberNumbers, (int)$recordRow[3]);
                 array_push($guestNumbers, (int)$recordRow[4]);
                 array_push($volNumbers, (int)$recordRow[5]);
             }
             $jsonReturn['title'] = $name;
             $jsonReturn['mtgDates'] = $mtgDates;
             $jsonReturn['memberNumbers'] = $memberNumbers;
             $jsonReturn['guestNumbers'] = $guestNumbers;
             $jsonReturn['volNumbers'] = $volNumbers;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'role-graph') {
             $ptcpMeetings = new Application_Model_DbTable_ParticipantMeetings;
             $mtgDates = array();
             $passive = array();
             $active = array();
             $leaders = array();
             foreach ($dataset as $meeting) {
                 $mid = $meeting['id'];
                 $date = $meeting['date'];
                 $numPassive = count($ptcpMeetings->fetchAll("meetingID = $mid and participationLevel = 'passive'")->toArray());
                 $numActive = count($ptcpMeetings->fetchAll("meetingID = $mid and participationLevel = 'contrib'")->toArray());
                 $numLeaders = count($ptcpMeetings->fetchAll("meetingID = $mid and participationLevel = 'leadrole'")->toArray());
                 
                 array_push($mtgDates, $date);
                 array_push($passive, $numPassive);
                 array_push($active, $numActive);
                 array_push($leaders, $numLeaders);
             }
             
             $jsonReturn['title'] = $groups->getName($groupID);
             $jsonReturn['mtgDates'] = $mtgDates;
             $jsonReturn['passive'] = $passive;
             $jsonReturn['active'] = $active;
             $jsonReturn['leaders'] = $leaders;
             
             $this->_helper->json($jsonReturn);
         }
    }
    
    public function progreportAction()
    {
        $jsonReturn = array();
        $id = $_POST['id'];
        $fromDate = $_POST['from'];
        $toDate = $_POST['to'];
        $format = $_POST['format'];
        
        $validFormats = array('excel','table','attend-graph', 'enroll-graph');
        
        if (!in_array($format, $validFormats)) { //make sure right format is being passed
            throw new exception ("Invalid format $format passed to report builder.");
        }
        
        //if no dates are passed, process the last 12 months
        if ((strlen($fromDate) == 0) && (strlen($toDate) == 0)) {
            $fromDate = time() - 365*24*60*60; //one year ago
            $toDate = time();
        } elseif (strlen($toDate == 0)) {
            $fromDate = strtotime($fromDate);
            $toDate = time();
        } else {
            $fromDate = strtotime($fromDate);
            $toDate = strtotime($toDate);
        }
        
        //figure out how many days in which month
        $thirtyOne = array("January", "March", "July", "May", "August", "October", "December");
        $thirty = array("April", "June", "September", "November");
        $feb = array("February");
        
        //initialize Column Headers
        $columns = array(
            array("sTitle" => "HiddenSort", array("aaSortingFixed" => true, "bVisible" => false)),
            array("sTitle" => "Group Name")
            );
        $returnData = array();
        
        //Get groups for this program
        $groupTable = new Application_Model_DbTable_Groups;
        $gMeetings = new Application_Model_DbTable_GroupMeetings;
        $groups = $groupTable->getProgramGroups($id);
        
        if (count($groups) == 0) {
            for ($i = $fromDate; $i < $toDate; $i += 31*24*60*60) {
                $dateArray = getDate($i);
                
                if (in_array($dateArray['month'], $thirtyOne)) $endDate = "31";
                if (in_array($dateArray['month'], $thirty)) $endDate = "30";
                if (in_array($dateArray['month'], $feb)) {
                    if ((int)$dateArray['year']%4 == 0) {
                        $endDate = "29";
                    } else {
                        $endDate = "28";
                    }
                }

                $monthBegin = $dateArray['month'] . " 1, " . $dateArray['year'];
                $monthEnd = $dateArray['month'] . " $endDate, " . $dateArray['year'];
                $searchStart = date('Y-m-d', strtotime($monthBegin));
                $searchEnd = date('Y-m-d', strtotime($monthEnd));
                
                $monthTitle = $dateArray['month'] . ' ' . $dateArray['year'];
                $myTitle = array("sTitle" => $monthTitle, "endDate" => $searchEnd);
                array_push($columns, $myTitle);
            }
        }
        
        //for each group, go through each month and get a total
        foreach ($groups as $index => $group) {
            $groupName = $group['name'];
            $myRow = array($index, $groupName);    
            for ($i = $fromDate; $i < $toDate; $i += 31*24*60*60) {
                $dateArray = getDate($i);

                if (in_array($dateArray['month'], $thirtyOne)) $endDate = "31";
                if (in_array($dateArray['month'], $thirty)) $endDate = "30";
                if (in_array($dateArray['month'], $feb)) {
                    if ((int)$dateArray['year']%4 == 0) {
                        $endDate = "29";
                    } else {
                        $endDate = "28";
                    }
                }

                $monthBegin = $dateArray['month'] . " 1, " . $dateArray['year'];
                $monthEnd = $dateArray['month'] . " $endDate, " . $dateArray['year'];
                $searchStart = date('Y-m-d', strtotime($monthBegin));
                $searchEnd = date('Y-m-d', strtotime($monthEnd));
                
                $total = $gMeetings->getTotalAttendance($group['id'], $searchStart, $searchEnd);
                array_push($myRow,$total);
                
                if($index == 0){ //only set column titles the first time around;                      
                    $monthTitle = $dateArray['month'] . ' ' . $dateArray['year'];
                    $myTitle = array("sTitle" => $monthTitle, "endDate" => $searchEnd);
                    array_push($columns, $myTitle);
                }
            }
            array_push($returnData,$myRow);
        }
        
        $totalRow=array('999', 'Program Total');
        //get number of columns,
        $nCols = count($columns);
        //for each row in returnData, sum row['column'], push it to totalRow
        for ($j=2;$j<$nCols;$j++) {//start at 2 to skip the name entry and hidden col
            $total = 0;
            foreach ($returnData as $row) {
                $total += $row[$j];
            }
            array_push($totalRow,$total);
        }
        
        array_push($returnData, $totalRow);
        
        if ($format == 'table') {
             $jsonReturn['aoColumns'] = $columns;
             $jsonReturn['aaData'] = $returnData;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'excel') {
             $programTable = new Application_Model_DbTable_Programs;
             $staff = new Application_Model_DbTable_Users;
             $prog = $programTable->getProg($id);
             $name = $prog['name'];
             $creator = $staff->getName($this->uid);
             $fileName = $this->_getExcelSheet($columns, $returnData, $name, $creator, 'removeOne');
             
             $jsonReturn['file'] = $fileName;
             $this->_helper->json($jsonReturn);
         }
         
         if (($format =='attend-graph') || ($format =='enroll-graph')) { //process for months, for both
             $programTable = new Application_Model_DbTable_Programs;
             $prog = $programTable->getProg($id);
             $name = $prog['name'];
             
             $months = array();
             
             $monthColumns = array_splice($columns, 2); // remove first two columns, the rest are months
             foreach ($monthColumns as $month) {
                 array_push($months, $month['sTitle']);
             }
         }
         
         if ($format == 'attend-graph') {
             $groups = array();
             array_pop($returnData); //remove the totals row
             
             foreach ($returnData as $recordRow) {
                 $groupName = $recordRow[1];
                 $dataRow = array_splice($recordRow, 2);
                 $thisRecord = array(
                   'name' => $groupName,
                   'data' => $dataRow
                 );
                 array_push($groups, $thisRecord);
             }
             $jsonReturn['title'] = $name;
             $jsonReturn['months'] = $months;
             $jsonReturn['groups'] = $groups;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'enroll-graph') {
             $concluded = array();         
             $leave     = array();         
             $waitlist  = array();         
             $active    = array();        
             
             foreach ($monthColumns as $month) {
                 
                 $statusArray=$this->_collectStatusTotals($id, $month['endDate']);
                 array_push($concluded,$statusArray['concluded']);
                 array_push($leave,$statusArray['leave']);
                 array_push($waitlist,$statusArray['waitlist']);
                 array_push($active,$statusArray['active']);
                 
             }
             
             $jsonReturn = array(
                 'title' => $name,
                 'months' => $months,
                 'concluded' => $concluded,
                 'leave' => $leave,
                 'waitlist' => $waitlist,
                 'active' => $active
             );
             
             $this->_helper->json($jsonReturn);
         }
    }
    
     public function staffreportAction()
    {
        $jsonReturn = array();
        $id = $_POST['id'];
        $fromDate = $_POST['from'];
        $toDate = $_POST['to'];
        $format = $_POST['format'];
        
        $validFormats = array('excel','table','caseload-graph','caseload-snap');
        
        if (!in_array($format, $validFormats)) { //make sure right format is being passed
            throw new exception ("Invalid format $format passed to report builder.");
        }
        
        $columns = array(
            array("sTitle" => "HiddenSort", array("aaSortingFixed" => true, "bVisible" => false)),
            array("sTitle" => "Status")
            );
        $returnData = array();
        
        //if no dates are passed, process the last 12 months
        if ((strlen($fromDate) == 0) && (strlen($toDate) == 0)) {
            $fromDate = time() - 365*24*60*60; //one year ago
            $toDate = time();
        } elseif (strlen($toDate == 0)) {
            $fromDate = strtotime($fromDate);
            $toDate = time();
        } else {
            $fromDate = strtotime($fromDate);
            $toDate = strtotime($toDate);
        }
        
        //figure out how many days in which month
        $thirtyOne = array("January", "March", "July", "May", "August", "October", "December");
        $thirty = array("April", "June", "September", "November");
        $feb = array("February");
        
        //get months in scope and create array with them as indeces
        for ($i = $fromDate; $i < $toDate; $i += 31*24*60*60) {
                $dateArray = getDate($i);

                if (in_array($dateArray['month'], $thirtyOne)) $endDate = "31";
                if (in_array($dateArray['month'], $thirty)) $endDate = "30";
                if (in_array($dateArray['month'], $feb)) {
                    if ((int)$dateArray['year']%4 == 0) {
                        $endDate = "29";
                    } else {
                        $endDate = "28";
                    }
                }

                $monthBegin = $dateArray['month'] . " 1, " . $dateArray['year'];
                $monthEnd = $dateArray['month'] . " $endDate, " . $dateArray['year'];
                $searchStart = date('Y-m-d', strtotime($monthBegin));
                $searchEnd = date('Y-m-d', strtotime($monthEnd));
                
                                    
                $monthTitle = $dateArray['month'] . ' ' . $dateArray['year'];
                $myTitle = array("sTitle" => $monthTitle, "startDate" => $searchStart, "endDate" => $searchEnd);
                array_push($columns, $myTitle);
                
            }
        
            /** WORK ON THIS
        if ($format == 'table') {
             $jsonReturn['aoColumns'] = $columns;
             $jsonReturn['aaData'] = $returnData;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'excel') {
             $programTable = new Application_Model_DbTable_Programs;
             $staff = new Application_Model_DbTable_Users;
             $prog = $programTable->getProg($id);
             $name = $prog['name'];
             $creator = $staff->getName($this->uid);
             $fileName = $this->_getExcelSheet($columns, $returnData, $name, $creator, 'removeOne');
             
             $jsonReturn['file'] = $fileName;
             $this->_helper->json($jsonReturn);
         }
         **/
         
        if (($format == 'caseload-snap')) {
            unset($columns);
            $columns = array(
                array("sTitle" => "HiddenSort", array("aaSortingFixed" => true, "bVisible" => false)),
                array('sTitle' => 'Name'),
                array('sTitle' => 'Program'),
                array('sTitle' => 'Status'),
                array('sTitle' => 'Since'),
                array('sTitle' => 'Status Duration')
            );
            $data = array();
            
            $clTable = new Application_Model_DbTable_ParticipantUsers;
            $ptcpTable = new Application_Model_DbTable_Participants;
            $progTable = new Application_Model_DbTable_Programs;
            $myCaseLoad = $clTable->getCaseLoad($id,date('Y-m-d',$toDate));
            
            
            foreach ($myCaseLoad as $clEntry) {
                $ptcpName = $ptcpTable->getName($clEntry['participantID']);
                $progName = $progTable->getName($clEntry['programID']);
                $status = $clEntry['status'];
                $statusDate = date("Y-m-d", strtotime($clEntry['statusDate']));
                $end = new DateTime(date("Y-m-d",$toDate));
                $start = new DateTime($statusDate);
                $interval = $end->diff($start);
                $statusLength = $interval->days . " days";

                $row = array(
                    'foo',
                    $ptcpName,
                    $progName,
                    $status,
                    $statusDate,
                    $statusLength
                );
                array_push($data,$row);
            }
            
            $jsonReturn['aoColumns'] = $columns;
            $jsonReturn['aaData'] = $data;
            $this->_helper->json($jsonReturn);
        }
            
         if (($format =='caseload-graph')) {
             $userTable = new Application_Model_DbTable_Users();
             $name = $userTable->getName($id);
             
             $months = array();
             
             $monthColumns = array_splice($columns, 2); // remove first two columns, the rest are months
             foreach ($monthColumns as $month) {
                 array_push($months, $month['sTitle']);
             }
         
             $concluded = array();         
             $leave     = array();         
             $waitlist  = array();         
             $active    = array();        
             
             foreach ($monthColumns as $month) {
                 $statusArray=$this->_collectStatusTotals($id, $month['endDate'], "user");
                 array_push($concluded,$statusArray['concluded']);
                 array_push($leave,$statusArray['leave']);
                 array_push($waitlist,$statusArray['waitlist']);
                 array_push($active,$statusArray['active']);
             }
             
             $jsonReturn = array(
                 'title' => $name,
                 'months' => $months,
                 'concluded' => $concluded,
                 'leave' => $leave,
                 'waitlist' => $waitlist,
                 'active' => $active
             );
             
             $this->_helper->json($jsonReturn);
         
        }
        
    }
    
    public function ptcpreportAction()
    {
        $jsonReturn = array();
        $pid = $_POST['id'];
        $fromDate = $_POST['from'];
        $toDate = $_POST['to'];
        $format = $_POST['format'];
        $type = $_POST['entity'];
        $eid = $_POST['eid'];
        $fields = $_POST['formFields'];
        
        if (strlen($fields) > 0) {
            parse_str($fields);
        }
        
        $ptcpTable = new Application_Model_DbTable_Participants;
        $name = $ptcpTable->getName($pid);
        
        $validFormats = array('excel','table','graph', 'formGraph');
        $validTypes = array('prog', 'form');
        
        if ((!in_array($format, $validFormats)) 
                || 
            (!in_array($type,$validTypes))) {
            throw new exception ("Invalid options $format/$type passed to report builder.");
        }
        
        //if no dates are passed, process the last 12 months
        if ((strlen($fromDate) == 0) && (strlen($toDate) == 0)) {
            $fromDate = time() - 365*24*60*60; //one year ago
            $toDate = time();
        } elseif (strlen($toDate == 0)) {
            $fromDate = strtotime($fromDate);
            $toDate = time();
        } else {
            $fromDate = strtotime($fromDate);
            $toDate = strtotime($toDate);
        }
        $startSearch = date('Y-m-d',$fromDate);
        $endSearch = date('Y-m-d',$toDate);
        
        switch ($type) {
            case 'prog': 
                $progID = $eid;
                $progTable = new Application_Model_DbTable_Programs;
                $p = $progTable->getRecord($progID);
                $pName = $p['name'];
                
                $returnData = array();
                $columns = array(
                        array('sTitle' => 'Date'),
                        array('sTitle' => 'Group Name'),
                        array('sTitle' => 'Engagement'),
                        array('sTitle' => 'Duration'),
                );
                $groupsTable = new Application_Model_DbTable_Groups;
                $staffGroups = $groupsTable->getStaffGroups($this->uid);
                //get a list of all ptcp meetings in date range
                $sqlText = 
                    "SELECT g.id, 
                            g.name, 
                            gm.date, 
                            gm.duration, 
                            pm.participationLevel 
                    FROM    participantMeetings AS pm, 
                            groupMeetings AS gm, 
                            groups AS g
                    WHERE   pm.meetingID = gm.id AND 
                            gm.groupID = g.id AND 
                            g.programID = $progID AND
                            pm.participantID = $pid AND 
                            gm.date > '$startSearch 00:00:01' AND
                            gm.date < '$endSearch 23:59:59'
                    ORDER BY date ASC";                
                
                $sql = $this->db->query($sqlText);
                $myRecords = $sql->fetchAll();
                $engage = array(
                    'contrib' => 'Active Participant',
                    'passive' => 'In Attendance',
                    'leadrole' => 'Leadership Role'
                );
                
                //check against staff groups and add data
                foreach ($myRecords as $meeting) {
                    if (in_array($meeting['id'],$staffGroups)) {
                        $thisRow = array(
                            $meeting['date'],
                            $meeting['name'],
                            $engage[$meeting['participationLevel']],
                            $meeting['duration']
                        );
                        array_push($returnData, $thisRow);
                    }
                }
                break;
            case 'form':
                //get form ID, table name
                $formID = $eid;
                $forms = new Application_Model_DbTable_Forms;
                $thisForm = $forms->getRecord($formID);
                $formName = $thisForm['name'];
                $tableName = $thisForm['tableName'];
                
                $fieldList = implode(',', $formFields);
                
                //select all entries from table for this ptcp
                $sqlText = "
                        SELECT responseDate,$fieldList
                        FROM $tableName
                        WHERE uid = $pid
                        AND responseDate >= '$startSearch'
                        AND responseDate <= '$endSearch'
                        ORDER BY responseDate DESC
                    ";
                $entries = $this->db->query($sqlText)->fetchAll();
                
                //for each element in list, get element name
                $columns = array(
                    array('sTitle' => 'Date')
                );
                $customFields = new Application_Model_DbTable_CustomFormElements;
                foreach ($formFields as $field) {
                    $e = $customFields->getElement($field, $formID);
                    $eName = $e['elementName'];
                    $thisColumn = array('sTitle' => $eName);
                    array_push($columns,$thisColumn);
                }
                
                //for each entry pulled earlier, create row array:
                //"Date", "value1", "value2", etc. ($returnData)
                $returnData = array();
                foreach ($entries as $entry) {
                    $formattedRow = array();
                    foreach ($entry as $column) {
                        array_push($formattedRow,$column);
                    }
                    array_push($returnData,$formattedRow);
                }
                
                break;
            
        }
        
        if ($format == 'table') {
             $jsonReturn['aoColumns'] = $columns;
             $jsonReturn['aaData'] = $returnData;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'excel') {
             $staff = new Application_Model_DbTable_Users;
             $creator = $staff->getName($this->uid);
             $fileName = $this->_getExcelSheet($columns, $returnData, $name, $creator);
             
             $jsonReturn['file'] = $fileName;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format =='graph') { //process for months, for both
             $dates = array();
             $levels = array(
                 'name' => $name,
                 'data' => array()
             );
             $numberLevels = array(
               'In Attendance' => 1,
               'Active Participant' => 2,
               'Leadership Role' => 3
             );
             foreach ($returnData as $recordRow) {
                 array_push($dates, $recordRow[0]);
                 array_push($levels['data'], $numberLevels[$recordRow[2]]);
             }
             $jsonReturn['title'] = $name . ' in ' . $pName;
             $jsonReturn['dates'] = $dates;
             $jsonReturn['levels'] = $levels;
             $this->_helper->json($jsonReturn);
         }
         
         if ($format == 'formGraph') {
             $dates = array();
             $chartsArray = array();
             
             //get Dates
             foreach ($returnData as $row) {
                 $date = $row[0];
                 array_push($dates, $date);
             }    
             
             //get chart data for each field ONLY IF RADIO OR NUM
             foreach ($formFields as $key => $field) {
                 $e = $customFields->getElement($field,$formID);
                 $type = $e['elType'];
                 switch ($type) {
                     case 'radio' : 
                         $thisChart = array();
                         $thisChart['name'] = $columns[$key+1]['sTitle']; //skip the 'Date' heading
                         $thisChart['values'] = array();
                         $optionsList = json_decode($e['options'], TRUE);
                         $thisChart['labels'] = $optionsList;
                         $numericValues = array_flip($optionsList); //switch keys and values;
                         $thisChart['max'] = max($numericValues);
                         foreach ($entries as $entry) {
                             $textValue = $entry[$field];
                             $numValue = $numericValues[$textValue];
                             array_push($thisChart['values'],$numValue);
                         }
                         $chartsArray[$key] = $thisChart;
                         break;
                     case 'num' :
                         $thisChart = array();
                         $thisChart['name'] = $columns[$key+1]['sTitle']; //without the 'Date' heading
                         $values = array();
                         foreach($entries as $entry) {
                             array_push($values,$entry[$field]);
                         }
                         $thisChart['max'] = max($values) * 1.2;
                         $thisChart['values'] = $values;
                         $chartsArray[$key] = $thisChart;
                         break;
                     default: continue;
                 }
             }
             
             $numCharts = count($chartsArray);
             
             $jsonReturn = array(
                 'pName'  => $name,
                 'fName'  => $formName,
                 'number' => $numCharts,
                 'dates'  => $dates,
                 'charts' => $chartsArray
             );
             
             $this->_helper->json($jsonReturn);
             
         }
    }      
    
    public function ptcpscopeAction()
    {
        $ptcpID = $_POST['id'];
        $type = $_POST['type'];
        $formsTable = new Application_Model_DbTable_Forms;
        $ptcpTable = new Application_Model_DbTable_Participants;
        $ptcpPrograms = new Application_Model_DbTable_ParticipantPrograms;
        $programs = new Application_Model_DbTable_Programs;
        $staffProgs = $programs->getStaffPrograms($this->uid);
        
        $dropdown = new Zend_Form_Element_Select('scopeDropdown');
        $setType = new Zend_Form_Element_Hidden('scopeType');
        $formatSelect = new Zend_Form_Element_Radio('formatSelectPtcp');
        
        switch ($type) {
            case 'form' :
                $setType->setValue('form');
                
                $dropdown->setLabel('Select Form');
                $myForms = $ptcpTable->getForms($ptcpID,'prepost');
                $permittedForms = $formsTable->getStaffForms();
                
                $formatSelect->addMultiOptions(array(
                    'table' => 'Table: View Survey Entries',
                    'excel' => 'Table: Download Spreadsheet',
                    'formGraph' => 'Graph: Outcomes over time'
                ))->setValue('table');
                
                foreach ($myForms as $fid => $fName) {
                    if (in_array($fid,$permittedForms)) {
                        $dropdown->addMultiOption($fid,$fName);
                    }
                }
                $content = $dropdown->render() . $setType->render();
                $rTypes  = $formatSelect->render();
                break;
            case 'att' : 
                $setType->setValue('prog');
                $dropdown->setLabel('Select Program');
                
                $formatSelect->addMultiOptions(array(
                    'table' => 'Table: Participation History',
                    'excel' => 'Table: Download Spreadsheet',
                    'graph' => 'Graph: Participation History' 
                ))->setValue('table');
                
                $myProgs = $ptcpPrograms->getList('progs',$ptcpID);
                foreach ($myProgs as $pid) {
                    if (in_array($pid, $staffProgs)) {
                        $pRec = $programs->getRecord($pid);
                        $pName = $pRec['name'];
                        $dropdown->addMultiOption($pid, $pName);
                    }
                }
                $content = $dropdown->render() . $setType->render();
                $rTypes = $formatSelect->render();
                break;
            default :
                $content = "Error: invalide type $type passed to report generator.";
                break;
        }
        
        if (strlen($ptcpID) < 1) {
            $content = "Error: no participant selected.";
        }
        
        $jsonReturn = array(
            'scopelist' => $content,
            'reportTypes' => $rTypes
        );
        
        $this->_helper->json($jsonReturn);
    }
    
    public function formfieldsAction()
    {
        $formID = $_POST['formID'];
        $elementsTable = new Application_Model_DbTable_CustomFormElements;
        $elements = $elementsTable->getElementNames($formID);
        
        $formFieldCheck = new Zend_Form_Element_MultiCheckbox('formFields');
        foreach ($elements as $element) {
            $displayName = $this->_neatTrim($element['name'],30,'');
            $formFieldCheck->addMultiOption($element['field'],$displayName);
        }
        
        $formFieldCheck->setLabel('Show data for which questions:');
        $content = $formFieldCheck->render();
        $this->_helper->json($content);
        
    }

   public function savestoredreportAction() {
        $includes = $_POST['includes'];
        $freq = $_POST['freq'];
        $name = $_POST['name'];
        
        //process recipients and add current user to list
        $recips = array();
        if (isset($_POST['recips'])) {
            $recips = $_POST['recips'];
        } 
        $self = array(
            'typeID' => $this->uid,
            'subtype' => 'single'
        );
        array_push($recips,$self);
        
        //make arrays db-compatible
        $recipsToStore = json_encode($recips);
        $includesToStore = json_encode($includes);
        
        //store it
        $storeTable = new Application_Model_DbTable_StoredReports;
        $ajaxReturn = $storeTable->storeReport($name,$freq,$recipsToStore,$includesToStore,$this->uid);
        $this->_helper->json($ajaxReturn);
        
    }
    
    public function srbuilderAction() {
        $id = $_POST['id'];
        $type = $_POST['type']; //recip or data
        $ajaxReturn = '';
        
        $ulID = $type . "FieldList";
        $ulClass = 'connected' . ucfirst($type);
        
        $top = "<ul class='$ulClass' id='$ulID'>";
        $meat = $this->_returnList($id);
        $bot = "</ul>";
        
        $ajaxReturn = $top . $meat . $bot;
        
        $this->_helper->json($ajaxReturn);
    }
 
    public function reportoptionsAction()
    {
        $id = $_POST['id'];
        $type = $_POST['type'];
        $step = $_POST['step']; //filter or data
        $ulID = $step . "FieldList";
        $ulClass = 'connected' . ucfirst($step);
        $return = "<ul class='$ulClass' id='$ulID'>";

        switch ($type) {
            case 'form' :
                $elementsTable = new Application_Model_DbTable_CustomFormElements;
                $formsTable = new Application_Model_DbTable_Forms;
                $elements = $elementsTable->fetchAll("formID = $id")->toArray();
                foreach ($elements as $element) {
                    //$displayName = $this->_neatTrim($element['elementName'],30,'');
                    $displayName = $element['elementName'];
                    $eType = $element['elType'];
                    $options = $element['options'];
                    $eID = $element['elementID'];
                    $formName = $formsTable->getName($id);
                    
                    $dataOptions = " data-options='$options'";
                    $dataType = " data-type='$eType'";
                    $dataFName = " data-formname='$formName'";
                    $dataFID = " data-formID='$id'";
                    $dataEID = " data-elementID='$eID'";
                    
                    $display = "<li class='draggable' 
                                    $dataOptions 
                                    $dataType
                                    $dataFName
                                    $dataFID
                                    $dataEID
                                >$displayName</li>";
                    $return .= $display;
                }
 
                break;
            case 'prog' :
                $programTable = new Application_Model_DbTable_Programs;
                $progRecord = $programTable->getProg($id);
                $pName = $progRecord['name'];
                
                $dataPName = " data-progname = '$pName'";
                $dataPID   = " data-progid = '$id'";
                $dataType  = " data-type = 'status'";
                
                $display = "<li class='draggable' $dataPName $dataPID $dataType>Status</li>";
                $return .= $display;
                
                break;
            
            case 'staff' :
                $staffTable = new Application_Model_DbTable_Users;
                $staffName = $staffTable->getName($id);
                $dataSName = " data-staffname = '$staffName'";
                $dataSID =   " data-staffid = '$id'";
                $dataType =  " data-type = 'status'";
                
                $display = "<li class='draggable' $dataSName $dataSID $dataType>Status<li>";
                $return .= $display;
                
                break;
            
            case 'group' :
                $groupTable = new Application_Model_DbTable_Groups;
                $groupName = $groupTable->getName($id);
                $options = array('attendance','active engagement','leadership role', 'volunteering');

                foreach ($options as $option) {
                   $dataGName = " data-groupname='$groupName'";
                   $dataGID = " data-groupID = '$id'";
                   $dataType = " data-type = '$option'";
                   
                   $display = "<li class='draggable' $dataGName $dataGID $dataType>$option</li>";
                   $return .= $display;
                }
                
                break;
            default: $return = "Wrong options passed to report generator.";
        }
        $return .= "</ul>";    
        $this->_helper->json($return);
        
    }
    
    
    public function dynamicreportAction()
    {
        $formsTable = new Application_Model_DbTable_Forms;
        $groupsTable = new Application_Model_DbTable_Groups;
        $progTable = new Application_Model_DbTable_Programs;
        $dataTable = new Application_Model_DbTable_DynamicForms;
        $pTable = new Application_Model_DbTable_Participants;
        $userTable = new Application_Model_DbTable_Users;
        $jsonReturn = array();
        
        
        //get variables
        $filterType = $_POST['fType']; //form, group, prog or staff
        $filterTarget = $_POST['fTarget']; //staff or participant
        $dataType = $_POST['dType']; //prepost or singleuse
        
        $filterFields = $_POST['fFields'];
        $dataFields = $_POST['dFields'];
                
        $from = $_POST['from'];
        $to = $_POST['to'];
        
        $reportType = $_POST['rType'];
        
        switch ($filterTarget) {
            case 'volunteer' :
                    $nameTable = $userTable;
                    $permissibleIDs = $userTable->getAllowedVolIDs();
                    //print_r($permissibleIDs);
                    break;
            case 'staff' : 
                    $nameTable = $userTable;
                    $permissibleIDs = $userTable->getStaffUsers();
                    break;
            case 'participant' : 
                    $nameTable = $pTable;
                    $permissibleIDs = $pTable->getStaffPtcps();
                break;
            default: break;
        }
        
        //get matching PIDs
        $pIDs = array();
        
        if ($filterType == 'form') {
            foreach ($filterFields as $count => $field) {
                $fid = $field['formID'];
                $elid= $field['elementID'];
                $search = $this->_getMatchPhrase($field['fCompare'],$field['match']);
                
                
                
                $myForm = $formsTable->getRecord($fid);
                $table = $myForm['tableName'];

                //for each table, only use the latest entries for each uID
                if ($filterTarget == 'participant') {
                    $qText = "SELECT f.uid, f.responseDate 
                        FROM $table as f
                        LEFT JOIN (
                                    SELECT f2.uID, max(f2.responseDate) AS MaxDate 
                                    FROM $table AS f2
                                    GROUP BY f2.uID
                                    ) AS SubQuery 
                                    ON f.uID = SubQuery.uID 
                                    AND f.responseDate = SubQuery.MaxDate
                        WHERE MaxDate IS NOT NULL
                        AND $elid $search
                        AND f.responseDate >= '$from' AND f.responseDate <= '$to'
                        AND doNotDisplay = 0 
                        "; //doNotDisplay field, if TRUE, contains edited duplicates.
                
                //unless it's for staff forms
                } else {
                    $qText = "SELECT DISTINCT uid from $table "
                            . "WHERE $elid $search "
                            . "AND responseDate >= '$from' "
                            . "AND responseDate <= '$to'";
                    
                    $fullQueryText = "SELECT * FROM $table "
                            . "WHERE $elid $search "
                            . "AND responseDate >= '$from' "
                            . "AND responseDate <= '$to' "
                            . "AND doNotDisplay = 0";
                }
            
                //troubleshoot Query Text
                //print $qText . "\n\n\n";
            
                $ids = $this->db->query($qText)->fetchAll();
                
                
                //on first filter field, put all matching PIDs into success array
                if ($count == 0) { 
                    foreach ($ids as $pid) {
                        if($pid['uid'] != 0) {
                            array_push($pIDs, $pid['uid']);
                        }
                    }
                //on later filters, filter existing success PIDs against current matching PIDs                
                } else {
                    $idArray = array();
                    foreach ($ids as $id) {
                        array_push($idArray,$id['uid']);
                    }
                    foreach ($pIDs as $index => $pid) {
                        if (!in_array($pid,$idArray)) {
                            unset($pIDs[$index]);
                        }
                    }
                }
            } //end element loop
        } //end 'form' 
        //print_r($pIDs);
        
        if ($filterType == 'group') {
            $terms = array(
                'attendance' => 'passive',
                'active engagement' => 'contrib',
                'leadership role' => 'leadrole',
                'volunteering' => 'volunteer'
            );
            
            $matchTerms = array( //translate human to MySQL
                'at least'      => '>=',
                'no more than'  => '<=',
                'exactly'       => '='
            );
            
            //for each element, get group ID
            foreach ($filterFields as $key => $groupField) {
                $longName  = $groupField['elementName'];
                $matchBy   = $matchTerms[$groupField['fCompare']];
                $matchNum  = $groupField['match'];
                $useful    = explode(" for ", $longName);
                $entName = $useful[1];
                $column    = $terms[$useful[0]];
                $groupRec  = $groupsTable->fetchRow("name = '$entName'")->toArray();
                $gID       = $groupRec['id'];
            
            //parse filter requirements
                switch ($column) {
                    case 'passive' : 
                        $filterSQL = ""; //get all attendance records
                        break;
                    case 'contrib' : 
                        $filterSQL = "AND (participationLevel = 'contrib' OR participationLevel = 'leadrole')";
                        break;
                    case 'leadrole' : 
                        $filterSQL = "AND participationLevel = 'leadrole'";
                        break;
                    case 'volunteer' : 
                        $filterSQL = "AND volunteer = 1";
                        break;
                }
            
            //build SQL query
                $qText = "  SELECT pm.participantID
                            FROM participantMeetings as pm, groupMeetings as gm
                            WHERE pm.meetingID = gm.id 
                            AND gm.groupID = $gID
                            AND gm.date >= '$from' AND gm.date <= '$to'
                            $filterSQL
                            GROUP BY participantID
                            HAVING COUNT(*) $matchBy $matchNum
                        ";
                $ids = $this->db->query($qText)->fetchAll();
            
            //on first filter field, put all matching PIDs into success array
                if ($key == 0) { 
                    foreach ($ids as $pid) {
                        array_push($pIDs, $pid['participantID']);
                    }
                //on later filters, filter existing success PIDs against current matching PIDs                
                } else {
                    $idArray = array();
                    foreach ($ids as $id) {
                        array_push($idArray,$id['participantID']);
                    }
                    foreach ($pIDs as $index => $pid) {
                        if (!in_array($pid,$idArray)) {
                            unset($pIDs[$index]);
                        }
                    }
                }
            }//end foreach loop
        }//end of group
        
        if ($filterType == 'prog') {
            foreach ($filterFields as $key => $progField) {
                $ids       = array();
                $longName  = $progField['elementName'];
                $style     = $progField['fCompare'];
                $status    = $progField['match'];
                $useful    = explode(" for ", $longName);
                $entName  = substr($useful[1],0,-2);
                $progRec   = $progTable->fetchRow("name = '$entName'")->toArray();
                $progID    = $progRec['id'];
                
                //waitlist bug - hacky fix
                if ($status == 'waitlisted') $status='waitlist';
                
                //get ptcp IDs who became active during our timeframe
                $tables = array('ptcpProgramArchive','participantPrograms');
                
                foreach ($tables as $table) {
                    $qText = "SELECT DISTINCT participantID
                            FROM $table
                            WHERE programID = $progID
                            AND status = '$status'
                            AND (prevStatus != '$status' OR prevStatus IS NULL)" //eliminate note updates that aren't status changes
                            . "
                            AND statusDate BETWEEN '$from 00:00:00' AND '$to 23:59:59'
                        ";
                    
                    $results = $this->db->query($qText)->fetchAll();
                                        
                    foreach ($results as $pid) {
                        if (!in_array($pid['participantID'],$ids)) {
                                array_push($ids,$pid['participantID']);
                            }
                    }
                }
                
                //if needed, get ptcp IDs who were already active
                if ($style == 'was') {
                    foreach ($tables as $table) {
                        $qText = "
                            SELECT p.participantID, p.status,p.statusDate
                            FROM $table AS p 
                            INNER JOIN ( 
                                    SELECT participantID, max(statusDate) AS status_date 
                                    FROM $table 
                                    WHERE programID=$progID
                                    AND statusDate < '$from 23:59:59'
                                    GROUP BY participantID ) pmax 
                            ON pmax.participantID = p.participantID and pmax.status_date = p.statusDate 
                            ORDER BY p.participantID ASC
                            ";
                        $results = $this->db->query($qText)->fetchAll();

                        foreach ($results as $pid) {
                            if ((!in_array($pid['participantID'],$ids)) && ($pid['status'] == $status)) {
                                array_push($ids,$pid['participantID']);
                            }
                        }
                    } //end tables loop
                } //end if style = was
             
             //on first filter field, put all matching PIDs into success array
                if ($key == 0) { 
                    foreach ($ids as $pid) {
                        array_push($pIDs, $pid);
                    }
                //on later filters, filter existing success PIDs against current matching PIDs                
                } else {
                    $idArray = array();
                    foreach ($ids as $id) {
                        array_push($idArray,$id);
                    }
                    foreach ($pIDs as $index => $pid) {
                        if (!in_array($pid,$idArray)) {
                            unset($pIDs[$index]);
                        }
                    }
                }   
                
            }//end foreach loop
        }//end of prog
          
        if ($filterType == 'staff') {
            foreach ($filterFields as $key => $staffField) {
                $ids       = array();
                $longName  = $staffField['elementName'];
                $style     = $staffField['fCompare'];
                $status    = $staffField['match'];
                $staffID    = $staffField['filterID'];
                
                //waitlist bug - hacky fix
                if ($status == 'waitlisted') $status='waitlist';
                
                //get ptcp IDs who became active during our timeframe
                $tables = array('ptcpUserArchive','participantUsers');
                
                foreach ($tables as $table) {
                    $qText = "SELECT DISTINCT participantID
                            FROM $table
                            WHERE userID = $staffID
                            AND status = '$status'
                            AND (prevStatus != '$status' OR prevStatus IS NULL)" //eliminate note updates that aren't status changes
                            . "
                            AND statusDate BETWEEN '$from 00:00:00' AND '$to 23:59:59'
                        ";
                    
                    $results = $this->db->query($qText)->fetchAll();
                                        
                    foreach ($results as $pid) {
                        if (!in_array($pid['participantID'],$ids)) {
                                array_push($ids,$pid['participantID']);
                            }
                    }
                }
                
                //if needed, get ptcp IDs who were already active
                if ($style == 'was') {
                    foreach ($tables as $table) {
                        $qText = "
                            SELECT p.participantID, p.status,p.statusDate
                            FROM $table AS p 
                            INNER JOIN ( 
                                    SELECT participantID, max(statusDate) AS status_date 
                                    FROM $table 
                                    WHERE userID=$staffID
                                    AND statusDate < '$from 23:59:59'
                                    GROUP BY participantID ) pmax 
                            ON pmax.participantID = p.participantID and pmax.status_date = p.statusDate 
                            ORDER BY p.participantID ASC
                            ";
                        $results = $this->db->query($qText)->fetchAll();

                        foreach ($results as $pid) {
                            if ((!in_array($pid['participantID'],$ids)) && ($pid['status'] == $status)) {
                                array_push($ids,$pid['participantID']);
                            }
                        }
                    } //end tables loop
                } //end if style = was
             
             //on first filter field, put all matching PIDs into success array
                if ($key == 0) { 
                    foreach ($ids as $pid) {
                        array_push($pIDs, $pid);
                    }
                //on later filters, filter existing success PIDs against current matching PIDs                
                } else {
                    $idArray = array();
                    foreach ($ids as $id) {
                        array_push($idArray,$id);
                    }
                    foreach ($pIDs as $index => $pid) {
                        if (!in_array($pid,$idArray)) {
                            unset($pIDs[$index]);
                        }
                    }
                }   
                
            }//end foreach loop
        }//end of staff

        
        //check PID permissions, remove duplicates
        $goodIDs = array_unique(array_intersect($pIDs, $permissibleIDs));
        
        //get needed fields
        
        $columnTitles = array(array('sTitle' => ucfirst($filterTarget)));
        $rowValues = array();
        $prepostValues = array();
        
        if ($filterTarget == 'participant') {
            $dobctitle = array('sTitle' => 'Date of Birth');
            array_push($columnTitles,$dobctitle);
        }
        
        foreach ($goodIDs as $pid) {
            if ($this->evaluator) {
                $pName = $pid; //anonymize for evaluators
            } elseif ($reportType == 'table') {
                $pName = "<a href='/participants/profile/id/$pid/' target='_blank'>" . $nameTable->getName($pid) . "</a>";
            } else {
                $pName = $nameTable->getName($pid);
            }
            
            $rowValues[$pid] = array($pName);
            if ($filterTarget == 'participant') {
                    $ptcp = $nameTable->getRecord($pid);
                    $dob = $ptcp['dateOfBirth'];                    
                    array_push($rowValues[$pid],$dob);
            }
        }
        

        foreach ($dataFields as $field) {
            $eName = $field['elementName'];
            $eID = $field['elementID'];
            
            switch ($dataType) {
                case 'singleuse': 
                    $nameArray = array('sTitle' => $eName);
                    array_push($columnTitles,$nameArray);
                    break;
                case 'prepost':
                    $col1Name = array('sTitle' => $eName . ' - Pre');
                    $col2Name = array('sTitle' => $eName . ' - Post');
                    array_push($columnTitles, $col1Name);
                    array_push($columnTitles, $col2Name);
                    break;
                default: break;
            }
            
            $formID = $field['formID'];
            $fRecord = $formsTable->getRecord($formID);
            $tableName = $fRecord['tableName'];
            
            //tested $goodIDs, July 2014 - filters correctly
            
            foreach ($goodIDs as $pid) {
                
                if ($filterTarget == 'staff') {
                    $pRecords = $this->db->query($fullQueryText)->fetchAll();
                } else {
                    $pRecords = $dataTable->getRecords($pid,$tableName);                    
                    //$pRecords = $dataTable->getRecords($pid,$tableName,$from,$to);                    
                }
                
                $numRecords = count($pRecords);
                
                $pRecords = array_merge($pRecords); //this resets the array keys
                                                    //so that first record is always [0]
                
                //print_r($pRecords);
                
                
                //figure out element type
                $elTable = new Application_Model_DbTable_CustomFormElements();
                $elType = $elTable->getElementType($eID,$formID);
                
                if ($filterTarget == 'participant' || $filterTarget == 'volunteer') {
                    
                    if ($numRecords > 1) {    
                        $latestRecord = $pRecords[0];
                        $earliestRecord = end($pRecords);
                        reset($pRecords);
                    } elseif ($numRecords == 1) {
                        $earliestRecord = array($eID => 'No data');
                        $latestRecord = $pRecords[0];
                    } else {
                        $latestRecord = array($eID => 'No data');
                        $earliestRecord = array($eID => 'No data');
                    }
                    
                    switch ($dataType) {
                    case 'singleuse':
                        //if element is a checkbox and we're adding things up, flag for later processing
                        if ($elType == 'checkbox' && $reportType == 'graph') {
                            $columnVal = "CKBX , " . $latestRecord[$eID];
                        } else {
                            $columnVal = $latestRecord[$eID];
                        }
                        array_push($rowValues[$pid],$columnVal);
                        break;
                    case 'prepost':
                        $col1Val = $earliestRecord[$eID];
                        $col2Val = $latestRecord[$eID];
                        if (strlen($col1Val) < 1) {
                            $col1Val = 'No data';
                        }
                        if (strlen($col2Val) < 1) {
                            $col2Val = 'No data';
                        }
                        
                        //fix weirdness around doing both pre/post and static
                        if (($col1Val == 'No data') && ($col2Val != 'No data')) {
                            $col1Val = $col2Val;
                            $col2Val = 'No data';
                        }
                        
                        array_push($rowValues[$pid],$col1Val);
                        array_push($rowValues[$pid],$col2Val);
                        
                        break;
                    default: break;
                }
                
                } else { 

                //for staff, only do statics, and do all records, not just latest
                // also, for staff, check final results against filter again.
                    $name = $rowValues[$pid][0];
                    reset($rowValues);
                    
                    foreach ($pRecords as $record) {
                        $recID = $record['id'];
                        //$rowValues[$recID] = array();
                        $rowValues[$recID][0] = $name;

                        if ($elType == 'checkbox' && $reportType == 'graph') {
                            $columnVal = "CKBX , " . $record[$eID];
                        } else {
                            $columnVal = $record[$eID];
                        }
                        array_push($rowValues[$recID],$columnVal);
                    }
                }                
            }
            
        }

        //in staff forms, array is set by row records, not userid records, 
        //so unset the empty userid record
        
        if ($filterTarget == 'staff') {
            unset ($rowValues[$pid]);
        }
        
//        print_r($rowValues);
        
        if ($reportType == 'table') {
             $tableValues = array();
             foreach ($rowValues as $v) {
                 array_push($tableValues,$v);
             }
             $jsonReturn['aoColumns'] = $columnTitles;
             $jsonReturn['aaData'] = $tableValues;
             $this->_helper->json($jsonReturn);
         }
         
         if ($reportType == 'excel') {
             $staff = new Application_Model_DbTable_Users;
             $creator = $staff->getName($this->uid);
             $fileName = $this->_getExcelSheet($columnTitles, $rowValues, '', $creator);
             
             $jsonReturn['file'] = $fileName;
             $this->_helper->json($jsonReturn);    
         }
         
         if (($reportType == 'graph') && ($dataType == 'prepost')) { //NEEDS WORK!!!!
             $jsonReturn['charts'] = array();
             //for each ODD column (all 'pre' columns)
             foreach ($columnTitles as $cKey => $columnTitle) {
                 if ((int)$cKey % 2 == 0) continue; //only want odd columns
                 $chartName = str_replace(" - Pre","",$columnTitle['sTitle']);
                 
                 $thisChart = array('name' => $chartName, 'values' => array());
                 
                 //get all possible values from existing data
                 $possibleValues = array();
                 foreach ($rowValues as $rowKey => $row) {
                     $val = $row[$cKey];    //pull all values from 'pre'
                     $val2 = $row[$cKey+1]; //and 'post'
                     
                     //don't graph participants without at least two entries
                     if (($val == 'No data') || ($val2 == 'No data')) {
                         unset($rowValues[$rowKey]);
                         continue;
                     }
                     
                     if (!in_array($val,$possibleValues)) {
                         array_push($possibleValues,$val);
                     }
                     if (!in_array($val2,$possibleValues)) {
                         array_push($possibleValues,$val2);
                     }
                     
                 }
                 
                 //going through existing data, count valid ones
                 foreach ($possibleValues as $k => $v) {
                     $numPreValues = 0;
                     $numPostValues = 0;
                     foreach ($rowValues as $row) {
                         if ($row[$cKey] == $v) $numPreValues++;
                         if ($row[$cKey+1] == $v) $numPostValues++;
                     }
                      
                     //create subArray for each value
                     $thisData = array(
                         'name' => $v,
                         'data' => array($numPreValues,$numPostValues)
                     );
                     array_push($thisChart['values'],$thisData);
                 }
                array_push($jsonReturn['charts'],$thisChart);    
             }
             //print_r($jsonReturn);
             $this->_helper->json($jsonReturn);
         }
         
         if (($reportType == 'graph') && ($dataType == 'singleuse')) {
             $jsonReturn['charts'] = array();
             
             
             foreach ($columnTitles as $key => $title) {
                 if ($key == 0) continue; //skip first column, it has names only
                 $colName = $title['sTitle'];
                 
                 //if there is a longname, change title
                 if (isset($entName)) {
                     $colNameForPrint = $colName . " for $entName";
                 } else {
                     $colNameForPrint = $colName;
                 }
                 unset($colValues);
                 $colValues = array();
                 
                 foreach ($rowValues as $k => $row) {
                     $thisColValue = $row[$key];    
                     if ($thisColValue == 'No data available') continue;
                     
                     //use checkbox values if flag is set
                     if (substr($thisColValue,0,4)=='CKBX') {
                         $colValues = $this->_processCheckBoxVals($colValues,$thisColValue);
                         
                         continue;
                     }
                     
                     if ($thisColValue == '') {
                         $thisColValue = 'N/A';
                     }
                     
                     $thisColValue = trim($thisColValue);
                     
                     if (!array_key_exists($thisColValue,$colValues)) {
                         $colValues[$thisColValue] = 1;
                     } else {
                         $colValues[$thisColValue] ++;
                     }
                 }
                 
                 
                 
                 //Count up all the responses, omit 'No data'
		if(array_key_exists("No data",$colValues)) {
                 $noData = $colValues['No data'];
                 unset($colValues['No data']);
		} else {
		 $noData = 0;
		}
                 
                 $validResponses = 0;
                 foreach ($colValues as $title => $count) {
                     $validResponses += (int)$count;
                 }
                 
                 $subtitle = "<i>n</i> = $validResponses ($noData empty results omitted)";
                 
                 $dataArray = array(
                     'name' => $colNameForPrint,
                     'subtitle' => $subtitle,
                     'values'=>$colValues
                 );
                 
                 array_push($jsonReturn['charts'],$dataArray);
             }
             $this->_helper->json($jsonReturn);
         }
    }
}
 
