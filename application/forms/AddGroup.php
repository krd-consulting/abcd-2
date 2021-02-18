<?php

class Application_Form_AddGroup extends Zend_Form
{

    public function init()
    {
	$this->setName('addGroupForm');
        
	$name = new Zend_Form_Element_Text('groupName');
	$name->setLabel('Group Name');
        
        $desc = new Zend_Form_Element_Textarea('groupDesc');
        $desc->setLabel('Brief Description');
        $desc->setOptions(array(
                    'cols' => '20',
                    'rows' => '3'
        ));

        //also a $prog select list, created in the controller.
        
	$this->addElements(array($name, $desc));
    }


}

