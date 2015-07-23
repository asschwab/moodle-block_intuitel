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
 * Local functions for Intuitel for Moodle block
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use intuitel\AccessDeniedException;
use intuitel\idFactory;
use intuitel\Intuitel;
use intuitel\IntuitelAdaptor;
use intuitel\LOId;
use intuitel\IntuitelException;
use intuitel\ProtocolErrorException;
use intuitel\IntuitelController;
use intuitel\VisitEvent;
use intuitel\UnknownLOException;
use intuitel\IntuitelXMLSerializer;
use intuitel\UrlLOFactory;
use block_intuitel\event\tug_viewed;
require_once("model/LOFactory.php");
require_once("model/intuitelLO.php");
require_once('model/exceptions.php');
require_once('impl/moodle/moodleAdaptor.php');
require_once('model/intuitelAdaptor.php');
require_once('model/intuitelController.php');
require_once('model/serializer.php');
require_once('model/VisitEvent.php');

/**
 * Check access rules to this LMS Adaptor from an INTUITEL service.
 * IPs configured in each line of get_config('block_intuitel','allowed_intuitel_ips')
 * If localhost shoud be allowed '::1' need to be included in the list.
 * '*' in the list disable the filtering and allows all IPs
 * Others are blocked
 * @throws AccessDeniedException
 * @see settings.php
 * @return true if access is granted
 */
function intuitel_check_access()
{
	global $CFG;
	$ips=	get_config('block_intuitel','allowed_intuitel_ips');
	if (strlen($ips)>0)
	{
	    $ip_array = explode("\n", $ips);
	}
	else
	{
	    $ip_array = array();
	}
	//$ip_array[]='::1'; //   TODO local interface is allowed by default, check this assumption
	$remote_addr = $_SERVER['REMOTE_ADDR'];
	foreach ($ip_array as $ip)
	{
		$ip= trim($ip);
		if ($ip=="localhost")
		    $ip = "::1";
	 	if ($ip==$remote_addr || $ip =='*')
	 		return true;
// 	 	if ($remote_addr == "::1")
// 	 	    $remote_addr = '127.0.0.1';
	 	if (intuitel_ipv4_match($remote_addr, $ip))
	 	    return true;
	 	if (intuitel_ipv6_match($remote_addr, $ip))
	 	    return true;
	}
	throw new AccessDeniedException("$remote_addr is not allowed to access this script. Please configure it at INTUITEL general settings.");

}
/**
 * @author Alnitak at http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php5
 * @param unknown $ip
 * @param unknown $range
 * @return boolean
 */
function intuitel_ipv4_match($ip, $cidrnet)
{
    if ($ip=='::1')
        $ip='127.0.0.1';
    $parts= explode('/', $cidrnet);
    if (count($parts)!=2)
    {
        return false;
    }
    else
    {
       $subnet=$parts[0];
       $bits=$parts[1];
    }
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
    return ($ip & $mask) == $subnet;
}
/**
 * @author SNIFF at http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet
 * @param unknown $ip
 * @param unknown $cidrnet
 * @return boolean
 */
function intuitel_ipv6_match($ip_string, $cidrnet)
{
    $parts= explode('/', $cidrnet);
    if (count($parts)!=2)
    {
        return false;
    }
    else
    {
        $net=$parts[0];
        $maskbits=$parts[1];
    }
    $net=inet_pton($net);
    if (strlen($net)==4) // IPV4
        return false;
    $ip = inet_pton($ip_string);
    if (strlen($ip)==4)  // IPV4
        return false;

    $binaryip=intuitel_inet_to_bits($ip);
    $binarynet=intuitel_inet_to_bits($net);

    $ip_net_bits=substr($binaryip,0,$maskbits);
    $net_bits   =substr($binarynet,0,$maskbits);

    if($ip_net_bits!==$net_bits)
        return false;
    else
        return true;
}
function intuitel_inet_to_bits($inet)
{
    $unpacked = unpack('A16', $inet);
    $unpacked = str_split($unpacked[1]);
    $binaryip = '';
    foreach ($unpacked as $char) {
        $binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    return $binaryip;
}

/**
 *
 * @param string $xml
 * @param array $aditional_params
 * @return mixed
 */
function intuitel_submit_to_intuitel($xml, $aditional_params=array())
{
	$intuitelEndPoint = intuitel_get_service_endpoint();

	//debugging('connecting to: '.$intuitelEndPoint,DEBUG_DEVELOPER);
    $rest_client= new curl();
    $rest_client->setHeader(array('Content-Type: application/xml'));
    $return = $rest_client->post($intuitelEndPoint, $xml,['CURLOPT_POST'=>true,'CURLOPT_RETURNTRANSFER'=>true,'CURLOPT_TIMEOUT'=>120]);
    $info = $rest_client->info;

	if ($info['http_code']!=200)
	{
	    throw new ProtocolErrorException('Intuitel Service did not respond correctly. Please report to the administrator. Error code:'.$info['http_code'].' Cause:'.curl_error($ch).' Response:'.$return,$info['http_code']);
	}
	return $return;
}
/**
 * Report user's activities to INTUITEL.
 * @param unknown $cmid
 * @param unknown $courseid
 * @param unknown $user_id
 * @param boolean $ignorelo whether to send LoId to INTUITEL (ignored in simulation mode)
 * @return Ambigous <string, lang_string, unknown, mixed>
 */
function intuitel_forward_learner_update_request($cmid,$courseid,$user_id,$ignorelo=false)
{
    $debug = optional_param('debug', false, PARAM_BOOL);
    $debug_response = optional_param('debugresponse', null, PARAM_ALPHANUM); // this param instruct Intuitel mock objects to respond with a pre-recorded response.

    $mmid = Intuitel::getIDFactory()->getNewMessageUUID();
    if (empty($cmid)) {
        $loId = Intuitel::getIDFactory()->getLoIdfromId('course', $courseid);
    } else {
        $loId = Intuitel::getIDFactory()->getLoIdfromId('module', $cmid);
    }
    // GET CURRENT USER id
    $userId = Intuitel::getIDFactory()->getUserId($user_id);

    global $CFG,$log;
    if ($ignorelo==true && get_config('block_intuitel','debug_server')==true)
    {
        $log->LogDebug("LMS send refreshing Learner message. Normal Learner procedure because in SIMULATED mode.");
    }
    if ($ignorelo==true && get_config('block_intuitel','debug_server')==false )// Ignore reporting to INTUITEL. Just send a Learner message with no LoId to repeat reasoning
                                                                        // But simulated mode need loid to be sent
    {
        $learnerUpdateMessage = '<INTUITEL>';
        $learnerUpdateMessage.='	<Learner mId="' . $mmid . '" uId="' . $userId->id . '"  time="' . time() . '"/>';
        $learnerUpdateMessage.='</INTUITEL>';
        $log->LogDebug("LMS send refreshing Learner message.");
    }
    else // normal reporting of current and unreported events
    {
        $log->LogDebug("LMS send normal Learner message for user $userId->id");
        if (get_config('block_intuitel','report_from_logevent'))
        {
            // get events unreported since last polling
            $events_user = Intuitel::getAdaptorInstance()->getLearnerUpdateData(array($user_id));

            $events = key_exists($userId->id, $events_user)?$events_user[$userId->id]:null;
        }

        if (!get_config('block_intuitel','report_from_logevent') || !$events)
        {
            $event = new VisitEvent($userId,$loId,time());
            $events = array($event);
        }
        $debug_param = get_config('block_intuitel','debug_server')?'debugcourse="' . $courseid . '"':''; // when using mock REST service help it with native courseid.
        $learnerUpdateMessage = '<INTUITEL>';
    $log->LogDebug("LearnerUpdate: LMS has found ".count($events)." events regarding user $userId .");
        foreach ($events as $ev)
        {
            $learnerUpdateMessage.='	<Learner mId="' . $mmid . '" uId="' . $ev->userId . '" loId="' . $ev->loId->id() . '" time="' . $ev->time . '" '.$debug_param.'/>';
        }
        $learnerUpdateMessage.='</INTUITEL>';
    }
    //  disable_moodle_page_exception_handler();
    $return = "No response from Intuitel";
$log->LogDebug("LMS sending: $learnerUpdateMessage");
    try {
        $return = intuitel_submit_to_intuitel($learnerUpdateMessage, array('debugresponse' => $debug_response));

        if ($debug)
            debugging("<p> Response from INTUITEL was: <p><pre>$return</pre>", DEBUG_DEVELOPER);
$log->LogDebug("LearnerUpdate: INTUITEL response was: $return.");

        // parse and generate TUG AND LORE html and messages
        list($html,$intuitel_elements) = IntuitelController::ProcessUpdateLearnerRequest($return,$courseid);

        if (count($intuitel_elements->Learner->Tug)>0)
        {
        $response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
        foreach ($intuitel_elements->Learner->Tug as $tug)
        {
            $mid=IntuitelXMLSerializer::get_required_attribute($tug,'mId');
            $text = str_replace('<![CDATA[','',$tug->MData);
            $text = str_replace(']]>','',$tug->MData);
            $text = strip_tags($text);

            Intuitel::getAdaptorInstance()->logTugView($courseid,$user_id,$mid,substr('mId='.$mid.' '.$text,0,255));

            IntuitelController::addTUGInmediateResponse($response,
                                                        IntuitelXMLSerializer::get_required_attribute($tug,'uId'),
                                                        IntuitelXMLSerializer::get_required_attribute($tug,'mId'),
                                                        "OK");
        }
        $xml=$response->asXML();
        intuitel_submit_to_intuitel($xml);
        $log->LogDebug("TUG inmediate response sent: $xml");
        }
        if (count($intuitel_elements->Learner->Lore)>0)
        {
        $response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
        foreach ($intuitel_elements->Learner->Lore as $lore)
        {
            $mid=IntuitelXMLSerializer::get_required_attribute($lore,'mId');
            $lores=array();

            foreach($lore->LorePrio as $lorePrio)
            {
                try{
                    $loid=IntuitelXMLSerializer::get_required_attribute($lorePrio,'loId');
                    $cmid = Intuitel::getIDFactory()->getIdfromLoId(new LOId($loid));
                    $lo = Intuitel::getAdaptorInstance()->createLO(new LOId($loid));
                    $lores[]= "($cmid)\"$lo->loName\"";
                } catch(Exception $ex)
                {
                    $lores[]= $loid;
                }
            }
            if (count($lores)) // log activity
            {
                Intuitel::getAdaptorInstance()->logLoreView($courseid,$user_id,$mid,substr(join(',',$lores),0,255));

            }

            $xml=IntuitelController::addLOREInmediateResponse($response,
                                                                IntuitelXMLSerializer::get_required_attribute($lore,'uId'),
                                                                IntuitelXMLSerializer::get_required_attribute($lore,'mId'),
                                                                "OK");
        }
        $xml=$response->asXML();
        intuitel_submit_to_intuitel($xml);
        }
        $html = '<div>' . $html . '</div>';
        }
        catch (ProtocolErrorException $exception)
        {
            // error
            $a = new stdClass();
            $a->service_point = intuitel_get_service_endpoint();
            $a->status_code = $exception->getStatusCode();
            $a->message = $exception->getMessage();
            $html = get_string('protocol_error_intuitel_node_malfunction','block_intuitel',$a);
	        $log->LogError("INTUITEL error: $a->message Return value:". $return);
        }
        return $html;
}
function intuitel_get_service_endpoint()
{
	global $CFG;
	if (get_config('block_intuitel','debug_server'))
	{
		$intuitelEndPoint = $CFG->wwwroot.'/blocks/intuitel/tests/mockrest/intuitel.php'; // Fake server
	}
	else
	{
		$intuitelEndPoint = get_config('block_intuitel','servicepoint_url');
	}
	return $intuitelEndPoint;
}

function intuitel_getMediaType($content){
	// add an i to the end of the pattern so no upper and lower case is distinguised
	$mediatype='';
	if(preg_match('/<img src/i',$content)==1)$mediatype='image';
	return $mediatype;
}

/**
 * Retrieves the (moodle) section object corresponding to an entry of the table course_sections
 * @param int $sectionid : id of the section
 * @return stdClass $section : object with information of a section (corresponding to a row in table course_sections)
 */

function intuitel_get_moodle_section($sectionid){
	global $DB;
	$section = $DB->get_record('course_sections',array('id'=>$sectionid));
	return $section;
}

/**
 * Retrieves the (moodle) course module object corresponding to an entry of the table course_modules
 * @param int $cmid : id of the course_module object
 * @return stdClass $cm : object with the information of the course_module object (corresponding to a row in the table course_modules)
 */
function intuitel_get_cm($cmid){
	global $DB;
	$cm = $DB->get_record('course_modules',array('id'=>$cmid));
	return $cm;
}


/**
 * Retrieves the name of the Moodle type of module the coursemodule corresponds to (e.g. Forum, page, quiz,etc.)
 * @param int $cmid  : id of the course module
 * @return string $module->name : name of the module
 * @throws UnknownLOException
 */
function intuitel_get_cm_type($cmid){
	global $DB;
	if(!$cm = $DB->get_record('course_modules',array('id'=>$cmid)))
		{
		throw new UnknownLOException($cmid);
		}
	if (!$module = $DB->get_record('modules', array ('id'=>$cm->module)))
		throw new UnknownLOException($cmid);
	return $module->name;
}

/**
 * Retrieves the first two characters of the lang code corresponding to the ISO-639-1 code (they go before the character "_")
 * @param string $lang : the code of the lang (eg. "es", "es_es", "de_kids",..)
 * @return string $parent_lang|NULL :  string of two characters with ISO-639-1 code of parent language, null if the parent part of the code does not have two characters as specified in ISO-639-1
 */

function intuitel_get_parent_lang($lang){
	$lang_parts=explode('_',$lang);
	if(strlen($lang_parts[0])==2)
		$parent_lang=$lang_parts[0];
	else
		$parent_lang=null;
	return $parent_lang;
}

/**
 * Retrieves the forced language of the course of null if not forced language.
 * @param course_modinfo $course_info : course_modinfo object of the course
 * @return string|NULL $lang: forced language of the course or null if not language is forced
 */
function intuitel_get_course_lang($course_info){
	$course=$course_info->get_course();
	if($course->lang!= null)
		$lang = intuitel_get_parent_lang($course->lang);
	else
		$lang= null;
	return $lang;
}

/**
 * Retrieves the completion status of the LO for an specific user (100 if this is completed and 0 if not), returns NULL if completion tracking is not enabled for that LO (or the entire course) and if the LO corresponds to a section or course LO
 * @author elever
 * @param cm_info $coursemodule_info : Moodle object with information of a module in a course
 * @param int $userid : moodle identifier of the user
 * @return int $completion_status | null
 */
function intuitel_get_completion_status(\cm_info $coursemodule_info, $userid){

	$completion = new \completion_info($coursemodule_info->get_course());
		if($completion->is_enabled($coursemodule_info)>0){//check if completion is enabled for a particular course and activity, returns 0 if it is not enabled, 1 if completion is enabled and is manual and 2 if automatic

			$completion_status=$completion->get_data($coursemodule_info,false, $userid); //this object has also information about viewed...is the row of the related table in the database
			$completion_status=$completion_status->completionstate;

			if($completion_status>0){
				$completion_status=100;  // moodle completion system retrieves 0 when the activity is not completed, 1 when the activity is completed (regardless of mark), 2 when the activity is completed with a passed mark, 3 when the activity is completed but with a fail mark
			}
		}else{
			$completion_status= null;
		}

	return $completion_status;

}


/**
 *  Retrieves the completion status of the course for an specific user (100 if this is completed and 0 if not), returns NULL if completion tracking is not enabled for the course and criteria for completing the course has not been set
 * @author elever
 *
 * @param int $lo_id : id of the course
 * @param int $native_user_id : moodle identifier of the user
 * @return int $completion_status| NULL
 */
function intuitel_get_completion_status_course($lo_id, $native_user_id){

	$course=get_course($lo_id);
	$completion = new \completion_info($course);

	global $DB;

	if($completion->is_enabled()>0){

		$criteria=$completion->get_criteria();

		if(!empty($criteria)){  //if criteria has been stablished for completing the course (in another case there is no course completion tracking)
			//when a user completes a course this is registered in table course_completions
			$timecompleted=$DB->get_field('course_completions','timecompleted', array('userid'=>$native_user_id,'course'=>$lo_id));
			if($timecompleted==false) $completion_status=0;//course has not been completed
			else
				$completion_status=100; // a record exists then the course has been completed
		}else{
			$completion_status=null;
		}
	}else{
		$completion_status=null;  // completion is not enabled for the course
	}
	return $completion_status;
}

/**
 * Retrieves the view/access status of the LO for an specific user (1 if the object has been viewed and 0 if not)
 * @author elever
 * @param int $cmid : identifier of the module in moodle
 * @param int $userid : moodle identifier of the user
 * @return boolean $access_status
 */
function intuitel_get_access_status($cmid, $userid){

		global $DB;
		$sql = 'SELECT * FROM {log} WHERE cmid = :cmid AND userid= :userid AND action LIKE \'%view%\'';
		$accesses=$DB->get_records_sql($sql, array('cmid'=>$cmid, 'userid'=>$userid));

		if(count($accesses)==0){
			$access_status=false;
		}else{
			$access_status=true;
		}
	return $access_status;
}

/**
 * Retrieves the view/access status of a course for an specific user (1 if the course has been viewed and 0 if not)
 * @author elever
 * @param int $courseid : native identifier of the course in moodle
 * @param int $userid : native identifier of the user
 * @return boolean $access_status | null
 */

function intuitel_get_access_status_course($courseid,$native_user_id){

	global $DB;
	$sql ='SELECT * FROM {log} WHERE course = :courseid AND userid= :userid AND action LIKE \'%view%\'';

	$accesses=$DB->get_records_sql($sql, array('courseid'=>$courseid, 'userid'=>$native_user_id));

	if(count($accesses)==0){
		$access_status=false;
	}else{
		$access_status=true;
	}

	return $access_status;

}

/**
 * Retrieves an array with grade information including grade obtained by a user for a LO in Moodle, maximum grade for that LO and min grade.  Returns null if the module is not gradable
 * or if the user has not been graded.
 * @author elever
 * @param cm_info $coursemodule_info : Moodle object with information of a module in a course
 * @param int $userid : moodle identifier of the user
 * @return array $grade_info | null
 */
function intuitel_get_grade_info(\cm_info $coursemodule_info, $userid){

		global $DB,$CFG;

		require_once($CFG->dirroot.'/lib/gradelib.php');
		require_once($CFG->dirroot.'/grade/querylib.php');

		// first check if the LO is gradable
		$gradable=false;
		$gradable_cms=grade_get_gradable_activities($coursemodule_info->course, $coursemodule_info->modname);
		foreach ($gradable_cms as $gradable_cm){
			if($gradable_cm->id==$coursemodule_info->id) $gradable=true;
		}

		// Second, check if the user has been graded
		if($gradable){
			if(grade_is_user_graded_in_activity($coursemodule_info, $userid)){

				$grade_complete_info=\grade_get_grades($coursemodule_info->course,'mod',$coursemodule_info->modname,$coursemodule_info->instance, $userid);

				$grade=array();
				$grade['grade'] = $grade_complete_info->items[0]->grades[$userid]->grade;
				$grade['grademax'] = $grade_complete_info->items[0]->grademax;
				$grade['grademin'] = $grade_complete_info->items[0]->grademin;

			}else{   //user not graded yet
				$grade=null;
			}
		}else{
			$grade=null;
		}

	return $grade;

}

/**
 * Retrieves an array with grade information including grade obtained by a user for a course in Moodle, maximum grade for that course and min grade.  Returns null if the module is not gradable
 * or if the user has not been graded.
 * @author elever
 * @param int $id_course : native id of the course
 * @param int $userid : moodle identifier of the user
 * @return array $grade_info | null
 */
function intuitel_get_grade_info_course($id_course, $userid){
	global $CFG;
	require_once($CFG->dirroot.'/lib/gradelib.php');
	require_once($CFG->dirroot.'/grade/querylib.php');
	$grade_info= \grade_get_course_grade($userid,$id_course);
	$grade=array();
	$grade['grade'] = $grade_info->grade;
	$grade['grademax'] = $grade_info->item->grademax;
	$grade['grademin'] = $grade_info->item->grademin;
	//TODO check result if user has not been graded in the course
	return $grade;

}

/**
 * From an array of native user ids, this function returns a new array containing only those ids belonging to online users
 * @param array $native_userids
 * @return array of native user ids
 */
function intuitel_get_online_users(array $native_userids){
	global $DB,$CFG;
	$online_users= array();
	list($insql,$inparams)=$DB->get_in_or_equal($native_userids);
	$timefrom = 300; //Seconds by default
	if (isset($CFG->block_online_users_timetosee)) {
		$timefrom = $CFG->block_online_users_timetosee * 60;
	}
	$now=time();
	$timefromsql = 100 * floor(($now - $timefrom) / 100); // Round to nearest 100 seconds for better query cache

	$params=array_merge($inparams,array($timefromsql));
	$sql="select id from {user} where id $insql and lastaccess > ?
	                           AND deleted = 0 ";

	$online_users= $DB->get_fieldset_sql($sql,$params); //returns first field (id of table user)
	return $online_users;
}

/**
 * MType: Description: Data provided as MData:
1
Simple message, not important
Text, if necessary with HTML formatting
2
Simple message, important
Text, if necessary with HTML formatting
3
Simple question, to be answered Yes/No
Text, if necessary with HTML formatting
4
Single choice question, to be answered with one out of n alternatives
Text, if necessary with HTML formatting; n different text pieces in structured writing
5
Multiple choice question, to be answered with any number out of n alternatives
Text, if necessary with HTML formatting; n different text pieces in structured writing
1000
LO recommendation
List of LOs and priorities
2000 + x
Emulation of USE, with value x is any valid MType < 1000
As required by the corresponding MType

100
Text question, to be answered with a natural language text
Text, if necessary with HTML formatting
200
Audio message
URI of the audio stream or audio file
300
Video message
URI of the video stream or video file
	*
<INTUITEL>
 <Learner mId="12345678-1234-abcd-ef12-123456789012" uId="jmb0001">
 	<Lore uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789013">
 	 <LorePrio loId="LO4711" value="42"/>
		<LorePrio loId="LO4712" value="50"/>
	</Lore>
	<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
		<MType>1</MType>
		<MData>Good Morning, dear Learner!</MData>
	</Tug>
</INTUITEL>
 * @param SimpleXMLElement $doc
 * @return string HTML
 */
function intuitel_generateHtmlForTugAndLore(SimpleXMLElement $intuitel_elements,$courseid)
{
	global $OUTPUT,$CFG,$PAGE;
	$html='';

foreach($intuitel_elements->Learner->Tug as $tug)
{
	$mtype = (string) $tug->MType;
	$mid = IntuitelXMLSerializer::get_required_attribute($tug,'mId');
	//escape dots from $mid for HTML
	$mid=str_replace('.','_',$mid);

	$tug_mdata =(string)$tug->MData;
	// filter $tug_mdata to decorate loIds
	$tug_mdata = intuitel_add_loId_decorations($tug_mdata);

	if ($mtype=='1') //Simple message, not important
	{
	    $html .=    intuitel_write_form_start($mid,$courseid).
		          '<p>'.$tug_mdata.'</p>'.
	               intuitel_write_form_end($mid,false);
		$html= intuitel_add_popup_notification($mid,$html);
		$html= intuitel_add_fadeIn_jscript($mid,$html);
	}
	else if ($mtype == '2') //Simple message, important
	{
	$html.= intuitel_write_form_start($mid,$courseid).
	      // $OUTPUT->pix_icon('i/warning', 'Important!').'<p>'.$tug_mdata.'</p>';
			'<p>'. $OUTPUT->pix_icon('warning','notice!','block_intuitel',array('width'=>32)).$tug_mdata.'</p>';
	//TODO printing code.
	$html .= intuitel_write_form_end($mid,false);
	$html = intuitel_add_fadeIn_jscript($mid,$html);
	$html = intuitel_add_printing_code($mid,$html);
	$html = intuitel_add_popup_notification($mid,$html);
	}
	else if ($mtype== '3') //Simple question, to be answered Yes/No
	{
	$html.=
			intuitel_write_form_start($mid,$courseid).
			'<p>'.$tug_mdata.'</p>
			<input type="RADIO" NAME="YesNo" value="Yes" checked >'.get_string('yes','moodle').'<br>
			<input type="RADIO" NAME="YesNo" value="No" >'.get_string('no','moodle').'<br>'.
			intuitel_write_form_end($mid);
	$html = intuitel_add_fadeIn_jscript($mid,$html);
	$html = intuitel_add_popup_notification($mid,$html);

	}
	else if ($mtype == '4') //Single choice question, to be answered with one out of n alternatives
	{
	    $tug_mdata = intuitel_change_select_into_radio($tug_mdata);
            $tug_mdata = intuitel_filter_out_trailing_lang_mark($tug_mdata);
		// TUG xml is supposed to be encoded as a W3C form
		$html .= intuitel_write_form_start($mid,$courseid).
			'<p>'.$tug_mdata.'</p>'.
			intuitel_write_form_end($mid);
		$html = intuitel_add_fadeIn_jscript($mid,$html);
		$html = intuitel_add_popup_notification($mid,$html);

	}
	else if ($mtype=='5') // Multiple choice question, to be answered with any number out of n alternatives
	{
		// TUG xml is supposed to be encoded as a W3C form
		$html .= 	intuitel_write_form_start($mid,$courseid).
			'<p>'.$tug_mdata.'</p>'.
			intuitel_write_form_end($mid);
		$html = intuitel_add_fadeIn_jscript($mid,$html);
		$html = intuitel_add_popup_notification($mid,$html);

	}
	else if ($mtype == '100')//Text question, to be answered with a natural language text
	{
		$html.=	intuitel_write_form_start($mid,$courseid).
			'<p>'.$tug_mdata.'</p>'.
			'<textarea name="text" rows="10" cols="30"></textarea>'.
			intuitel_write_form_end($mid);
		$html = intuitel_add_fadeIn_jscript($mid,$html);
		$html = intuitel_add_popup_notification($mid,$html);

	}
	else if ($mtype == '200')//Audio message URI of the audio stream or audio file
	{
	    $html .= intuitel_write_form_start($mid,$courseid);
		$html .= format_text('You have a recommendation in this Sound Message: <a href="'.$tug_mdata.'">Sound</a>');
		$html .= intuitel_write_form_end($mid,false);
	}
	else if ($mtype == '300')//Video message URI of the video stream or video file
	{
	    $html .= intuitel_write_form_start($mid,$courseid);
		$html .= format_text('You have a recommendation in this Video Message: <a href="'.$tug_mdata.'">Video message</a>');
		$html .= intuitel_write_form_end($mid,false);

		$html = intuitel_add_popup_notification($mid,$html);
		$html = intuitel_add_fadeIn_jscript($mid,$html);
	}
} // different Tugs
	/**
	 * LORE Messages
	 */
    $idFactory = Intuitel::getIDFactory();
	$html.='<div id="INTUITEL_LORE">';


	$header_shown=false;

	foreach($intuitel_elements->Learner->Lore as $lore)
	{
		$html.='<ul id="intuitel_lore_recommendations">';
		$ids=array();
		foreach($lore->LorePrio as $lorePrio)
		{
			$atts=$lorePrio->attributes();
			$loId=(string)$atts['loId'];
			$value = (string)$atts['value'];

			if(!$header_shown)
			{
				$html.=get_string('personalized_recommendations','block_intuitel');
				$header_shown=true;
			}


			$cmid = $idFactory->getIdfromLoId(new LOId($loId));
			$module_link = intuitel_generateHtmlModuleLink($cmid);
			$html.="<li loid=\"$loId\" id=\"intuitel_lore_$cmid\" >";
			if ($module_link)
				{
				$html.=$module_link;
				}
			else
				{
// TODO: labels are reported and can't be rendered nor hyper-linked
//       labels are excluded from rendering, other unknown KO are reported form debugging
				$type = Intuitel::getIDFactory()->getType(new LOId($loId));
				if ($type!='label')
				$html.="Activity with loId= $loId of type $type not found in this course. (Please report to developers)";
				}
			$html.='  <span id="intuitel_lore_score_navigation">'.($value/20).'</span> ';
			$html.='</li>';
		}
		$html.='</ul>';
	}
	$html.='</div>';

	return $html;
}
/**
 * Patch for filtering out the annoying @en sufix in some mLP options.
 */
function intuitel_filter_out_trailing_lang_mark($tug_mdata){
    $tug_mdata_filtered = str_replace ("@en", "", $tug_mdata);
    return $tug_mdata_filtered;
}
/**
 * Patch for rendering select TUGs with radio. Valid only for INTUITEL implemented by march 2015
 * @param unknown $tug_mdata
 */
function intuitel_change_select_into_radio($tug_mdata)
{
    $matches=array();
//     preg_match('/(.*)<select name=\"(.*)\[\]\".*<option.+value=\"(.*)\".*>(.*)<\/option>.*<option.+value=\"(.*)\".*>(.*)<\/option>.*<\/select>(.*)/', $tug_mdata,$matches);
    $ocurrences = preg_match('/(.*)<select name=\"(.*)\[\]\" >(.*)<\/select>(.*)/',$tug_mdata,$matches);
    if ($ocurrences==0)
        return $tug_mdata;
    $prefix= $matches[1];
    $fieldname=$matches[2];
    $options=$matches[3];
    $sufix=$matches[4];
    $html=$prefix."\n<br/>";
    $ocurrences=preg_match_all('/<option name=\".*\" value=\"(.*)\">(.*)<\/option>/sU', $options,$matches);
    for ($i=0;$i<$ocurrences;$i++)
    {
    $value=$matches[1][$i];
    $text=$matches[2][$i];
    $radio="<label style=\"white-space:nowrap\"><input type=\"radio\" name=\"$fieldname\" id=\"$fieldname\" value=\"$value\" style=\"word-wrap: break-word;\" /><span style=\"white-space:normal\">$text</span></label><br/>\n";
    $html.=$radio;
    }
    $html=$html."\n".$sufix;
    global $log;
    $log->LogDebug("TUG MData rewritten as:".$html);
    return $html;

}
function intuitel_generateHtmlModuleLink($cmid)
{
    global $PAGE;

    // TODO: this markup works only with modules
    $cm=intuitel_get_cm($cmid);
    $courseinfo= get_fast_modinfo($cm->course);
    $cm_info=$courseinfo->get_cm($cmid);

    $ids[]=$cmid;


    if ($cm_info->modname=='label')
    {
        //continue; // ignore label in the case LORE recommends one (illegally because Mapping won't report them.)
        $module_link="";
    }
    else
    {
        $module_link=$PAGE->get_renderer('core','course')->course_section_cm_name($cm_info);
    }
    return $module_link;
}
function intuitel_write_form_start($mid,$courseid)
{
	global $CFG;
	$wwwroot=$CFG->wwwroot;
	return <<<html
    <div id="INTUITEL_TUG_$mid">
	    <form id="INTUITEL_TUG_FORM_$mid" target="_blank"
			 action="$wwwroot/blocks/intuitel/IntuitelProxy.php"
			 onSubmit="return M.local_intuitel.submitTUG(Y,'$mid')" >
			 <input type="HIDDEN" NAME="courseid" value="$courseid"/>
html;
}
function intuitel_write_form_end($mid,$showSubmit=true)
{
    $html=<<<html
        <input type="HIDDEN" NAME="mId" value="$mid"/>
        <input type="HIDDEN" NAME="_intuitel_intent" value="TUGRESPONSE"/>
        <input type="HIDDEN" NAME="_intuitel_TUG_cancel" value=""/>
html;
    if ($showSubmit)
    {
        $submit_str=get_string('submit','block_intuitel');
        $html.=<<<html
         <button type="SUBMIT" onclick="this.form._intuitel_TUG_cancel.value='false';" NAME="_intuitel_user_intent" value="submit">$submit_str</button>
html;
    }
    $dismiss_str=get_string('dismiss','block_intuitel');
    $html.=<<<html
       <button type="SUBMIT" onclick="this.form._intuitel_TUG_cancel.value='true';" NAME="_intuitel_user_intent" value="cancel">$dismiss_str</input>
	</form>
 <div id="INTUITEL_TUG_MSG_$mid"></div></div>
html;
    return $html;
}
function intuitel_add_fadeIn_jscript($mid,$html)
{
	return $html;
	$jscript = <<<code

<script type="text/javascript">

	// Create a YUI instance using the io-form sub-module.
	YUI().use("transition","panel","node", function(Y)
	{


	var div=Y.one('#INTUITEL_TUG_$mid');
	  Y.one('#INTUITEL_TUG_$mid').transition({
	    duration: 1, // seconds
	    easing: 'ease-in',
	    height: 0,
	    width: 0,
	    left: '150px',
 	    top: '100px',
  		opacity: 0
		});

    });
       </script>
code;
	return $jscript;
}
/**
 * Complement the html to allow printing using a new popup
 * @param string $html content to include in the printing
 */
function intuitel_add_printing_code($mid, $html)
{
	global $OUTPUT;
	// TODO button and javascript for printing
// 	$button = $OUTPUT->pix_icon('t/print', 'Print');
 	return $html;
}
function intuitel_add_popup_notification($mid,$html)
{
	global $PAGE;
	$url='';
	$strmessages = 'Notice from INTUITEL';
	$strgomessage = 'go msg';
	$strstaymessage = 'Close';
	$jscode=<<<code
			<script type="text/javascript">
				M.local_intuitel.showTUGNotification(Y,"$mid");
			</script>
code;
	return $html.$jscode;
}
function intuitel_add_loId_decorations($tug_mdata)
{
    global $CFG;
    $tug_mdata= trim($tug_mdata);
    if (strpos($tug_mdata, '<![CDATA[')===0)
        $tug_mdata=substr($tug_mdata, 9,strlen($tug_mdata)-9-3);
    $regExpr=Intuitel::getIDFactory()->getIDRegExpr();
    preg_match_all($regExpr,$tug_mdata,$results);
    foreach($results[0] as $result)
    {
    $cmid = Intuitel::getIDFactory()->getIdfromLoId(new LOId($result));
    $lo = Intuitel::getAdaptorInstance()->createLO(new LOId($result));
    $type = Intuitel::getIDFactory()->getType(new LOId($result));
    if ($type!='section' && $type!='course')
	   $module_link = intuitel_generateHtmlModuleLink($cmid);
    else
        $module_link = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$cmid.'">'.$lo->loName.'</a>';

	$tug_mdata=str_replace($result,$module_link,$tug_mdata);
    }
    return $tug_mdata;
}
/**
 * Extract from the REQUEST the message from intuitel.
 * if method is GET param named 'xml' is used.
 * if method is POST request body is used.
 * @param $methods string to allow HTTP method. A list of allowed methods. i.e: 'POST,GET'
 * @return string xml message
 */
function intuitel_get_input_message($methods='POST,GET')
{

    // Parse GET parameters
    $http_method = $_SERVER ['REQUEST_METHOD'];
    if (stripos($methods, $http_method) === false)
    {
        header("Allow: $methods",true);
        throw new ProtocolErrorException ( "Check Data Model. This ServicePoint is not for $http_method-ing Information!!",405 );
    }

	if ($http_method =='POST')
	{
		//Check content_type
		$content_type = $_SERVER['CONTENT_TYPE'];
		if (strchr($content_type,'application/xml')===false &&
			strchr($content_type,'text/xml')===false)
			throw new ProtocolErrorException("Payload must be application/xml or text/xml but '$content_type' was received",415);
		return file_get_contents('php://input');
	}
	else
	if ($http_method =='GET')
	{
		return required_param('xml', PARAM_RAW);
	}
}
/**
 * Disable moodle exception hadler and page formatting
 * and set a default context for $PAGE->context
 */
function intuitel_disable_moodle_page_exception_handler()
{
	ob_start();
	set_exception_handler('intuitel_exception_handler');
	global $PAGE;
	$PAGE->set_context(context_system::instance());
}
function intuitel_exception_handler($exception)
{
	//ob_end_clean(); // JPC: uncomment it if you want to avoid debugging information in the response
	$code = 500;
	if ($exception instanceof IntuitelException)
	{
		$code=$exception->getStatusCode();
	}
	$string = trim(preg_replace('/\s+/', ' ', $exception->getMessage()));
	header('X-Error-Message: '.$string, true, $code);
	global $log;
	$log->LogError("Exception found:".$exception->getMessage()." Trace:".$exception->getTraceAsString());
	die($exception->getMessage());
}