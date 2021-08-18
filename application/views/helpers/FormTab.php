<?php

class Zend_View_Helper_FormTab extends Zend_View_Helper_Abstract {
    
    protected function _dataTR($record=array(),$formID,$type,$source) {
        $id = $record['id']; //entryID
        $trTop = "<tr class='hidden' id='data-$id'>";
        $tdTop = "<td colspan=3 style='text-align: left'>";
        $tdBottom = "</td>";
        $trBottom = "</tr>";
        
        $uid = $record['enteredBy'];
        $users = new Application_Model_DbTable_Users;
        $enteredName = $users->getName($uid);
        
        $forms = new Application_Model_DbTable_Forms;
        $formInfo = $forms->getRecord($formID);
        $fcssID = $formInfo['fcssID'];
        
        $myData = $record; //only using the latest

        //get "edit" data for into html5 form for jQuery to set cookies; set identifier type
        $targetID = $myData['uID'];
        switch ($formInfo['target']) {
            case 'participant' :    $ptcpTable = new Application_Model_DbTable_Participants;
                                    $targetName = $ptcpTable->getName($targetID);
                                    $displayName = $targetName;
                                    break;
            case 'staff' :          $staffTable = new Application_Model_DbTable_Users;
                                    $targetName = $staffTable->getName($targetID);
                                    $displayName = $targetName;
                                    
                                    //WILL FIX LATER WHEN ENTITIES ARE ADDED
                                    if ($formID == 13) {
                                        $displayName = $myData['field_1'];
                                    }
                                    
                                    
                                    break;
            case 'group' :          $groupTable = new Application_Model_DbTable_Groups;
                                    $targetName = $groupTable->getName($targetID);
                                    break;
            default: throw new exception("Invalid form Target type " . $formInfo['target'] . " passed to editor.");

        }                    

        //set identifier type
        if ($source == 'ptcp') {
            $identifier = $record['responseDate'];
        } elseif ($source == 'forms') {
            $identifier = $displayName;
        }
        
        
        
        //build row
        $divMeat = "<span class='date'><a href='#' class='toggleRecord'>" . $identifier . "</a></span>";
        

            //Process them into html
            $nonDisplayFields = array('id', 'uID', 'enteredOn', 'enteredBy', 'responseDate', 'groupID', 'deptID', 'doNotDisplay');
            $elementNameTable = new Application_Model_DbTable_CustomFormElements;

        $divMeat .= "<div id='detail-$id' class='hidden content-data'>";
            $divMeat .= "<span class='date tiny'>Entered by $enteredName</span>";
            
            if (($type == 'singleuse') && ($fcssID == NULL)) {
                    $divMeat .= "<button class='editLatest' data-formid='$formID' data-entryid='$id' data-userid='$targetID' data-username='$targetName'>Edit Data</button>";
                }
              
                
            foreach ($myData as $k => $v) {
            
                if (!in_array($k, $nonDisplayFields)) {
                    $element = $elementNameTable->getElement($k,$formID);
                    $printName = $element['elementName'];
                } else {
                    $printName = $k;
                }
                
                if (strlen($v) == 0) continue;
                
                $contentLineTop = "<div class='form-display-item";
                    if (in_array($k, $nonDisplayFields)) {
                        $contentLineTop .= " hidden";
                    }
                $contentLineTop .= "'>\n";
                
                //preserve line breaks
                $v = nl2br($v);
                
                //fix encoding wackiness
                $v = ABCD_Encoding::filterTextOther($v);
                
                $contentLineTitle = "<span class='title'>$printName</span>\n";
                $contentLineValue = "<span class='value' id='$k'>$v</span>\n";
                $contentLineBottom = "</div><!-- .form-display-item -->\n";
                
                $contentLine = $contentLineTop . $contentLineTitle . $contentLineValue . $contentLineBottom;
                
                $divMeat .= $contentLine;
            }
            
        $divMeat .= "</div><!-- .content-data -->"; 
                
        $tr = $trTop . $tdTop . $divMeat . $tdBottom . $trBottom;
        return $tr;
    }
    
    protected function _doHTMLEntities ($string)
    {
        $trans_tbl = get_html_translation_table (HTML_ENTITIES);
       
        // MS Word strangeness..
        // smart single/ double quotes:
        $trans_tbl[chr(145)] = '\'';
        $trans_tbl[chr(146)] = '\'';
        $trans_tbl[chr(147)] = '&quot;';
        $trans_tbl[chr(148)] = '&quot;';

                // Acute 'e'
        $trans_tbl[chr(142)] = '&eacute;';
       
        return strtr ($string, $trans_tbl);
    } 
    
    protected function _processEditedForms($dataArray) {
        $existingDates = array();
        $displayEntries = array();
        foreach ($dataArray as $id => $values) {
                if ($values['doNotDisplay'] == 0) {
                    $displayEntries[$id] = $values;
                }
        }
        return $displayEntries;
    }
    
    public function formTab($rows = array(),$source='ptcp') {
    $formTable = new Application_Model_DbTable_Forms;
    
    $trArray = NULL;
    
    if ($source == 'ptcp') {
        $tableHeader = 'Form Name';
        $buttonClass = 'addRecord';
    } else {
        $tableHeader = 'Department';
        $buttonClass = 'addRecordForm';
    }
    
    $tableTopWrapper    = "<table class='formsTable' id='forms'>";
    $tableBottomWrapper = '</table>';
    
    $table = $tableTopWrapper;
    $table .= "<tr>
                <th>$tableHeader</th>
                <th>History</th>
                <th>Actions</th>
               </tr>";
    
    foreach ($rows as $rowData) {
        $id = $rowData['id'];
        $name = $rowData['name'];
        $freq = $rowData['frequency'];
        $type = $formTable->getType($id);
        
        if (array_key_exists('data', $rowData)) {
            $validEntries = $this->_processEditedForms($rowData['data']);
            $numEntries = count($validEntries);
        } else {
            $numEntries = 0;
        }
        $addEntry = "<button data-path='/forms/dataentry/id/$id' class='$buttonClass'>Add Entry</button>";

        if ($numEntries > 0) {
                $mostRecentRow = reset($rowData['data']);
                $mostRecent = "Last Entry: " . date('M j, Y', strtotime($mostRecentRow['responseDate']));
                $showEntries = "<button class='showRecords'>Show Entries</button>";
                $trArray = array();
                foreach ($validEntries as $record) {
                    $tr2 = $this->_dataTR($record,$id,$type,$source);
                    array_push($trArray, $tr2);
                }
        } else {
            $mostRecent = '';
            $showEntries='';
        }

//        if ($req) {
//            $descText = ucfirst(trim("$freq form required by $by"));
//        } else {
//            $descText = ucfirst(trim("$freq form, associated with $by")); 
//        }

        $nameCell =     "<td class='nameTD'>
                            <span class='name'>$name</span><br>

                        </td>";

        $historyCell = "<td class='nameTD'>
                            <span class='name'>$numEntries entries</span><br>
                            $mostRecent
                        </td>";

        $actionCell = "<td>
                        $showEntries
                        $addEntry
                    </td>";

        $tr = "<tr class='descriptor' id=$id> $nameCell $historyCell $actionCell </tr>";

        $table .= $tr;
        if (is_array($trArray)) {
            foreach ($trArray as $subTr) { $table .= $subTr; }
        }
    }
    
    $table .= $tableBottomWrapper;
    
    return $table;
    }
   
}
