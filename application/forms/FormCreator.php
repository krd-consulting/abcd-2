<?php

class Application_Form_FormCreator extends Zend_Form
{

    protected $depts = array();
    
    public function setDepts($depts)
    {
        $this->depts = $depts;
    }
            
    public function init()
    {
	$this->setName('formCreator');
	
	$formName = new Zend_Form_Element_Text('formName');
	$formName->setLabel('Form Title');
        
        $description = new Zend_Form_Element_Textarea('description');
        $description->setLabel('Description')
                    ->setAttribs(array('cols' => '25', 'rows' => '2'));
        
	$formType = new Zend_Form_Element_Select('formType');
        $formType->addMultiOption('singleuse', 'Static Information')
                 ->addMultiOption('prepost', 'Pre/post Survey')
                 ->setLabel('Form Type');
        
        $formTarget = new Zend_Form_Element_Select('formTarget');
        $formTarget->addMultiOption('participant', 'Program Participants')
                 ->addMultiOption('staff', 'Program Staff / Volunteers')
                 ->addMultiOption('group', 'Groups / Group Meetings')
                 ->addMultiOption('other', 'Other')
                 ->setLabel('Form is about');

        $deptList = new Zend_Form_Element_Select('dept');
        foreach ($this->depts as $id => $name) {
            $deptList->addMultiOption($id, $name);
        }
        $deptList->setLabel('Department');
        
	$this->addElements(array($formName, $deptList, $formTarget, $formType, $description));
    }


}

