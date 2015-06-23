<?php
use intuitel\Intuitel;
use intuitel\LOId;
use intuitel\CourseLO;
use intuitel\IntuitelController;
use intuitel\DumbIntuitel;
use intuitel\SmartyIntuitel;
require_once('../../../../config.php');
require_once("../../model/Intuitel.php");
require_once("../../model/intuitelController.php");
require_once ('../../model/intuitelLO.php'); 
require_once("../../locallib.php");
require_once("../../impl/moodle/moodleAdaptor.php");

//  disable_moodle_page_exception_handler();
$userid= required_param('userid',PARAM_INT);
$courseid= required_param('courseid',PARAM_INT);
$time = optional_param('time',5*3600,PARAM_INT); // time window duration
$show = optional_param('show','graph',PARAM_ALPHA);
$fromtime_str = optional_param('fromtime',null,PARAM_ALPHANUM);
$totime_str = optional_param('totime',null, PARAM_ALPHANUM);
$format = optional_param('format', 'svg', PARAM_ALPHA);
$rankdir = optional_param('rankdir', 'LR', PARAM_ALPHA);
$supress_course = optional_param('nocourse', true, PARAM_BOOL);
$limit_event_num = optional_param('limit',null,PARAM_INT);
$min_time = optional_param('mintime',null,PARAM_INTEGER); // minimum time to consider a transition
$forcestructure = optional_param('forcestructure', false, PARAM_BOOL); // include course structure

require_login($courseid,false);

if ($fromtime_str)
    $fromtime = strtotime($fromtime_str);
else
    $fromtime = time()-$time;
if ($totime_str)
    $totime=strtotime($totime_str);
else
    $totime=time();

$adaptor = Intuitel::getAdaptorInstance();
$courseLo = $adaptor->createLO(Intuitel::getIDFactory()->getLoIdfromId('course',$courseid));
$userID = Intuitel::getIDFactory()->getUserId($userid);
 
$events_user = $adaptor->getLearnerUpdateData(array($userid),$courseLo,$fromtime,$totime,false);

$events=array();
if (array_key_exists((string)$userID,$events_user))
    $events = $events_user[(string)$userID];
// limit events to process
if ($limit_event_num)
{
    $events=array_slice($events,0,$limit_event_num);
}

if ($show=='events')
{
    header('Content-type: text/plain');
    foreach ($events as $event)
    {
        $lo=$adaptor->createLO($event->loId);
        $loType=Intuitel::getIDFactory()->getType($lo->loId);
        $time = date('Y-m-d H:i:s',$event->time);
        echo "$time  --> $lo->loName\n";
    }
    die;
}
list($revisits,$durations) = IntuitelController::compute_revisits($events,$totime);

$node_list=$revisits;//

$first_node = isset($durations[0]->loId)?(string)$durations[0]->loId:null;

$node_lines='';
$clusters=array();
$node_style="shape = box, style=\"rounded,filled\" fillcolor=white";
if ($forcestructure)
{
    $rankdir='TD';
    $constraint='constraint=no';
    $constraint_structure='style=dotted constraint=yes color=red';
}
else
{
    $constraint='constraint=yes';
    $constraint_structure='style=dotted constraint=no color=red';
}
if ($rankdir=='default')
    $graph_rankdir='';
else
    $graph_rankdir="rankdir=$rankdir;";
$graph_clustered='';
$graph_unclustered='';
$graph_start_node='';
$fromtime_label= date('Y-m-d H:i:s',$fromtime);
$totime_label= date('Y-m-d H:i:s',$totime);

/******************
 * Node lines
 */
foreach($node_list as $node=>$visits)
{
    $loId=new LOId($node);
    $lo=$adaptor->createLO($loId);
    $loType=Intuitel::getIDFactory()->getType($lo->loId);
    if ($supress_course && $loType == 'course')
        continue;
    $name=str_replace('"','',$lo->loName);   
    $node = loId_escape($lo->loId);
    list($imgurl,$url)=cleanHTML(generateHtmlModuleLink(Intuitel::getIDFactory()->getIdfromLoId($loId)));
    //$img = "<IMG SRC=\"$imgurl\"/>";
    $use_data= $adaptor->getUseData($lo,$userid);
    $label_grade_row='';
    if (isset($use_data['grade']))
        $label_grade_row = '<FONT POINT-SIZE="10">Final grade:'.number_format($use_data['grade']).'/'.number_format($use_data['grademax']).'</FONT>';
    if ($label_grade_row)
    {
        $label ="<<TABLE BORDER=\"0\">".
                "<TR><TD ROWSPAN=\"2\">$name</TD><TD ALIGN=\"LEFT\"><FONT POINT-SIZE=\"10\">$visits visits</FONT></TD></TR><TR><TD>$label_grade_row</TD></TR>".
                "</TABLE>>";
    }
    else
    {
        $label ="<<TABLE BORDER=\"0\">".
                "<TR><TD>$name</TD></TR><TR><TD><FONT POINT-SIZE=\"10\">$visits visits</FONT></TD></TR>".
                "</TABLE>>";
    }
    //$label = "\"$name\"";
    $line =  "\t$node [ label=$label , URL=\"$url\" $node_style ];\n";
    $node_lines =$node_lines. $line;
}

/*********************
 * Graph title
 */
$graph_title = "fontsize=20;\nlabelloc=\"t\";\nlabel = \"Graph of user $userid activity from $fromtime_label to $totime_label\";";

/***********************
 * transitions
 */ 
$previousEvent=null;
$durations=array_reverse($durations);
$num=1;

foreach ($durations as $event)
{
    $eventType=Intuitel::getIDFactory()->getType($event->loId);
    if ($supress_course && $eventType == 'course')
        continue;
    if ($previousEvent==null) // first iteration
    {
        $first_node=$event;
        $previousEvent=$event;
        $graph_start_node ="\t start_node [shape=doublecircle, fillcolor=black label=\"\"]\n";
        $graph_start_node.="\t".loId_escape($event->loId)."\n";//."[shape=box3d, style=filled, fillcolor=plum];\n";
        $graph_start_node .="\tstart_node  -> ";
        $graph_start_node.="\t".loId_escape($event->loId)."[ $constraint ];\n";
    }
    else
    {
        $previouslo=$adaptor->createLO($previousEvent->loId);
        $eventlo=$adaptor->createLO($event->loId);  
        if ($min_time==null || $previousEvent->duration>$min_time) // ignore too short transitions
        {
        $duration = $previousEvent->duration <60?"$previousEvent->duration sec.":strftime("%M min %S sec", $previousEvent->duration);
        $label = "<<TABLE VALIGN=\"MIDDLE\" COLOR=\"gray\" BGCOLOR=\"white\" BORDER=\"1\" CELLBORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"0\" >".
                 "<TR><TD ROWSPAN=\"2\" BGCOLOR=\"darkgray\"><font color=\"white\">$num</font></TD><TD><FONT POINT-SIZE=\"10\">$duration</FONT></TD></TR>";
        $label.='</TABLE>>';
       
        $line1= loId_escape($previousEvent->loId).' -> '.loId_escape($event->loId);
        $line2="[$constraint label = $label ];\n";
        //$line3="[label = \"$num ($previousEvent->duration s)\" constraint=false];\n";
        if ($eventlo->hasParent) //cluster
        {
            $clusters[(string)$eventlo->hasParent][]=loId_escape($event->loId)."[$node_style];\n";
        }
        
        if ($previouslo->hasParent==$eventlo->hasParent)
            $clusters[(string)$eventlo->hasParent][]=$line1.$line2;
            else
            $graph_unclustered.=$line1.$line2;
        } 
        $previousEvent=$event;
        $num++;
    }
}

/*********************
 * Create clusters
 */ 

foreach($clusters as $loidcluster=>$cluster)
{
    $cluster = array_unique($cluster);
    $clusterlo=$adaptor->createLO(new LOId($loidcluster));
    $cluster_name= isset($clusterlo->loName)?$clusterlo->loName:loId_escape($loidcluster);
    $clusterid=loId_escape($loidcluster);
    $cluster_lines = implode("\t",$cluster);
    
    $graph_clustered.=<<<cluster

subgraph cluster_$clusterid {
        label = "$cluster_name";
        color=black;
        bgcolor = "oldlace";
    	$graph_rankdir
        $cluster_lines
    }
cluster;
}
/*********************
 * Make course structure
 */
$structure_lines='';
if ($forcestructure)
{
$sectionsLoid=$courseLo->hasChild;
$lastModuleLoId=null;
$structure=array();
foreach($sectionsLoid as $sectionLoId)
{
    $sectionLo=$adaptor->createLO($sectionLoId);
    $modulesLoId = $sectionLo->hasChild;
    foreach ($modulesLoId as $moduleLoId)
    {
        if (!array_key_exists((string)$moduleLoId,$node_list))
            continue;
       //$structure[]=loId_escape($moduleLoId).' [ style="star"];';
       if ($lastModuleLoId!=null)
       {
          $structure[]=loId_escape($lastModuleLoId).' -> '.loId_escape($moduleLoId)."[$constraint_structure];";
       }
       $lastModuleLoId=$moduleLoId;
    }
}
$structure_lines = "\n\t".implode("\n\t",$structure)."\n";
}
/********************
 * Compound DOT file
 */
$graph_nodes=<<<head
    $node_lines
head;

$dotfile="digraph student_sequence{
$graph_rankdir
node [$node_style];
$graph_title
$graph_start_node
$graph_nodes
$graph_unclustered
$graph_clustered
$structure_lines
}";
if ($show=='dot')
{
header('Content-type: text/plain');
print $dotfile;die;
}


$dot_cmd = $CFG->block_intuitel_graphviz_command;//'dot -Tpng'
// find mimetype
// if (preg_match("/([a-z]+) -T([a-z]+)/", $dot_cmd, $results))
//     $format = isset($results[2])?$results[2]:null;
if ($format)
{

$dot_cmd.=' -T'.$format;

if ($format == 'svg')
    header('Content-type: image/svg+xml');
else
    header('Content-type: image/'.$format);
header("Content-Disposition: filename=\"course$courseid.user$userid.$format\"");
}

    
$descriptorspec = array(
        0 => array("pipe", "r"),  // stdin es una tubería usada por el hijo para lectura
        1 => array("pipe", "w"),  // stdout es una tubería usada por el hijo para escritura
        2 => array("file", "/tmp/graphviz-error-output.log", "a") // stderr es un fichero para escritura
);

$cwd = null;
$env = array('some_option' => 'aeiou');
$process = proc_open($dot_cmd, $descriptorspec, $pipes, $cwd, $env);

if (is_resource($process)) {
    // $pipes ahora será algo como:
    // 0 => gestor de escritura conectado al stdin hijo
    // 1 => gestor de lectura conectado al stdout hijo
    // Cualquier error de salida será anexado a /tmp/error-output.txt

    fwrite($pipes[0], $dotfile);
    fclose($pipes[0]);
    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    //Es importante que se cierren todas las tubería antes de llamar a proc_close para evitar así un punto muerto
    $return_value = proc_close($process);
}

function loId_escape($loId)
{
    return str_replace(array('-','.'),'_',$loId);
}
function cleanHTML($html)
{
    preg_match('/href="([^"><]+)">/',$html,$matches);
    $url = $matches[1];
    preg_match('/src="([^"]+)/',$html,$matches);
    $img = $matches[1];
   
//     $html=str_replace(array('class="" onclick=""',
//                             'class="iconlarge activityicon" alt=" " role="presentation"',
//                             '<span class="instancename">',
//                             '<span class="accesshide " >',
//                             '</span>'),'',$html);
//     $html=str_replace(  array('<a  href=','</a>','<img src='),
//                         array('<A HREF=','</A>','<IMG SRC='),$html);
    return array($img, $url);
}