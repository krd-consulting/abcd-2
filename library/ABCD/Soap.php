<?php

class ABCD_Soap {
    

    protected function _init() {
	  
        $this->debug = FALSE;
        $this->uid  = Zend_Registry::get('uid');
        $this->root = Zend_Registry::get('root');
        $this->mgr  = Zend_Registry::get('mgr');
        
        $this->formsModel = new Application_Model_DbTable_Forms;
        $this->elmntModel = new Application_Model_DbTable_CustomFormElements;
        $this->deptsModel = new Application_Model_DbTable_Depts;
    }
    
    protected function _connect($service) {
        $allowedServices = array ('GetCodeLookupsByName', 'SubmitSurvey');
        
        if (!in_array($service, $allowedServices)) {
           throw new Exception ("Can't connect to FSII for service $service");
        }
       
        //** CONNECTION SETTINGS - OK TO EDIT **//
        $wsdlFileName   =   "FamilyCommunitySurvey.wsdl";
        $wsdlLocation   =   APPLICATION_PATH . "/configs/";
        $endPoint       =   "https://fcs.calgary.ca/eProxy/service/FamilyCommunitySurvey";
        $userName       =   "CalgaryFamily";
        $password       =   "4generations";
        $timeout        =    1; //in seconds
        //** DO NOT CHANGE CODE BELOW THIS LINE **//
        
        
        $wsdl = $wsdlLocation . $wsdlFileName;        
        $options = array (
            'soapVersion'  => SOAP_1_1,
            'login'        => $userName,
            'password'     => $password
        );
        
        //**ADD STANDARD HEADERS**//
        $headerObj = new StdClass();
        $headerObj->ServiceProvider = array(
            'Name' => 'FamilyCommunitySurvey',
            'OperationName' => $service
            );
        $headerObj->SourceName = 'ABCD';
        $headerObj->CorrelationID = time();       
        $header = new SoapHeader('http://coc.gov/xsd/ESB/SupplementalData/V1','SupplementalData',$headerObj);

        
        //** INSTANTIATE SOAP CLIENT
        $fsiiConnect = new Zend_Soap_Client($wsdl, $options);
        $fsiiConnect->setLocation($endPoint);
        $fsiiConnect->setSoapClient(new ABCD_Soapfix($wsdl,$options));
        $fsiiConnect->addSoapInputHeader($header);
        
        //** SET TIMEOUT
        $context = stream_context_create(array('http' => array('timeout' => $timeout)));
        $fsiiConnect->setStreamContext($context);
        
        return $fsiiConnect;
    }
    
    protected function _getFCS() {
        $fcs = array (
            'SubmissionCode' => time(),
            'SubmissionDate' => date('Y-m-d',time())
        );
        return $fcs;
    }
    
    protected function _getProg(array $values, $formID) {
        $ptcpID = $values['uid'];
        
        //get program code from departments
        $deptRecord = $this->deptsModel->getRecord($values['deptID']);
        if (strlen($deptRecord['fcssID'] > 0)) {
            $progCode = sprintf("%04d", $deptRecord['fcssID']);
            $otherName = 'na';
        } else {
           $progCode = '0000';
           $otherName = $deptRecord['name'];
        }
        
        //get agency code, codebook from customValues
        $customModel    = new Application_Model_DbTable_CustomValues;
        $agencyCode     = $customModel->getValue("FCSS Agency Code");
        $codeBook       = $customModel->getValue("FCSS Code Book");
        
        //get client id from participants
        $encrModel = new Application_Model_DbTable_PtcpSecureIds;
        $secureID = $encrModel->getRecord($ptcpID);
        $estimated = '0'; //we always have consistend ptcp IDs.
        
        //get survey type based on type and prev. records
        $formRec    = $this->formsModel->getRecord($formID);
        $type = $formRec['type'];
                
        if ($type == 'singleuse') {
            $surveyType = 'NULL';
        } else {
            //check submission type
            $prePost = $values['prepost'];
            if ($prePost == 'pre') {
                $surveyType = 'PRE';
            } else {
                $surveyType = 'POST';
            }
        }
        
        $progDate = $this->_getProgDate($values, $formID);
        $surveyCode = $this->_getSurveyCode($formID);
        $questionnaire = $this->_processForm($values, $formID);
        
        //compile survey array
        $survey = array (
            'Code' => $surveyCode['Code'],
            'Survey' => $questionnaire
        );
        
        //compile and return array
        $prog = array (
            'ProgramCode'       => $progCode,
            'OtherProgramName'  => $otherName,
            'AgencyCode'        => $agencyCode,
            'CodeBookVersion'   => $codeBook,
            'ClientID'          => $secureID,
            'ClientIDEstimated' => $estimated,
            'SurveyType'        => $surveyType,
            'ProgramDate'       => $progDate,
            'Surveys'           => $survey
            //'Questionnaire'     => $questionnaire
        );
        return $prog;
    }
    
    protected function _getProgDate(array $values,$formID) {
        $formRecord = $this->formsModel->getRecord($formID);
        $fcssID = $formRecord['fcssID'];
        
        switch ($fcssID) {
            case '1':   $dateField = 'RegistrationDate';
                            break;
            case '2':   $dateField = 'DiscontinueDate';
                            break;
            default:    $dateField = 'TestDate';
        }
        
        $date = array (
            $dateField => $values['responseDate']
        );
        
        return $date;
    }
    
    protected function _getSurveyCode($formID) {
        $formRecord = $this->formsModel->getRecord($formID);
        $fcssID = sprintf("%03d", $formRecord['fcssID']);
        $code = array (
            "Code"    => $fcssID
        );
        return $code;
    }
    
    protected function _processForm (array $values, $form) {
        //set code book option arrays
         
         //---> blank codes <------
         $code99 = array(
            'aborid','gender','popgrp','homelang',
            'borncan','referby','marital','adults','numchild',
             'actlim1', 'actlim2'
          );
         
	$code88 = array(
		'brsc5','brsc6'
	);
   
         $code77 = array(
            'grade','ch1age','ch2age','ch3age','ch4age',
            'ch5age','ch6age','ch7age','ch8age','hous1a',
	     'prlt1a','prlt2a','prlt3a','prlt4a','prlt5a','prlt6a','prlt7a',
	     'prlt1b','prlt2b','prlt3b','prlt4b','prlt5b','prlt6b','prlt7b',
	     'prst1a','prst2a','prst3a','prst4a',
	     'prst1b','prst2b','prst3b','prst4b'
         );
         
         $code777 = array(
             'yrscan'
         );

         $code1   = array(
             'cadu1', 'cadu2', 'cadu3', 'cadu4', 'cadu5', 'cadu6', 'cadu7', 'cadu8',
             'cadu9', 'cadu10', 'cadu11', 'cadu12', 'cadu13', 'cadu14', 'cadu15', 'cadu16'
         );
         
         $codeDate = array(
             'hous1until'
         );

	 $codeNA = array(
	     'genderoth'
	 );
         
        //---> alphanumeric questions <----
            $alphaNums = array(
                'popgrp','homelang','country', 'referby'
            );
        
        //end code book option arrays
       
        $questions = array();
        //get all necessary elements
        $elementSQL = "SELECT elementID,fsiiName,options 
                       FROM customFormElements
                       WHERE fsiiName IS NOT NULL
                       AND formID = $form";
        $elementList = $this->elmntModel->getAdapter()
                            ->fetchAll("$elementSQL");
        
        foreach ($elementList as $element) {
            $eName = $element['elementID'];
            
            if (array_key_exists($eName, $values)) {
                $val = $values[$eName];
            } else {
                $val = '';
            }
            
            
            $qCode = $element['fsiiName'];
            //FCSS handles multiple-answer questions as series of
            //separate 'yes/no' dyads; we prefer checkboxes,
            //so must process. we've called these 'processCheck' for convenience
            
            if ($qCode == 'processCheck') {
               $submittedAnswers = explode(' , ' , strtolower($val));
             
               $set = json_decode($element['options'], TRUE);
               
               foreach ($set as $newQuestionCode => $newValue) {
                  
                  if (in_array(strtolower(trim($newValue)), $submittedAnswers)) {
                     $answerCode = '2'; //YES
                  } elseif (strlen($val) > 0) {
                     $answerCode = '1'; //NO
                  } else {
                     $answerCode = '77' ; //NA
                  }
                  $processedCheckSet = array (
                    'QuestionCode' => $newQuestionCode,
                    'Answer'       => $answerCode
                  );
                  array_push($questions,$processedCheckSet);
               }
               continue;
            }
            
            
            $answers = json_decode($element['options'], TRUE);
            
            //if there are coded options, $answers will be > 0,
            //but some coded options (in $alphaNums) no longer want their codes
            
            if ((!in_array($qCode,$alphaNums)) && (count ($answers) > 0)) {
                $codes = array_flip($answers);
                if (array_key_exists($val,$codes)) {
                   $response = $codes[$val];    
                } else {
                   $response = '';
                }
            } else {
                //keep free text
                $response = $val;
            }
            
            if (in_array($qCode, $code99)) {
               $blankCode = '99';
            } elseif (in_array ($qCode, $code88)){
               $blankCode = '88';
            } elseif (in_array ($qCode, $code77)){
               $blankCode = '77';
            } elseif (in_array($qCode, $code777)) {
               $blankCode = '777';
            } elseif (in_array($qCode, $code1)) {
               $blankCode = '1';
            } elseif (in_array($qCode, $codeDate)) {
               $blankCode = '7777-77-77'; 
            } elseif (in_array($qCode, $codeNA)) {
               $blankCode = 'na'; 
            } else {
               $blankCode = '';
            }
            
            if ($response == '') $response = $blankCode;
            
            $tempAnswerSet = array (
                'QuestionCode'  => $qCode,
                'Answer'        => $response
            );
            
            array_push($questions,$tempAnswerSet);
            
        }
        
        if ($form == '100') //Intake form requires age, will calculate
        {
            $ptcp = new Application_Model_DbTable_Participants;
            $ptcpID = $values['uid'];
            $ptcpRecord = $ptcp->getRecord($ptcpID);
            $age = $ptcpRecord['age'];
            $tempAnswerSet = array (
                'QuestionCode' => 'age',
                'Answer'       => $age
            );
            array_push($questions,$tempAnswerSet);            
        }
        
        if ($form == '103') //Discontinue form requires date twice
        {
           $tempAnswerSet = array (
             'QuestionCode' => 'disc',
             'Answer'       => $values['responseDate']
           );
           
           array_push($questions,$tempAnswerSet);
        }
        
        /* LEGACY 
         if ($form == '102' || $form == '113') { //In poverty forms, question-codes vary based on prepost
           
           if ($values['prepost'] == 'pre') {
                 $suffix = 'a';
                 $emptySuffix = 'b';
              } else {
                 $suffix = 'b';
                 $emptySuffix = 'a';
           }
              
           foreach ($questions as $key => $qArray) {   
              $code = $qArray['QuestionCode'];
              
              $qArray['QuestionCode'] = $code . $suffix;
              $questions[$key] = $qArray;
              
              $emptyArray = array(
                  'QuestionCode' => $code . $emptySuffix,
                  'Answer' => '77'
              );
              array_push($questions, $emptyArray);
           }
           
        } */
        
        return $questions;
    }
            
    public function submitForm(array $values,$form) {
        $this->_init();
        
        //progDetails contails externals for date 1and survey data
        $progDetails   = $this->_getProg($values,$form);
        
        //put all the submission data into a standard class
        $response = new stdClass();
        $response->SubmissionCode = time();
        $response->SubmissionDate = date('Y-m-d',time());
        $response->ProgramDetails = $progDetails;
        
        //wrap the submission in top-level body tag 'FamilyCommunitySurvey'
        $fcs = new stdClass();
        $fcs->FamilyCommunitySurvey = $response;
        
        $connect = $this->_connect('SubmitSurvey');
        try {
           $soapReturn = $connect->SubmitSurvey($fcs);
        }
        catch (Exception $e) {
           print_r($e);
        }
        if ($this->debug == TRUE) {
		print_r($connect->getLastRequest());
		print_r($soapReturn);
	  }	 


        if ($soapReturn->Result == '1') {
            return 1;
        } else {
            $errorText = $this->_processError($soapReturn);
            return $errorText;
        }
    }
    
    public function getCodes($codeType) {
       $soapData = new stdClass();       
       $soapData->Name  =  $codeType;

       $connect = $this->_connect('GetCodeLookupsByName');
       
       try {
         $result = $connect->GetCodeLookupsByName($soapData);
       }
       catch (Exception $e) {
          print "Failed:\n\n";
          print $connect->getLastRequest() . '\n\n';
          print $e;
       }

       print_r ($result);
    }
    
    protected function _processError($soapData) {
       $errorArray = $soapData->Errors;
       
       $htmlError = "<b>Your data did not pass FCSS validation:</b><br><br>";
       $htmlError .= "<ul><li>";
       $htmlError .= $errorArray[0]->Message . " in question coded <i>'" . $errorArray[0]->QuestionCode . "'</i>";
       $htmlError .= "</li></ul><br>Please correct and resubmit.";
       
       return $htmlError;
       
    }
    
}
