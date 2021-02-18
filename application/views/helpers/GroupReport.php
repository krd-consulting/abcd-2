<?php

class Zend_View_Helper_GroupReport extends Zend_View_Helper_Abstract
{
  public function groupReport()
  {
      $wrapperDiv = "<div id='options-div-group'>";
      $divEnd = "</div>";

        //1: multiselect of all forms
        $firstDiv = "<div id='groupSelect' class='reportBuilder block'>";
        
        $firstDiv .= "<div class='block-header'>
                        1. Choose a group
                      </div>";
        
        $groupSelect = new Zend_Form_Element_Select('groupList');
        
        
        $optionsArray = array();
        $groupTable = new Application_Model_DbTable_Groups;
        $allGroups = $groupTable->getStaffGroups($this->view->uid);
        
        foreach ($allGroups as $groupID) {
            $group = $groupTable->getRecord($groupID);
            $name = $group['name'];
            $id = $group['id'];
            $optionsArray[$id] = $name;
        }
        
        $size = 6; //show 6 lines;
        
        $groupSelect->setAttrib('size', $size)
                    ->setMultiOptions($optionsArray);
        
        $firstDiv .= $groupSelect->render();
        
        $firstDiv .= "</div>";
        
        //2: Date Range form
        $secondDiv = "<div id='dateOptions-group' class='hidden reportBuilder block'>";
        
        $secondDiv .= "<div class='block-header'>
                        2. Choose Date Options
                      </div>";
        
        $dateSelect = new Zend_Form_Element_Radio('dateSelectGroup');
        $dateSelect ->setMultiOptions(array('all' => 'Show all data', 'filter' => 'Filter by date'));
        
        $secondDiv .= $dateSelect->render();
        
        $fromDate = new Zend_Form_Element_Text('fromDateGroup');
        $fromDate->setLabel('From')
                 ->setAttrib('class','dynamicdatepicker');
                
        $toDate = new Zend_Form_Element_Text('toDateGroup');
        $toDate->setLabel('To')
                 ->setAttrib('class','dynamicdatepicker');
        
        $dateBoxes = "<div id='dateBoxes-group' class='hidden'>" . $fromDate->render() . $toDate->render() . "</div>";
        
        $secondDiv .= $dateBoxes;
        
        $secondDiv .= "</div>";
        
        //3: Format selection form + go button
        $thirdDiv = "<div id='formatOptions-group' class='hidden reportBuilder block'>";
        
        $thirdDiv .= "<div class='block-header'>
                        3. Choose a report
                      </div>";
        
        $formatSelect = new Zend_Form_Element_Radio('formatSelectGroup');
        $formatSelect->addMultiOptions(array(
            'attend-graph' => 'Graph: attendance by type',
            'role-graph' => 'Graph: participant engagement',
            'table' => 'Table: attendance summary',
            'excel' => 'Table: download spreadsheet'
            ))
                    ->setValue('table');
        $thirdDiv .= $formatSelect->render();
        
        $thirdDiv .= "<button id='buildGroupReport'>Go!</button>";
        
        $thirdDiv .= "</div>";
                
        $reportDiv = "<div id='reportDiv-group'></div>";
        
        return $wrapperDiv . $firstDiv . $secondDiv . $thirdDiv . $divEnd . $reportDiv;
        
        
  }
}
