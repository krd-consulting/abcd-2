<?php

/**
 * Description of UserDepartments
 *
 * @author roman
 * Associates Users (staff) with Departments
 * 
 * $userID
 * $deptID
 */
class Application_Model_DbTable_UserDepartments extends Zend_Db_Table_Abstract {
    protected $_name = 'userDepartments';
    
    public function getManagerDepts($uid) {
        $select = "`userID` = $uid AND `manager` = 1";
        $rowset = $this->fetchAll($select)->toArray();
        
        $result = array();
        foreach ($rowset as $row) {
            $deptID = $row['deptID'];
            array_push($result,$deptID);
        }
        
        return $result;
    }
    
    public function getList($column,$id) /* $column = 'users' or 'depts' */ 
    {
        switch ($column) {
            case 'users' : 
                $select     = "deptID = " . (int)$id;
                $colname    = "userID";
                break;
            case 'depts' :
                $select     = "userID = " . (int)$id;
                $colname    = "deptID";
                break;
            default : throw new Exception("Could not get list of $column");
        }

        $rowset = $this->fetchAll($select)->toArray();
        
        $results = array();
        foreach ($rowset as $row) {
            array_push($results,$row[$colname]);
        }
        return $results;
    }
        
    public function addRecordtoDept($user,$dept) {
        $data = array(
            'userID' => (int)$user,
            'deptID' => (int)$dept
        );
        $this->insert($data);
    }
    
    public function delRecordfromDept($user,$dept) {
        $select = "userID = " . (int)$user . " and deptID = " . (int)$dept;
        $this->delete($select);
    }
    
    public function getManager($dept) {
        $select = "manager = TRUE and deptID = " . (int)$dept;
        $result = $this->fetchAll($select)->toArray();
        $uid = NULL;
        if ($result) {
        $uid = $result[0]['userID'];
        }
        return $uid;
    }
}