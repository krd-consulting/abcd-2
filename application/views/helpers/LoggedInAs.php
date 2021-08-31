<?php

class Zend_View_Helper_LoggedInAs extends Zend_View_Helper_Abstract
{
  public function loggedInAs ()
  {
	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
  	  $fname = $auth->getIdentity()->firstName;
	  $lname = $auth->getIdentity()->lastName;
   	  $roleID = $auth->getIdentity()->role;
          $id = $auth->getIdentity()->id;
          
          $roles = new Application_Model_DbTable_Roles;
          $role = $roles->getRole($roleID)->roleName;
          
	  $logoutUrl = $this->view->url(
                        array('controller'=>'auth','action'=>'logout'), 
                        null, 
                        true);
	  $helperContent =  "<div id=loggedInAs>Welcome " . $fname . ' ' . $lname . "</div>" .
                            "<div id=accessLogout>" .
                                "<span class=linespace id=access>Access level: " . $role . "</span>" .
                                "<span class=linespace id=logout><a href=" . $logoutUrl . ">Logout</a></span>" .
                            "</div>";

	  return $helperContent;
	}

	$request = Zend_Controller_Front::getInstance()->getRequest();
	$controller = $request->getControllerName();
	$action = $request->getActionName();

	if ($controller == 'auth' && $action == 'index') { return ''; }

	$loginUrl = $this->view->url(array('controller'=>'auth','action'=>'index'));
	return '<a href="' . $loginUrl . '">Login</a>';

  }
}
