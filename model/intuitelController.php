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
 * Bussiness logic of INTUITEL protocols
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;
use core\session\exception;
use block_intuitel\event\tug_response;
require_once("LOFactory.php");
require_once("intuitelLO.php");
require_once('serializer.php');
require_once(dirname(__FILE__).'/Intuitel.php');
require_once('exceptions.php');
require_once('KLogger.php');

global $log;
/**
 * Bussiness logic of INTUITEL protocols
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class IntuitelController
{

/**
 * Process a LORE request and generate proper response.
 * @param string $xml
 * @return string xml response
 */
	public static function ProcessLoreRequest($xml)
	{
	    global $log;
	    $log->LogWarn("Moodle support for INTUITEL uses communication mode 0. No LORE messages should reach me!! Message was: $xml",0);

		$intuitel_elements= IntuitelController::getIntuitelXML($xml);
		$lores = $intuitel_elements->Lore;
		IntuitelXMLSerializer::get_required_element($intuitel_elements, 'Lore');
		$intuitel_element = IntuitelXMLSerializer::getIntuitelXMLTemplate();

// 		$response= "<INTUITEL>";
		foreach ( $lores as $lore )
		{
			$mid = IntuitelXMLSerializer::get_required_attribute($lore,'mId');
			$uid = IntuitelXMLSerializer::get_required_attribute($lore,'uId');

			//  Check Existance of User uid
			$user_id=new UserId((string)$uid);
			try {
				$user = Intuitel::getAdaptorInstanceForCourse ( null )->getNativeUserFromUId ( $user_id );
				$retVal = 'PAUSE';
			}catch(UnknownUserException $ex){
				$retVal='ERROR';
			}
			catch (UnknownIDException $ex)
			{
				$retVal='ERROR';
			}
			$lore_element=$intuitel_element->addChild('Lore',null,INTUITEL_LMS_NAMESPACE_URI);
			$lore_element->addAttribute('uId', $uid);
			$lore_element->addAttribute('mId', $mid);
			$lore_element->addAttribute('retVal', $retVal);

// 			$response.= "<Lore uId=\"$uid\" mId=\"$mid\" retVal=\"$retVal\" />";
		}
// 		$response.="</INTUITEL>";
		$response = $intuitel_element->asXML();
		return IntuitelXMLSerializer::formatXmlString($response);
	}
	public static function ProcessLearnerRequest($xml)
	{
		$intuitel_elements= IntuitelController::getIntuitelXML($xml);

		$learners=IntuitelXMLSerializer::get_required_element($intuitel_elements,'Learners');

		$mid = IntuitelXMLSerializer::get_required_attribute($learners,'mId');

		$eventsOrderedbyUser=array();

		$adaptor=Intuitel::getAdaptorInstance();

		$events= $adaptor->getLearnerUpdateData(null,null,null,null,true);

				// componse the xml response
				$response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
				$learners_xml = $response->addChild('Learners',null,INTUITEL_LMS_NAMESPACE_URI);
				$dom_intuitel = dom_import_simplexml($learners_xml);
				$dom_comment = $dom_intuitel->ownerDocument->createComment('showing only on-line users');
				$dom_intuitel->appendChild($dom_comment);
				$learners_xml->addAttribute('mId', $mid);


				foreach($events as $userId=>$array_user_events)
				{
					$learner_xml = $learners_xml->addChild('Learner',null,INTUITEL_LMS_NAMESPACE_URI);
					$learner_xml->addAttribute('uId', $userId);
					foreach($array_user_events as $user_event)
					{
						$visitedLO_xml = $learner_xml->addChild('VisitedLO',null,INTUITEL_LMS_NAMESPACE_URI);
						$visitedLO_xml->addAttribute('loId', $user_event->loId->id());
						$visitedLO_xml->addAttribute('time', $user_event->time);
					}
				}
		return IntuitelXMLSerializer::formatXmlString($response->asXML());
	}
	public static function ProcessTUGRequest($xml)
	{
	    global $log;
	    $log->LogWarn("Moodle support for INTUITEL uses communication mode 0. No TUG messages should reach me!! Message was: $xml",0);
		$intuitel_elements= IntuitelController::getIntuitelXML($xml);
		$tug = $intuitel_elements->Tug;
		$atts = $tug->attributes();
		$mid = (string)$atts['mId'];
		$uid = (string)$atts['uId'];
		if (!$uid)
		{
		    throw new ProtocolErrorException("Bad message content: ".$xml,400);
		}
		// Check Existance of User uid
		$user_id=new UserId((string)$uid);
		try{
			$user = Intuitel::getAdaptorInstanceForCourse(null)->getNativeUserFromUId($user_id);
			$retVal='PAUSE';
		}catch(UnknownUserException $ex){
			$retVal='ERROR';
		}
		catch (UnknownIDException $ex)
		{
			$retVal='ERROR';
		}
		$response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
		IntuitelController::addTUGInmediateResponse($response,$uid,$mid,$retVal);
		return IntuitelXMLSerializer::formatXmlString($response->asXML());
	}
	public static function addTUGInmediateResponse(&$response,$uId,$mid, $retVal)
	{

	    $tug_xml = $response->addChild('Tug',null,INTUITEL_LMS_NAMESPACE_URI);
	    $tug_xml->addAttribute('uId', $uId);
	    $tug_xml->addAttribute('mId', $mid);
	    $tug_xml->addAttribute('retVal', $retVal);
	    return;
	}
	public static function addLOREInmediateResponse(&$response,$uId, $mid, $retVal)
	{
	    $lore_xml = $response->addChild('Lore',null,INTUITEL_LMS_NAMESPACE_URI);
	    $lore_xml->addAttribute('uId', $uId);
	    $lore_xml->addAttribute('mId', $mid);
	    $lore_xml->addAttribute('retVal', $retVal);
	    return;
	}
	public static function ProcessTUGResponse($native_user_id, array $params, $courseid)
	{
	    $userID= Intuitel::getIDFactory()->getUserId($native_user_id);
	    $response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
	    $tug_xml = $response->addChild('Tug',null,INTUITEL_LMS_NAMESPACE_URI);
	    $tug_xml->addAttribute('uId', $userID->id);
	    $tug_xml->addAttribute('mId', $params['mId']);

	    if ($params['_intuitel_user_intent']=='cancel')
	    {// dismiss TUG message	DEPRECATED according final D1.1 cancelled TUGs are not reported
	       throw new ProtocolErrorException("Cancelled TUGs shouldn't be reported to INTUITEL.",400);
		   $tug_xml->addAttribute('retVal', "OK");
	    }
	    else
	    { // process form and send it to INTUITEL
	    $msg_datas='';
	    foreach ($params as $name=>$value)
	    {
	        if ($name!='mId' && $name!='_intuitel_intent' && $name!='_intuitel_user_intent' && $name!='_intuitel_TUG_cancel' && $name!='courseid')
	        {
	            if (is_array($value)) // For  select multiple
	            {
	                foreach ($value as $val)
	                {
	                	$data_xml=$tug_xml->addChild("Data",null,INTUITEL_LMS_NAMESPACE_URI);
	                	$data_xml->addAttribute('name', $name);
						$data_xml->addAttribute('value', $val);
						$msg_datas.="$name = $val ,";
	                }
	            }
	            else
	            {
	            	$data_xml=$tug_xml->addChild("Data",null,INTUITEL_LMS_NAMESPACE_URI);
	            	$data_xml->addAttribute('name', $name);
					$data_xml->addAttribute('value', $value);
					$msg_datas.="$name = $value ,";
	            }
	        }
	    }
        if ($msg_datas!='')
        {
            $mid=required_param('mId', PARAM_ALPHANUMEXT);
            $info = 'ANSWER to mId='.$mid.'  Data:'.$msg_datas;
            Intuitel::getAdaptorInstance()->logTugAnswer($courseid,$native_user_id,$mid,$info);
        }
	   }
		return IntuitelXMLSerializer::formatXmlString($response->asXML());
	}
	public static function ProcessUsePerfRequest($xml)
	{
		$intuitel_elements= IntuitelController::getIntuitelXML($xml);

		$userPerfs = IntuitelXMLSerializer::get_required_element($intuitel_elements,'UsePerf');

		// OUTPUT Inmediate Response
 		$response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
	  	$adaptor=Intuitel::getAdaptorInstanceForCourse();

		foreach($userPerfs as $useperf)
		{
			$uid=IntuitelXMLSerializer::get_required_attribute($useperf,'uId');
			$mid=IntuitelXMLSerializer::get_required_attribute($useperf, 'mId');
			$loPerfs = $useperf->LoPerf;

			$user_id=new UserId((string)$uid);
			try {
				$user = $adaptor->getNativeUserFromUId($user_id);
			}catch(UnknownUserException $ex)
			{
				$user=null;
			}
				$useperf_xml = $response->addChild('UsePerf',null,INTUITEL_LMS_NAMESPACE_URI);
				$useperf_xml->addAttribute('uId', $uid);
				$useperf_xml->addAttribute('mId', $mid);



				if($loPerfs->count()==0)
				{ // If loId-attribute is left blank, the LMS returns all available LO scores
						if($user==null){  //the user id is not known
							$LoPerf_xml = $useperf_xml->addChild('LoPerf',null,INTUITEL_LMS_NAMESPACE_URI);
							$LoPerf_xml->addAttribute('loId', '');
							$LoPerf_xml->addAttribute('mId', $mid);

							$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
							$score_xml->addAttribute('type', 'internal');
							$score_xml->addAttribute('value', 'learnerUnknown');


						}else{

							//all data for every LO should be returned, need all loIds of the Intuitel enabled courses in which the user is registered

							// list of intuitel courses in which the user is student
							$listCourseLO=Intuitel::getAdaptorInstanceForCourse()->getCoursesEnrolled($user_id);

							// get the learning objects of those courses
							$listLO=array();
							foreach($listCourseLO as $intuitelCourse){
								$adaptor=Intuitel::getAdaptorInstanceForCourseLOId($intuitelCourse->loId);
								$listLO = array_merge($listLO, $adaptor->findLOAll());
							}

							//TODO check: if no objects in courses or not enrolled in any course, what is the message to deliver?
							if(count($listLO)>0){
								foreach($listLO as $lo){
									$use_data=$adaptor->getUseData($lo,intval($user->id));
									$loId=$lo->getloId();

									//TODO this code that creates the xml message is the same to the one in the ELSE, should be unified.
									$LoPerf_xml = $useperf_xml->addChild('LoPerf',null,INTUITEL_LMS_NAMESPACE_URI);
									$LoPerf_xml->addAttribute('loId', $loId);

									if(isset($use_data['completion'])){
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'completion');
										$score_xml->addAttribute('value', $use_data['completion']);
									}


									if($use_data['accessed']===true){
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'accessed');
										$score_xml->addAttribute('value', 'true');
									}
									else{
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'accessed');
										$score_xml->addAttribute('value', 'false');
									}

									if(isset($use_data['grade'])){

										$grade= IntuitelController::get_scaled_grade($use_data['grade'],$use_data['grademin'],$use_data['grademax']);
										//$response.="<Score type=\"grade\" value=\"".$grade."\"/>";
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'grade');
										$score_xml->addAttribute('value', $grade);
									}

									if(isset($use_data['seenPercentage'])){
										//$response.="<Score type=\"seenPercentage\" value=\"".$use_data['seenPercentage']."\"/>";
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'seenPercentage');
										$score_xml->addAttribute('value', $use_data['seenPercentage']);
									}

								}
							}


						}
				}else{

					//CASE B loids are provided
					foreach ($loPerfs as $loPerf)
					{
						$loId=IntuitelXMLSerializer::get_required_attribute($loPerf,'loId');
						$loId = new LOId($loId);

						// get LO from LoId
						try{
							$lo = IntuitelAdaptor::createLO($loId);
						}catch(UnknownLOException $ex){
							$lo = null;
						}catch(UnknownIDException $ex)
						{
							$lo = null;
						}

						//$response.="<LoPerf loId=\"$loId\">";
						$LoPerf_xml = $useperf_xml->addChild('LoPerf',null,INTUITEL_LMS_NAMESPACE_URI);
						$LoPerf_xml->addAttribute('loId', $loId);

						if ($user!=null && $lo!=null)//if user and lo are known
						{

							$use_data=$adaptor->getUseData($lo,intval($user->id));


							if(isset($use_data['completion'])){
									//$response.="<Score type=\"completion\" value=\"".$use_data['completion']."\"/>";
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'completion');
										$score_xml->addAttribute('value', $use_data['completion']);
							}

							if($use_data['accessed']){
								//$response.="<Score type=\"accessed\" value=\"true\"/>";
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'accessed');
										$score_xml->addAttribute('value', 'true');
							}else{
								//$response.="<Score type=\"accessed\" value=\"false\"/>";
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'accessed');
										$score_xml->addAttribute('value', 'false');
							}

							if(isset($use_data['grade'])){

								$grade= IntuitelController::get_scaled_grade($use_data['grade'],$use_data['grademin'],$use_data['grademax']);
								//$response.="<Score type=\"grade\" value=\"".$grade."\"/>";
										$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
										$score_xml->addAttribute('type', 'grade');
										$score_xml->addAttribute('value', $grade);
							}

							if(isset($use_data['seenPercentage'])){
								//$response.="<Score type=\"seenPercentage\" value=\"".$use_data['seenPercentage']."\"/>";
								$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
								$score_xml->addAttribute('type', 'seenPercentage');
								$score_xml->addAttribute('value', $use_data['seenPercentage']);
								}
						}

							if ($user==null)
							{
// 								$response.="<Score type=\"internal\" value=\"learnerUnknown\" />";
								$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
								$score_xml->addAttribute('type', 'internal');
								$score_xml->addAttribute('value', 'learnerUnknown');

							}
							if ($lo==null)
							{
// 								$response.="<Score type=\"internal\" value=\"loIdUnknown\" />";
								$score_xml = $LoPerf_xml->addChild('Score',null,INTUITEL_LMS_NAMESPACE_URI);
								$score_xml->addAttribute('type', 'internal');
								$score_xml->addAttribute('value', 'loIdUnknown');
							}
// 							$response.="</LoPerf>";
					} // end foreach loPerf
				}// end else CASE B
// 				$response.="</UsePerf>";
			} //end foreach user

// 			$response.= "</INTUITEL>";

		return IntuitelXMLSerializer::formatXmlString($response->asXML());
	}
	public static function processUseEnvRequest($xml)
	{
		$intuitel_elements= IntuitelController::getIntuitelXML($xml);
		$uses = IntuitelXMLSerializer::get_required_element($intuitel_elements,'UseEnv');

		// OUTPUT
 		$response = IntuitelXMLSerializer::getIntuitelXMLTemplate();

 		$adaptor=Intuitel::getAdaptorInstanceForCourse();

		foreach($uses as $useenv)
		{
			$uid=IntuitelXMLSerializer::get_required_attribute($useenv,'uId');
			$mid=IntuitelXMLSerializer::get_required_attribute($useenv,'mId');

			$user_id=new UserId((string)$uid);
			try {
				$user = $adaptor->getNativeUserFromUId($user_id);
			}catch(UnknownUserException $ex)
			{
				$user=null;
			}

			if($user==null){  //the user id is not known
// 				$response.="<UseEnv uId=\"$uid\" mId=\"$mid\" retVal=\"ERROR\">";
				$useenv_xml = $response->addChild('UseEnv',null,INTUITEL_LMS_NAMESPACE_URI);
				$useenv_xml->addAttribute('uId', $uid);
				$useenv_xml->addAttribute('mId', $mid);
				$useenv_xml->addAttribute('retVal', 'ERROR');

			}else{ //check if the learner is logged in

				$active_user=array();
				$active_user=block_intuitel_get_online_users(array($user->id));
				if(!empty($active_user))
				{

// 					$response.="<UseEnv uId=\"$uid\" mId=\"$mid\" retVal=\"OK\">";
					$useenv_xml = $response->addChild('UseEnv',null,INTUITEL_LMS_NAMESPACE_URI);
					$useenv_xml->addAttribute('uId', $uid);
					$useenv_xml->addAttribute('mId', $mid);
					$useenv_xml->addAttribute('retVal', 'OK');
					//get use environment data
					$useenv_data=$adaptor->getUseEnvData($user);
					foreach ($useenv_data as $env)
					   {
// 					   $response.="<Data name=\"$env->type\" value=\"".$env->value."\"/>";
					   $data_xml = $useenv_xml->addChild('Data',null,INTUITEL_LMS_NAMESPACE_URI);
					   $data_xml->addAttribute('name', $env->type);
					   $data_xml->addAttribute('value', $env->value);
					   }

				}else
				{ //user is known but not online
// 					$response.="<UseEnv uId=\"$uid\" mId=\"$mid\" retVal=\"PAUSE\">";
					$useenv_xml = $response->addChild('UseEnv',null,INTUITEL_LMS_NAMESPACE_URI);
					$useenv_xml->addAttribute('uId', $uid);
					$useenv_xml->addAttribute('mId', $mid);
					$useenv_xml->addAttribute('retVal', 'PAUSE');
				}
			}

// 			$response.="</UseEnv>";
		} //end foreach user

// 		$response.= "</INTUITEL>";

		return IntuitelXMLSerializer::formatXmlString($response->asXML());
	}
	public static function ProcessAuthRequest($xml)
	{
	global $log;
		$intuitel_elements= IntuitelController::getIntuitelXML($xml);
		$auths = IntuitelXMLSerializer::get_required_element($intuitel_elements,'Authentication');
		$response =null;
		$response = IntuitelXMLSerializer::getIntuitelXMLTemplate();
		$global_validated=true; // As may be more than one auth request accumulate here de validation statusç
		$useridsvalidated=array();
		foreach($auths as $auth)
		{
			$uid=IntuitelXMLSerializer::get_required_attribute($auth,'uId');
			$mid=IntuitelXMLSerializer::get_required_attribute($auth,'mId');
			$paswd = (string) $auth->Pass;
			$user_id=new UserId((string)$uid);
			$adaptor=Intuitel::getAdaptorInstanceForCourse();
			try
			{
				$user = $adaptor->getNativeUserFromUId($user_id);
			// Validate password
				$validated = $adaptor->authUser($user,$paswd);
				$useridsvalidated[]=$uid;
				$log->LogDebug("Sucessfull login request '$mid' for user: $uid");
			}
			catch(UnknownUserException $ex)
			{
				$validated=false;
				$log->LogDebug("Failed login request '$mid' for user:$uid");
			}
			catch (UnknownIDException $ex)
			{
				$validated=false;
				$log->LogDebug("Failed login request '$mid' for unknown user:$uid");
			}

			if (!$validated)
			{
				$status = 'ERROR';
			}
			else
			{
				$status = "OK";
			}
			// OUTPUT Inmediate Response

			$auth_xml = $response->addChild('Authentication',null,INTUITEL_LMS_NAMESPACE_URI);
			$auth_xml->addAttribute('uId', $uid);
			$auth_xml->addAttribute('mId', $mid);
			$auth_xml->addAttribute('status', $status);
// 			$response.="<Authentication uId=\"$uid\" mId=\"$mid\" status=\"$status\">";

			if ($validated)
			{
				// get courses with proper capabilities
				$courses= $adaptor->getCoursesOwnedByUser($user_id);
				foreach ($courses as $course)
				{
					$loId = $course->getLoId()->id();

// 					$response.="<LoPerm loId=\"$loId\"/>";
					$loperm_xml=$auth_xml->addChild('LoPerm',null,INTUITEL_LMS_NAMESPACE_URI);
					$loperm_xml->addAttribute('loId', $loId);

				}
			}
// 			$response.="</Authentication>";
        $global_validated &=$validated;
		}

// 		$response.= "</INTUITEL>";

		return array($global_validated,$useridsvalidated,IntuitelXMLSerializer::return_xml($response));
	}
	/**
	 * Parse xml response and generate proper html to be inserted in a DIV in the user interface
	 * @throws ProtocolErrorException if userId is not current user
	 *
	 *
	 * @param string $xml
	 * @return array(string,SimpleXMLElement)
	 */
	public static function ProcessUpdateLearnerRequest($xml,$courseid)
	{

		$intuitel_elements= IntuitelController::getIntuitelXML($xml);
		$learner = IntuitelXMLSerializer::get_required_element($intuitel_elements,'Learner');
		$user_id=IntuitelXMLSerializer::get_required_attribute($learner,'uId');

		$adaptor=Intuitel::getAdaptorInstanceForCourse();
		$user = $adaptor->getNativeUserFromUId(new UserId($user_id));

		global $USER;
		if ($USER->id != $user->id)
		{
			throw new AccessDeniedException("User $user->id is not current user");
		}

		// Generate HTML for the TUG and LORE parts
		$html = $adaptor->generateHtmlForTugAndLore($intuitel_elements,$courseid);

		return array($html,$intuitel_elements);
	}
	/**
	 * scale the grade for intuitel valid values (integer in the interval [INTUITEL_MIN_GRADE,INTUITEL_MAX_GRADE]
	 * @author elever
	 * @param float $rawgrade : grade that will be scaled
	 * @param float $rawgrade_max : maximum grade from source scale
	 * @param float $rawgrade_min : minimum grade from source scale
	 * @return int $scaled_value : rounded and scaled grade
	 */

	private static function get_scaled_grade($rawgrade, $rawgrade_min, $rawgrade_max){

		//TODO check this function after asking Peter/Florian if higher grade is 1 or 6
		$intuitel_grade_max=INTUITEL_MAX_GRADE;
		$intuitel_grade_min=INTUITEL_MIN_GRADE;

		if($rawgrade_min == $rawgrade_max){
			$scaled_value= $intuitel_grade_max;
		}else{
			$scaled_value= round($intuitel_grade_min + (($rawgrade - $rawgrade_min)* ($intuitel_grade_max - $intuitel_grade_min) / ($rawgrade_max-$rawgrade_min)));
		}
		return $scaled_value;

	}
	/**
	 * @see IntuitelController::compute_durations
	 * @param array $eventsArray
	 * @return array:VisitEvent array:VisitEvent  durations,revisits arrays
	 */
	public static function compute_revisits(array $eventsArray, $totime=null)
	{
	    $revisits = array();
	    $durations = IntuitelController::compute_durations($eventsArray,$totime);
	    $dur_obj=new \ArrayObject($durations);
	    $duration_copy= $dur_obj->getArrayCopy(); // copy to process and destroy with array_pop

	    while (($current = array_pop($duration_copy))!=null)
	    {
	        if (array_key_exists($current->loId->id,$revisits)==true)
	        {
	            $count = $revisits[$current->loId->id];
	        }
	        else
	        {
	            $count=0;
	        }
	        $revisits[$current->loId->id]= $count+1;
	    }
	    return array($revisits, $durations);
	}
	/**
	 * Calculate an array with differential information and without contiguous visit events to Course Modules.
	 * A chain of events regarding to the same loId is filtered out.
	 * @param array:VisitEvent $eventsArray ordered by time ASC
	 * @param int timestamp of the end of the time window if null time() is assumed
	 * @return array:VisitEvent with duration field computed. ordered by time. Newer first. Course and sections are excluded
	 */
	public static function compute_durations($eventsArray,$totime=null)
	{
	    $delays= array();
	    $lastseen = null;
	    $current = null;
	    if ($totime==null)
	        $totime=time();
	$eventsArray=array_reverse($eventsArray); // older last
	    while (($current = array_pop($eventsArray))!=null) // get oldest event
	    {
	        if ($lastseen==null || $lastseen->loId != $current->loId)
	        {
	        try
		    {
		    if ($current instanceof intuitel\VisitEvent)
		    {
		        $type =Intuitel::getIDFactory()->getType($current->loId);
		    }
		    else
		    {
		        $type = 'Interaction';
		    }

		        if (
// 		              $type!='course' &&
		              $type!='section')
		        	{
	        		$delays[]=$current;
		        	}
		    }catch(UnknownLOException $e)
			{
			// ignore this event (probably a deleted content)
			}

			    if ($lastseen!=null)
			        $lastseen->duration = min(($current->time - $lastseen->time), 15*60); // limit 15 min
			// TODO detect and exclude logoffs and absentions
	            $lastseen=$current;
	        }

	    }
	    if ($lastseen)
	        $lastseen->duration = $totime - $lastseen->time;
	    return array_reverse($delays);
	}
	/**
	 * Return only the elements inside INTUITEL element that belongs to INTUITEL namespace
	 * @see INTUITEL_LPM_NAMESPACE_URI constant
	 * @param string $xml
	 * @throws ProtocolErrorException
	 * @return SimpleXMLElement
	 */
	public static function getIntuitelXML($xml)
	{
		$intuitel_msg = simplexml_load_string($xml,null,LIBXML_NOCDATA);

		if ($intuitel_msg===false ||
			"INTUITEL" != (string)$intuitel_msg->getName())
		{
			throw new ProtocolErrorException('XML message empty or unparseable!:'.$xml,400);
		}
		$namespaces=$intuitel_msg->getNamespaces(true);
		if (array_search(INTUITEL_LPM_NAMESPACE_URI, $namespaces)!==false)
			return $intuitel_msg->children(INTUITEL_LPM_NAMESPACE_URI);
		else
		if (array_search(INTUITEL_RR_NAMESPACE_URI, $namespaces)!==false)
		    return $intuitel_msg->children(INTUITEL_RR_NAMESPACE_URI);
		else
			return $intuitel_msg->children();
	}


}
