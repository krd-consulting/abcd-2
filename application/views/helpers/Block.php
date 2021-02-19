<?php

class Zend_View_Helper_Block extends Zend_View_Helper_Abstract 
{
    private $readOnly = FALSE;
    
    protected function _blockWrapper($type,$content) {
      $noLinkTypes = array('alerts','upcoming','needs');

      $noButtonIfReadOnlyTypes = array('alerts'); 
      
      if (in_array($type,$noLinkTypes)) {
          $moreLink = '';
      } else {
          $moreLink = "<a href='#' class='moreRecords tiny'>Show more records</a>";
      }
        
      if ($type == 'upcoming') {
          $title = "Upcoming Commitments";
          $button = "<button class='absolute-right tiny' id='schedule-upcoming'>View full schedule</button>";
      } elseif ($type == 'needs') {
          $title = "Help Needed";
          $button = '';
      } else {
          $title = ucfirst($type);
          $button = "<button class='absolute-right tiny' id='add-$type'>Add $type</button>";
      }
      
      if (in_array($type,$noButtonIfReadOnlyTypes) && $this->readOnly) {
          $button = "";
      }
      
      
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
    
  protected function _liAlertWrapper($row, $dataType='') {
      if (!$this->readOnly) {
          $removeImg =     '<img class="sprite-pic remove-pic" data-type="' . $dataType . '" align="right" src="/skins/default/images/blank.gif">';
      } else {
          $removeImg = "";
      }
      
      if ($row['type'] == 'system') {
          $formID = $row['formID'];
          if (!$this->readOnly) {
              $listItem = "<li class='relative'><a href='/forms/dataentry/id/$formID'>" . $row['text'] . "</a></li>";
          } else {
              $listItem = "<li class='relative'>" . $row['text'] . "</li>";
          }
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
    
  protected function _liEventWrapper($row, $hidClass ='') {
      $listItem = "<li class='relative $hidClass'>"
              . "<h3>" . $row['printDate'] . "</h3>"
                . "<ul class='plain-block'>"  
                  . "<li>" . $row['printTime'] . "</li>"
                  . "<li><b>" . $row['name'] . "</b> - <i>" . $row['job'] . "</i></li>"
                  . "<li><b>Location:</b> " . $row['location'] . "</li>"
                . "</ul>"
            . "</li>";
      return $listItem;
  }
  
  protected function _liProgNeedWrapper($row) {
      $listItem = "<li class ='relative'>"
              . "<h3>" . $row['progName'] . "</h3>"
              . "<button data-progid='" . $row['progID'] . "' class='absolute-right tiny progCalendar'> View Calendar </button>"
              . "<br><ul class='plain-block'>";
      foreach ($row as $eventListing) {
          if (is_array($eventListing)) {
            
            $name = $eventListing['name'];
            $needs = $eventListing['needs'];
            $date = $eventListing['date'];

            $listItem .= "<b>$date - $name</b><li><i>Needed:</i> <br> $needs</li>";
          } else {
            continue;
          }
      }
      $listItem .= "</ul></li>";
      return $listItem;
              
  }
  
  protected function _liActWrapper($row,$hidClass = '') {
    //print_r($row);
    $displayName = "";
    $showNotesLink = "<a href='#' class='showNotes tiny'>Show Notes</a>";
    $hideNotesLink = "<a href='#' class='hideNotes tiny hidden'>Hide Notes</a>";      
    $pid = $this->view->participant['id'];
    
    
    
      if (array_key_exists('groupName',$row)) {
          $id = $row['meetingID'];
          $displayName = 'in ' . $row['groupName'];
          $ajaxID = $pid.'-'.$id;
      } elseif (array_key_exists('userID',$row)) {
          $id = $row['userID'];
          $displayName = 'with ' . $row['staffName'];
          $ajaxID = $pid.'-'.$id.'-user';
      } elseif (!array_key_exists('fromTime',$row)) {
          $id = $row['typeID'];
          $entType = $row['type'];
          if ($entType == 'participant') {
              $ptcpTable = new Application_Model_DbTable_Participants;
              $name = $ptcpTable->getName($id);
          }
          $displayName = "with $name";
          $ajaxID = $pid . '-' . $id . '-user';
        }
      
      
      //volunteers data looks different, just a hacky way here
      if (array_key_exists('fromTime',$row)) {          
          $name = $row['typeName'];
          $id = $volID = $row['volunteerID'];
          $entType = $row['type'];
          $entID = $row['typeID'];
          switch ($entType) {
              case 'group': $connector = " in "; break;
              case 'participant': $connector = " with "; break;
          }
          $displayName = $connector . $name;
          $ajaxID = $row['id'] . "-vol";
          
            if ($this->view->mgr) {
                $removeImg =     '<img class="sprite-pic remove-pic" ' . 
                                 'data-type="vol-act" data-id="' . $row['id'] . '" ' 
                                 . 'align="right" src="/skins/default/images/blank.gif">';
            } else {
                $removeImg = "";
            }
          
      }
        
      if (array_key_exists("note",$row)) {
          $note = $row['note'];
      } elseif (array_key_exists("description",$row)) {
          $note = $row['description'];
      } else {
          $note = "";
      }
      
      $listItem = "<li class='relative $hidClass' id='$id'>"
                        . $removeImg . "<h3>" . $row['date'] . "</h3>" 
                        . $row['duration'] . " hours " . $displayName . "."
                        . $showNotesLink . $hideNotesLink
                        . "<div id='$ajaxID' style='width: 100%!important' class='hidden notesDiv'>" . $note . "</div>"
                     . "</li>";  
      
      return $listItem;
  }
  
  protected function _liFileWrapper($row,$hidClass='') {
      extract($row);
      $dlLink = "<button class='right tiny download-file' data-id='$id'>Download</button>";
      $deleteLink = "<button class='right tiny archive-file' data-id='$id'>Archive</button>";
      
      $listItem = 
          "<li class='relative $hidClass' data-id='$id'>"
              . "<h3 class='float-left'> $description </h3>" . $dlLink . $deleteLink 
          . "</li>";
      
      return $listItem;
  }
  
  
  
  protected function _getCommitments($eid,$entity='vol') {
      if (($entity != 'vol') && ($entity != 'volunteer')) {
          throw new exception ("Invalid entity $entity passed to Commitments block.");
      }
      
      $programEventsTable = new Application_Model_DbTable_ProgramEventSignups;
      
      $myProgEvents = $programEventsTable->getUpcomingEvents($eid);
      return $myProgEvents;
      
  }
  
  protected function _getEventNeeds($eid,$entity='vol') {
      if (($entity != 'vol') && ($entity != 'volunteer')) {
          throw new exception ("Invalid entity $entity passed to Commitments block.");
      }
      
      $userProgsTable = new Application_Model_DbTable_UserPrograms;
      $progsTable = new Application_Model_DbTable_Programs;
      $programEventsTable = new Application_Model_DbTable_ProgramEvents;
      
      $myProgs = $userProgsTable->getList('progs',$eid);
      $myNeeds = array();
      $thinking = "";

      foreach ($myProgs as $progID) {
          unset($jobListing,$stillNeeded,$progName,$progNeeds);
          $progName = $progsTable->getName($progID);
          
          $progNeeds = array(
              'progID' => $progID,
              'progName' => $progName
          );
          
          $progEvents = $programEventsTable->getProgEvents($progID,$eid,date("Y-m-d"));
          foreach ($progEvents as $event) {
              $jobListing = '';
              $eventID = $event['id'];
              $needs = $programEventsTable->getEventNeeds($eventID,$eid);
              
              $eventName = $event['name'];
              $eventNeeds = array(
                  'id' => $eventID,
                  'name' => $eventName,
                  'date' => date("Y-m-d",strtotime($event['startDate'])),
                  'needs' => ''
              );
              
              foreach($needs['needDetails'] as $jobList) {
                  $nc = (int)$jobList['neededCount'];
                  $sc = (int)$jobList['signedUpCount'];
                  $stillNeeded = $nc - $sc;
                  
                  if ($stillNeeded < 1) {
                      continue;
                  } else {
                      $jobListing .= $jobList['jobName'] . ": $stillNeeded</br>" ;
                      $eventNeeds['needs'] = $jobListing;
                  }
              }
              array_push($progNeeds,$eventNeeds);
//              if ($stillNeeded > 0) {array_push($progNeeds,$eventNeeds);}
          }
          array_push($myNeeds,$progNeeds);
      }
  
      return $myNeeds;
  }
  
  protected function _getAlerts($pid, $type='ptcp') {
      if ($type =='ptcp') {
            $ptcpAlerts = new Application_Model_DbTable_AlertsParticipants;
            $alerts = $ptcpAlerts->getPtcpAlerts($pid);
      }
      
      if ($type == 'vol') {
          $volAlerts = new Application_Model_DbTable_AlertsVolunteers;
          $alerts = $volAlerts->getVolAlerts($pid);
          //throw new exception ("Looking for alerts for $type id $pid");
      }
      
      return $alerts;
  }
  
  protected function _getActivities($pid, $type) 
  {
      switch ($type) {
            case 'ptcp': $ptcpMeetings = new Application_Model_DbTable_ParticipantMeetings;
                         $activities = $ptcpMeetings->getPtcpMeetings($pid);
                         break;
            case 'vol':  $volActivities = new Application_Model_DbTable_VolunteerActivities;
                         $activities = $volActivities->getTypeActivities('vol', $pid);
      }
      return $activities;
  }
  
  protected function _getFiles($type,$pid)
  {
      $filesTable = new Application_Model_DbTable_Files;
      $files = $filesTable->getFileList($type, $pid);
      return $files;
  }
  
  public function block($type, $pid, $entity='ptcp', $readOnly = FALSE)
  {
	$this->readOnly = $readOnly;
        
        $validTypes = array('alerts', 'activities', 'files', 'upcoming', 'needs');
        if (!in_array($type, $validTypes)) {
            throw new exception("Can't draw a '$type' block.");
        }
        
        $list = "<ul>";
        
        switch ($type) {
            case 'alerts': 
                $dataArray = $this->_getAlerts($pid,$entity);
                foreach ($dataArray as $row) {
                    $liEntry = $this->_liAlertWrapper($row,$entity);
                    $list .= $liEntry;
                }
                break;
            case 'activities':
                $dataArray = $this->_getActivities($pid,$entity);
                $maxCount = 5;
                foreach ($dataArray as $key => $row) {
                    if ($key < $maxCount) {
                        $liEntry = $this->_liActWrapper($row);
                    } else {
                        $liEntry = $this->_liActWrapper($row,'hidden');
                    }
                    $list .= $liEntry;
                }   
                break;
            case 'needs':
                $dataArray = $this->_getEventNeeds($pid,$entity);
                
                foreach ($dataArray as $row) {
                    if(strpos(json_encode($row),"needs") > 0) { //events with no needs do not need to be listed
                        $liEntry = $this->_liProgNeedWrapper($row);
                        $list .= $liEntry;
                    } else {
                        continue;
                    }                    
                }
                
                break;
                
            case 'upcoming':
                $count = 0;
                $dataArray = $this->_getCommitments($pid,$entity);
                $maxCount = 3;
                foreach ($dataArray as $row) {
                    if ($count < $maxCount) {
                        $liEntry = $this->_liEventWrapper($row);
                    } else {
                        $liEntry = $this->_liEventWrapper($row,'hidden');
                    }
                    $list .= $liEntry;
                    $count++;
                    
                }
                break;
            case 'files':
                $dataArray = $this->_getFiles($entity,$pid);
                $maxCount = 5;
                foreach ($dataArray as $key => $row) {
                    if ($key < $maxCount) {
                        $liEntry = $this->_liFileWrapper($row);
                    } else {
                        $liEntry = $this->_liFileWrapper($row,'hidden');
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
