<?php
use intuitel\Intuitel;
use intuitel\IntuitelXMLSerializer;
use intuitel\idFactory;
use intuitel\IntuitelController;
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once 'locallib.php';
require_once("model/LOFactory.php");
require_once("model/intuitelLO.php");
require_once 'model/serializer.php';
require_once ('model/intuitelController.php');

disable_moodle_page_exception_handler();
check_access();
$params=array();

	$xml = get_input_message();
	$response = IntuitelController::ProcessLearnerRequest($xml);
	header ( 'Content-type: text/xml' );
	echo $response;

?>