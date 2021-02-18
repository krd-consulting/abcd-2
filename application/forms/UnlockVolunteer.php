<?php

class Application_Form_UnlockVolunteer extends Zend_Form
{

    public function init()
    {
	$this->setName('dialog-form');
	
        $username = new Zend_Form_Element_Text('username');
        $username -> setLabel('Login');
        
        $userID = new Zend_Form_Element_Hidden('userID');
                
        $password = new Zend_Form_Element_Password('password');
        $password ->setLabel('New Password');
                
	$this->addElements(array($username, $password, $userID));
    }


}

