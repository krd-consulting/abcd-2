<?php

class DashController extends Zend_Controller_Action
{
    private $auth = NULL;
    private $uid = NULL;
    private $root = FALSE;
    private $mgr = FALSE;
    private $evaluator = FALSE;
    private $role = NULL;
    private $referringFormID = NULL;
    
    public function init()
    {
        $this->auth = Zend_Auth::getInstance();
        $this->uid = $this->auth->getIdentity()->id;
        $this->role = $this->auth->getIdentity()->role;
        if ($this->role == 4) {$this->root = TRUE; $this->mgr = TRUE;};
        if ($this->role == 3) {$this->mgr = TRUE;};
        if ($this->role == 1) {$this->evaluator = TRUE;};
    }

    protected function _process($values) {
        $type = $values['acType'];
        $key = $values['searchkey'];
        
        switch ($type) {
            case 'participant' : $model = new Application_Model_DbTable_Participants; $controller = 'participants'; break;
            case 'group' : $model = new Application_Model_DbTable_Groups; $controller = 'groups'; break;
            case 'staff' : $model = new Application_Model_DbTable_Users; $controller = 'users'; break;
            default: throw new Exception("QuickSearch only works with participants, groups or staff.");      
        }
        
        $result = $model->search($key);
        $num = count($result);
        
        
        switch ($num) {
            case '1' : $id = $result[0]['id'];
                       $this->_helper->redirector('profile', $controller, 'default', array('id' => $id));
                       break;
            case '0' : throw new exception("Could not find a record for $key");
                       break;
            default  : $ids=array(); $i=0;
                       foreach ($result as $multiples) {
                        $ids[$i] = $multiples['id']; $i++;
                       }
                       
                       $this->_helper->flashMessenger->addMessage($ids);
                       $this->_helper->redirector('list', $controller);
            }
         
        
    }
    
    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->_process($_POST);
        } else {
            if ($this->evaluator) {
                $this->_helper->redirector('index','reports');
            }
            $this->showDash();
        }
    }    
    public function showDash() 
    {
       $this->view->layout()->customJS = '<script src="/js/ac.js"></script>';   
                     
        $resources = new Application_Model_DbTable_Resources;
        $dashItems = $resources->fetchAll('dash = 1 and resourceClass <=' . $this->role);
        $search = new Application_Form_Search(array('role' => "$this->role"));
        
        $this->view->items = $dashItems;
        $this->view->search= $search;
             
    }

    private function _filterFormPermissions($data,$formID) {
        $formDeptTable = new Application_Model_DbTable_DeptForms;
        $ptcpDeptTable = new Application_Model_DbTable_ParticipantDepts;
        $goodData = array();
        
        $deptList = $formDeptTable->getList('depts',$formID);
        foreach ($data as $ptcpRec) {
            $ptcpID = $ptcpRec['id'];
            $ptcpDepts = $ptcpDeptTable->getList('depts',$ptcpID);
            if (count(array_intersect($deptList,$ptcpDepts)) > 0) {
                array_push($goodData,$ptcpRec);
            }
        }
        
        return $goodData;
    }
    
    private function _getReferenceResults($table,$field) {
        $db = $this->getInvokeArg('bootstrap')->getResource('db');
        $agency = $db->query("SELECT value from customValues where descriptor='agency'")->fetchAll();
        $agencyName = $agency[0]['value'];
        
        
        if ($this->root) {
            $selectQuery = "SELECT DISTINCT $field FROM $table WHERE $field IS NOT NULL";
        } elseif ($this->mgr) {
            $userDeptTable = new Application_Model_DbTable_UserDepartments;
            $myDepts = $userDeptTable->getList('depts',$this->uid);
            $myDeptsString = implode(",",$myDepts);
            
            $selectQuery = "SELECT DISTINCT $field,deptID FROM $table WHERE $field IS NOT NULL AND deptID in ($myDeptsString)";
            
        } elseif (!$this->evaluator) {
            $myID = $this->uid;
            $selectQuery = "SELECT DISTINCT $field,enteredBy FROM $table WHERE $field IS NOT NULL AND enteredBy = $myID";
        }
        
        $selectQuery .= " AND doNotDisplay = 0";
        
        if ($table == 'form_13' && $this->referringFormID == '18') {
             $selectQuery .= " AND field_24 = 'Active' ";
        }
        $query = $db->query($selectQuery);
        
        return ($query);
    }
    
    public function autocompleteAction()
    {
     $this->filter = $_GET['term'];
     $type = $_GET['type'];
     $db = $this->getInvokeArg('bootstrap')->getResource('db');
     
     $sourceIsForm = FALSE;
     $extraSelect = "";
     $extraFrom = "";
     $extraWhere = "";
     
     $referer = $_SERVER['HTTP_REFERER'];
     if (strpos($referer, "forms/dataentry") == TRUE) {
         $sourceIsForm = TRUE;
         $s = explode("/",$referer);
         $formID = $s[6];
         $extraFrom = ", deptForms df ";
         $extraWhere = "AND pd.deptID = df.deptID AND df.formID = $formID "; 
         $this->referringFormID = $formID;
     }
     
     switch ($type) {
         case 'reference' :
             $form = $_GET['form'];
             //pad with leading 0
             if ($form < 10) {
                 $form = "0" . $form;
             }
             $field = $_GET['field'];
             $table = "form_" . $form;
             
             $select = $this->_getReferenceResults($table, $field);
             
             break;
         
         case 'participant' : 
             $minSelect = 'SELECT firstName, lastName, id, dateOfBirth ';
             $select = $minSelect . $extraSelect;
             
             $minFrom = 'FROM participants p, participantDepts pd, userDepartments ud ';
             $from = $minFrom . $extraFrom;
             
             $minWhere = 'WHERE p.id = pd.participantID AND pd.deptID = ud.deptID AND ud.userID = ' . $this->uid . ' ';
             $where = $minWhere . $extraWhere;
             
             $queryText = $select . $from . $where;

             if ($this->root) {
                if (!$sourceIsForm) {
                    $select = $db->query('SELECT firstName, lastName, id, dateOfBirth from participants p');
                } else {
                    $select = $db->query('SELECT firstName, lastName, id, dateOfBirth '
                                       . 'FROM participants p, participantDepts pd, deptForms df '
                                       . 'WHERE p.id = pd.participantID AND pd.deptID = df.deptID AND df.formID = ' . $formID);
                }
             } else {
                $select = $db->query($queryText);
             }
             
             break;
             
         case 'group' : 
             $select = $db->query('SELECT name,id FROM
                                   groups, userPrograms WHERE
                                   groups.programID = userPrograms.programID AND
                                   userPrograms.userID = ' . $this->uid);
             if ($this->root) {
                 $select = $db->query('SELECT name, id from groups');
             }
             break; 
         
         case 'staff' : 
             $sqlText = "SELECT firstName, lastName, id, eMail 
                         FROM users 
                         WHERE id = $this->uid";
             
             if ($this->mgr) {
                 $sqlText = "
                            SELECT firstName, lastName, id, eMail
                            FROM users, userDepartments
                            WHERE users.id = userDepartments.userID
                            AND EXISTS (
                                        SELECT * FROM userDepartments ud
                                        WHERE userID = $this->uid
                                        AND ud.deptID = userDepartments.deptID
                                        )
                            ";
             }
             
             if ($this->root) {
                 $sqlText = "SELECT firstName, lastName, id, eMail FROM users";
             }
             $select = $db->query($sqlText);
             break;
         
         case 'community' :
             $select = $db->query('SELECT id, name, quadrant from communities');
             break;
         
         default: throw new Exception('QuickSearch only works with participants, groups, and users.');
     }

     $rawValues = $select->fetchAll();
     
//     if ($sourceIsForm) {
//         $rawValues = $this->_filterFormPermissions($rawValues,$formID);
//     }
     
     //Format for JSON
     $values = array();
     $i = 0;
     $extraHead = "<span class=ac-extra>";
     $extraTail = "</span>";

     foreach ($rawValues as $rawValue) {
         if ($type == 'participant' || $type == 'staff') {
            $values[$i]['label'] = $rawValue['firstName'] . ' ' . $rawValue['lastName'];
         } else if ($type == 'reference') {
             $values[$i]['label'] = $rawValue[$field];
         } else {
            $values[$i]['label'] = $rawValue['name']; 
         }
         
         if ($type != 'reference') {
             $values[$i]['value'] = $rawValue['id'];
         } else {
             $values[$i]['value'] = $rawValue[$field];
         }
         
	 if ($type == 'participant') {$extra = "Date of Birth: " . $rawValue['dateOfBirth'];}
	 if ($type == 'staff') {$extra = "Email: " . $rawValue['eMail'];}
	 if ($type == 'community') {$extra = ucfirst($rawValue['quadrant']);}
         if ($type == 'group' || $type == 'reference') {$extra = '';}
        
	 $values[$i]['extra'] = $extraHead. $extra. $extraTail; 
         
         $i++;
     }

     //Filter results
     function match($value)
        {
            $filter = $_GET['term'];
            if(stristr($value['label'],$filter)) return true; 
            return false;
        };
    
     $matchValues = array_filter($values, "match");
     if (count($matchValues) == 0) {
	$matchValues[0]['label']='No valid matches found';
	$matchValues[0]['extra']='';
     }

     
     
     //Return to browser
     $this->_helper->json(array_values($matchValues));
    }
    

}

