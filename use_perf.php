<?php
use intuitel\Intuitel;
use intuitel\IntuitelXMLSerializer;
use intuitel\idFactory;
use intuitel\IntuitelController;
use intuitel\ProtocolErrorException;

require_once (dirname(dirname(dirname(__FILE__))).'/config.php');
require_once 'locallib.php';
require_once ("model/LOFactory.php");
require_once ("model/intuitelLO.php");
require_once 'model/serializer.php';
require_once ('model/exceptions.php');

disable_moodle_page_exception_handler();

check_access ();
$params = array ();
$serializer = new IntuitelXMLSerializer();

	$xml = get_input_message();
	global $log;$log->LogDebug("USE_PERF request received: $xml");
	$response=IntuitelController::ProcessUsePerfRequest($xml);
	header ( 'Content-type: text/xml' );
	$log->LogDebug("USE_PERF response sent: $response");
	echo $response;
?>