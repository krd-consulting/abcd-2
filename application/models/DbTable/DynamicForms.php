<?php

/**
 * Description of dynamicForms
 *
 * @author roman
 */
class Application_Model_DbTable_DynamicForms extends Zend_Db_Table_Abstract {
    protected $_name='';
    
    public function setName($name) {
        $this->_name = $name;
    }
    
    public function updateData($tableName, $data=array(), $where) {
        $this->setName($tableName);
        return $this->update($data,$where);
    }
    
    public function insertData($tableName, $data=array()) {
        $this->setName($tableName);
        
//        $uID = $data['uid'];
//        $responseDate = $data['responseDate'];
//        $where = "uID = $uID AND responseDate = '$responseDate'";
//        
//        $exists = $this->fetchRow($where);
//        if (count($exists) == 1) {
//            $return = $this->update($data, $where);
//        } else {
        $return = $this->insert($data);
//        }
//        
        return $return;
    }
    
    public function getLatestPtcpRecord($tableName,$ptcpID) {
        $this->setName($tableName);
        $records = $this->fetchAll("uID = $ptcpID", "enteredOn desc")->toArray();
        return $records[0];    
    }
    
    public function getAllRecords($tableName) {
        $this->setName($tableName);
        $records = $this->fetchAll(NULL,"enteredOn desc")->toArray();
        $filteredRecords = $this->_filterEditDisplay($records);
        
        return $filteredRecords;
    }
    
    public function getRecordsAdHocFilter($tableName,array $filters) {
        $this->setName($tableName);
        $selectLine = '';
        
        foreach ($filters as $colName => $value) {
            $selectLine .= "$colName = '$value' AND ";
        }
        $selectLineFinal = rtrim($selectLine," AND ");
        
        $records = $this->fetchAll($selectLineFinal,"responseDate DESC")->toArray();
        $finalRecords = $this->_filterEditDisplay($records);
        
        return $finalRecords;
    }
    
    public function getRecordByID($tableName,$recordID) {
        $this->setName($tableName);
        $record = $this->fetchRow("id = $recordID");
        return $record->toArray();
    }
    
    public function getRecords($entityID=NULL, $tableName, $startDate='', $endDate='') {
        $this->setName($tableName);
        if ($entityID) {
            $records = $this->fetchAll("uID = $entityID", 'responseDate desc')->toArray();
        } else {
            $records = $this->fetchAll(NULL, 'responseDate desc')->toArray();
        }
        $datedRecords = $this->_filterByDates($records, 'responseDate', $startDate, $endDate);
        
        //filter out doNotDisplay
        $filteredRecords = $this->_filterEditDisplay($datedRecords);
        
        return $filteredRecords;
    }
    
    protected function _filterEditDisplay($dataRecords) {
        $returnRecords = array();
        foreach ($dataRecords as $record) {
            if (($record['doNotDisplay'] == '0') || (array_key_exists('doNotDisplay',$record) == FALSE)) {
                array_push($returnRecords,$record);
            }
        }
        return $returnRecords;
    }
    
    protected function _filterByDates($records=array(), $timefield, $startDate, $endDate) {
        if (strlen($startDate > 0)) {
            $begin = strtotime($startDate);
            $searchBegin = TRUE;
        } else {
            $searchBegin = FALSE;
        }   
        if (strlen($endDate > 0)) {
            $end = strtotime($endDate);
            $searchEnd = TRUE;
        } else {
            $searchEnd = FALSE;
        }
        
        if ($searchBegin) {
            foreach ($records as $key => $meeting) {
                $myDate = strtotime($meeting[$timefield]);
                if (!($myDate >= $begin)) {
                    unset($records[$key]);
                }
            }
        }
        
        if ($searchEnd) {
            foreach ($records as $key => $meeting) {
                $myDate = strtotime($meeting[$timefield]);
                if (!($myDate <= $end)) {
                    unset($records[$key]);
                }
            }
        }
        
        return $records;
    
    }
    
}
