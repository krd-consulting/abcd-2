<?php

class Application_Form_EnrollVolunteer extends Zend_Form
{
    
    public function init()
    {
	$this->setName('recruitVolunteerForm')
             ->setAttrib('class', 'dialog-form');
        
        $job = new Zend_Form_Element_Select('job');
        $job->setLabel('Job to enroll: ')
                     ->addMultiOption("","");
        
        $resourceAdder = new Zend_Form_Element_Text('resource');
        $resourceAdder->setLabel('Name')
                      ->setAttribs(array('class' => ''));
        
        $event   = new Zend_Form_Element_Hidden('event');
        $event   ->  setLabel('');
        $event   ->  setAttribs(array('class' => 'hidden'));
                
	$this->addElements(array($job, $resourceAdder, $event));
    }


}

