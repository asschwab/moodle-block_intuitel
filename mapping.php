<?php
use intuitel\Intuitel;
use intuitel\IntuitelXMLSerializer;
use intuitel\ProtocolErrorException;
use intuitel\IntuitelController;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('locallib.php');
require_once ('model/exceptions.php');
require_once ('model/intuitelController.php');
global $log;

disable_moodle_page_exception_handler();
if (!isset($_SESSION['user_validated'])) // if the user has been authenticated by the AUTH request allows him to request mappings from any IP
{
check_access();
}
else
{
    $log->LogDebug("Mapping request authenticated as:" .join(',',$_SESSION['user_ids_validated']));
}

$params=array();
$serializer = new IntuitelXMLSerializer();

$xml = get_input_message();
$log->LogDebug("loMapping Request: $xml");
$intuitel_elements= IntuitelController::getIntuitelXML($xml);
$loMappings = IntuitelXMLSerializer::get_required_element($intuitel_elements,'LoMapping');
$loMappingResults = array ();
foreach ( $loMappings as $loMapping ) 
{
    $mId = IntuitelXMLSerializer::get_required_attribute($loMapping, 'mId');
    if (isset($loMappingResults[$mId]))
        throw new ProtocolErrorException("Duplicated message id: $mId");
    $params = $serializer->parse_mapping_request($loMapping);
    
    // support sending KVP for testing
    foreach ($_GET as $name => $value)
    {
        if ($name != 'xml' && $name != 'XDEBUG_SESSION_START')
        {
            $val = optional_param($name, null, PARAM_TEXT);
            if (isset($val)) // CHECK if NULL is valid parameter
                $params[$name] = $val;
        }
    }
    
    $result = Intuitel::getAdaptorInstanceForCourse(null)->findLObyAttributes($params);
    
    $loMappingResults[$mId] = $result;
}

$response= $serializer->serializeLoMapping($loMappingResults);
$log->LogDebug("Mapping response: $response");
header('Content-type: text/xml');
echo $response;
?>
