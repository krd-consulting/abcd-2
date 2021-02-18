<?php

class Application_Form_AddParticipant extends Zend_Form
{

    public function init()
    {
	$this->setName('addParticipant');
	$id = new Zend_Form_Element_Hidden('participantID');
	$id     ->  addFilter('int');

	$fname = new Zend_Form_Element_Text('fname');
	$fname	->  setLabel('First Name');

        $lname = new Zend_Form_Element_Text('lname');
	$lname	->  setLabel('Last Name');
        
        $dob   = new Zend_Form_Element_Text('dob');
        $dob    ->  setLabel('Date of Birth')
                ->  setAttribs(array('class' => 'birthdaypicker'));
                
        $dept   = new Zend_Form_Element_Select('dept');
        $dept   ->  setLabel('');
        $dept   ->  setAttribs(array('class' => 'hidden'));

	$this   ->  addElements(array($id, $fname, $lname, $dob, $dept));
    }
}

