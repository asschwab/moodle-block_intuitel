<?php
use intuitel\Intuitel;
use intuitel\LOId;
use intuitel\CourseLO;
use intuitel\IntuitelController;
use intuitel\DumbIntuitel;
use intuitel\SmartyIntuitel;
require_once('../../../../config.php');
require_once("../../locallib.php");
require_once("../../impl/moodle/moodleAdaptor.php");
require_once("../../model/Intuitel.php");
require_once("../../model/intuitelController.php");
require_once('../../model/intuitelLO.php');
//require_once('DumbIntuitel.php');
require_once('SmartyIntuitel.php');

// test this with: http://localhost/moodle2/blocks/intuitel/tests/mockrest/intuitel.php?xml=%3CINTUITEL%3E%3CLearner%20uId=%22pepe%22/%3E%3C/INTUITEL%3E
// http://localhost/moodle2/blocks/intuitel/tests/mockrest/intuitel.php?xml=%3CINTUITEL%3E%3CLearner%20uId=%22pepe%22%20loId=%22wqHKFQmiYXEq4tE6y4BztVFIdzsIe2d7localhost-CO2%22%20debugcourse=%222%22/%3E%3C/INTUITEL%3E
// http://localhost/moodle2/blocks/intuitel/tests/mockrest/intuitel.php?debugresponse=true&userid=3&xml=%3CINTUITEL%3E%3CLearner%20uId=%22pepe%22%20loId=%22wqHKFQmiYXEq4tE6y4BztVFIdzsIe2d7localhost-CO2%22%20debugcourse=%222%22/%3E%3C/INTUITEL%3E
// accept: debugcourse with native courseid to simulare Lore Loids
//          debugresponse to enable HTML page formating
//          userid to use native userid in the request.
$debugresponse = optional_param('debugresponse',false, PARAM_ALPHANUM);
if ($debugresponse==false)
    disable_moodle_page_exception_handler();
$native_userid= optional_param('userid',null,PARAM_INTEGER);

$query=get_input_message();
$intuitelMsg= IntuitelController::getIntuitelXML($query);
//sleep(4);
if ($intuitelMsg->Learner) //Learner update message
{
if ($native_userid) // Debugging backdoor
    $learnerid=Intuitel::getIDFactory()->getUserId($native_userid);
else
    $learnerid=(string)$intuitelMsg->Learner['uId'];

$loId = new LOId((string)$intuitelMsg->Learner['loId']);
$nativeCourseId = (string)$intuitelMsg->Learner['debugcourse'];
$adaptor = Intuitel::getAdaptorInstanceForCourse();
$idFactory = Intuitel::getIdFactory();

if ($nativeCourseId)
{
	global $PAGE;
	$context=context_course::instance($nativeCourseId);
	$PAGE->set_context($context);
	$courseLoId = $idFactory->getLoIdfromId('course',$nativeCourseId);
	$course = $adaptor->createLO($courseLoId);
}
else
{
	$learningObject= $adaptor->createLO($loId);
	if ($learningObject instanceof CourseLO)
	{
		$course = $learningObject;
	}
	else // assume it is a moduleLo and try to navigate upwards
	{
		$sectionId = $learningObject->getParent();
		$section = $adaptor->createLO($sectionId);
		$courseId = $section->getParent();
		$course = $adaptor->createLO($courseId);
	}
}

$simulator = new SmartyIntuitel($adaptor,$course);
$simulator->process_learner_update_request($loId,$learnerid,$course);
$selected = $simulator->get_LO_recommendation();

$lore_fragment = '';

foreach ($selected as $lo)
{
	$lore_fragment.=
			'
			<intuirr:LorePrio loId="'.$lo->loId.'" value="'.$lo->score.'"></intuirr:LorePrio>';
}
$tugfragment = $simulator->get_tug_fragment($debugresponse);
$intuitel_header = <<<xml
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<intuirr:INTUITEL xmlns:intuirr="http://www.intuitel.eu/public/intui_DMRR.xsd">
<intuirr:Learner mId="12345678-1234-abcd-ef12-123456789012" uId="jmb0001">
<intuirr:Lore uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789013">
		<intuirr:LorePrio loId="LO4711" value="42"/>
</intuirr:Lore>
<intuirr:Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014"/>
xml;

$intuitel_footer = <<<xml
 <!-- LORE and TUG as specified in the respective sections -->
</intuirr:Learner>
</intuirr:INTUITEL>
xml;

$response1 = $intuitel_header.$intuitel_footer;
$response1 = str_replace('<intuirr:Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014"/>',(string)$tugfragment,$response1);
$response1 = str_replace('jmb0001', (string)$learnerid, $response1);
// Replace Placeholder with Lore fragment
$response1 = str_replace('<intuirr:LorePrio loId="LO4711" value="42"/>', (string)$lore_fragment, $response1);

header('Content-type: text/xml');
echo $response1;
} // end learner update message
else
if ($intuitelMsg->Tug) // tug delayed response
{
    header('Content-type: text/xml');
    echo $query;
}
