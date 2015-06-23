<?php
use intuitel\Intuitel;
use intuitel\IntuitelXMLSerializer;
use intuitel\idFactory;
use intuitel\IntuitelController;
use intuitel\ProtocolErrorException;

require_once (dirname(dirname(dirname(__FILE__))).'/config.php');
require_once 'locallib.php';
require_once 'model/exceptions.php';

disable_moodle_page_exception_handler();

check_access ();	
$xml = get_input_message();
$response = IntuitelController::ProcessLoreRequest($xml);
header ( 'Content-type: text/xml' );
echo $response;

?>