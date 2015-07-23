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
 * Block intuitel for Moodle
 *
 * Module developed at the University of Valladolid
 * this module is provides as-is without any guarantee. Use it as your own risk.
 * @package block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright (c) 2014, INTUITEL Consortium
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
use intuitel\Intuitel;
use intuitel\IntuitelController;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once('locallib.php');
require_once('model/intuitelController.php');
require_once('model/exceptions.php');
require_once('model/KLogger.php');

global $CFG;
global $log;
/*
 * Proxy for the user interface.
 *
 * Params:
 *
 * _intuitel_intent: LORERESPONSE|TUGRESPONSE|LEARNERUPDATE
 * debug:	Boolean that enables outputting of debugging info
 * courseid: course from which the user is reporting
 * For TUGRESPONSE
 * cmid: coursemodule visited by user
 * debugresponse: index of the testing response (see tests/mockrest/intuitel.php)
 */
if (!isloggedin()) {
    echo "Need to be logged before using this script.";
    die();
}

global $log;
// Resend action to INTUITEL.
// Get LoID.
$debug = optional_param('debug', false, PARAM_BOOL);
$action = required_param('_intuitel_intent', PARAM_ALPHA); // LORERESPONSE|TUGRESPONSE|LEARNERUPDATE
/*
 * Process notifications from the user
 * Example testing URL:
 * http://localhost/moodle2/blocks/intuitel/IntuitelProxy.php?_intuitel_intent=LEARNERUPDATE&courseid=2&cmid=2&debugresponse=0
 * &debug=true&XDEBUG_SESSION_START=jpc
 */
if ($action == 'LEARNERUPDATE') {
    $courseid = required_param('courseid', PARAM_INT);
    require_login($courseid);
    $PAGE->set_url('/blocks/intuitel/IntuitelProxy.php');

    $cmid = required_param('cmid', PARAM_INT);
    session_write_close(); // Release the session to avoid block requests while waiting intuitel response.

    $adaptor = Intuitel::getAdaptorInstance();
    // Location.
    $dpi = optional_param('pixel_density', null, PARAM_INTEGER);
    if ($dpi != null) {
        $adaptor->registerEnvironment('dDensity', "$dpi", $USER, time());
    }
    /*
     * Some LEARNERUPDATE requests are only for reloading LOREs after a TUG answer
     * INTUITEL prefers to not receive LoIDs in this case.
     */
    $ignorelo = optional_param('ignoreLo', false, PARAM_BOOL);
    $html = intuitel_forward_learner_update_request($cmid, $courseid, $USER->id, $ignorelo);

    // If this is used in a iFrame send Moodle's headers to get styling.
    $includeheaders = optional_param('includeHeaders', false, PARAM_BOOL);
    if ($includeheaders) {
        $url = new moodle_url('/blocks/intuitel/IntuitelProxy.php');
        $url->param('_intuitel_intent', $action);
        $url->param('courseid', $courseid);
        $url->param('cmdid', $cmid);
        $PAGE->set_url($url);
        // TODO theme styling makes this to render out of the viewable area.
        // TODO include CSS manually to change  typography and style??
        echo $html;
    } else {
        echo $html;
    }
} else if ($action == 'TUGRESPONSE') {
    require_login();
    session_write_close(); // Release the session to avoid block requests while waiting intuitel response.
    $courseid = required_param('courseid', PARAM_INT);
    /*
     * YUI form submittion does not support two submit buttons this is a workaround.
     */
    $tugcancelledfield = optional_param('_intuitel_TUG_cancel', null, PARAM_ALPHA);
    if ($tugcancelledfield !== '') { // The browser use Javascript.
        if ($tugcancelledfield === 'true') {
            $_REQUEST['_intuitel_user_intent'] = 'cancel';
        } else {
            $_REQUEST['_intuitel_user_intent'] = 'submit';
        }
    }
    // JPC: end of workaround for YUI bug with forms.
    if ($_REQUEST['_intuitel_user_intent'] === 'submit') {
        // Ignore cancellation of the TUG messages.
        // TODO: Avoid sending this form in the User Interface but find a way to work without javascript too.
        $message = IntuitelController::ProcessTUGResponse($USER->id, $_REQUEST, $courseid);
        $log->LogDebug("LMS sends TUG answer: $message");
        intuitel_submit_to_intuitel($message);
    } else {

        $mid = required_param('mId', PARAM_ALPHANUMEXT);
        Intuitel::getAdaptorInstance()->logTugDismiss($courseid, $USER->id, $mid, 'DISMISS mId=' . $mid);
    }
} else if ($action == 'GEOLOCATION') {
    require_login();
    session_write_close(); // Release the session to avoid block requests while waiting intuitel response.
    $adaptor = Intuitel::getAdaptorInstance();
    // Location.
    $lat = required_param('lat', PARAM_FLOAT);
    $lon = required_param('lon', PARAM_FLOAT);
    $adaptor->registerEnvironment(
            'dLocation',
            // Alternative format can be GML like
            //         "<gml:Point srsName=\"http://www.opengis.net/gml/srs/epsg.xml#4326\">
            //         <gml:coordinates>$lat $lon</gml:coordinates>
            //         </gml:Point>"
            // GML Geometry Format.
            "EPSG:4326;POINT($lat $lon)", // EWKT format.
            $USER, time());
}