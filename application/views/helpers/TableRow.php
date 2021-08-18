<?php

class Zend_View_Helper_TableRow extends Zend_View_Helper_Abstract {
    
    private $row = NULL;
    private $rowTopWrapper      = '';
    private $rowBottomWrapper   = '</tr>';
    private $rowContent = NULL;
    private $isDefault = '';
    private $myID = NULL;
   
    private function _neat_trim($str, $n, $delim='...') 
    {
        $matches = array();
        $len = strlen($str);
        if ($len > $n) {
            preg_match('/(.{' . $n . '}.*?)\b/', $str, $matches);
            return rtrim($matches[1]) . $delim;
        } else {
            return $str;
        }
    }
    
    public function tableRow($type, $class, $rowData, $viewLinks, $home = FALSE) {
        /*   $type = person or entity - this determines how the name is displayed
         *   $class = participants, users, groups or programs - determines additional default info
         *   $rowData = array holding view data
         *   $viewLinks = array holding the links to display in the row
         */
	/*if (!$this->view->mgr) {
		$rowData = get_object_vars($rowData);
	}*/
        
	if ($home) {
            switch ($type) {
                case 'entity' : $trHome = 'homedept'; break;
                case 'person' : $trHome = 'homerecord'; break;
                default: break;
            }
        } else {
            $trHome = 'default';
        }
        
	$this->rowTopWrapper = "<tr class='$trHome' id=" . $rowData['id'] . ">";

    /*
     * Set appropriate URLs.
     */
    
	
        
    $urls = array();

    if (in_array('profile', $viewLinks)) {
       $urls['profile']   = $this->view->url(
                            array(
                                'controller'  =>  $class,
                                'action'      =>  'profile',
                                'id'          =>  $rowData['id']),
                            null, TRUE);
    }
    
    if (in_array('lock', $viewLinks)) {
       $urls['lock']   = $this->view->url(
                            array(
                                'controller'  =>  $class,
                                'action'      =>  'lock',
                                'id'          =>  $rowData['id']),
                            null, TRUE);
    }
    
    if (in_array('edit', $viewLinks)) {
       $urls['edit'] = "'#'";
    }
    
    if (in_array('unlock', $viewLinks)) {
       $urls['unlock']   = "'#'";
    }
    
    if (in_array('enable', $viewLinks)) {
       $urls['enable']   = "'#'";
    }
    
    if (in_array('setDefault', $viewLinks)) {
       $this->myID = $this->view->dept['id'];
       $urls['setDefault']   = $this->view->url(
                            array(
                                'controller'  =>  $class,
                                'action'      =>  'setdefault',
                                'form'        =>  $rowData['id'],
                                'dept'        =>  $this->myID),
                            null, TRUE);
    }
    
    if (in_array('setHome', $viewLinks)) {
        $urls['setHome'] = "'#'";
    }
    
    if (in_array('enroll', $viewLinks)) {
       $urls['enroll']   = $this->view->url(
                            array(
                                'controller'  =>  $class,
                                'action'      =>  'enroll',
                                'id'       =>  $rowData['id']),
                            null, TRUE);
    }
    
    if (in_array('close', $viewLinks)) {
       $urls['close']   = $this->view->url(
                            array(
                                'controller'  =>  $class,
                                'action'      =>  'close',
                                'group'       =>  $rowData['id']),
                            null, TRUE);
    }
    
    if (in_array('removedefault', $viewLinks)) {
    $this->myID = $this->view->dept['id'];
    $urls['removedefault']   = $this->view->url(
                            array(
                                'controller'  =>  $class,
                                'action'      =>  'cleardefault',
                                'form'        =>  $rowData['id'],
                                'dept'        =>  $this->myID),
                            null, TRUE);
    }
    
    if (in_array('notes', $viewLinks)) {
       $urls['notes']     = $this->view->url(
                            array(
                                'controller' => 'notes',
                                'action'     => $class,
                                'id'         => $rowData['id']),
                            null, TRUE);
    }

    if (in_array('alerts', $viewLinks)) {
       $urls['alerts']    = $this->view->url(
                            array(
                                'controller' => 'alerts',
                                'action'     => $class,
                                'id'         => $rowData['id']), 
                            null, TRUE);
    }
    
    if (in_array('delete', $viewLinks)) {
       $urls['delete']    = $this->view->url(
                            array(
                                'controller' => $class,
                                'action'     => 'delete',
                                'id'         => $rowData['id']),
                            null, TRUE);
    }
    
    if (in_array('enter', $viewLinks)) {
       $urls['enter']    = $this->view->url(
                            array(
                                'controller' => $class,
                                'action'     => 'dataentry',
                                'id'         => $rowData['id']),
                            null, TRUE);

       if (($class == "forms") && ($rowData['target'] == "staff")) {
           $staffTable = new Application_Model_DbTable_Users;
           $staffName = $staffTable->getName($this->view->uid);
           setcookie("staffID",$this->view->uid,0,"/");
           setcookie("staffName",$staffName,0,"/");
       }
       
    }
    
    if (in_array('deptRemove', $viewLinks)) {
       $urls['deptRemove']= $this->view->url(
                            array(
                                'controller' => $class,
                                'action'     => 'deptRemove',
                                'id'         => $rowData['id'],
                                'deptID'     => $this->view->dept['id']),
                            null, TRUE);
    }
    
    if (in_array('reports', $viewLinks)) {
       $urls['reports']= $this->view->url(
                        array(
                            'controller' => 'reports',
                            'action'     => $class,
                            'id'         => $rowData['id']), 
                         null, TRUE);
    }
    
    if (in_array('meeting', $viewLinks)) {
       $urls['meeting']= $this->view->url(
                        array(
                            'controller' => $class,
                            'action'     => 'meetings',
                            'id'         => $rowData['id']), 
                         null, TRUE);
    }
    
    
    /*
     * Set display name; wrap it in tags for CSS.
     */
       
    switch ($type) {
        case 'person' : 
            $displayName = $rowData['firstName'] . ' ' . $rowData['lastName'];
            break;
        case 'entity' : 
            if ($class == 'depts') {
                $displayName = $rowData['deptName'];
            } else {
                $displayName = $rowData['name'];
            }
            break;
        default : 
            throw new Exception ('Cannot create Table Row for type ' . $type);
            break;
    } $displayName = '<div class="pName">' . $displayName . '</div>';
    
    /*
     * Set additional line to be displayed below the name.
     */
    
    switch ($class) {
        case 'participants' :
            $additional = 
            "<div class='dob'>
                    <span class='label'>Date of Birth:</span>
                    <span class='data'>" . $rowData['dateOfBirth'] . " </span>
            </div>";
            if ($rowData['flag']) {
                $class .= ' alert';
            }
            break;
        case 'users' :
            $additional = 
            "<div class='dob'>
                    <span class='label'>E-mail:</span>
                    <span class='data'>" . $rowData['eMail'] . " </span>
             </div>";
            $this->rowTopWrapper="<tr id=" . $rowData['id'] . ">";
            if ($rowData['lock'] == 1) {
                $class .= " locked";
            }
            break;
        case 'forms' :
            switch ($rowData['type']) {
                case 'singleuse': $readableType = 'Single Use'; break;
                case 'prepost'  : $readableType = 'Pre/Post'; break;
                default         : $readableType = 'Unknown';
            }
            
            switch ($rowData['target']) {
                case 'participant': $target = ', participant form'; break;
                case 'staff' : $target = ', staff form'; break;
                case 'group' : $target = ', group form'; break;
            }
            
            $readableType .= $target;
            
            if ($rowData['id'] == $this->view->defaultID) {
                $this->isDefault = 'default';
            } else {
                $this->isDefault = '';
            }
            
            $readableDesc = $this->_neat_trim($rowData['description'], 40);
            
            $additional = 
            "<div class='desc' style='float: left;'>
                <span class='data'>" . $readableDesc . "</span>
             </div>
             <div class='type'>
                <span class='label'>&nbsp;|</span>
                <span class='data'>" . $readableType . "</span></div>";

	    //HACKY TEMP JOB
	    $additional = "";
            break;
        
        case 'programs' :
            if (is_numeric($rowData['deptID'])) {
                $additional = '';
            } else {
                $additional = 
            "<div class='type'>
                <span class='label'></span>
                <span class='data'>" . $rowData['deptID'] . " </span>
            </div>";
            }
            break;

        
        default: $additional = '';
    }
    
    /*
     * Set additional links, each gets its own <td>.
     */
    
    $tdLinks = NULL;
    $viewLinks = array_slice($viewLinks, 1); //no need for a profile link here
    foreach ($viewLinks as $viewLink) {
        $rowUrl = $urls[$viewLink];
        $linkName = $viewLink;
        
        if ($viewLink == 'setDefault') {
            if ($this->isDefault == 'default') {
                $linkName = 'Remove as dept profile';
                $rowUrl = $urls['removedefault'];
            } elseif ($rowData['type'] == 'singleuse') {
                $linkName = 'Set as dept profile';
            } else {
                $linkName = '';
            }
        }
        
        if ($viewLink == 'setHome') {
            if (!$home) {
                $linkName = 'Set Home';
            } else {
                $linkName = 'Unset Home';
            }
        }
        
        if ($viewLink == 'enter') {
            $linkName = 'Enter new data';
        }
        
        if ($viewLink == 'enroll') {
            $linkName = 'View Enrollment';
        }

	if (($viewLink == 'delete') && ($class == 'forms')) {
	   $linkName = 'Disable';
	}
        
	if ($viewLink == 'meeting') {
	   $linkName = 'Add a meeting';
	}
        
        $tdLinks .= "<td class='view-link " . $viewLink . "'>
                     <a 
                        data-id='" . $rowData['id'] . 
                        "' data-type='" . $class . 
                        "' href=" . $rowUrl . ">" . $linkName . "</a>
                     </td>";
     }
    
     if ($trHome == 'homedept') {
         $class .= " homedept ";
     }
    
     
     
    /*
     * Set main name <td>.
     */ 
    $nameLink = '<a href=' . $urls['profile'] . "><div class='table-link $class $this->isDefault'>" . 
                    $displayName . 
                    $additional . 
                 '</div></a>';
    
    $tdName = "<td class='nameLink'>";
    $tdName .= $nameLink;
    $tdName .= '</td>';
       
    
    $this->rowContent = $tdName . $tdLinks;
    
    $this->row = $this->rowTopWrapper . $this->rowContent . $this->rowBottomWrapper;
   
    return $this->row;
    }
   
}
