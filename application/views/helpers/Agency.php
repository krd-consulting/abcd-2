<?php

class Zend_View_Helper_Agency extends Zend_View_Helper_Abstract
{
  public function agency()
  {
	$auth = Zend_Auth::getInstance();
	if ($auth->hasIdentity()) {
  	  $customdata = new Application_Model_DbTable_CustomValues;
          $agency = $customdata->getValue('agency');
          
          if ($agency) {
            $helperContent =  $agency;
          } else {
            $helperContent = FALSE;  
          }
          return $helperContent;
	}

	return FALSE;

  }
}
