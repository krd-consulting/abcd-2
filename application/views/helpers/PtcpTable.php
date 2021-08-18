<?php

class Zend_View_Helper_PtcpTable extends Zend_View_Helper_Abstract {
    
            
    public function ptcpTable($type, $typeID, $rows = array()) {
    
    $validTypes = array('ptcp', 'prog', 'caseload');
    
    if (!in_array($type,$validTypes)) {
        throw new exception ("Can't build a participant table for a $type");
    }
    
    $tableTopWrapper    = "<table class='formsTable ptcpTable' id='$type'>";
    $tableBottomWrapper = '</table>';
    
    switch ($type) {
        case 'ptcp':
        case 'caseload':
            $headName = 'Name';
            $headSearch = '<div id=programPtcpSearch></div>';
            $headTwo = $this->view->filterForm;
            $headThree = 'Note';
            $setRType = 'ptcp';
            break;
        case 'prog':
            $headName = 'Program';
            $headSearch = '';
            $headTwo = 'Status';
            $headThree = 'Note';
            $setRType = 'prog';
            break;
        default:
            break;
    }
    
    $table = $tableTopWrapper;
    $table .= "<tr id='headTR'>
                <th class='nameCol'>    $headName   </th>
                <th class='shortCol'>   $headTwo    </th>
                <th class='longCol'>    $headThree  </th>
               </tr>";
    
    $trid = 1;
    foreach ($rows as $rowID => $rowData) {
    
        if (($type == 'ptcp') || ($type == 'caseload')) {
            $pid        = $rowID;      
            $progID     = $typeID;
            $ptcpID     = $pid;
            $rawName    = $rowData['name'];
            $name       = "<a href='/participants/profile/id/" . $pid . "'>" . $rawName . "</a>";
            $status     = $rowData['status'];
            $statusNote = $rowData['statusNote'];
            $disableClass = '';
            $linkClass = 'changeStatus';
            $smallText =  "<span class='ac-extra'>Date of Birth: " . $rowData['dob'] . "</span>";
            if (array_key_exists('statusProg',$rowData)) {
                $sinceText =  "<span class='ac-extra'>" . $rowData['statusProg'] . ", since ". $rowData['since'] . "</span>";
            } else {
                $sinceText = "<span class='ac-extra'> Since " . $rowData['since'] . "</span";
            }
        } elseif ($type == 'prog') {
            $pid = $rowID;
            $progID = $pid;
            $ptcpID = $typeID;
            $name = $rowData['name'];
            $status = $rowData['enroll']['status'];
            $enrolledSince = $rowData['enroll']['enrollDate'];
            $enrolledSince = date("F j, Y", strtotime($enrolledSince));
            $statusNote = $rowData['enroll']['statusNote'];
            $statusDate = $rowData['enroll']['statusDate'];
            $statusDate = date("F j, Y", strtotime($statusDate));
            $disableClass = '';
            $linkClass = 'ptcpStatus';
            $smallText = "<span class='ac-extra'>Enrolled on $enrolledSince </span>";
            $sinceText = "<span class='ac-extra'>Since $statusDate </span>";
        }
        $status = ucfirst($status);
        if ($statusNote == '') {
            $statusNote = 'None';
        }
        if ($status == 'Leave') {
            $status = 'On Leave';
        }
        
        if (array_key_exists('statusProgID',$rowData)) {
            $spid = $rowData['statusProgID'];
            $statusProgIdText = " data-statusprogid='$spid'";
        } else {
            $statusProgIdText = FALSE;
        }
        
        if ($rowData['caseload']) {
            $changeStatusClass = "'changeStatus off'";
            $assignedDate = date("F j, Y",strtotime($rowData['assignedToDate']));
            $sinceText = "<span class='ac-extra'>Assigned to " . 
                    "<a href=/users/profile/id/" . $rowData['assignedToID'] . ">" .
                    $rowData['assignedTo'] . "</a><br> as of " . $assignedDate .
                    "</span>";
        } else {
            $changeStatusClass = "'changeStatus'";
        }
        
        $hiddenCell = "<td class='hidden'><input type='hidden' value='$pid'/></td>";

        $nameCell =     "<td class='nameTD nameLink'>
                            <span class='name' data-rtype='$setRType' data-rid='$pid' id='$pid'>$name</span><br>
                            $smallText
                        </td>";

        $statusCell =    "<td class='statusTD'>
                            <span>
                            <a class=$changeStatusClass 
                               data-ptcpid='$ptcpID'
                               data-progid='$progID' " . 
                                $statusProgIdText . 
                               " href='#'> $status </a>
                            <br/>
                            $sinceText
                            </span>
                        </td>";

        $statusNoteCell = "<td class='noteTD'>
                            <span>
                                <a href='#' 
                                   class='changeNote'
                                   data-ptcpid='$ptcpID'
                                   data-progid='$progID'" . $statusProgIdText  
                                . ">$statusNote</a>
                            </span>
                        </td>";

        $tr = "<tr id='$trid' class='$disableClass'> 
                $hiddenCell 
                $nameCell 
                $statusCell 
                $statusNoteCell 
            </tr>";

        $table .= $tr;

        $trid++;
    }

    $table .= $tableBottomWrapper;
    
    return $headSearch . $table;
    }
   
}
