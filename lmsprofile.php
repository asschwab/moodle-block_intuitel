<?php
use intuitel\IntuitelXMLSerializer;
use intuitel\Intuitel;
use intuitel\ProtocolErrorException;
use intuitel\IntuitelController;
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once 'locallib.php';
require_once ('model/exceptions.php');
require_once('model/intuitelController.php');
disable_moodle_page_exception_handler();
check_access();
global $CFG;

    $xml = get_input_message();
    $intuitel_msg= IntuitelController::getIntuitelXML($xml);
    $lmsProfile=$intuitel_msg->LmsProfile;
    $mid=IntuitelXMLSerializer::get_required_attribute($lmsProfile,'mId');
    if (!isset($mid))
    	throw new ProtocolErrorException("Bad LmsProfile request.");
    $serializer = new IntuitelXMLSerializer();
    $properties = Intuitel::getAdaptorInstance()->getLMSProfile();
    header('Content-type: text/xml');
    echo $serializer->serializeLMSProfile($mid,$properties);
