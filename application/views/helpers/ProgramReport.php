<?php

class Zend_View_Helper_ProgramReport extends Zend_View_Helper_Abstract
{
  public function ProgramReport()
  {
      $wrapperDiv = "<div id='options-div-prog'>";
      $divEnd = "</div>";

        //1: multiselect of all forms
        $firstDiv = "<div id='progSelect' class='reportBuilder block'>";
        
        $firstDiv .= "<div class='block-header'>
                        1. Choose a program
                      </div>";
        
        $progSelect = new Zend_Form_Element_Select('progList');
        
        
        $optionsArray = array();
        $progTable = new Application_Model_DbTable_Programs;
        $allProgs = $progTable->getStaffPrograms($this->view->uid);
        
        foreach ($allProgs as $progID) {
            $prog = $progTable->getRecord($progID);
            $name = $prog['name'];
            $id   = $prog['id'];
            $optionsArray[$id] = $name;
        }
        
        $size = 6; //show 6 lines;
        
        $progSelect->setAttrib('size', $size)
                    ->setMultiOptions($optionsArray);
        
        $firstDiv .= $progSelect->render();
        
        $firstDiv .= "</div>";
        
        //2: Date Range form
        $secondDiv = "<div id='dateOptions-prog' class='hidden reportBuilder block'>";
        
        $secondDiv .= "<div class='block-header'>
                        2. Choose Date Options
                      </div>";
        
        $dateSelect = new Zend_Form_Element_Radio('dateSelectProg');
        $dateSelect ->setMultiOptions(array('all' => 'Last 12 months', 'filter' => 'Other date range'));
        
        $secondDiv .= $dateSelect->render();
        
        $fromDate = new Zend_Form_Element_Text('fromDateProg');
        $fromDate->setLabel('From')
                 ->setAttrib('class','dynamicdatepicker');
                
        $toDate = new Zend_Form_Element_Text('toDateProg');
        $toDate->setLabel('To')
                 ->setAttrib('class','dynamicdatepicker');
        
        $dateBoxes = "<div id='dateBoxes-prog' class='hidden'>" . $fromDate->render() . $toDate->render() . "</div>";
        
        $secondDiv .= $dateBoxes;
        
        $secondDiv .= "</div>";
        
        //3: Format selection form + go button
        $thirdDiv = "<div id='formatOptions-prog' class='hidden reportBuilder block'>";
        
        $thirdDiv .= "<div class='block-header'>
                        3. Choose a report
                      </div>";
        
        $formatSelect = new Zend_Form_Element_Radio('formatSelectProg');
        $formatSelect->addMultiOptions(array(
            'attend-graph' => 'Graph: attendance by group',
            'enroll-graph' => 'Graph: enrollment by status',
            'table' => 'Table: attendance summary',
            'excel' => 'Table: download spreadsheet'
            ))
                    ->setValue('table');
        $thirdDiv .= $formatSelect->render();
        
        $thirdDiv .= "<button id='buildProgReport'>Go!</button>";
        
        $thirdDiv .= "</div>";
                
        $reportDiv = "<div id='reportDiv-prog'></div>";
        
        return $wrapperDiv . $firstDiv . $secondDiv . $thirdDiv . $divEnd . $reportDiv;
        
        
  }
}
