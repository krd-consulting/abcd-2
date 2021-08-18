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

        $dept   = new Zend_Form_Element_Select('deptField');
        $dept   ->setLabel('Department');
        
        
	$this->addElements(array($id, $name, $dept));
    }


}

