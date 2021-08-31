<?php

class Application_Form_ElementSelector extends Zend_Form
{

    public function init()
    {
	$this->setName('elementSelector');
	
	$elementType = new Zend_Form_Element_Select('elementType');
        $elementType->addMultiOption(NULL, '-choose one-')
                    ->addMultiOption('text', 'Text Field')
                    ->addMultiOption('date', 'Date Field')
                    ->addMultiOption('dropdown', 'Dropdown List')
                    ->addMultiOption('matrix', 'Matrix')
                    ->addMultiOption('checkbox', 'Checkbox List')
                    ->addMultiOption('textbox', 'Text Box')
                    ->setLabel('Select an Element');
        
        $this->addElements(array($elementType));
    }


}

