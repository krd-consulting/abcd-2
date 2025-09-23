<?php

class Application_Model_DbTable_Files extends Zend_Db_Table_Abstract {
    protected $_name = 'files';
    

    public function getName($id) {
        $record = $this->getFile($id);
        $name = $record['description'];
        return $name;
    }
    
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
        
    public function addFile($entType, $entID, $description, $location, $createdBy, $formID='',$column='',$formEntryID=0) {
       
       $now  =  date("Y-m-d"); 
       
       switch ($entType) {
           case 'participant' : $entType = 'ptcp'; break;
           case 'prog'        : $entType = 'program'; break;
       }
       
       $entityList = array('ptcp', 'staff', 'vol', 'program');
       if (!in_array($entType, $entityList)) {
           throw new exception ("Invalid folder type $type passed.");
       }
       
       //if uploaded from a form, set doNotDisplay until form is saved
       if ($formID != '' && $formEntryID == 0) {
           $doNotDisplay = 1;
       } else {
           $doNotDisplay = 0;
       }
       
       $data = array(
            'entityID'        =>  $entID,
            'entityType'      =>  $entType,
            'description'     =>  $description,
            'location'        =>  $location,
            'createdOn'       =>  $now,
            'createdBy'       =>  $createdBy,
            'formID'          =>  $formID,
            'fieldID'          =>  $column,
            'formEntryID'     =>  $formEntryID,
            'doNotDisplay'    =>  $doNotDisplay
        );
        
        $recordID = $this->insert($data);
        return $recordID;
    }
    
    public function activateFile($id,$formEntryID) {
        $this->update(array('formEntryID' => $formEntryID, 'doNotDisplay' => 0),"id = $id");
    }
    
    public function archiveRecord($id) {
        $data = array (
            'doNotDisplay' => 1
        );
        
        $record = $this->getFile($id);
        
        
        
        if ($record['formEntryID'] > 0) {
            //update form record
            $table = $record['formID'];
            $eid = $record['formEntryID'];
            $col = "field_" . $record['fieldID'];
            $entryTable = new Application_Model_DbTable_DynamicForms;

            $formData = array (
                $col => NULL
            );
            
            $entryTable->updateData($table,$formData,"id = $eid");
        }
        
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