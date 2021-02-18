<?php

class Zend_View_Helper_PtcpGroupTable extends Zend_View_Helper_Abstract {
    
    protected function _generateList($groups = array()) {
        $header = "<ul class='meeting-list'>";
        $footer = "</ul>";
        $meat = '';
        
        $showNotesLink = "<a href='#' class='showNotes tiny'>Show Notes</a>";
        $hideNotesLink = "<a href='#' class='hideNotes tiny hidden'>Hide Notes</a>";
        
        $showDetailsLink = "<a href='#' class='showDetails tiny'>Show Details</a>";
        $hideDetailsLink = "<a href='#' class='hideDetails tiny hidden'>Hide Details</a>";
        
        
        foreach ($groups as $record) {
            //Print header info here
            $groupLiHead = "<li id=" . $record['id'] . " class='relative'>";
            $groupLi = "<span class='date'>" 
                        . "<a href='/groups/profile/id/" . $record['id'] . "'>"
                        . $record['name'] 
                        . "</a>"
                        . "</span>";
            if (array_key_exists('meetings', $record)) {
                $numMtgs = count($record['meetings']);
            } else $numMtgs = 0;
            
            if ($numMtgs == '1') {
                $word = ' meeting';
            } else {
                $word = ' meetings';
            }
            
            $ptcpLevels = array(
                'passive'   =>  'In Attendance',
                'contrib'   =>  'Active Contributor',
                'leadrole'  =>  'Leadership Role'
            );
            
            //$volLevels = array('', 'Volunteer Role');
            
            if ($numMtgs > 0) {
                $groupLi .= "<span class='data'>" . $numMtgs . $word . $showDetailsLink . $hideDetailsLink;
                $meetingUL = "<ul class='meeting-data hidden relative'>";
                foreach($record['meetings'] as $meeting) {
                    if ($meeting['duration'] == 1) {
                        $duration = '1 hour';
                    } else {
                        $duration = $meeting['duration'] . ' hours';
                    }
                    
                    $noteID = $this->view->participant['id'] . '-' . $meeting['id'];
                    
                    $meetingUL .= "<li id=" . $meeting['id'] . " class='draggable'>";
                    $meetingUL .= "<h3>" . $meeting['date'] ."</h3>";
                    $meetingUL .= "<span>" . $ptcpLevels[$meeting['level']] . " for " . $duration . "</span>";
                    $meetingUL .= "$showNotesLink $hideNotesLink";
                    $meetingUL .= "<div id=$noteID class='hidden notesDiv'>" . $meeting['note'] . "</div>";
                }
                $meetingUL .= "</ul>";
                $groupLi .= $meetingUL . "</span>";
            }
            $groupLiTail = "</li>";
            $row = $groupLiHead . $groupLi . $groupLiTail;
        
            $meat .= $row;
        }
             
        
        $result = $header . $meat . $footer;
        return $result;
    }
    
    public function ptcpGroupTable($groups = array()) {
        $addPtcpToGroupUrl = $this->view->url(array(
                               'controller'  =>'participants',
                               'action'      =>'associate',
                               'type'        =>'group',
                               'id'          => $this->view->participant['id']));
        
        $ptcpAddMore = '<a class="add-link" href=' . $addPtcpToGroupUrl . '>
                            Enroll in other groups
                        </a>
                ';

        $bottomLinks = "<div class='bottom-links'> $ptcpAddMore </div>";

        if (count($groups) > 0) {
            $result = $this->_generateList($groups);
        } else {
            $result = "<h2>" . $this->view->pName . " is not enrolled in any groups yet.</h2>";
        }
        
        $result .= $bottomLinks;
        
        return $result;
    }
   
}
