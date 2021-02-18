<?php

class Application_Form_AddJob extends Zend_Form
{

    public function init()
    {
	$this->setName('addJobForm');
        
	$name = new Zend_Form_Element_Text('name');
	$name->setLabel('Job Name');
        
        $desc = new Zend_Form_Element_Textarea('description');
        $desc->setLabel('Brief Description');
        $desc->setOptions(array(
                    'cols' => '28',
                    'rows' => '4'
        ));

	$this->addElements(array($name, $desc));
    }

}

