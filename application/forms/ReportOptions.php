<?php

class Application_Form_ReportOptions extends Zend_Form
{

    public function init()
    {
	$this->setName('reportOptions');
	
        $filterTarget = new Zend_Form_Element_Select('filterTarget');
        $filterTarget->addMultiOptions(array(
                    'participant'  => 'Participants',
                    'staff'  => 'Staff'
        ))
                     ->setLabel('Report about');
        
	$filterType = new Zend_Form_Element_Select('filterType');
        $filterType->addMultiOption('form', 'Form Data')
                   ->addMultiOption('staff', "Caseload")
                   ->addMultiOption('prog', 'Program Status')
                   ->addMultiOption('group', 'Group Attendance')
                   ->setLabel('Filter by');
        
        $dataTarget = new Zend_Form_Element_Select('dataFrom');
        $dataTarget->addMultiOption('singleuse', 'Static Forms')
                   ->addMultiOption('prepost', 'Outcome Forms')
                   ->setLabel('Show data from');
        
	$this->addElements(array($filterTarget,$filterType,$dataTarget));
    }


}

