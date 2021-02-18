<?php

class Application_Model_DbTable_StoredReports extends Zend_Db_Table_Abstract
{

    protected $_name = 'storedReports';
    private $uid,$mgr,$root;
    
    public function init() {
    }
    
    public function getName($id) {
        $row = $this->fetchRow("id = $id");
        $name = $row['name'];
        return $name;
    }
        
    public function search($key) {
        $name = $key;
        
        if (!is_null($name)) {        
            $select = $this->select()->where('name like \'%' . $name . '%\'');
        } 
        $rowset = $this->fetchAll($select);
        return $rowset;
    }

    public function getIDs() {
        $row = $this->fetchAll();
        $result = array();
        foreach ($row as $srRecord) {
            array_push($result, $srRecord['id']);
        }
        return $result;
    }
    
    public function getStaffReportIDs($uid,$mgr,$root) {
        //if root, use full list
        if ($root) {
            $resultIDs = $this->getIDs();
            return $resultIDs;
        } else { //everybody else only edits their own
                 //built as array in case in future we want to add more ability to managers
            $allowedIDs = array($uid);
        }
        $resultIDs = array();
        $records = $this->fetchAll();
        foreach ($records as $report) {
            $rid = $report['id'];
            $cid = $report['updatedBy'];
            
            if (in_array($cid,$allowedIDs)) {
                array_push($resultIDs,$rid);
            }
        }
        return $resultIDs;
    }
    
    public function getRecord($id)
    {
       $id = (int)$id;
       $row = $this->fetchRow('id = ' . $id);
	if (!$row) {
	 throw new Exception("Could not find report with ID $id");
	}
       return $row->toArray();
    }

    public function storeReport ($name,$freq,$recips,$options,$uid) 
    {
       $now  =  date("Y-m-d");
      
       $data = array(
	'name' => $name,
	'recipients' => $recips,
	'frequency' => $freq,
	'includeOptions' => $options,
        'lastUpdated' => $now,
	'updatedBy' => $uid,
        'enabled' => (int)1
       );
      return $this->insert($data);
    }

    public function updateReport($id,$name,$freq,$recips,$options) 
    {
        $now  =  date("Y-m-d");
       
        $data = array(
	'name' => $name,
	'recipients' => $recips,
	'frequency' => $freq,
	'includeOptions' => $options,
        'lastUpdated' => $now,
	'updatedBy' => $this->uid
       );

      $this->update($data, 'id = ' . (int)$id);
    }

    public function deleteReport($id)
    {
       if ($this->delete('id = ' . (int)$id)) {
           return TRUE;
       } else {
           return FALSE;
       }
    }

}

