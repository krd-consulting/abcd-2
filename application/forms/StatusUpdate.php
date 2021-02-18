<?php

class Application_Form_StatusUpdate extends Zend_Form
{

    public function init()
    {
	$this->setName('statusForm');
		        
        $status = new Zend_Form_Element_Select('status');
        $status ->setLabel('Program Status')
                ->addMultiOptions(array(
                        'waitlist' => 'Waitlist',
                        'active'   => 'Active',
                        'leave'    => 'On Leave',
                        'concluded'=> 'Concluded'
                ));
        $note   = new Zend_Form_Element_Textarea('note');
        $note   ->setLabel('Status Notes')
                ->setOptions(array(
                    'rows' => '4',
                    'cols' => '25'
                ));
        

	$this   ->  addElements(array($status, $note));
    }
}