<?php

class Application_Model_DbTable_Files extends Zend_Db_Table_Abstract {
    protected $_name = 'files';
        
    public function getFileList ($entType, $entID) /* */ 
    {
       $where = "`entityID` = $entID AND `entityType` = '$entType' and `doNotDisplay` = 0";
       //throw new exception ($where);
        $list=$this->fetchAll($where)->toArray();
       return $list;
    }
    
    public function getFile($id) 
    {
            $file=$this->fetchRow("id = $id and `doNotDisplay` = 0")->toArray();
            return $file;
    }
        
    public function addFile($entType, $entID, $description, $location, $createdBy) {
       
       $now  =  date("Y-m-d"); 
        
       $entityList = array('ptcp', 'staff', 'vol');
       if (!in_array($entType, $entityList)) {
           throw new exception ("Invalid folder type $type passed.");
       }
       
       $data = array(
            'entityID'        =>  $entID,
            'entityType'      =>  $entType,
            'description'     =>  $description,
            'location'        =>  $location,
            'createdOn'       =>  $now,
            'createdBy'       =>  $createdBy,
            'doNotDisplay'    =>  0
        );
        
        $recordID = $this->insert($data);
        return $recordID;
    }
    
    public function archiveRecord($id) {
        $data = array (
            'doNotDisplay' => 1
        );
        
        $this->update($data,"id = $id");
    }
    
    public function updateRecord($id, $location='', $description='')
    {
        $data = array();
        
        if (strlen($location) > 0) {
            $data['location'] = $location;
        }
        
        if (strlen($description) > 0) {
            $data['description'] = $description;
        }
        
        if (count($data) == 0) {
            throw new exception ("Nothing to update file record with!");
        } else {
            $this->update($data, "id = $id");
        }
    }
}