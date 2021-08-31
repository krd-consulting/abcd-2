<?php

class Application_Form_FilterForm extends Zend_Form
{

    public function init()
    {
	$this->setName('statusFilter');
		        
        $filter = new Zend_Form_Element_Select('filter');
        $filter ->setLabel('Status:')
                ->addMultiOptions(array(
                        'All'             => 'All',
                        'Waitlist'        => 'Waitlist',
                        'Active'          => 'Active',
                        'On Leave'        => 'On Leave',
                        'Concluded'       => 'Concluded'
                ));        

	$this   ->  addElements(array($filter));
    }
}