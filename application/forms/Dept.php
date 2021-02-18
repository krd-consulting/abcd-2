<?php

class Application_Form_Dept extends Zend_Form
{

    public function init()
    {
	$this->setName('dept');
	$id = new Zend_Form_Element_Hidden('id');
	$id->addFilter('int');

	$dept = new Zend_Form_Element_Text('deptName');
	$dept	->setLabel('Name')
		->setRequired(true)
		->addFilter('StripTags')
		->addFilter('StringTrim')
		->addValidator('NotEmpty');
        
	$this->addElements(array($id, $dept));
    }


}

