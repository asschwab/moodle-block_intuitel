<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Abstract class to ease the implementation of INTUITEL in a more LMS-independent manner
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;
require_once('intuitelLO.php');
require_once('exceptions.php');

abstract class IntuitelAdaptor
{
	/**
	 * Course for subsequent operations
	 * @var stdClass
	 */
	var $course;


	function __construct(\stdClass $course=null)
	{
		$this->course=$course;
	}

	/**
	 * Creates a new LearningObject representing an existing object in the LMS
	 * @param string $type
	 * @param string $loId
	 * @return intuitelLO new instance
	 * @throws intuitel\UnknownLOException
	 */
	public static function createLO(LOId $loId)
	{
		$idfactory= Intuitel::getIDFactory();
		$type = $idfactory->getType($loId);
		try{
		    $factory = Intuitel::getLOFactory($type);
		}catch(UnknownLOTypeException $ex)
		{
		    $factory= new GenericLOFactory();
		}
    	return $factory->createLO($loId);
	}
	/**
	 * Compute a list of LOs for this LMS
	 *
	 * @param array $attributes
	 *        	name=>value filter array
	 */
	public function findLObyAttributes(array $attributes) {

		if (array_key_exists( 'loId', $attributes )
			&& array_key_exists ( 'getFullCourse', $attributes )
			&& $attributes['getFullCourse'] == 'true')
		{
			// retrieve all objects in course
			$id = $attributes ['loId'];
			$loId = $id instanceof LOId?$id:new LOId( $id );
			try
			{
				$adaptor = Intuitel::getAdaptorInstanceForCourseLOId( $loId );
				$list = $adaptor->findLOAll ();
				return $list;
			}catch(UnknownLOException $ex)
			{
				return array();
			}
			catch(UnknownIDException $ex)
			{
				return array();
			}
		}
		else if (array_key_exists ( 'loId', $attributes ))
		{
			// return one object
			$id = $attributes ['loId'];
			$loId = $id instanceof LOId?$id:new LOId ( $id );

			try{
				$lo = IntuitelAdaptor::createLO($loId);
				return array (
						$lo
				);
			}catch(UnknownLOException $ex)
			{
				return array();
			}
			catch(UnknownIDException $ex)
			{
				return array();
			}
		} else { // search every course by params

			$courses = Intuitel::getIntuitelEnabledCourses();
			$total_list = array ();
			foreach ( $courses as $course ) {
				$filter = array (
						'loId' => $course->loId,
						'getFullCourse' => 'true'
				);
				$list = $this->findLObyAttributes ( $filter );
				$total_list = array_merge ($total_list, $list );
			}

			// filter out unwanted items
			$final_list = $this->filterUnmatched($total_list, $attributes );
			return $final_list;
		}
	}
	/**
	 *
	 * @param array(IntuitelLO) $total_list
	 *        	of IntuitelLO
	 * @param array $attributes
	 *        	for filtering by attributes
	 */
	function filterUnmatched($total_list, $attributes) {
		$filtered = array ();
		foreach ( $total_list as $lo ) {
			if ($lo->match ( $attributes ))
				$filtered [] = $lo;
		}
		return $filtered;
	}

	/**
	 * gets a native representation of a user with uid
	 * @param string $uid
	 * @return mixed native representation
	 * @throws UnknownUserException
	 */
	abstract public function getNativeUserFromUId(UserId $uid);
	/**
	 * Check passwd against LMS authentication chains
	 * @param string $user	native representation of the user
	 * @param string $password secret to authenticate with
	 * @throws UnknownUserException
	 * @return mixed|false native representation of the user
	 */
	abstract public function authUser($login, $password);
	/**
	 * List courses enabled for Inutitel in the whole LMS
	 * @return multitype:\intuitel\intuitelLO
	 */
	abstract public function getIntuitelEnabledCourses();
	/**
	 * List courses in which the user has proper permissions
	 * @param UserId $user_id
	 * @return array(CourseLO)
	 */
	abstract public function getCoursesOwnedByUser(UserId $user_id);
	/**
	 * Retrieves a list of Intuitel enabled courses in which the user is enrolled but without the capability of editing INTUITEL block (tipically it will return courses in which the user is a "student")
	 * @param UserId $user_id
	 * @return array(CourseLO)
	 */
	abstract public function getCoursesEnrolled(UserId $user_id);
	/**
	 * Retrieves a list of native user ids of users enrolled in a course but without the capability of editing INTUITEL block (tipically it will return courses in which the user is a "student")
	 * @param CourseLO $course
	 * @return array(int)  native user ids
	 */
	abstract public function getUsersEnrolled(CourseLO $course);
	/**
	 * get the USE data for a certain object and user (completion, grade, accessed, seenPercentage) for a USE request
	 * @param intuitelLO $lo
	 * @param int $native_user_id
	 */
	abstract public function getUseData($lo, $native_user_id);
	/**
	 * get the USE environment data for a certain user
	 * @param stdClass $native_user
	 * @param string $type
	 * @return array(EnvEntry) array of environmental properties
	 */
	abstract public function getUseEnvData($native_user, $type);
	/**
	 * register last seen environment metering.
	 * Allows registering more than one value for each $type and $native_user
	 * @param string $type
	 * @param string $value in native format.
	 * @param stdClass $native_user
	 * @param long $timestamp
	 * @return void
	 */
	abstract public function registerEnvironment($type,$value,$native_user,$timestamp);


	/**
	 * for each user in $user_ids, updates database table intuitel_polltimes with the time indicated as parameter (time when last poll of learner logs data)
	 * @param array $user_ids native user ids list
	 * @param long integer $time
	 */
	abstract public function markLearnerUpdatePollTime(array $user_ids,$time);
	/**
	 * get the LEARNER_UPDATE data for  certain users containing events (accesses to learning objects) after the given time
	 * grouped by userid
	 *
	 * @param array $native_user_ids list
	 * @param CourseLO|null $course object null means all intuitel-enabled courses
	 * @param long|null $from time window starting if null last polling record is used
	 * @param long|null $to time window ending Null end of records
	 * @param boolean $filter_offline_users exclude users who are not currently online (apply a last-time-seen criteria)
	 * @return array(array(VisitEvent)) of arrays userID=>array(VisitEvent)
	 */
	abstract public function getLearnerUpdateData(array $native_user_ids, CourseLO $course=null, $from=null, $to=null,$filter_offline_users=true);
	/**
	 * get the interactions of the user with INTUITEL system
	 * grouped by userid
	 *
	 * @param array $native_user_ids list
	 * @param CourseLO|null $course object null means all intuitel-enabled courses
	 * @param long|null $from time window starting if null last polling record is used
	 * @param long|null $to time window ending Null end of records
	 * @param boolean $filter_offline_users exclude users who are not currently online (apply a last-time-seen criteria)
	 * @return array(array(VisitEvent)) of arrays userID=>array(VisitEvent)
	 */
	abstract public function getINTUITELInteractions(array $native_user_ids, CourseLO $course=null, $from=null, $to=null,$filter_offline_users=true);

	/**
	 *
	 * @param \stdClass $nativedata
	 *        	Native record of LMS
	 * @return $factory
	 * @throws UnknownLOTypeException
	 */
	abstract function getGuessedFactory( $nativedata);
	/**
	 * @param string|null $type null means unknown. Tries to guess type.
	 * @return intuitelLO
	 * @param  $native
	 */
	abstract function createLOFromNative($native, $type=null);
	/**
	 * UniqueId assigned to the LMS
	 */
	abstract public function getLMSId();
	/**
	 * Properties defining the INTUITEL characteristics of this adaptation
	 * @return array
	 */
	abstract public function getLMSProfile();
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
     * @param courseid identification of the course in which context this TUG and LORE are displayed
     * @return string HTML
     */
	abstract public function generateHtmlForTugAndLore(\SimpleXMLElement $doc, $courseid);
	abstract public function logTugAnswer($courseid,$native_user_id,$mid,$info);
	abstract public function logTugDismiss($courseid,$native_user_id,$mid,$info);
	abstract public function logTugView($courseid,$native_user_id,$mid,$info);
	abstract public function logLoreView($courseid,$native_user_id,$mid,$info);
}
abstract class IntuitelAdaptorFactory
{
	/**
	 *
	 * @param \stdClass $course can be null if is to be used for LMS-wide operations
	 * @return IntuitelAdaptor
	 */
	public abstract function getInstanceForCourse(\stdClass $course=null);
	/**
	 *
	 * @param LOId $course
	 * @return IntuitelAdaptor
	 */
	public abstract function getInstanceForCourseLoID(LOId $course);

}