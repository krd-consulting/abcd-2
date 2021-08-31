<?php

class Zend_View_Helper_FormReport extends Zend_View_Helper_Abstract
{
  public function formReport()
  {
      $wrapperDiv = "<div id='options-div'>";
      $divEnd = "</div>";

        //1: multiselect of all forms
        $firstDiv = "<div id='formSelect' class='reportBuilder block'>";
        
        $firstDiv .= "<div class='block-header'>
                        1. Choose a form
                      </div>";
        
        $formSelect = new Zend_Form_Element_Select('formList');
        
        
        $optionsArray = array();
        $formTable = new Application_Model_DbTable_Forms;
        $allForms = $formTable->fetchAll("enabled = 1", "name asc")->toArray();
        $permittedForms = $formTable->getStaffForms();
        foreach ($allForms as $form) {
            $name = $form['name'];
            $id = $form['id'];
            if (in_array($id,$permittedForms)) {
                $optionsArray[$id] = $name;
            }
        }
        
        $size = count($optionsArray);
        if ($size > 6) {
            $size = 6;
        }
        
        $formSelect->setAttrib('size', $size)
                   ->setMultiOptions($optionsArray);
        
        $firstDiv .= $formSelect->render();
        
        $firstDiv .= "</div>";
        
        //2: Date Range form
        $secondDiv = "<div id='dateOptions' class='hidden reportBuilder block'>";
        
        $secondDiv .= "<div class='block-header'>
                        2. Choose Date Options
                      </div>";
        
        $dateSelect = new Zend_Form_Element_Radio('dateSelect');
        $dateSelect ->setMultiOptions(array('all' => 'Show all data', 'filter' => 'Filter by date'));
        
        $secondDiv .= $dateSelect->render();
        
        $fromDate = new Zend_Form_Element_Text('fromDate');
        $fromDate->setLabel('From')
                 ->setAttrib('class','dynamicdatepicker');
                
        $toDate = new Zend_Form_Element_Text('toDate');
        $toDate->setLabel('To')
                 ->setAttrib('class','dynamicdatepicker');
        
        $dateBoxes = "<div id='dateBoxes' class='hidden'>" . $fromDate->render() . $toDate->render() . "</div>";
        
        $secondDiv .= $dateBoxes;
        
        $secondDiv .= "</div>";
        
        //3: Format selection form + go button
        $thirdDiv = "<div id='formatOptions' class='hidden reportBuilder block'>";
        
        $thirdDiv .= "<div class='block-header'>
                        3. Choose display options
                      </div>";
        
        $formatSelect = new Zend_Form_Element_Radio('formatSelect');
        $formatSelect->addMultiOptions(array(
            'table' => 'View report as table',
            //'graph' => 'View report graphs',
            'excel' => 'Download Excel spreadsheet'
            ))
                    ->setValue('table');
        $thirdDiv .= $formatSelect->render();
        
        $thirdDiv .= "<button id='buildFormReport'>Go!</button>";
        
        $thirdDiv .= "</div>";
                
        $reportDiv = "<div id='reportDiv'></div>";
        
        return $wrapperDiv . $firstDiv . $secondDiv . $thirdDiv . $divEnd . $reportDiv;
  }
}
