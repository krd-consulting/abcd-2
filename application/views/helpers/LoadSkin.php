<?php
 /**
  * This loads skins from /public/skins *
  */

class Zend_View_Helper_LoadSkin extends Zend_View_Helper_Abstract {
  public function loadSkin ($skin) {
	//always use jquery stylesheet
        $this->view->headLink()->appendStylesheet('/js/jQuery/css/ui-lightness/jquery-ui.css', 'screen,print');
        
        //load the config file
	$skinData = new Zend_Config_Xml('./skins/' . $skin . '/skin.xml');
	$stylesheets = $skinData->stylesheets->stylesheet->toArray();

	//append the stylesheets in order
	if (is_array($stylesheets)) {
	  foreach ($stylesheets as $stylesheet) {
	    $this->view->headLink()->appendStylesheet('/skins/' . $skin . '/css/' . $stylesheet, 'screen,print');
	  } 
	}
        //append print overrides
        $this->view->headLink()->appendStylesheet('/skins/' . $skin . '/css/print.css', 'print');
        
  } 

} 
