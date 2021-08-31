<?php

/**
 * Description of ParticipantSecureIds
 *
 * @author roman
 * Associates Participants (clients) with encrypted IDs
 * 
 * $ptcpID
 * $anonID
 */
class Application_Model_DbTable_PtcpSecureIds extends Zend_Db_Table_Abstract {
    protected $_name = 'ptcpSecureIDs';
    
    public function getRecord($id,$column='id') /* $column = 'ptcp' or 'id' */ 
    {
        switch ($column) {
            case 'ptcp' : 
                $select     = "anonID = " . $id;
                $colname    = "ptcpID";
                break;
            case 'id' :
                $select     = "ptcpID = " . (int)$id;
                $colname    = "anonID";
                break;
            default : throw new Exception("\"$column\" is not a valid option.");
        }

        $row = $this->fetchRow($select)->toArray();
        $result = $row[$colname];
        return $result;
    }
        
    public function addRecord($ptcpID) {
        $ptcpTable = new Application_Model_DbTable_Participants(); 
        $ptcp = $ptcpTable->getRecord($ptcpID);
        $fName = $ptcp['firstName'];
        $lName = $ptcp['lastName'];
        $dob   = $ptcp['dateOfBirth'];
 
        $safeID = strtoupper(substr($fName,0,2) . substr($lName,0,2)) . $dob;
        $encrID = md5($safeID);
        
        $data = array(
            'ptcpID' => $ptcpID,
            'anonID' => $encrID
        );
        $this->insert($data);
    }
}
