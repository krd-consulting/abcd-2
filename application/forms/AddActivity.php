<?php

class Application_Form_AddActivity extends Zend_Form
{
    protected $ptcpID    = 0;
    protected $uID       = 0;
    protected $myName    = '';
    
    public function setPtcp($ptcp) {
        $this->ptcpID = $ptcp;
        
        $ptcpTable = new Application_Model_DbTable_Participants;
        $ptcp = $ptcpTable->getRecord($ptcp);
        $name = $ptcp['firstName'] . ' ' . $ptcp['lastName'];
        $this->myName = $name;
    }
    
    public function setUser($user) {
        $this->uID = $user;
    }
    
    public function init()
    {
	$this->setName('addActivityForm')
             ->setAttrib('class', 'dialog-form');
        
        $ptcpName = new Zend_Form_Element_Text('ptcpName');
        $ptcpName->setLabel('Name')
                 ->setValue($this->myName);
        
        $date = new Zend_Form_Element_Text('date');
        $date->setLabel('Date')
                  ->setAttrib('class', 'entrydaypicker');
        
	$note = new Zend_Form_Element_Textarea('note');
	$note->setLabel('Notes')
             ->setOptions(array(
                 'cols' => '40',
                 'rows' => '4'
             ));
        
        $duration = new Zend_Form_Element_Text('duration');
        $duration->setAttrib('class', 'numeric')
                 ->setLabel('Duration');
        
        $ptcpId = new Zend_Form_Element_Hidden('participantID');
        $ptcpId->setValue($this->ptcpID);
        
        $uId = new Zend_Form_Element_Hidden('userID');
        $uId->setValue($this->uID);
        
	$this->addElements(array($ptcpName, $date, $duration, $note, $ptcpId, $uId));
    }


}

