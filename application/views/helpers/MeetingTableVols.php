<?php

class Zend_View_Helper_MeetingTableVols extends Zend_View_Helper_Abstract {
    
    public function meetingTableVols($rows = array(),$type) {
    if ($type == 'group') {
        $divID = 'groupVolSearch'; 
        $tableID='groupVolAttendance';
    } else {
        $divID = 'progVolSearch';
        $tableID = 'progVolAttendance';
    }
    
    $headSearch = "<div id=$divID></div>";
    $tableTopWrapper    = "<table id='$tableID' class='attndTable volTable formsTable'>";
    $tableBottomWrapper = '</table>';
    
    $table = $tableTopWrapper;
    $table .= "<tr id='headTR'>
                <th class='nameCol'>Name</th>
                <th class='shortCol'>
                        Present?
                        <br/><a href='#' id='checkAllVols' class='tiny'>All</a> / 
                             <a href='#' id='uncheckAllVols' class='tiny'>None</a>
                </th>
                <th class='shortCol'>Job</th>
                <th class='longCol'>Time In / Out</th>
               </tr>";
    
    foreach ($rows as $rowData) {
    
    $pid        = $rowData['id'];      
    $name       = $rowData['firstName'] . ' '. $rowData['lastName'];
    $disableClass = '';
    $smallText  = '';
    
    $nameCell =     "<td class='nameLink nameTD'>
                        <span class='name' id='$pid'>$name</span><br>
                        $smallText
                    </td>";
        
    $attendCell =   "<td class='attendTD'>
                        <span>
                           <input type=checkbox name='vol_attendance[]' value='$pid' />
                        </span>
                     </td>";
    
    $roleCell =     "<td class='roleTD'>
                        <span class='editable-jobs' data-jobid='' data-volid=$pid data-col='vol-job'>
                            <i>--</i>
                        </span>
                       </td>";
    
    $timeCell =     "<td class='noteTD'>
                        <span>
                            From: <input type=text class='volFromTime timepicker'>
                            To: <input type=text class='volToTime timepicker'>
                        </span>
                       </td>";
    
    $tr = "<tr class='$disableClass'> 
            $nameCell 
            $attendCell
            $roleCell
            $timeCell
          </tr>";
    
    $table .= $tr;
    }

    $table .= $tableBottomWrapper;
    
    return $headSearch . $table;
    }
   
}
