<?php

class Zend_View_Helper_Footer extends Zend_View_Helper_Abstract
{
  public function footer()
  {
	$htmlWrapTop = "<div id=footerText class='tiny'>";
	$htmlWrapBottom = "</div>";

	$text = "abcd is free software licensed under the GPL.";

	$content = $htmlWrapTop . $text . $htmlWrapBottom;
	return $content;
  }
}
