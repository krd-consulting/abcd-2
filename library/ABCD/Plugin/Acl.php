<?php

class ABCD_Plugin_Acl extends Zend_Controller_Plugin_Abstract {

	private $_acl = null;

	public function __construct(Zend_Acl $acl) {
	$this->_acl = $acl;
	}

	public function preDispatch(Zend_Controller_Request_Abstract $request) {

	  //Determine role from session - set to guest if none
	  $auth = Zend_Auth::getInstance();  
	  if ($auth->hasIdentity()) {
		$role = $auth->getIdentity()->role; 
	  } else {
		$role = ABCD_Roles::GUEST;
	  }

	  //Determine both possible resources
	  $controller = $request->getControllerName();
	  $action   = $request->getActionName();

	  $dbResource = new Application_Model_DbTable_Resources();

	  //try to get one for controller first; if none, get one for action
	  $resource = $dbResource->getResource($controller);
	  if(!$resource) { $resource = $dbResource->getResource($action); }

	  $access = $resource['resourceClass'];  //permission roles are in 'resourceClass'
	  $type = $resource['type'];		 //type should be either controller or action

	  switch ($type) {
		case 'controller' : $aclArgs=($access); break;
		case 'action'	  : $aclArgs=('*,' . $access); break;
		default		  : throw new Exception("Resource must be CONTROLLER or ACTION");
	  };

	  // If no permission, send them Home, which will forward to dashboard if logged in.
	  if (!$this->_acl->isAllowed($role,$aclArgs)) {
		//$request->setControllerName('index')
		//	->setActionName('index');
                throw new exception ("This functionality protects confidential information. If you think you should have access to it, please contact your administrator.");
	  }
	}

}
