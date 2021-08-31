<?php

class Zend_View_Helper_FormsTable extends Zend_View_Helper_Abstract {
    
            
    public function formsTable($type, $rows = array()) {
    
    switch ($type) {
        case 'depts':
            $tableHeader = 'Department Name';
             break;
        case 'progs':
            $tableHeader = 'Program Name';
            break;
        case 'funders':
            $tableHeader = 'Funder';
            break;
        case 'forms':
            $tableHeader = 'Form Name';
            break;
    }    
    
    $tableTopWrapper    = "<table class='formsTable' id='$type'>";
    $tableBottomWrapper = '</table>';
    
    $table = $tableTopWrapper;
    $table .= "<tr>
                <th>$tableHeader</th>
                <th>Required</th>
                <th>Repeat Frequency</th>
               </tr>";
                
    foreach ($rows as $rowID => $rowData) {
    
    $did = $rowID;      
    $name = $rowData['name'];
    $required = $rowData['required'];
    $frequency = $rowData['frequency'];
    $disableClass = '';
    $inherit = '';
    
    if (!$this->view->mgr) {
        $disableClass = 'disabled';
    }
    
    if (isset($rowData['inherit'])) {
        $disableClass = 'disabled';
        $inherit = "<span class='ac-extra'>Inherited from " . $rowData['inherit'] . "</span>";
    }
    
    if ($frequency == 'null' || strlen($frequency) < 2) {
        $frequency = "None";
    } else {
        $frequency = "\"" . ucfirst($frequency) . "\"";
    }
    
    if ($required == 1) {
        $requireCheck = " checked='true' ";
    } else {
        $requireCheck = "";
    }
    
    $hiddenCell = "<td class='hidden'><input type='hidden' value='$did'/></td>";
    
    $nameCell =     "<td class='nameTD'>
                        <span class='name' id='$did'>$name</span><br>
                        $inherit
                    </td>";
        
    $requiredCell = "<td>
                        <span>
                           <input type='checkbox' class='isRequired $disableClass' name='isRequired' $requireCheck value='yes'/>
                        </span>
                     </td>";
    
    $frequency = "<td>
                    <span class='dbUpdate dropdown $disableClass'>$frequency</span>
                  </td>";
    
    $tr = "<tr class=$disableClass> $hiddenCell $nameCell $requiredCell $frequency </tr>";
    
    $table .= $tr;
    
    }

    $table .= $tableBottomWrapper;
    
    return $table;
    }
   
}
