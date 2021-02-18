<?php

class Application_Form_AddAlert extends Zend_Form
{
    protected $type = 'ptcp';
    protected $options = array();
    
    
    public function setType($type) {
        $this->type = $type;
    
        switch ($this->type) {
            case 'ptcp': 
                $this->options = array(
                    'participant' => 'Individual Participant',
                    'group'       => 'Current group members' 
                ); 
                break;
            case 'vol':
                $this->options = array(
                    'volunteer'     => 'Individual Volunteer',
                    'group'         => 'Current group volunteers',
                    'program'       => 'All volunteers in a program'
                );
                break;
            default: throw new exception("Invalid target $type passed to Alert Form Creator.");
        }
    }
    
    public function init()
    {
	$this->setName('addAlertForm')
             ->setAttrib('class', 'dialog-form');
        
	$name = new Zend_Form_Element_Textarea('alert');
	$name->setLabel('Alert')
             ->setOptions(array(
                 'cols' => '30',
                 'rows' => '3'
             ));
        
        $assignTo = new Zend_Form_Element_Select('formTarget');
        $assignTo->setLabel('Assign alert to');
        $assignTo->setMultiOptions($this->options);

        $target = new Zend_Form_Element_Text('name');
        $target->setAttrib('class', 'autocomplete');
        $target->setLabel('Name');
        
        $targetId = new Zend_Form_Element_Hidden('targetID');
        
        $startDate = new Zend_Form_Element_Text('startDate');
        $startDate->setLabel('Show alerts after')
                  ->setAttrib('class', 'dynamicdatepicker');
        
	$this->addElements(array($assignTo, $target, $name, $startDate, $targetId));
    }


}

