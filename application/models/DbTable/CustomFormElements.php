<?php

class Application_Model_DbTable_CustomFormElements extends Zend_Db_Table_Abstract
{

    protected $_name = 'customFormElements';

    public function getElementNames($formID,$type=FALSE) {
        $list = array();
         
        if (!$type) {
            $myElements = $this->fetchAll('formID = ' . (int)$formID)->toArray();
        } else {
            $myElements = $this->fetchAll('formID = ' . (int)$formID . " AND elType = '" . $type . "'");
        }
        
        
        foreach ($myElements as $element) {
            $thisElement = array(
                'field' => $element['elementID'],
                'name' => $element['elementName']
                );
            array_push($list,$thisElement);
        }
        return $list;
    }
    
    public function getElementType($eID,$fID) {
        $element = $this->fetchRow("elementID = '$eID' and formID = $fID")->toArray();
        return $element['elType'];
    }
    
    public function getElementName($eID,$fID) {
        $element = $this->fetchRow("elementID = '$eID' and formID = $fID")->toArray();
        return $element['elementName'];
    }
    
    public function getElementOptions($eID, $fID) {
        $element = $this->fetchRow("elementID = '$eID' and formID = $fID")->toArray();
        if (($element['elType'] != 'radio') && ($element['elType'] != 'checkbox')) {
            return NULL;
        } else {
            $options = json_decode($element['options'], TRUE); //TRUE sets it to return an assoc array
            return $options;
        }
    }
    
    public function addElement($id,$formID,$name,$type,$options=array()) 
    {
        $optionString = json_encode($options, JSON_FORCE_OBJECT);
        
	$data = array(
		'elementID'         => $id,
                'formID'            => $formID,
                'elementName'       => $name,
                'elType'            => $type,
                'options'           => $optionString        
		);
        
	$this->insert($data);        
    }

    public function deleteElement($elementID,$formID)
    {
	$this->delete('elementID = ' . (int)$elementID . ' and formID = ' . $formID);
    }
    
    public function getElement($elementID,$formID) {
        $row = $this->fetchRow("elementID = '$elementID' and formID = '$formID'");
        return $row;//->toArray();
    }
}

