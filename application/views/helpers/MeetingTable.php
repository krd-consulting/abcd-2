<?php

class Zend_View_Helper_MeetingTable extends Zend_View_Helper_Abstract {
    
    public function meetingTable($rows = array()) {
    
    $tableTopWrapper    = "<table class='attndTable formsTable'>";
    $tableBottomWrapper = '</table>';
    
    $table = $tableTopWrapper;
    $table .= "<tr id='headTR'>
                <th class='nameCol'>Name</th>
                <th class='shortCol'>
                        Present?
                        <br/><a href='#' id='checkAll' class='tiny'>All</a> / 
                             <a href='#' id='uncheckAll' class='tiny'>None</a>
                </th>
                <th class='shortCol'>Level of Participation</th>
                <th class='longCol'>Volunteer Duties</th>
               </tr>";
    
    foreach ($rows as $rowData) {
    
    $pid        = $rowData['id'];      
    $name       = $rowData['firstName'] . ' '. $rowData['lastName'];
    $disableClass = '';
    $smallText  = '';
    
    $nameCell =     "<td class='nameTD'>
                        <span class='name' id='$pid'>$name</span><br>
                        $smallText
                    </td>";
        
    $attendCell =   "<td class='attendTD'>
                        <span>
                           <input type=checkbox name='attendance[]' value='$pid' />
                        </span>
                     </td>";
    
    $roleCell =     "<td class='roleTD'>
                        <span class='editable' data-id=$pid data-col='level'>
                            <i>--</i>
                        </span>
                       </td>";
    
    $volCell =     "<td class='noteTD'>
                        <span>
                            <input type=checkbox name='volunteers[]' value=$pid/>
                        </span>
                       </td>";
    
    $tr = "<tr class='$disableClass'> 
            $nameCell 
            $attendCell
            $roleCell
            $volCell
          </tr>";
    
    $table .= $tr;
    }

    $table .= $tableBottomWrapper;
    
    return $table;
    }
   
}
