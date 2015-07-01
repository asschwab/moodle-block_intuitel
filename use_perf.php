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
use intuitel\IntuitelXMLSerializer;
use intuitel\IntuitelController;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once("model/LOFactory.php");
require_once("model/intuitelLO.php");
require_once('model/serializer.php');
require_once('model/exceptions.php');

disable_moodle_page_exception_handler();

check_access();
$params = array();
$serializer = new IntuitelXMLSerializer();

$xml = get_input_message();
global $log;
$log->LogDebug("USE_PERF request received: $xml");
$response = IntuitelController::ProcessUsePerfRequest($xml);
header('Content-type: text/xml');
$log->LogDebug("USE_PERF response sent: $response");
echo $response;
