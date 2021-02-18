<?php

class Zend_View_Helper_MeetingList extends Zend_View_Helper_Abstract {
    
    public function meetingList($rows = array()) {
        
        $list = "<ul class='meeting-list'>";
        
        foreach ($rows as $meeting) {
            //print_r($meeting);
            $attendance = "";
            
            $liTop = "<li id='meeting_" . $meeting['id'] . "' class='relative'>";
            $liBottom = "</li>";
            
            $showNotesLink = "<a href='#' class='showNotes tiny'>Show Notes</a>";
            $hideNotesLink = "<a href='#' class='hideNotes tiny hidden'>Hide Notes</a>";
            
            $preFormatDate = strtotime($meeting['date']);
            $date = date('F j, Y', $preFormatDate);
            
            if (strlen($meeting['eventName']) > 0) {
                $eventName = $meeting['eventName'];
            }      else {
                $eventName = "";
            }  
            
            //Count participants
            if (strlen($meeting['enrolledIDs']) > 0) {
                $attArray = explode(',', $meeting['enrolledIDs']);
                $numEnrolled = count($attArray);
            } else {
                $numEnrolled = 0;
            }
            $guests = $meeting['guestCount'];
            $ptcpCount = $numEnrolled + (int)$guests;
            
            //Count volunteers
            if (strlen($meeting['volunteerIDs']) > 0) {
                $volArray = explode(',', $meeting['volunteerIDs']);
                $numVols = count($volArray);
            } else {
                $numVols = 0;
            }
            $guestVols = $meeting['nonVolVols'];
            $volCount = $numVols + (int)$guestVols;
            
            
            
            if ($ptcpCount > 1) {
                $attendance = "$ptcpCount participants";
            } elseif ($ptcpCount == 1) {
                $attendance = "1 participant";
            } else {
                $attendance = "";
            }
            
            if ((int)$guests > 1) {
                $attendance .= ", including $guests guests.";
            } elseif ((int)$guests == 1) {
                $attendance .= ", including 1 guest.";
            } else {
                $attendance .= "";
            }
            
            if (strlen($attendance) > 0) {
                $attendance .= "<br>";           
            }
            
            if ($volCount > 1) {
                $attendance .= " $volCount volunteers";
            } elseif ($volCount == 1) {
                $attendance .= " $volCount volunteer";
            } else {
                $attendance .= "";
            }
            
            if ((int)$guestVols > 1) {
                $attendance .= ", including $guestVols guest volunteers.";
            } elseif ((int)$guestVols == 1) {
                $attendance .= ", including 1 guest volunteer.";
            } else {
                $attendance .= "";
            }
            
            if ($volCount + $ptcpCount == 0) {
                throw new exception("No participants recorded for group meeting id " . $meeting['id'] . ".");
            }
            
            $notes = nl2br($meeting['notes']);
            
            $headDiv = "<div class='headDiv'>
                        <div id='leftpart'>
                            <span class='date'> $date <br> "
                                . "<font size='3pt'>" . $meeting['duration'] . " hours <br> $eventName</font></span>    
                        </div>
                        <div id='rightpart'>
                            <span class='data'> $attendance $vols</span>
                             <div class='sideLinks'> $showNotesLink $hideNotesLink </div>
                        </div>
                        </div>";
            $notesDiv = "<div id='" . $meeting['id'] . "' class='hidden notesDiv'>" . $notes . "</div>";
            
            $liEntry = $liTop . $headDiv . $notesDiv . $liBottom;
            
            $list .= $liEntry;
        }
        
        $list .= "</ul>";
        
        return $list;
    }
   
}
