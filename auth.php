<?php
use intuitel\Intuitel;
use intuitel\IntuitelXMLSerializer;
use intuitel\idFactory;
use intuitel\IntuitelController;
use intuitel\ProtocolErrorException;

require_once ("../../config.php");
require_once 'locallib.php';
require_once ('model/exceptions.php');
//error_reporting( E_ALL ); 
//ini_set('display_errors', 1);

disable_moodle_page_exception_handler();
//check_access();

$xml = get_input_message('POST|GET');
list($validated,$user_ids_validated,$response) = IntuitelController::ProcessAuthRequest($xml);
if ($validated)
{
//session_start();
$_SESSION['user_validated']=true;
$_SESSION['user_ids_validated']=$user_ids_validated;
}
else
{
session_destroy();
}
header ( 'Content-type: text/xml' );
echo $response;
?>
