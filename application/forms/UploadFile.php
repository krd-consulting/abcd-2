<?php

class Application_Form_UploadFile extends Zend_Form
{
    protected $type      = 0;
    protected $typeID    = 0;
    
    public function setType($type) {
        $this->type = $type;
    }
    
    public function setTypeID($typeID) {
        $this->typeID = $typeID;
    }
    
    
    public function init()
    {
	$this->setName('uploadFileForm')
             ->setAttrib('class', 'dialog-form')
             ->setAttrib('enctype','multipart/form-data')
             ->setMethod('post');
        
        $targetType = new Zend_Form_Element_Hidden('targetType');
        $targetId = new Zend_Form_Element_Hidden('targetID');
        $targetType->setValue($this->type);
        $targetId->setValue($this->typeID);
        
        $description = new Zend_Form_Element_Text('fileDescription');
        $description->setLabel('Description');
        
        $uploadedFile = new Zend_Form_Element_File('uploadedFile');
        $uploadedFile->setLabel('Select File')
                     ->setDestination(APPLICATION_PATH . "/../data/uploaded-files")
                     ->setName('files[]')
                     ->addValidator('Count', FALSE, 1);
        
	$this->addElements(array($targetType, $targetId, $description, $uploadedFile));
    }


} 

