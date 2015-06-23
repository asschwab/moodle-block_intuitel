<?php
use intuitel\Intuitel;
use intuitel\intuitel_get_service_endpoint;
use intuitel\IntuitelController;
use intuitel\ProtocolErrorException;
use block_intuitel\event;

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once ('locallib.php');
require_once ('model/intuitelController.php');
require_once ('model/exceptions.php');
require_once ('model/KLogger.php');

global $CFG;
global $log;
/**
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
if (! isloggedin()) {
    echo "Need to be logged before using this script.";
    die();
}

global $log;
// resend action to INTUITEL

// Get LoID
$debug = optional_param('debug', false, PARAM_BOOL);
$action = required_param('_intuitel_intent', PARAM_ALPHA); // LORERESPONSE|TUGRESPONSE|LEARNERUPDATE
/**
 * Process notifications from the user
 * Example testing URL:
 * http://localhost/moodle2/blocks/intuitel/IntuitelProxy.php?_intuitel_intent=LEARNERUPDATE&courseid=2&cmid=2&debugresponse=0&debug=true&XDEBUG_SESSION_START=jpc
 */
if ($action == 'LEARNERUPDATE')
{
    $courseid = required_param('courseid', PARAM_INT);
    require_login($courseid);
    $PAGE->set_url('/blocks/intuitel/IntuitelProxy.php');
    //$context = context_course::instance($courseid);
    //$PAGE->set_context($context); // needed for some internals of Moodle
    
    $cmid = required_param('cmid', PARAM_INT);
    session_write_close(); // release the session to avoid block requests while waiting intuitel response
    
    $adaptor= Intuitel::getAdaptorInstance();
    //location
    $dpi = optional_param('pixel_density',null,PARAM_INTEGER);
    if ($dpi!=null)
        $adaptor->registerEnvironment('dDensity',"$dpi",$USER,time());
    /**
     * Some LEARNERUPDATE requests are only for reloading LOREs after a TUG answer
     * INTUITEL prefers to not receive LoIDs in this case
     */
    $ignore_Lo=optional_param('ignoreLo', false, PARAM_BOOL);
    $html = forward_learner_update_request($cmid,$courseid, $USER->id,$ignore_Lo);
    
    // if this is used in a iFrame send Moodle's headers to get styling.
    $includeHeaders = optional_param('includeHeaders', false, PARAM_BOOL);
    if ($includeHeaders)
    {
        $url = new moodle_url('/blocks/intuitel/IntuitelProxy.php');
        $url->param('_intuitel_intent',$action);
        $url->param('courseid',$courseid);
        $url->param('cmdid',$cmid);
        $PAGE->set_url($url);
        // TODO theme styling makes this to render out of the viewable area
        // TODO include CSS manually to change  typography and style??
        echo $html;
    }
    else
    {
        echo $html;
    }
    
} else 
  if ($action == 'TUGRESPONSE')
  {
       require_login();
       session_write_close(); // release the session to avoid block requests while waiting intuitel response
       $courseid = required_param('courseid', PARAM_INT);
       /**
        * YUI form submittion does not support two submit buttons this is a workaround
        */
       $TUG_cancelled_field = $_REQUEST['_intuitel_TUG_cancel']; 
       if ($TUG_cancelled_field!=='') // the browser use Javascript
       {
           if ($TUG_cancelled_field==='true')
           $_REQUEST['_intuitel_user_intent'] = 'cancel';
           else 
           $_REQUEST['_intuitel_user_intent'] = 'submit';
               
       }
       // JPC: end of workaround for YUI bug with forms
       if ($_REQUEST['_intuitel_user_intent'] === 'submit') // ignore cancellation of the TUG messages
       // TODO: Avoid sending this form in the User Interface but find a way to work without javascript too.
       {
       $message= IntuitelController::ProcessTUGResponse($USER->id, $_REQUEST,$courseid);
       $log->LogDebug("LMS sends TUG answer: $message");
       submit_to_intuitel($message);
       }
       else 
       {
          
           $mId=required_param('mId', PARAM_ALPHANUMEXT);
           Intuitel::getAdaptorInstance()->logTugDismiss($courseid,$USER->id, $mId,'DISMISS mId='.$mId);
       }
        
  } else 
  if ($action == 'GEOLOCATION')
  {
    require_login();
    session_write_close(); // release the session to avoid block requests while waiting intuitel response
    $adaptor= Intuitel::getAdaptorInstance();
    //location
    $lat = required_param('lat',PARAM_FLOAT);
    $lon = required_param('lon',PARAM_FLOAT);
    $adaptor->registerEnvironment(
	'dLocation',
//"<gml:Point srsName=\"http://www.opengis.net/gml/srs/epsg.xml#4326\">
//    <gml:coordinates>$lat $lon</gml:coordinates>
//</gml:Point>" // GML Geometry Format
	"EPSG:4326;POINT($lat $lon)"// EWKT format
    ,$USER,time());
  }
