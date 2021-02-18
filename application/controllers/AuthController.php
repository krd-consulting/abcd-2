<?php

class AuthController extends Zend_Controller_Action
{
    
    protected function _recordLogin($uname) {
        $userTable = new Application_Model_DbTable_Users;
                
        $loginTime = date("Y-m-d H:i");
        
        $where = "userName = '$uname'";
        $data = array('lastLogin' => $loginTime);
        
        $userTable->update($data, $where);
    }
    
    protected function _process($values)
    {
	$adapter = $this->_getAuthAdapter();  
	$adapter->setIdentity($values['username']);
	$adapter->setCredential($values['password']);

        $namespace = new Zend_Session_Namespace('Zend_Auth');
        $namespace -> setExpirationSeconds(28800); 
        
	$auth = Zend_Auth::getInstance();
	$result = $auth->authenticate($adapter);
	if ($result->isValid()) {
		$user = $adapter->getResultRowObject();
		$auth->getStorage()->write($user);
                $this->_recordLogin($values['username']);                
                return true;
	} else {
                $this->_helper->flashMessenger->addMessage('Incorrect username/password.');
                return false;
        }
    }

    protected function _getAuthAdapter()
    {
	$dbAdapter = Zend_Db_Table::getDefaultAdapter();
	$authAdapter = new Zend_Auth_Adapter_DbTable($dbAdapter);

	$authAdapter	->setTableName('users')
			->setIdentityColumn('userName')
			->setCredentialColumn('password')
			->setCredentialTreatment('md5(?)');

	return $authAdapter;
    }

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
	$form = new Application_Form_Login();
	$request = $this->getRequest();
	if ($request->isPost()) {
		if ($form->isValid($request->getPost())) {
			if ($this->_process($form->getValues())) {
			    $this->_helper->redirector('index', 'dash');
			} else {
                            $this->_helper->flashMessenger->addMessage('Incorrect username/password.');
                            $this->_helper->redirector('index');
                        }
		}
	}
        
        if ($this->_helper->flashMessenger->getMessages()) {
            $message = $this->_helper->flashMessenger->getMessages();
            $this->view->message = $message[0];
        }
        
	$this->view->form = $form;
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/loginHeight.js"></script>';
    }

    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        unset ($msg);
        if ($_GET) {
            $msg = $_GET['q'];
        };
        
        if ($msg == "timeout") {
            $this->_helper->flashMessenger->addMessage('You have been logged out due to inactivity.');
        };
        
	$this->_helper->redirector('index', 'auth');
    }


}