<?php

class Application_Model_DbTable_VolunteerJobs extends Zend_Db_Table_Abstract {
    protected $_name = 'volunteerJobs';
    
     public function getName($id) {
        $row = $this->fetchRow("id = $id");
        $name = $row['name'];
        return $name;
    } 
    
    public function getJobsByProgram($progID) {
        $progJobTable = new Application_Model_DbTable_ProgramJobs;
        $jobIDs = $progJobTable->getList('jobs',$progID);
        $result = array();
        
        foreach ($jobIDs as $jobID) {
            $record = $this->getRecord($jobID);
            array_push($result,$record);
        }
        return $result;
    }
    
    public function getRecord($id) {
        $result = $this->fetchRow("id = $id");
        $resultNum = count($result);
        if ($resultNum > 0) {
                $row = $result->toArray();
        } else {
                $row = array();
        }
        return $row;
    }
    
    public function getIDs() {
        $result = array();
        $set = $this->fetchAll()->toArray();
        foreach ($set as $row) {
            array_push($result,$row['id']);
        }
        
        return $result;
    }
    
    public function addRecord($title,$description,$userID) {
        $data = array(
            'name' => $title,
            'description' => $description,
            'updatedBy' => $userID
        );
        
        $recordID = $this->insert($data);
        return $recordID;
    }
    
    public function updateRecord($id, $userID, $title='', $description='')
    {
        $data = array(
            'updatedBy' => $userID
        );
        
        if (strlen($title) > 0) {
            $data['title'] = $duration;
        }
        
        if (strlen($description) > 0) {
            $data['description'] = $description;
        }
        
        if (count($data) == 1) {
            throw new exception ("Nothing to update activity record with!");
        } else {
            $this->update($data, "id = $id");
        }
    }
}