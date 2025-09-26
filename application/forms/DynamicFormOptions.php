<?php

class Application_Form_DynamicFormOptions extends Zend_Form
{

    protected $type;
    
    public function setType($type) 
    {
        $this->type = $type;
    }
    
    public function init()
    {
	$this->setName('formOptions');
        
        $elementType = new Zend_Form_Element_Hidden('elementType');
        $elementType->setValue($this->type);
        
        $fieldTitle = new Zend_Form_Element_Text('fieldTitle');
        $fieldTitle->setLabel("Question / Title:");

        $isRequired = new Zend_Form_Element_Select('isRequired');
        $isRequired->setLabel("This field is");
        $isRequired->addMultiOption('true', 'Required');
        $isRequired->addMultiOption('false', 'Optional');
        
        $this->addElement($fieldTitle);
        $this->addElement($elementType);
        
        switch ($this->type) {
            case 'text':
                $referenceOpts = new Zend_Form_Element_Select('referenceOpts');
                $referenceOpts->setLabel("This Field")
                              ->addMultiOption('standAlone', 'Stands alone')
                              ->addMultiOption('refersToForm','Refers to a form field')
                              ->addMultiOption('refersToPtcp','Refers to participant')
                              ->addMultiOption('refersToStaff','Refers to staff')
                              ->addMultiOption('refersToProg','Refers to program');
                $this->addElement($referenceOpts);
                
                
                break;
            case 'num':
            case 'upload':
                break;
            case 'date':
                
                break;
            case 'textarea':
                break;
            case 'radio':
            case 'check':
                $numBoxes = new Zend_Form_Element_Text('numBoxes');
                $numBoxes->setLabel('Number of choices:')
                         ->setAttrib('style', 'width: 1em');
                $this->addElement($numBoxes);
                break;
            case 'matrix':
                $numRows = new Zend_Form_Element_Text('numRows');
                $numCols = new Zend_Form_Element_Text('numCols');
                $numRows->setLabel('Number of questions: ')
                        ->setAttrib('style', 'width: 1em');
                $numCols->setLabel('# of choices per question : ')
                        ->setAttrib('style', 'width: 1em');
                $this->addElements(array($numRows,$numCols));
                break;
            default:
                throw new Exception("Unexpected element type $this->type cannot be processed.");
        }
        
        $this->addElement($isRequired);
        
        $this->clearDecorators();
        $this->addDecorator('FormElements')
             ->addDecorator('HtmlTag', array('tag' => '<ul>','class' => 'optionsList'))
             ->addDecorator('Form');
       
        $this->setElementDecorators(array(
           array('ViewHelper'),
            array('Errors'),
            array('Description'),
            array('Label', array('separator' => ' ')),
            array('HtmlTag', array('tag' => '<li>', 'class' => ''))
        ));
        
    }


}

