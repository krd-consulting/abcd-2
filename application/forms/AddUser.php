<?php

class Application_Form_AddUser extends Zend_Form
{

    public function init()
    {
	$this->setName('addUser');
	$id = new Zend_Form_Element_Hidden('userID');
	$id->addFilter('int');

        $username = new Zend_Form_Element_Text('username');
        $username -> setLabel('Username');
        
	$fname = new Zend_Form_Element_Text('fname');
	$fname	->setLabel('First Name');

        $lname = new Zend_Form_Element_Text('lname');
	$lname	->setLabel('Last Name');
        
        $email   = new Zend_Form_Element_Text('email');
        $email    ->setLabel('E-mail Address');
                
        $password = new Zend_Form_Element_Password('pwd');
        $password ->setLabel('Password');
        
        $role = new Zend_Form_Element_Select('role');
        $role ->setLabel('Role');
        $role ->addMultiOption(2, 'staff');
        $role ->addMultiOption(3, 'manager');
        $role ->addMultiOption(4, 'admin');
        $role ->addMultiOption(1, 'evaluator');
        $role ->setAttribs(array('class' => 'hidden'));
        
        $dept   = new Zend_Form_Element_Select('dept');
        $dept   ->setLabel('');
        $dept   ->setAttribs(array('class' => 'hidden'));

        $elementsArray = array($username, $fname, $lname, $email, $password, $role, $dept);
        
	$this->addElements($elementsArray);
    }


}

