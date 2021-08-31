<?php

class Application_Form_AddVolActivity extends Zend_Form
{
      protected $vol   = 0;
      protected $user  = 0;
      protected $progs = array();
    
    public function setVol($vol) {
        $this->vol = $vol;
    }
    
    public function setUser($user) {
        $this->user = $user;
    }
    
    public function setProgs($progs) {
       $this->progs = $progs;
    }
    
    public function init()
    {
        $volTable = new Application_Model_DbTable_Users;
        $name = $volTable->getName($this->vol);
        
        
        $this->setName('addVolActivityForm')
             ->setAttrib('class', 'dialog-form');
        
        $volName = new Zend_Form_Element_Text('volName');
        $volName->setLabel('Name')
                 ->setValue($name);
        
        $date = new Zend_Form_Element_Text('date');
        $date->setLabel('Date')
                  ->setAttrib('class', 'entrydaypicker');
        
        $progSelect = new Zend_Form_Element_Select('program');
        $progSelect ->setLabel('Program')
                    ->addMultiOption("","");
        
        foreach ($this->progs as $progRecord) {
            $progID = $progRecord['id'];
            $pName = $progRecord['name'];
            $progSelect->addMultiOption($progID,$pName);
        }

        $volBeneficiary = new Zend_Form_Element_Text('volBenef');
        $volBeneficiary ->setLabel('Volunteered with:')
                        ->setAttrib('class','autocomplete');
        
	$note = new Zend_Form_Element_Textarea('note');
	$note->setLabel('Notes')
             ->setOptions(array(
                 'cols' => '40',
                 'rows' => '4'
             ));
        
        $fromTime = new Zend_Form_Element_Text('fromTime');
        $fromTime->setAttrib('class', 'timepicker start')
                 ->setLabel('From: ');
        
        $toTime = new Zend_Form_Element_Text('toTime');
        $toTime->setAttrib('class', 'timepicker end')
                 ->setLabel('To: ');
        
        
        $volId = new Zend_Form_Element_Hidden('volunteerID');
        $volId->setValue($this->vol);
        
        $uId = new Zend_Form_Element_Hidden('userID');
        $uId->setValue($this->user);
        
	$this->addElements(array(
            $volName, 
            $date,
            $progSelect, 
            $volBeneficiary, 
            $fromTime, $toTime, 
            $note, 
            $volId, 
            $uId
        ));
    }


}

