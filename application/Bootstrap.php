<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initAutoLoad() {
	$autoLoader = Zend_Loader_Autoloader::getInstance();
        Zend_Loader_Autoloader::getInstance()->registerNamespace('ABCD_');
        $autoLoader->pushAutoloader(new ABCD_Loader_Autoloader_PHPExcel());
	return $autoLoader;
	}

	protected function _initFrontControllerPlugins() {
	
	$fc = Zend_Controller_Front::getInstance();

	//* ACL PLUGIN *
	$acl = new ABCD_Acl();
	$fc->registerPlugin(new ABCD_Plugin_Acl($acl));

	return $fc;
	
	}

	protected function _initSessions() {
		$this->bootstrap('session');
	}

	protected function _initView() {
	$view = new Zend_View();
	$view->doctype('XHTML1_STRICT');
//        $view->setEncoding('UTF-8');
//        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
	$view->headTitle('A Better Community Database 2.1');
	$view->skin = 'default';

	$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
	$viewRenderer->setView($view);

	return $view;
	}
        
        protected function _initJquery() {

        $this->bootstrap('view');
        $view = $this->getResource('view'); //get the view object

        //add jquery view helper path
        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
        Zend_Controller_Action_HelperBroker::addHelper(new ZendX_JQuery_Controller_Action_Helper_AutoComplete);

        //jquery lib includes here (default loads from google CDN)
        $view->jQuery()->enable() //enable jquery ; ->setCdnSsl(true) if need to load from ssl location
                        ->setLocalPath('/js/jQuery/jquery.js')
                        ->setUiLocalPath('/js/jQuery/jqueryUi.js')
                        ->setVersion('1.7')
                        ->setUiVersion('1.8')
                        //->addStylesheet('/js/jQuery/css/ui-lightness/jquery-ui.css', 'screen,print')
                        ->uiEnable();
        }

        
        protected function _initNavigation() {
            $this->bootstrap('layout');
            $layout =  $this->getResource('layout');
            $view = $layout->getView();
            //$roleID = '0'; use this if 'guest' navigation is needed
            //$roles = array('guest','staff','manager','admin');
            $roles = array(
                '20' => 'staff',
                '30' => 'manager',
                '40' => 'admin',
                '10' => 'evaluator',
                '15' => 'volunteer'
                );
            
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $roleID = $auth->getIdentity()->role;
                $role = $roles[$roleID];
                $config = new Zend_Config_Xml(APPLICATION_PATH . '/configs/navs/' . $role . '.xml', 'nav');
                $navigation = new Zend_Navigation($config);
                $view->navigation($navigation);
            } 
        }
        
        protected function _initRegistry() {
        $root = FALSE;
        $mgr = FALSE;
        $evaluator = FALSE;
        $volunteer = FALSE;
        $auth = null;
        $db = '';
        $uid = '';
        
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) {
            return FALSE;
        }
        
        $uid = $auth->getIdentity()->id;
        
        /* Set role vars*/
        $roleID = $auth->getIdentity()->role;
        switch ($roleID) {
            case '40' : $root = TRUE; $mgr = TRUE; break;
            case '30' : $mgr = TRUE; break;
            case '15' : $volunteer = TRUE; break;
            case '10' : $evaluator = TRUE; break;
            case '20' : $staff = TRUE; break;
            default: throw new exception ("Unknown role id $roleID found in bootstrap."); break;
        }
        
        /* Set Database */
        $db = $this->getResource('db');
        
        $registry = Zend_Registry::getInstance();
        $registry['uid'] = $uid;
        $registry['root'] = $root;
        $registry['mgr'] = $mgr;
        $registry['evaluator'] = $evaluator;
        $registry['volunteer'] = $volunteer;
        $registry['role'] = $auth->getIdentity()->role;
        $registry['db'] = $db;
        
        return $registry;
        
    }
}

