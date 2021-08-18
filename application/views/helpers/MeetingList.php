<?php

class Zend_View_Helper_MeetingList extends Zend_View_Helper_Abstract {
    
    public function meetingList($rows = array()) {
        
        $list = "<ul class='meeting-list'>";
        
        foreach ($rows as $meeting) {
            $liTop = "<li id='meeting_" . $meeting['id'] . "' class='relative'>";
            $liBottom = "</li>";
            
            $showNotesLink = "<a href='#' class='showNotes tiny'>Show Notes</a>";
            $hideNotesLink = "<a href='#' class='hideNotes tiny hidden'>Hide Notes</a>";
            
            $preFormatDate = strtotime($meeting['date']);
            $date = date('F j, Y', $preFormatDate);
            
            if (strlen($meeting['enrolledIDs']) > 0) {
                $attArray = explode(',', $meeting['enrolledIDs']);
                $numEnrolled = count($attArray);
            } else {
                $numEnrolled = 0;
            }
            $guests = $meeting['unenrolledCount'];
            $attendance = $numEnrolled + (int)$guests;
            
            if ($attendance != 1) {
                $attendance = "$attendance participants";
            } else {
                $attendance = "1 participant";
            }
            
            if ((int)$guests > 1) {
                $attendance .= ", including $guests guests.";
            } elseif ((int)$guests == 1) {
                $attendance .= ", including 1 guest. ";
            } else {
                $attendance .= ". ";
            }
            
            if ((int)$meeting['volunteers'] > 1) {
                $vols = $meeting['volunteers'] . " volunteers.";
            } elseif ((int)$meeting['volunteers'] == 1) {
                $vols = '1 volunteer.';
            } else {
                $vols = '';
            }
            
            $notes = nl2br($meeting['notes']);
            
            $headDiv = "<div class='headDiv'>
                        <span class='date'> $date </span>
                        <span class='data'> $attendance $vols</span>
                            <div class='sideLinks'> $showNotesLink $hideNotesLink </div>
                        </div>";
            $notesDiv = "<div id='" . $meeting['id'] . "' class='hidden notesDiv'>" . $notes . "</div>";
            
            $liEntry = $liTop . $headDiv . $notesDiv . $liBottom;
            
            $list .= $liEntry;
        }
        
        $list .= "</ul>";
        
        return $list;
    }
   
}
