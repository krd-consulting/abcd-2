<?php

class Zend_View_Helper_StaffReport extends Zend_View_Helper_Abstract
{
  public function StaffReport()
  {
      $wrapperDiv = "<div id='options-div-staff'>";
      $divEnd = "</div>";

        //1: multiselect of all available users
        $firstDiv = "<div id='userSelect' class='reportBuilder block'>";
        
        $firstDiv .= "<div class='block-header'>
                        1. Choose a person
                      </div>";
        
        $userSelect = new Zend_Form_Element_Select('userList');
        
        
        $optionsArray = array();
        $userTable = new Application_Model_DbTable_Users;
        $allUserIDs = $userTable->getAllowedStaffIDs();
        
        foreach ($allUserIDs as $userID) {
            $user = $userTable->getRecord($userID);
            $name = $user['firstName'] . " " . $user['lastName'];
            $id   = $user['id'];
            $optionsArray[$id] = $name;
        }
        
        $size = 10; //show 6 lines;
        
        $userSelect->setAttrib('size', $size)
                    ->setMultiOptions($optionsArray);
        
        $firstDiv .= $userSelect->render();
        
        $firstDiv .= "</div>";
        
        //2: Date Range form
        $secondDiv = "<div id='dateOptions-staff' class='hidden reportBuilder block'>";
        
        $secondDiv .= "<div class='block-header'>
                        2. Choose Date Options
                      </div>";
        
        $dateSelect = new Zend_Form_Element_Radio('dateSelectStaff');
        $dateSelect ->setMultiOptions(array('all' => 'Last 12 months', 'filter' => 'Other date range'));
        
        $secondDiv .= $dateSelect->render();
        
        $fromDate = new Zend_Form_Element_Text('fromDateStaff');
        $fromDate->setLabel('From')
                 ->setAttrib('class','dynamicdatepicker');
                
        $toDate = new Zend_Form_Element_Text('toDateStaff');
        $toDate->setLabel('To')
                 ->setAttrib('class','dynamicdatepicker');
        
        $dateBoxes = "<div id='dateBoxes-staff' class='hidden'>" . $fromDate->render() . $toDate->render() . "</div>";
        
        $secondDiv .= $dateBoxes;
        
        $secondDiv .= "</div>";
        
        //3: Format selection form + go button
        $thirdDiv = "<div id='formatOptions-staff' class='hidden reportBuilder block'>";
        
        $thirdDiv .= "<div class='block-header'>
                        3. Choose a report
                      </div>";
        
        $formatSelect = new Zend_Form_Element_Radio('formatSelectStaff');
        $formatSelect->addMultiOptions(array(
            'caseload-graph' => 'Caseload by status (view graph)',
            'caseload-snap' => 'Caseload snapshot (as of latest date)'
            //'table' => 'Caseload statistics (view table)',
            //'excel' => 'Caseload statistics (download)'
            ))
                    //->setValue('table')
                    ;
        $thirdDiv .= $formatSelect->render();
        
        $thirdDiv .= "<button id='buildStaffReport'>Go!</button>";
        
        $thirdDiv .= "</div>";
                
        $reportDiv = "<div id='reportDiv-staff'></div>";
        
        return $wrapperDiv . $firstDiv . $secondDiv . $thirdDiv . $divEnd . $reportDiv;
        
        
        
        
        
  }
}
