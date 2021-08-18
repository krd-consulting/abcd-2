<?php

class Zend_View_Helper_Block extends Zend_View_Helper_Abstract
{
    protected function _blockWrapper($type,$content) {
      if ($type == 'activities') {
          $moreLink = "<a href='#' class='moreRecords tiny'>Show more records</a>";
      } else {
          $moreLink = '';
      }
        
      $title = ucfirst($type);
      $button = "<button class='absolute-right tiny' id='add-$type'>Add $type</button>";
      
      $blockDivTop = "<div class='block' id='$type-block'>";
      $blockDivTitle = "<div class='block-header relative'>
                            $title
                            $button
                        </div>";
      $blockDivMeat = $content;
      
      $blockDivEnd = "</div>";
      
      $blockDiv = $blockDivTop . $blockDivTitle . $blockDivMeat . $moreLink . $blockDivEnd;
      return $blockDiv;
  }
    
  protected function _liAlertWrapper($row) {
      $removeImg =     '<img class="sprite-pic remove-pic" align="right" src="/skins/default/images/blank.gif">';

      if ($row['type'] == 'system') {
          $formID = $row['formID'];
          $listItem = "<li class='relative'><a href='/forms/dataentry/id/$formID'>" . $row['text'] . "</a></li>";
      } else {
        $listItem = "<li class='relative deletable' id='" . $row['id'] . "'>
                        <span class='float-left w90'>" 
                            . $row['text'] . 
                        "</span>" 
                        . $removeImg . 
                    "</li>";  
      }
      return $listItem;
  }
    
  protected function _liActWrapper($row,$hidClass = '') {
    $showNotesLink = "<a href='#' class='showNotes tiny'>Show Notes</a>";
    $hideNotesLink = "<a href='#' class='hideNotes tiny hidden'>Hide Notes</a>";      
    $pid = $this->view->participant['id'];
         
      if (array_key_exists('groupName',$row)) {
          $id = $row['meetingID'];
          $type = 'in ' . $row['groupName'];
          $ajaxID = $pid.'-'.$id;
      } else {
          $id = $row['userID'];
          $type = 'with ' . $row['staffName'];
          $ajaxID = $pid.'-'.$id.'-user';
      }  
      
      $listItem = "<li class='relative $hidClass' id='$id'>"
                        . "<h3>" . $row['date'] . "</h3>"
                        . $row['duration'] . " hours " . $type . "."
                        . $showNotesLink . $hideNotesLink
                        . "<div id='$ajaxID' style='width: 100%!important' class='hidden notesDiv'>" . $row['note'] . "</div>"
                    . "</li>";  
      
      return $listItem;
  }
  
  protected function _getAlerts($pid)
  {
      $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
      $alerts = $ptcpAlerts->getPtcpAlerts($pid);
      return $alerts;
  }
  
  protected function _getActivities($pid) 
  {
      $ptcpMeetings = new Application_Model_DbTable_ParticipantMeetings;
      $activities = $ptcpMeetings->getPtcpMeetings($pid);
      return $activities;
  }
  
  public function block($type, $pid)
  {
	$validTypes = array('alerts', 'activities');
        if (!in_array($type, $validTypes)) {
            throw new exception("Can't draw a '$type' block.");
        }
        
        $list = "<ul>";
        
        switch ($type) {
            case 'alerts': 
                $dataArray = $this->_getAlerts($pid);
                foreach ($dataArray as $row) {
                    $liEntry = $this->_liAlertWrapper($row);
                    $list .= $liEntry;
                }
                break;
            case 'activities':
                $dataArray = $this->_getActivities($pid);
                $maxCount = 3;
                foreach ($dataArray as $key => $row) {
                    if ($key < $maxCount) {
                        $liEntry = $this->_liActWrapper($row);
                    } else {
                        $liEntry = $this->_liActWrapper($row,'hidden');
                    }
                    $list .= $liEntry;
                }   
                break;
            default:
                break;
        }
        
        $list .= "</ul>";
        $content = $this->_blockWrapper($type,$list);
        return $content;
  }
}
