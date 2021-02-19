<?php

class Zend_View_Helper_ReportTab extends Zend_View_Helper_Abstract
{
    function reportTab() {
        $nowStamp = date('Y-m-d', time());
        $yearAgoStamp = date('Y-m-d', (time() - 365*24*60*60));
        
        $fromDate = new Zend_Form_Element_Text('fromDate');
        $fromDate->setLabel('From')
                 ->setAttrib('class','dynamicdatepicker')
                 ->setValue($yearAgoStamp);
                
        $toDate = new Zend_Form_Element_Text('toDate');
        $toDate->setLabel('To')
                 ->setAttrib('class','dynamicdatepicker')
                 ->setValue($nowStamp);
        
        
        $secondDiv = "<div id='dateOptions' class=''>";
            $secondDiv .= "<h3> Choose Dates: </h3>";
            $dateBoxes = "<div id='dateBoxes-prog'>" . $fromDate->render() . $toDate->render() . "</div>";
            $secondDiv .= $dateBoxes;
        $secondDiv .= "</div>";
        
        $thirdDiv = "<div id='formatOptions' style='margin: 25px 0'>";
        
        $thirdDiv .= "<h3>
                        Choose display options
                      </h3>";
        
        $formatSelect = new Zend_Form_Element_Radio('formatSelect');
        $formatSelect->addMultiOptions(array(
            'table' => 'Table: view report',
            'excel' => 'Table: download spreadsheet',
            'graph' => 'Graph: view charts'
            ))
                    ->setValue('table');
        $thirdDiv .= $formatSelect->render();
        
        $thirdDiv .= "<br><br><button id='buildMyReport'>Go!</button>";
        
        $thirdDiv .= "</div>";
        
        
        return $secondDiv . $thirdDiv;
    }
}