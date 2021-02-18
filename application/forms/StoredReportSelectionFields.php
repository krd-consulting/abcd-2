<?php

class Application_Form_StoredReportSelectionFields extends Zend_Form {
    protected $role,$customType;
    
    public function setMaxRole ($role) {
        $this->role = $role;
    }
    
    public function setCustomType ($customType) {//recips, freq, type
        $this->customType = $customType;
    }
    
    protected function createRecipTool() {
        
        $recipsDropDown = new Zend_Form_Element_Select('recipsDropDown');
        $recipsDropDown->addMultiOption('single','Individually');
        if ($this->role == 'mgr') {
            $recipsDropDown->addMultiOption('grps','By Group');
            $recipsDropDown->addMultiOption('prgs','By Program');
        }
        if ($this->role == 'admin') {
            $recipsDropDown->addMultiOption('depts','By Dept');
        }
        
        $recipsDropDown->setLabel('Choose additional recipients');
        return $recipsDropDown;
    }
    
    protected function createFreqTool() {
        $freqDropDown = new Zend_Form_Element_Select('freqDropDown');
        $freqDropDown->addMultiOptions(array(
            'daily'     => 'Daily',
            'weekly'    => 'Weekly',
            'monthly'   => 'Monthly'
        ));
        
        $freqDropDown->setLabel('Choose report frequency');
        return $freqDropDown;
    }
    
    protected function createTypeTool() {
        $typeDropDown = new Zend_Form_Element_Select('typeDropDown');
        $typeDropDown->addMultiOptions(array(
            'progs' => 'Programs',
            'groups'=> 'Groups',
            'forms' => 'Forms'
        ));
        
        $typeDropDown->setLabel("Include information about");
        return $typeDropDown;
    }
    
    public function init() {
        $validTypes = array('recip','freq','type');
        if (!in_array($this->customType,$validTypes)) {
            throw new Exception("Invalid type $this->customType passed to StoredReportSelectionFields");
        }
        
        switch ($this->customType) {
            case 'recip': $this->createRecipTool(); break;
            case 'freq'  : $this->createFreqTool(); break;
            case 'type'  : $this->createTypeTool(); break;
            default: break;
        }
        
    }
    
}
