<?php


class Application_Model_DbTable_ProgramJobs extends Zend_Db_Table_Abstract {
    protected $_name = 'programJobs';
    
    public function getList($column,$id) /* $column = 'progs' or 'jobs' */ 
    {
        switch ($column) {
            case 'progs' : 
                $select     = "jobID = " . (int)$id;
                $colname    = "programID";
                break;
            case 'jobs' :
                $select     = "programID = " . (int)$id;
                $colname    = "jobID";
                break;
            default : throw new Exception("\"$column\" is not a valid option.");
        }

        $rowset = $this->fetchAll($select)->toArray();
        $results = array();
        foreach ($rowset as $row) {
            array_push($results, $row[$colname]);
        }
        return $results;
    }
    
    public function getRecord ($jobID,$progID) {
        $record = $this->fetchRow("jobID = $jobID AND programID = $progID");
        return $record->toArray();
    }
    
    public function addJobToProg($jobID,$progID) {
        $data = array(
            'jobID' => (int)$jobID,
            'programID' => (int)$progID
        );
        $this->insert($data);
    }
    
    public function delJobFromProg($jobID,$progID) {
        $select = "programID = " . (int)$progID . " and jobID = " . (int)$jobID;
        $this->delete($select);
    }
} 