<?php

class Application_Form_FilterFormFreq extends Zend_Form
{

    public function init()
    {
	$this->setName('statusFilter');
		        
        $filter = new Zend_Form_Element_Select('filter');
        $filter ->setLabel('Frequency:')
                ->addMultiOptions(array(
                        'All'         => 'All',
                        'Daily'       => 'Daily',
                        'Weekly'      => 'Weekly',
                        'Monthly'     => 'Monthly'
                ));        

	$this   ->  addElements(array($filter));
    }
}