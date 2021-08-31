<?php

class Zend_View_Helper_ParticipantProfile extends Zend_View_Helper_Abstract {
    
    private $content = '';
    
    public function participantProfile($did, $pid, $pName) {
        /*   $did = department id
         *   $pid = participant id
         *   $pName = participantName
         */
 
    //Get default form for department
    $deptFormsTable = new Application_Model_DbTable_DeptForms;
    $formsTable = new Application_Model_DbTable_Forms;
    $userTable = new Application_Model_DbTable_Users;
    
    $defaultForm = $deptFormsTable->getSpecial('defaultForm', $did);
    if (count($defaultForm) != 1) {
        $this->content = "<h2>There is no default form for this department.</h2>";
    } else {
        $formRecord = $formsTable->getRecord($defaultForm[0]);

        $table = $formRecord['tableName'];
        $fid = $formRecord['id'];
        $formName = $formRecord['name'];
        
        $this->content = "<h2>Profile form: <i>$formName</i></h2>";
        

        //Get latest values --> if multiple versions not 'hidden', use latest 
        $select = $this->view->db->query("SELECT * 
                                    FROM $table
                                    WHERE uID = $pid AND doNotDisplay = 0
                                    ORDER BY responseDate DESC,enteredOn DESC 
                                  ");

        $rowset = $select->fetchAll(); //OK to get multiple records
        if (count($rowset) == 0) {
            $this->content .= "<p>This participant has not filled out default form $formName.<br>
                               <div class='bottom-links'>
                                    <a class='add-link' href='/forms/dataentry/id/$fid'>
                                        Click here to fill it out now.
                                    </a>
                               </div>
                               </p>";
        } else {
            $staffID = $rowset[0]['enteredBy'];
	    $staffName = $userTable->getName($staffID);

            $myData = $rowset[0]; //only using the latest
            $recID = $myData['id'];
            
            $this->content .= "<button id='$fid' data-entryid='$recID' data-formid='$fid' data-userid='$pid' data-username='$pName' class='editProfile top-right'>Edit Data</button>";
            $this->content .= "<p class='asof'>Data last updated on " . $rowset[0]['responseDate'] . " by " . $staffName . "</p>";
            

            //Process them into html
            $nonDisplayFields = array('id', 'uID', 'enteredOn', 'enteredBy', 'responseDate', 'groupID', 'deptID', 'doNotDisplay');
            $elementNameTable = new Application_Model_DbTable_CustomFormElements;

            $this->content .= "<div id='$table' class=content-data>";
            
            foreach ($myData as $k => $v) {
                if (!in_array($k, $nonDisplayFields)) {
                    $element = $elementNameTable->getElement($k,$fid);
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
                
                $contentLineTitle = "<span class='title'>$printName</span>\n";
                $contentLineValue = "<span class='value' id='$k'>$v</span>\n";
                $contentLineBottom = "</div><!-- .form-display-item -->\n";
                
                $contentLine = $contentLineTop . $contentLineTitle . $contentLineValue . $contentLineBottom;
                
                $this->content .= $contentLine;
            }
            $this->content .= "</div><!-- .content-data -->"; 
        }
    }    
    
    return $this->content;
    }
   
}
