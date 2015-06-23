<?php
require_once ('../../../../config.php');
require_once ("../../model/Intuitel.php");
require_once ("../../model/intuitelController.php");
require_once ('../../model/intuitelLO.php');
require_once ("../../locallib.php");
require_once ("../../impl/moodle/moodleAdaptor.php");

$useridsleft = required_param('useridsleft', PARAM_RAW);
$useridsright = required_param('useridsright', PARAM_RAW);
$courseid = required_param('courseid', PARAM_INT);
$time = optional_param('time', null, PARAM_INT); // time window duration
$fromtime = optional_param('fromtime', null, PARAM_ALPHANUM);
$totime = optional_param('totime', null, PARAM_ALPHANUM);
$format = optional_param('format', 'html', PARAM_ALPHA);
$rankdir = optional_param('rankdir', 'TD', PARAM_ALPHA);
$nocourse = optional_param('nocourse', true, PARAM_BOOL);
$limit = optional_param('limit', null, PARAM_INT);
$mintime = optional_param('mintime', null, PARAM_INTEGER);
$params = array(
        'useridsleft',
        'useridsright',
        'courseid',
        'fromtime',
        'totime',
        'time',
        'nocourse',
        'limit',
        'mintime',
        'format'
);
require_login($courseid, false);
global $output;
if ($format!='csv')
{
// show headings and menus of page
$url = new moodle_url('/blocks/intuitel/tests/mockrest/export.php');
$PAGE->set_url($url, $_REQUEST);
$PAGE->set_title("Export");
// $PAGE->set_context($context_module);
$PAGE->set_heading("INTUITEL ACTIVITY MONITORING TOOL EXPORT");
global $OUTPUT;
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo "<form method=\"GET\" action=\"$url\">\n";
$i = 0;
foreach ($params as $name) {
    if ($i ++ % 3 == 0)
        echo "   <p>";
    echo "   $name:<input name=\"$name\" value=\"" . $$name . "\">";
}
echo "<input type=\"SUBMIT\"></form>";
echo $OUTPUT->box_end();
}

$useridsleft = array_reverse(explode(',', $useridsleft));
$useridsright = array_reverse(explode(',', $useridsright));
if ($fromtime)
    $fromtime = strtotime($fromtime);
else
    $fromtime = time()-$time;
if ($totime)
    $totime=strtotime($totime);
else
    $totime=time();
output_header($format);

foreach ($useridsleft as $userid)
{
    output_row($userid, $courseid, $fromtime, $totime,$format,'A');
}
foreach ($useridsright as $userid)
{ 
    output_row($userid, $courseid, $fromtime, $totime,$format,'B');
}
output_footer($format);

die();
function output_row($userid,$courseid,$fromtime,$totime,$format,$label='')
{
    $events = get_user_events($userid, $courseid, $fromtime, $totime);
    list ($revisits, $durations) = intuitel\IntuitelController::compute_revisits(
            $events, $totime);
    foreach ($durations as $event) {
        $date=date('Y-m-d H:i:s', $event->time);
        $lo=intuitel\Intuitel::getAdaptorInstance()->createLO($event->loId);
        switch($format)
        {
        	case "txt":
        	    echo "$event->userId->id;$date; $event->loId; $event->duration ; $label ; $lo->loName\n";
        	    break;
        	case "csv":
        	    global $output;
        	    fputcsv($output, array($event->userId->id,$date, $event->loId, $event->duration , $label,$lo->loName),";");
        	    break;
        	case "html":
            	echo "<tr><td>$event->userId->id</td></td><td>$date</td><td>$event->loId</td><td>$event->duration</td><td>$label</td><td>$lo->loName</td></tr>";
            	break;
        }
        }
}
function output_header($format)
{
    switch ($format)
    {
    	case "txt":
            echo "<pre>";
            break;
        case "html":
            echo '<table border="1" width="100%"><tr><th>User</th><th>Time</th><th>LoId</th><th>Duration</th><th>Label</th><th>Name</th></tr>';
            break;
        case "csv":
           
            // output headers so that the file is downloaded rather than displayed
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=data.csv');
            global $output;
            // create a file pointer connected to the output stream
            $output = fopen('php://output', 'w');
            // output the column headings
            fputcsv($output, array('User', 'Time', 'LoId','Duration','Label','Name'),";");
            break;
    }    
}
function output_footer($format)
{
    switch ($format)
    {
    	case "txt":
    	    echo "</pre>";
    	    echo $OUTPUT->footer();
    	    break;
    	case "html":
    	    global $OUTPUT;
    	    echo "</table>";
    	    echo $OUTPUT->footer();
    	    break;
    	case "csv":
    	    global $output;
    	    fclose($output);
    	    break;
    }

}
function get_user_events ($userid, $courseid, $fromtime, $totime, 
        $limit_event_num = null)
{
    $adaptor = intuitel\Intuitel::getAdaptorInstance();
    $courseLo = $adaptor->createLO(
            intuitel\Intuitel::getIDFactory()->getLoIdfromId('course', 
                    $courseid));
    $userID = intuitel\Intuitel::getIDFactory()->getUserId($userid);
    
    $events_user = $adaptor->getLearnerUpdateData(array(
            $userid
    ), $courseLo, $fromtime, $totime, false);
    
    $events = array();
    if (array_key_exists((string) $userID, $events_user))
        $events = $events_user[(string) $userID];
        // limit events to process
    if ($limit_event_num) {
        $events = array_slice($events, 0, $limit_event_num);
    }
    
    return $events;
}