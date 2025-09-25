<?php

class ScheduleController extends Zend_Controller_Action
{
    private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $evaluator = FALSE;
    private $db = NULL;
    
    public function init() {
        /* Get user credentials */
        $this->auth = Zend_Auth::getInstance();
        if (!$this->auth->hasIdentity()) {
            throw new Exception("You must be logged in to access this function.");
        }
        
        /* Set UID and roles */
        $this->uid = Zend_Registry::get('uid');
        $this->root = Zend_Registry::get('root');
        $this->mgr = Zend_Registry::get('mgr');
        $this->evaluator = Zend_Registry::get('evaluator');
        $this->volunteer = Zend_Registry::get('volunteer');

        /* Set Database */
        $this->db = $this->getInvokeArg('bootstrap')->getResource('db');

    }

    public function indexAction()
    {
        if ($this->volunteer) {
            $this->_helper->redirector('calendar', 'my');
        } else {
            $this->_helper->redirector('list');
        }
    }

    
    public function deptaddAction()
    {
        $sid = $_POST['sid'];
        $did = $_POST['did'];
        
        $setDepts = new Application_Model_DbTable_ScheduleDepts;
        $setDepts->addRecordToDept($sid,$did);
        
        $json=array('success' => 'yes');
        $this->_helper->json($json);
    }
    
    public function listAction() 
    {
        if ($this->volunteer) {
            $this->_helper->redirector('calendar', 'my');
        } 
        
        //get existing scheduleSets + pass them to view
        $setTable = new Application_Model_DbTable_ScheduleSets;
        $list = $setTable->getList($this->uid,$this->root);
        
        $addSetForm = new Application_Form_AddScheduleSet(array('uID' => $this->uid));
        
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/confirmDelete.js"></script>' .
                '<script type="text/javascript" src="/js/setCreate.js"></script>' .
                '<script type="text/javascript" src="/js/editDataWithModal.js"></script>' .
                '<script type="text/javascript" src="/js/datePicker.js"></script>' .
                '<script type="text/javascript" src="/js/timepicker/jquery.timepicker.min.js"></script>' .
                '<script type="text/javascript" src="/js/filter.js"></script>'; 
        
        
        $this->view->sets = $list;
        $this->view->count = count($list);
        $this->view->form = $addSetForm;
        $this->view->unlockForm = $unlockForm;
        $this->view->admin = $this->root;
        $this->view->mgr = $this->mgr;
        $this->view->myID = $this->uid;
    }

    
    public function profileAction() {
        if ($this->volunteer) {
            $this->_helper->redirector('calendar', 'my');
        } else {
            //$this->_helper->redirector('list');
        }
        
        //get schedule ID, make sure I'm allowed to see it
        $sID = $this->_getParam('id');
        $setsTable = new Application_Model_DbTable_ScheduleSets;
        $myAllowedSets = $setsTable->getList($this->uid, $this->root, 'ids');
        $permission = (in_array($sID,$myAllowedSets) ? TRUE : FALSE);
        
        if (!$permission) {
            throw new exception("Your access level is not sufficient to view this Schedule Set.");
        }
        
        //pull schedule set 
        $scheduleSet = $setsTable->getSet($sID);

        /*
        
          **pull appointments? not sure whether this will happen via JS later.***
 
        */
        
        //pass record, credentials to view
        $this->view->set = $scheduleSet;
        $this->view->root = $this->root;
        $this->view->mgr = $this->mgr;
        $this->view->uid = $this->uid;
        
        //pass javascripts to view
        $this->view->layout()->customJS = 
                '<script type="text/javascript" src="/js/setHeight.js"></script>' .
                '<script type="text/javascript" src="/js/timeout/js/timeout-dialog.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/dhtmlxscheduler.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_container_autoresize.js"></script>' .
                    '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_cookie.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_csp.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_daytimeline.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_drag_between.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_editors.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_expand.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_grid_view.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_html_templates.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_key_nav.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_layer.js"></script>' .
                '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_limit.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_map_view.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_minical.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_monthheight.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_multisection.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_multiselect.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_multisource.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_mvc.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_offline.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_outerdrag.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_pdf.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_quick_info.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_readonly.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_recurring.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_serialize.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_timeline.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_tooltip.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_treetimeline.js"></script>' .
                    '<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_units.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_url.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_week_agenda.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_wp.js"></script>' .
                    //'<script type="text/javascript" src="/js/scheduler/ext/dhtmlxscheduler_year_view.js"></script>' .
                '<script type="text/javascript" src="/js/abcdScheduler.js"></script>' .
'';
        
        
        
    }
    
}

