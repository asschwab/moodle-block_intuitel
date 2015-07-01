<?php
// This file is part of INTUITEL http://www.intuitel.eu as an adaptor for Moodle http://moodle.org/
//
// INTUITEL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// INTUITEL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with INTUITEL for Moodle Adaptor.  If not, see <http://www.gnu.org/licenses/>.
/**
 * REST interface.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use intuitel\Intuitel;
use intuitel\IntuitelXMLSerializer;
use intuitel\ProtocolErrorException;
use intuitel\IntuitelController;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('locallib.php');
require_once('model/exceptions.php');
require_once('model/intuitelController.php');
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
    $mid = IntuitelXMLSerializer::get_required_attribute($loMapping, 'mId');
    if (isset($loMappingResults[$mid]))
        throw new ProtocolErrorException("Duplicated message id: $mid");
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

    $loMappingResults[$mid] = $result;
}

$response= $serializer->serializeLoMapping($loMappingResults);
$log->LogDebug("Mapping response: $response");
header('Content-type: text/xml');
echo $response;