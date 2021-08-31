<?php

class ABCD_VoipFunctions {
    
    public function getData($ip,$port,$debug=FALSE) {
        $result = array();
        if ($debug) {
            $socket = fopen("/tmp/data.txt","r");
        } else {
        //connect to remote log server
            $socket = fsockopen($ip,$port);
        }
        //store output as array of strings
        if ($socket) {
            while (($buffer = fgets($socket,148)) !== FALSE) {
                array_push($result,$buffer);
            }
            
            if (!feof($socket)) {
                fclose($socket);
            }
            
        } else {
            throw new Exception("Could not connect to phone server.");
        }
        //return array
        return $result;
    }
    
    public function parseData(array $data) {
        $processedRecords = array();
        
        //field definitions from pp 30-36, Mitel Data Interface Specification
        $fieldDefs = array( 
          'longCall' => array(0,1), //start at 0, 1 long 
          'callDate' => array(1,5), //start at 1, 5 long 
          'startTime' => array(7,6), //start at 7, 6 long
          'callLength' => array(14,8), //start at 14, 8 long hh:mm:ss
          'callingParty' => array (23,4), //start at 23, 4 long (Tnnn on incoming)
          'timeToAnswer' => array(29,3), //start at 29, 3 long
          'digitsDialed' => array(33,26), //start at 33, 26 long
          'calledParty' => array(61,4), //start at 61, 4 long
          'callerID' => array(91,10) //start at 91, 10 long
        );
       
        //get array of Mitel-encoded lines into a more usable form
        foreach ($data as $string) {
            $myCall = array();
            foreach ($fieldDefs as $colName => $lengthData) {
                $start = $lengthData[0];
                $length = $lengthData[1];
                $colData = trim(substr($string,$start,$length));
                $myCall[$colName] = $colData;
            }
            array_push($processedRecords,$myCall);
        }
        //return it
        return $processedRecords;
    }
    
    public function filterData(array $call,$filterType,$filterVal=array()) {
        $validTypes = array('incoming','extension');
        if (!in_array($filterType,$validTypes)) {
            throw new exception ("Invalid filter type $filterType passed to filterData()");
        }
        
        switch ($filterType) {
            case 'incoming':
                //on incoming calls callerID is non-empty
                $testString = $call['callerID'];
                if (strlen($testString) > 0) {
                    return TRUE;
                } else {
                    return FALSE;
                }
                break;
            
            case 'extension' :
                //test if call placed to particular extension
                //must have at least one extenstion passed to test
                if (count($filterVal) == 0) {
                    throw new exception ("Must pass at least one extension to filterData.");
                }
                $testString = $call['calledParty'];
                if (in_array($testString,$filterVal)) {
                    return TRUE;
                } else {
                    return FALSE;
                }
                break;
            
            default: break;
        }
        
    }
}

?>
