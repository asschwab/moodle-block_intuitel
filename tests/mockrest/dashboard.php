<?php
require_once('../../../../config.php');
require_once("../../model/Intuitel.php");
require_once("../../model/intuitelController.php");
require_once ('../../model/intuitelLO.php'); 
require_once("../../locallib.php");
require_once("../../impl/moodle/moodleAdaptor.php");


$useridsleft= optional_param('useridsleft',null,PARAM_SEQUENCE);
$useridsright= optional_param('useridsright',null,PARAM_SEQUENCE);
$courseid= required_param('courseid',PARAM_INT);
$time = optional_param('time',null,PARAM_INT); // time window duration
$fromtime = optional_param('fromtime',null,PARAM_ALPHANUM);
$totime = optional_param('totime',null, PARAM_ALPHANUM);
$format = optional_param('format', 'svg', PARAM_ALPHA);
$rankdir = optional_param('rankdir', 'TD', PARAM_ALPHA);
$nocourse = optional_param('nocourse', true, PARAM_BOOL);
$limit = optional_param('limit',null,PARAM_INT);
$mintime = optional_param('mintime',null,PARAM_INTEGER);
$forcestructure = optional_param('forcestructure', false, PARAM_BOOL); // include course structure
$includeINTUITEL = optional_param('includeINTUITEL', false, PARAM_BOOL); // include course structure

$params=array('useridsleft','useridsright','courseid','fromtime','totime','time','nocourse','limit','mintime','forcestructure','rankdir','format','includeINTUITEL');
require_login($courseid,false);

// show headings and menus of page
$url =  new moodle_url('/blocks/intuitel/tests/mockrest/dashboard.php');
$PAGE->set_url($url,$_REQUEST);
$PAGE->set_title("Graphs");
// $PAGE->set_context($context_module);
$PAGE->set_heading("INTUITEL ACTIVITY MONITORING TOOL");
global $OUTPUT;
echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo "<form method=\"GET\" action=\"$url\">\n";
$i=0;
foreach($params as $name)
{
    if ($i++ % 3 ==0)
     echo "   <p>";
    echo "   $name:<input name=\"$name\" value=\"".$$name."\">";
}
echo "<input type=\"SUBMIT\"></form>";
echo $OUTPUT->box_end();
$useridsleft = array_reverse(explode(',',$useridsleft));
$useridsright = array_reverse(explode(',',$useridsright));
$rows = max(count($useridsleft),count($useridsright));
echo '<table border="1" width="100%">';
for($i=0;$i<$rows;$i++)
{
$userleft=array_pop($useridsleft);
$userright=array_pop($useridsright);
$graph_params="courseid=$courseid&fromtime=$fromtime&totime=$totime&rankdir=$rankdir&nocourse=$nocourse&limit=$limit&mintime=$mintime&format=$format&forcestructure=$forcestructure&includeINTUITEL=$includeINTUITEL";
$imgleft="graph.php?userid=$userleft&$graph_params";
$imgright="graph.php?userid=$userright&$graph_params";

echo '<tr><td width="50%" valign="top"><a target="blank" href="'.$imgleft.'">
        <img width="100%" src="'.$imgleft.'"/></a></td>
        <td width="50%" valign="top"><a target="blank" href="'.$imgright.'"><img width="100%" src="'.$imgright.'"/></a></td></br>';
}
echo '</table>';
echo $OUTPUT->footer();