<?php

class Zend_View_Helper_StoredReport extends Zend_View_Helper_Abstract {
    
            
    public function storedReport() {
    $type="sr"; //storedreports
    $rows = $this->view->reports;
    $goodIDs = $this->view->allowedIDs;
    $userTable = new Application_Model_DbTable_Users;
    
    //set up "Add New" button
    $srAddMore = "<div>
                    <button style='display: block; margin: 40px 0' id='addStoredReport'>
                      Build Stored Report
                    </button>
                  </div>";
    
    if (count($rows) == 0) {
        $emptyHeader = "<h2>No stored reports are available yet.</h2>";
        return $emptyHeader . $srAddMore;
    }
    
    //get the filter dropdown
    $filterForm = new Application_Form_FilterFormFreq();

    //Table classes and ID
    $tableTopWrapper    = "<table class='formsTable ptcpTable' id='$type'>";
    $tableBottomWrapper = '</table>';
    
    //table headers for <th>
    $headName = 'Name';
    $headSearch = '<div id=reportSearch></div>';
    $headTwo = $filterForm;
    $headThree = 'Report Options';
    $headFour = 'Delete';
    
    //Iterate table building
    $table = $tableTopWrapper;
    $table .= "<tr id='headTR'>
                <th class='nameCol'>    $headName   </th>
                <th class='shortCol'>   $headTwo    </th>
                <th class='longCol'>    $headThree  </th>
                <th class='shortCol'>   $headFour   </th>
               </tr>";
    
    $trid = 1;
    
    foreach ($rows as $rowID => $rowData) {
        
            $sequenceID        = $rowID;      
            $reportID   = $rowData['id'];
            $rawName    = $rowData['name'];
//          $name       = "<a href='/reports/profile/id/" . $reportID . "'>" . $rawName . "</a>";
            $name       = $rawName;
            $creator    = $userTable->getName($rowData['updatedBy']);
            $freq       = $rowData['frequency'];
            $options    = json_decode($rowData['includeOptions'],TRUE);
            $disableClass = '';
            $linkClass = 'changeStatus';
            $sinceText =  "<span class='ac-extra'>Created by " . $creator . "</span>";
            $status     = '';
        
        $displayFreq = ucfirst($freq);
        $displayOptions = '';
        
        if (in_array($reportID,$goodIDs)) {
            $status = "<button class='deleteButton' id='$reportID'>Delete</button>";
        }
            
        foreach ($options as $line) {
            $displayName = $line['name'];
            switch ($line['subtype']) {
                case 'prgs': $displayType = 'program'; break;
                case 'grps': $displayType = 'group'; break;
                case 'forms': $displayType = 'form'; break;
            }
            $myLine = $line['level'] . " for " . $displayName . " " . $displayType;            
            $displayOptions .= $myLine . "<br>";
        }        
        
        
        
        $hiddenCell = "<td class='hidden'><input type='hidden' value='$reportID'/></td>";

        $nameCell =     "<td class='nameTD nameLink'>
                            <span class='name' data-rtype='$type' data-rid='$reportID' id='$sequenceID'>$name</span>
                                <br>
                                $sinceText
                        </td>";

        $freqCell =    "<td class='freqTD'>
                            <span>
                             <a class='changeFreq' 
                               data-reportID='$reportID'
                               href='#'> $displayFreq </a>
                            </span>
                        </td>";

        $optionsCell = "<td class='optionsTD'>
                            <span>
                                $displayOptions
                            </span>
                        </td>";
        
        $statusCell = "<td class='statusTD'>
                            <span>
                                $status
                            </span>
                        </td>";
        
        $tr = "<tr id='$trid' class='$disableClass'> 
                $hiddenCell 
                $nameCell 
                $freqCell 
                $optionsCell
                $statusCell
            </tr>";

        $table .= $tr;

        $trid++;
    }

    $table .= $tableBottomWrapper;
    
    $footer = $srAddMore;
    
    return $headSearch . $table . $footer;
    }
   
}
