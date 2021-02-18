<?php

class Application_Form_AddProgram extends Zend_Form
{

    public function init()
    {
	$this->setName('addProgram');
	$id = new Zend_Form_Element_Hidden('programID');
	$id->addFilter('int');

	$name = new Zend_Form_Element_Text('pname');
	$name	->setLabel('Program Name');
        
        $volType = new Zend_Form_Element_Select('voltype');
        $volType->addMultiOption('none','None')
                ->addMultiOption('oneToOne', 'One to One')
                ->addMultiOption('group', 'Group')
                ->setLabel('Volunteer Type');

        $dept   = new Zend_Form_Element_Select('deptField');
        $dept   ->setLabel('Department');
        
        
	$this->addElements(array($id, $name, $volType, $dept));
    }


}

