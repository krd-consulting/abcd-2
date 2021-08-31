<?php

class ABCD_Soapfix extends SoapClient {

   public function __construct($wsdl, $options = null) {
        $soap = parent::__construct($wsdl,array_merge($options,array('trace' => 1)));
        return $soap;
    }

    public function __doRequest($request, $location, $action, $version) {
        $dom = new DOMDocument('1.0');

        $request = str_replace(array('xmlns:ns3="http://coc.gov/xsd/ESB/SupplementalData/V1"','ns3:'),array('xmlns:v1="http://coc.gov/xsd/ESB/SupplementalData/V1" xmlns:ns="http://schemas.calgary.ca/xsd/familycommunitysurvey/2012/08"','v1:'),$request);
        return parent::__doRequest($request, 'https://fcs.calgary.ca/eProxy/service/FamilyCommunitySurvey', $action, $version);
    }

   
}

?>
