<?php

class Application_Form_AddScheduleSet extends Zend_Form
{
    protected $uID;
    
    public function setuID($uID) {
        $this->uID = $uID;
    }
    
    public function init()
    {
	$this->setName('addSetForm')
             ->setAttrib('class', 'dialog-form');
        
        $setName = new Zend_Form_Element_Text('setName');
        $setName->setLabel('Calendar Name');
        
        $startDate = new Zend_Form_Element_Text('startDate');
        $startDate->setLabel('From Date')
                  ->setAttrib('class', 'dynamicdatepicker');
        
        $endDate = new Zend_Form_Element_Text('endDate');
        $endDate->setLabel('End Date')
                ->setAttrib('class', 'dynamicdatepicker');
        
        $fromTime = new Zend_Form_Element_Text('fromTime');
        $fromTime->setAttrib('class', 'timepicker start')
                 ->setLabel('Daily start time: ');
        
        $toTime = new Zend_Form_Element_Text('toTime');
        $toTime->setAttrib('class', 'timepicker end')
                 ->setLabel('End time: ');
        
	$resourceType = new Zend_Form_Element_Select('resourceType');
        $resourceType->setLabel('Resource Type: ')
                     ->addMultiOption("","")
                     ->addMultiOption("adhoc","Ad-hoc Resources")
                     ->addMultiOption("volunteer","Volunteers")
                     ->addMultiOption("staff","Staff");
        
        $resourceAdder = new Zend_Form_Element_Text('resource');
        $resourceAdder->setLabel('Name')
                      ->setAttribs(array('class' => ''));
        
        $dept   = new Zend_Form_Element_Select('dept');
        $dept   ->  setLabel('');
        $dept   ->  setAttribs(array('class' => 'hidden'));
        
        $uId = new Zend_Form_Element_Hidden('userID');
        $uId->setValue($this->uID);
        
	$this->addElements(array($setName, $startDate, $endDate, $fromTime, $toTime, $resourceType, $resourceAdder, $dept, $uId));
    }


}

