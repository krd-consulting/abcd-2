<?php

class Zend_View_Helper_PtcpReport extends Zend_View_Helper_Abstract
{
  public function PtcpReport()
  {
      $wrapperDiv = "<div id='options-div-ptcp'>";
      $divEnd = "</div>";

        //1: multiselect of all forms
        $firstDiv = "<div id='ptcpSelect' class='reportBuilder block'>";
        
        $firstDiv .= "<div class='block-header'>
                        1. Choose participant & type
                      </div>";
        
        $ptcpSelect = new Zend_Form_Element_Text('name');
        $ptcpSelect->setLabel('Name');
        $ptcpID = new Zend_Form_Element_Hidden('targetID');
        $target = new Zend_Form_Element_Hidden('formTarget');
        $target->setValue('participant');
        
        $firstDiv .= $ptcpSelect->render();
        $firstDiv .= $ptcpID->render();
        $firstDiv .= $target->render();
        
        $typeSelect = new Zend_Form_Element_Radio('repType');
        $typeSelect//->setLabel('Report about:')
                   ->setMultiOptions(array(
                      'att' => 'Activity Report',
                      'form' => 'Outcomes Report'
                   ));
        
        $firstDiv .= $typeSelect->render();
        
        $firstDiv .= "</div>";
        
        //2: Date Range form
        $secondDiv = "<div id='scopeOptions-ptcp' class='hidden reportBuilder block'>";
        
        $secondDiv .= "<div class='block-header'>
                        2. Choose Scope
                      </div>";
        
        $secondDiv .= "<div id='scopeHolder'>" . 
                        
                      "</div>";
        
        $nowStamp = date('Y-m-d', time());
        $yearAgoStamp = date('Y-m-d', (time() - 365*24*60*60));
        
        $fromDate = new Zend_Form_Element_Text('fromDatePtcp');
        $fromDate->setLabel('Beginning')
                 ->setAttrib('class','dynamicdatepicker')
                 ->setValue($yearAgoStamp);
                
        $toDate = new Zend_Form_Element_Text('toDatePtcp');
        $toDate->setLabel('Ending')
                 ->setAttrib('class','dynamicdatepicker')
                 ->setValue($nowStamp);
        
        $dateBoxes = "<div id='dateBoxes-prog' class=''>" . $fromDate->render() . $toDate->render() . "</div>";
        
        $formFields = "<div id='formFieldSelect' class='hidden'></div>";
        
        $secondDiv .= $formFields . $dateBoxes . "</div>";
        
        //3: Format selection form + go button
        $thirdDiv = "<div id='formatOptions-ptcp' class='hidden reportBuilder block'>";
        
        $thirdDiv .= "<div class='block-header'>
                        3. Choose a report
                      </div>";
        
        $thirdDiv .= "<button id='buildPtcpReport'>Go!</button>";
        
        $thirdDiv .= "</div>";
                
        $reportDiv = "<div id='reportDiv-ptcp'></div>";
        
        return $wrapperDiv . $firstDiv . $secondDiv . $thirdDiv . $divEnd . $reportDiv;
        
        
  }
}
