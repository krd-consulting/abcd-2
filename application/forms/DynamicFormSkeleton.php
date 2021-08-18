<?php

class Application_Form_DynamicFormSkeleton extends Zend_Form
{

    protected $type;
    protected $target;
    protected $dept;
    
    public function setTarget($target) //participant, group, user
    {
        $this->target = $target;
    }
    
    public function setType($type) //static, prepost
    {
        $this->type = $type;
    }
    
    public function setDept($dept)
    {
        $this->dept = $dept;
    }
    
    public function init()
    {
	$this->setName('dynamicForm');
	$formType = new Zend_Form_Element_Hidden('formType');
        $formType->setValue($this->type);
        
	$formDept = new Zend_Form_Element_Hidden('formDept');
        $formDept->setValue($this->dept);
        
	$formTarget = new Zend_Form_Element_Hidden('formTarget');
        $formTarget->setValue($this->target);
        
        $targetID = new Zend_Form_Element_Hidden('targetID');
        
	$name = new Zend_Form_Element_Text('name');
        $name->setLabel('Full Name')
             ->setAttribs(array('class' => 'required'));
        
        $responseDate = new Zend_Form_Element_Text('responseDate');
        $responseDate->setLabel('Date Completed');
        $responseDate->setAttribs(array('class' => 'entrydaypicker required'));
        
        $prePostStatus = new Zend_Form_Element_Select('prepost');
        $prePostStatus->setLabel('Completed for');
        $prePostStatus->addMultiOptions(
                    array(
                        'pre'       => 'Pre-test',
                        'interim'   => 'Interim',
                        'post'      => 'Post-test'
                    )
                );
        
        switch ($this->target) {
        case 'participant' : 
            $name->setLabel('Participant Name');
            $this->addElements(array($formType, $formDept, $formTarget, $targetID, $name, $responseDate));
            break;
        case 'staff' :
            $name->setLabel('Staff Name');
            $this->addElements(array($formType, $formDept, $formTarget, $targetID, $name, $responseDate));
            break;
        case 'group' :
            $name->setLabel('Group Name');
            $this->addElements(array($formType, $formDept, $formTarget, $targetID, $name, $responseDate));
            break;
        case 'other' :
            $name->setLabel('Identifier (click to change)');
            $this->addElements(array($formType,$formDept,$formTarget,$name,$responseDate)); break;
        default:
            throw new Exception("Unexpected form target '$this->target' cannot be processed.");
        }
        
        if ($this->type == 'prepost') {
            $this->addElement($prePostStatus);
        }
        
        $this->clearDecorators();
        $this->addDecorator('FormElements')
             ->addDecorator('HtmlTag', array('tag' => '<ul>'))
             ->addDecorator('Form');
       
        $this->setElementDecorators(array(
           array('ViewHelper'),
            array('Errors'),
            array('Description'),
            array('Label', array('separator' => ' ')),
            array('HtmlTag', array('tag' => '<li>', 'class' => 'skeletonElements'))
        ));
    }
}

