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
 * The Intuitel factory implementation for Moodle.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/intuitelLO.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/exceptions.php');
require_once('CourseLOFactory.php');
require_once('SectionLOFactory.php');
require_once('MoodleIdFactory.php');
require_once('ModuleLOFactory.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/VisitEvent.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/InteractionEvent.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/EnvEntry.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/KLogger.php');
global $DB;
global $CFG;
global $log;
if ($debug_file = get_config('block_intuitel','debug_file') // this only can be false when first installing
    || get_config('block_intuitel','debug_level') === KLogger::OFF ) {
    $log = new \KLogger($debug_file, get_config('block_intuitel','debug_level'));
}else{
     $log = new \KLogger('', \KLogger::OFF); // disabled
}

/**
 * LMS-specific methods to integrate INTUITEL protocols into Moodle
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodleAdaptor extends IntuitelAdaptor {
    /**
     *
     * @global \stdClass $DB
     * @param \stdClass $course
     */
    function __construct(\stdClass $course = null) {

        global $DB;
        parent::__construct($course);
    }

    /**
     * static initializer to let a chance to use cached instances
     * @param stdClass $course
     * @return \intuitel\IntuitelAdaptor
     * @deprecated
     */
    public static function getAdaptorForCourse(\stdClass $course = null) {
        return new moodleAdaptor($course);
    }

    /**
     * Creates an Adaptor object
     *
     * @param LOId $courseLOId
     * @return Ambigous <\intuitel\IntuitelAdaptor, \intuitel\moodleAdaptor>
     * @deprecated use IntuitelAdaptor methods
     * @throws UnknownLOException
     */
    public static function getAdaptorForCourseLOId(LOId $courseLOId) {
        global $DB;
        $courseid = Intuitel::getIDFactory()->getIdfromLoId($courseLOId);
        try {
            $moodlecourse = get_fast_modinfo($courseid)->get_course();
            return MoodleAdaptor::getAdaptorForCourse($moodlecourse);
        } catch (\dml_missing_record_exception $ex) {
            throw new UnknownLOException();
        }
    }

    /**
     * courses listing should be static because it is to be
     * invoked without using the __construct
     * @return array of courses native records
     */
    static function getIntuitelEnabledCoursesInMoodle() {
        global $DB;
        $courses = array();
        $blocks = $DB->get_records('block_instances', array('blockname' => 'intuitel'));
        foreach ($blocks as $blockInstance) {
            $context = \context::instance_by_id($blockInstance->parentcontextid);

            // TODO check in the parentcontextid corresponds to the context of a course... in other way $course will be null (eg. if INTUITEL block is added in a forum but not in the entire course, $course is null for that case)
            //$courseids[]=$context->instanceid;
            $course = $DB->get_record('course', array('id' => $context->instanceid));
            if ($course) {
                $courses[] = $course;
            }
        }
        return $courses;
    }

    public function getIntuitelEnabledCourses() {
        $coursesLo = array();
        $coursesNative = moodleAdaptor::getIntuitelEnabledCoursesInMoodle();
        foreach ($coursesNative as $courseNative) {
            $loId = Intuitel::getIDFactory()->getLoIdfromId('course', $courseNative->id);
            $coursesLo[] = $this->createLO($loId);
        }
        return $coursesLo;
    }

    /**
     * In moodle a course is owned by an INTUITEL user if he has the permission block/intuitel:externallyedit
     * @see \intuitel\IntuitelAdaptor::getCoursesOwnedByUser()
     * @global type $CFG
     * @param \intuitel\UserId $user_id
     * @return array
     */
    public function getCoursesOwnedByUser(UserId $user_id) {
        global $CFG;
        require_once $CFG->libdir . '/accesslib.php';
        $user = $this->getNativeUserFromUId($user_id);
        //Get courses enrolled
        $mycourses = enrol_get_all_users_courses($user->id, true, NULL, 'visible DESC,sortorder ASC');

        $editable_courses = array();
        foreach ($mycourses as $mycourse) {
            \context_helper::preload_from_record($mycourse);
            $ccontext = \context_course::instance($mycourse->id);

            if (has_capability('block/intuitel:externallyedit', $ccontext, $user)) {

                $course_info = get_fast_modinfo($mycourse->id, $user->id);
                $editable_courses[] = $this->createLOFromNative($course_info, 'course');
            }
        }
        return $editable_courses;
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::getLearnerUpdateData()
     * @global \stdClass $DB
     * @global type $USER
     * @global \intuitel\type $CFG
     * @param array $onlythisnativeuserids
     * @param type $course
     * @param type $from
     * @param type $to
     * @param type $filter_offline_users
     * @return type
     */
    function getLearnerUpdateData(array $onlythisnativeuserids = null, CourseLO $course = null, $from = null, $to = null,
            $filter_offline_users = true) {
        global $DB;

        $native_courseids = array();
        $native_activeuserids = array();

        if ($onlythisnativeuserids != null) {
            $native_userids = $onlythisnativeuserids;
        } else {
            $native_userids = array();
        }
        $adaptor = Intuitel::getAdaptorInstance();

        if ($course == null) {   // retrieve all intuitel enabled courses
            $courses = $adaptor->getIntuitelEnabledCourses();
        } else {
            $courses = array($course);
        }
        // collect course ids and user ids
        foreach ($courses as $course) {
            $native_courseids[] = Intuitel::getIDFactory()->getIdfromLoId($course->getloId());
            if ($onlythisnativeuserids == null) {  //get all users enrolled in the different intuitel enabled courses
                $native_userids = array_merge($native_userids, $adaptor->getUsersEnrolled($course));
            }
        }

        if ($filter_offline_users) {//filter out those users who are not currently online
            $native_activeuserids = intuitel_get_online_users($native_userids);
        } else {
            $native_activeuserids = $native_userids;
        }

        // if $USER is defined include him in the active users list (May be a teacher inpersonating a student for testing
        global $USER;
        {
            if (isset($USER) && isset($USER->loginascontext) && array_search($USER->id, $native_activeuserids) === false) {
                $native_activeuserids[] = $USER->id;
            }
        }
        // (if from==null) take polltimes from database (table intuilte_polltimes)
        $polltimes = array();
        $fromsql = null;
        if ($from === null) {
            //get the records ordered by polltime (From older to newer)
            if (count($native_activeuserids) > 0) {

                $polltimes = $DB->get_records_list('intuitel_polltimes', 'userid', $native_activeuserids, 'polltime');

                if (count($polltimes) > 0) {
                    $olderpolled = array_shift($polltimes);
                    $fromsql = $olderpolled->polltime;  // from is the oldest polltime
                } else {
                    $fromsql = 0; // if first polling, not lower limit of time
                }
            }
        } else {
            $fromsql = $from;
        }
        // retrieve from database the events using $from, or the older polltime (if from ==null)
        global $CFG;
        if ($CFG->version >= 2014051200) { // log table has changed
            return $this->getFilteredEventsPost2_7($native_activeuserids, $native_courseids, $to, $from, $fromsql, $polltimes);
        } else {
            return $this->getFilteredEventsPrior2_7($native_activeuserids, $native_courseids, $to, $from, $fromsql, $polltimes);
        }
    }

    function getFilteredEventsPost2_7($native_activeuserids, $native_courseids, $to, $from, $fromsql, $polltimes) {
        global $DB;
        $visitEvents = array();
        if (!empty($native_activeuserids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($native_activeuserids);
            list($insqlCourses, $inparamsCourse) = $DB->get_in_or_equal($native_courseids);

            if ($to == null) { //not upper limit of time
                $params = array_merge($inparamsCourse, array('from' => $fromsql), $inparams);
                $sql = "SELECT * FROM {logstore_standard_log} WHERE courseid $insqlCourses AND timecreated >= ?  AND userid $insql AND action ='viewed' ORDER BY timecreated ASC";
            } else {
                $params = array_merge($inparamsCourse, array('from' => $fromsql, 'to' => $to), $inparams);
                $sql = "SELECT * FROM {logstore_standard_log} WHERE courseid $insqlCourses AND timecreated >= ? and timecreated <= ? AND userid $insql AND action = 'viewed' ORDER BY timecreated ASC";
            }

            $events = $DB->get_records_sql($sql, $params);
            // iterate to create the array (if from==null) ignoring those that not match the  last polltime for the specifi user (the oldertime is lower than the polled time for that user)
            if ($from == null && $fromsql != 0) {
                $filteredEvents = array();
                foreach ($events as $event) {
                    $user_previouslypolled = false;
                    foreach ($polltimes as $polltime) {
                        if ($event->userid == $polltime->userid) {
                            $user_previouslypolled = $polltime->polltime;
                        }
                    }
                    if ($user_previouslypolled === false) {   //event  user never polled so the event should be included
                        $filteredEvents[$event->id] = $event;
                    }
                    if (($user_previouslypolled != false) && ($event->timecreated >= $user_previouslypolled)) {
                        $filteredEvents[$event->id] = $event;
                    }
                }
                $events = $filteredEvents;
            }

            foreach ($events as $event) {

                $userId = intuitel::getIDFactory()->getUserId($event->userid);

                if ($event->eventname == '\core\event\course_viewed') {  // the visited LO is a course
                    $loID = Intuitel::getIDFactory()->getLoIdfromId('course', $event->courseid);
                    $visitEvents[$userId->id()][$event->id] = new VisitEvent($userId, $loID, $event->timecreated);
                } else if ($event->target == 'course_module' && $event->action == 'viewed') {  //the visited LO is a module
                    $loID = Intuitel::getIDFactory()->getLoIdfromId('module', $event->contextinstanceid);
                    $visitEvents[$userId->id()][$event->id] = new VisitEvent($userId, $loID, $event->timecreated);
                }
            }
            if ($to == null) {
                $this->markLearnerUpdatePollTime($native_activeuserids, time());
            }  //mark polltimes to avoid re-poll same information
            else {
                $this->markLearnerUpdatePollTime($native_activeuserids, $to);
            }
        }
        return $visitEvents;
    }

    function getFilteredEventsPrior2_7($native_activeuserids, $native_courseids, $to, $from, $fromsql, $polltimes) {
        global $DB;
        $visitEvents = array();
        if (!empty($native_activeuserids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($native_activeuserids);
            list($insqlCourses, $inparamsCourse) = $DB->get_in_or_equal($native_courseids);

            if ($to == null) { //not upper limit of time
                $params = array_merge($inparamsCourse, array('from' => $fromsql), $inparams);
                $sql = "SELECT * FROM {log} WHERE course $insqlCourses AND time >= ?  AND userid $insql AND action LIKE '%view%' ORDER BY time ASC";
            } else {
                $params = array_merge($inparamsCourse, array('from' => $fromsql, 'to' => $to), $inparams);
                $sql = "SELECT * FROM {log} WHERE course $insqlCourses AND time >= ? and time <= ? AND userid $insql AND action LIKE '%view%' ORDER BY time ASC";
            }

            $events = $DB->get_records_sql($sql, $params);
            // iterate to create the array (if from==null) ignoring those that not match the  last polltime for the specifi user (the oldertime is lower than the polled time for that user)
            if ($from == null && $fromsql != 0) {
                $filteredEvents = array();
                foreach ($events as $event) {
                    $user_previouslypolled = false;
                    foreach ($polltimes as $polltime) {
                        if ($event->userid == $polltime->userid) {
                            $user_previouslypolled = $polltime->polltime;
                        }
                    }
                    if ($user_previouslypolled === false) {   //event  user never polled so the event should be included
                        $filteredEvents[$event->id] = $event;
                    }
                    if (($user_previouslypolled != false) && ($event->time >= $user_previouslypolled)) {
                        $filteredEvents[$event->id] = $event;
                    }
                }
                $events = $filteredEvents;
            }

            foreach ($events as $event) {

                $userId = intuitel::getIDFactory()->getUserId($event->userid);
                if ($event->cmid == 0) { // cmid=0 is indicated for login into the system,etc, and also when the event refers to the "course"module
                    if ($event->module === 'course') {  // the visited LO is a course
                        $loID = Intuitel::getIDFactory()->getLoIdfromId('course', $event->course);
                        $visitEvents[$userId->id()][$event->id] = new VisitEvent($userId, $loID, $event->time);
                    }
                } else {  //the visited LO is a module
                    $loID = Intuitel::getIDFactory()->getLoIdfromId('module', $event->cmid);
                    $visitEvents[$userId->id()][$event->id] = new VisitEvent($userId, $loID, $event->time);
                }
            }
            if ($to == null) {
                $this->markLearnerUpdatePollTime($native_activeuserids, time());
            }  //mark polltimes to avoid re-poll same information
            else
                $this->markLearnerUpdatePollTime($native_activeuserids, $to);
        }
        return $visitEvents;
    }

    /**
     * get the interactions of the user with INTUITEL system
     * grouped by userid
     * {@inheritDoc}
     * @see \intuitel\IntuitelAdaptor::getINTUITELInteractions()
     * @global \intuitel\type $CFG
     * @param array $native_user_ids
     * @param \intuitel\CourseLO $course
     * @param type $from
     * @param type $to
     * @param type $filter_offline_users
     * @return type
     */
    function getINTUITELInteractions(array $native_user_ids = null, CourseLO $course = null, $from = null, $to = null, $filter_offline_users = true) {
        global $CFG;
        if ($CFG->version >= 2014051200) {
            return $this->getINTUITELInteractionsPost2_7($native_user_ids, $course, $from, $to, $filter_offline_users);
        } else {
            return $this->getINTUITELInteractionsPre2_7($native_user_ids, $course, $from, $to, $filter_offline_users);
        }
    }

    /**
     * Implementation that uses logstore_standard_log table. In the future may be necessary to use EventAPI
     * with subscription to events instead of reading logs.
     * TODO: research the convenience of reacting to the events to report them to INTUITEL using a different Comstyle.
     * @param array $native_user_ids
     * @param CourseLO $course
     * @param string $from
     * @param string $to
     * @param string $filter_offline_users
     * @return multitype:\intuitel\InteractionEvent
     */
    function getINTUITELInteractionsPost2_7(array $native_user_ids = null, CourseLO $course = null, $from = null, $to = null, $filter_offline_users = true) {
        global $DB;

        $native_courseids = array();
        $native_userids = array();

        $interactionEvents = array();
        if ($native_user_ids != null) {
            $native_userids = $native_user_ids;
        }
        $adaptor = Intuitel::getAdaptorInstance();

        if ($course == null) {   // retrieve all intuitel enabled courses
            $courses = $adaptor->getIntuitelEnabledCourses();
        } else {
            $courses = array($course);
        }

        // collect course ids and user ids
        foreach ($courses as $course) {
            $native_courseids[] = Intuitel::getIDFactory()->getIdfromLoId($course->getloId());
            if ($native_user_ids == null) {  //get all users enrolled in the different intuitel enabled courses
                $native_userids = array_merge($native_userids, $adaptor->getUsersEnrolled($course));
            }
        }

        if ($filter_offline_users) {//filter out those users who are not currently online
            $native_activeuserids = intuitel_get_online_users($native_userids);
        } else {
            $native_activeuserids = $native_userids;
        }


        if ($from === null) {
            $fromsql = 0;
        } else {
            $fromsql = $from;
        }
        // retrieve from database the events using $fromsql
        if (!empty($native_activeuserids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($native_activeuserids);
            list($insqlCourses, $inparamsCourse) = $DB->get_in_or_equal($native_courseids);

            if ($to == null) { //not upper limit of time
                $params = array_merge($inparamsCourse, array('from' => $fromsql), $inparams);
                $sql = "SELECT * FROM {logstore_standard_log} WHERE courseid $insqlCourses AND timecreated >= ?  AND userid $insql AND component = 'block_intuitel' ORDER BY timecreated ASC";
            } else {
                $params = array_merge($inparamsCourse, array('from' => $fromsql, 'to' => $to), $inparams);
                $sql = "SELECT * FROM {logstore_standard_log} WHERE courseid $insqlCourses AND timecreated >= ? and timecreated <= ? AND userid $insql AND component = 'block_intuitel' ORDER BY timecreated ASC";
            }

            $events = $DB->get_records_sql($sql, $params);

            foreach ($events as $event) {
                $userId = intuitel::getIDFactory()->getUserId($event->userid);
                $otherfields = unserialize($event->other);
                $description = $event->action . ':' . $otherfields['info'];
                $interactionEvents[$event->id] = new InteractionEvent($userId, $event->id, $description, $event->timecreated);
            }
        }
        return $interactionEvents;
    }

    function getINTUITELInteractionsPre2_7(array $native_user_ids = null, CourseLO $course = null, $from = null, $to = null, $filter_offline_users = true) {
        global $DB;

        $native_courseids = array();
        $native_userids = array();

        $interactionEvents = array();
        if ($native_user_ids != null) {
            $native_userids = $native_user_ids;
        }
        $adaptor = Intuitel::getAdaptorInstance();

        if ($course == null) {   // retrieve all intuitel enabled courses
            $courses = $adaptor->getIntuitelEnabledCourses();
        } else {
            $courses = array($course);
        }

        // Collect course ids and user ids.
        foreach ($courses as $course) {
            $native_courseids[] = Intuitel::getIDFactory()->getIdfromLoId($course->getloId());
            if ($native_user_ids == null) {  //get all users enrolled in the different intuitel enabled courses
                $native_userids = array_merge($native_userids, $adaptor->getUsersEnrolled($course));
            }
        }

        if ($filter_offline_users) {//filter out those users who are not currently online
            $native_activeuserids = intuitel_get_online_users($native_userids);
        } else {
            $native_activeuserids = $native_userids;
        }


        if ($from === null) {
            $fromsql = 0;
        } else {
            $fromsql = $from;
        }
        // Retrieve from database the events using $fromsql.
        if (!empty($native_activeuserids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($native_activeuserids);
            list($insqlCourses, $inparamsCourse) = $DB->get_in_or_equal($native_courseids);

            if ($to == null) { //not upper limit of time
                $params = array_merge($inparamsCourse, array('from' => $fromsql), $inparams);
                $sql = "SELECT * FROM {log} WHERE course $insqlCourses AND time >= ?  AND userid $insql AND module = 'INTUITEL' ORDER BY time ASC";
            } else {
                $params = array_merge($inparamsCourse, array('from' => $fromsql, 'to' => $to), $inparams);
                $sql = "SELECT * FROM {log} WHERE course $insqlCourses AND time >= ? and time <= ? AND userid $insql AND module = 'INTUITEL' ORDER BY time ASC";
            }

            $events = $DB->get_records_sql($sql, $params);

            foreach ($events as $event) {
                $userId = intuitel::getIDFactory()->getUserId($event->userid);
                $interactionEvents[$event->id] = new InteractionEvent($userId, $event->id, $event->action . ':' . $event->info, $event->time);
            }
        }
        return $interactionEvents;
    }

    /**
     * Find all Learning Objects in this course
     * @return multitype:
     */
    public function findLOAll() {
        $courseinfo = get_fast_modinfo($this->course);

        // the course
        $list_course = $this->fillArrayWithLOs(array($courseinfo), 'course');

        // compute sections
        $sections = $courseinfo->get_section_info_all();

        $list_sections = $this->fillArrayWithLOs($sections, 'section');
        $modules = $courseinfo->get_cms();
        $list_modules = $this->fillArrayWithLOs($modules, null);

        $list = array_merge($list_course, $list_sections, $list_modules);
        return $list;
    }

    /**
     *
     * @param array $rawDatas
     * @param string $type
     *        	null means unknown. Tries to guess type.
     * @return array of intuitelLO
     */
    private function fillArrayWithLOs(array $rawDatas, $type) {
        $list = array();
        $factory = null;
        foreach ($rawDatas as $data) {
            try {
                $newLO = $this->createLOFromNative($data, $type);
                if ($newLO != null) { // if section, empty ones are not added
                    $list [] = $newLO;
                }
            } catch (UnknownLOTypeException $e) {
                // unknown type: Wrap it with a GenericLO
                // TODO: use logging output
                //print ('unknown LOtype:' . $e->getMessage () . '\l\n') ;
                $factory = new GenericLOFactory ();
                $newLO = $factory->createLOFromNative($data);
                if ($newLO != null) { // empty ones are not added
                    $list [] = $newLO;
                }
            }
        }

        return $list;
    }

    function createLOFromNative($native, $type = null) {
        if ($type != null) {     // deterministic type
            $factory = Intuitel::getLOFactory($type);
        } else {     // guess type
            $factory = $this->getGuessedFactory($native);
        }
        $newLO = $factory->createLOFromNative($native);
        return $newLO;
    }

    function getGuessedFactory($data) {
        $type = null;
        if (isset($data->category) && isset($data->shortname)) {   // Moodle course
            $type = 'course';
        } else if ($data instanceof \cm_info) {   // || (($data->module)) // Moodle activity
            $type = $data->modname;
        } else if (isset($data->section)) {   // Moodle section
            $type = 'section';
        }

        try {
            $factory = Intuitel::getLOFactory($type);
            return $factory;
        } catch (UnknownLOTypeException $e) {
            $factory = new GenericLOFactory ();
            return $factory;
        }
    }

    public function getNativeUserFromUId(UserId $uid) {

        try {
            $userlogin = $uid->id();

// 		$parts=Intuitel::getIDFactory()->getIdParts($uid);
            $user_details = get_complete_user_data('username', $userlogin);
            if ($user_details) {
                return $user_details;
            } else {
                throw new UnknownUserException("User $uid->id is unknown.");
            }
        } catch (UnknownIDException $ex) {
            throw new UnknownUserException("User $uid->id is unknown." . $ex->getMessage());
        }
    }

    public function authUser($user, $password) {
        $authsequence = get_enabled_auth_plugins(true); // auths, in sequence
        foreach ($authsequence as $authname) {
            $authplugin = get_auth_plugin($authname);
            if ($authplugin->user_login($user->username, $password)) {
                return true;
            }
        }
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::getCoursesEnrolled()
     */
    public function getCoursesEnrolled(UserId $user_id) {
        $listCoursesEnrolled = array();
        $intuitelCourses = Intuitel::getIntuitelEnabledCourses();
        $ownedCourses = Intuitel::getAdaptorInstanceForCourse()->getCoursesOwnedByUser($user_id);
        $native_userid = $user = $this->getNativeUserFromUId($user_id)->id;
        foreach ($intuitelCourses as $intuitelCourse) {
            $nativeCourseId = Intuitel::getIDFactory()->getIdfromLoId($intuitelCourse->loId);
            // get_user_roles_in_course($user->id, $nativeCourseId) TODO decide between this and is_enrolled, with this we can know if the user is student but with is_enrolled we get true for students and teachers.
            if (is_enrolled(\context_course::instance($nativeCourseId), $native_userid)) {
                //do not include those courses in which the user is teacher
                if (array_search($intuitelCourse, $ownedCourses) === FALSE) {
                    $listCoursesEnrolled[] = $intuitelCourse;
                }
            }
        }

        return $listCoursesEnrolled;
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::getUsersEnrolled()
     */
    public function getUsersEnrolled(CourseLO $course) {
        $listStudentsEnrolled = array();
        $nativeCourseId = Intuitel::getIDFactory()->getIdfromLoId($course->loId);

        $context = \context_course::instance($nativeCourseId);

        $enrolledUserIds = get_enrolled_users($context, '', 0, 'u.id');
        foreach ($enrolledUserIds as $enrolledUserId) { // get only students
            if (!has_capability('block/intuitel:externallyedit', $context, $enrolledUserId->id)) {
                $listStudentsEnrolled[] = $enrolledUserId->id;
            }
        }

        return $listStudentsEnrolled;
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::getUseData()
     */
    public function getUseData($lo, $native_user_id) {

        $useData = array('completion' => null, 'grade' => null, 'grademax' => null, 'grademin' => null, 'accessed' => null, 'seenPercentage' => null);

        // if this is a section get access data (a section is seen once main screen of the course is seen)
        if (get_class($lo) == 'intuitel\SectionLO') {
            $courseLOid = $lo->getParent();
            $courseid = Intuitel::getIDFactory()->getIdfromLoId($courseLOid);
            $useData['accessed'] = intuitel_get_access_status_course($courseid, $native_user_id);
        } else if (get_class($lo) == 'intuitel\CourseLO') { //the lo is the CourseLO, get the completion, accessed and grade data
            $loId = $lo->getloId();
            $lo_id = Intuitel::getIDFactory()->getIdfromLoId($loId);
            $useData['accessed'] = intuitel_get_access_status_course($lo_id, $native_user_id);
            $useData['completion'] = intuitel_get_completion_status_course($lo_id, $native_user_id);
            intuitel_get_grade_info_course($lo_id, $native_user_id);
        } else {  //this is a module (activity or resource)
            $loId = $lo->getloId();
            $lo_id = Intuitel::getIDFactory()->getIdfromLoId($loId);

            $cm = get_coursemodule_from_id(null, $lo_id);
            $course_modinfo = get_fast_modinfo($cm->course);
            $coursemodule_info = $course_modinfo->get_cm($lo_id);

            //get the completion status from Moodle
            $useData['completion'] = intuitel_get_completion_status($coursemodule_info, $native_user_id);

            $useData['accessed'] = intuitel_get_access_status($lo_id, $native_user_id);
            $grade_info = intuitel_get_grade_info($coursemodule_info, $native_user_id);  //grade_info contains info of the grade obtained as well as maximum and minimum grade of the module
            if ($grade_info != null) {
                $useData['grade'] = $grade_info['grade'];
                $useData['grademax'] = $grade_info['grademax'];
                $useData['grademin'] = $grade_info['grademin'];
            }

            if (($lo->media == 'video') || ($lo->media == 'audio')) {

                $viewed = intuitel_get_access_status($lo_id, $native_user_id);
                if ($viewed) {
                    $useData['seenPercentage'] = 100;
                } else {
                    $useData['seenPercentage'] = 0;
                }
            }
        }

        return $useData;
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::getUseEnvData()
     */
    public function getUseEnvData($native_user, $type = null) {
        $env_entries = array();
        global $DB;
        $envs = $DB->get_records('intuitel_use_env', array('userid' => $native_user->id));

        if ($envs) {
            foreach ($envs as $env) {
                $env_entries[] = new EnvEntry($env);
            }
        }

        // get lName  - full name of the learner
        $use_env_data = new \stdClass();
        $use_env_data->type = 'lName';
        $use_env_data->userid = $native_user->id;
        $use_env_data->value = $native_user->firstname . " " . $native_user->lastname;

        $env_entries[] = $use_env_data;

        // get eTime - current daytime of the learner
        global $USER;
        $currentUser = $USER;
        $USER = $native_user; // userdate uses $USER global. Hence exchange it with queried learner
        $learnerTime = userdate(time(), '%H:%M:%S');

        $USER = $currentUser; // Restore $USER global
        $use_env_data = new \stdClass();
        $use_env_data->type = 'eTime';
        $use_env_data->userid = $native_user->id;
        $use_env_data->value = $learnerTime;

        $use_env_data = new \stdClass();
        $use_env_data->type = 'lLanguages';
        $use_env_data->userid = $native_user->id;
        $use_env_data->value = $native_user->lang; // assume that preferred language is known.

        $env_entries[] = $use_env_data;

        if ($type != null) { // filter
            $types = array();
            foreach ($env_entries as $env) {
                if ($env->type == $type)
                    $types[] = $env;
            }
            $env_entries = $types;
        }

        return $env_entries;
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::registerEnvironment()
     */
    public function registerEnvironment($type, $value, $native_user, $timestamp) {

        if ($type == 'dType') {
            $device = '';
            switch ($value) {
                case 'default': $value = 'desktop';
                    break;
                case 'tablet': $value = 'tablet';
                    break;
                case 'legacy': $value = 'desktop';
                    break;
                case 'mobile': $value = 'phone';
                    break;
                default: $value = 'desktop';
            }
        }

        global $DB, $CFG;
        $data = new EnvEntry();
        $data->type = $type;
        $data->userid = $native_user->id;
        $data->timestamp = $timestamp;
        $data->value = $value;
        $DB->delete_records_select('intuitel_use_env', 'userid=? AND timestamp<?', array($native_user->id, $timestamp - $CFG->block_online_users_timetosee * 60));
        // Delete any identical record to allow updating the timestamp
        $DB->delete_records('intuitel_use_env', array('userid' => $native_user->id, 'type' => $type, 'value' => $value));
        $DB->insert_record('intuitel_use_env', $data);
    }

    /**
     * (non-PHPdoc)
     * @see \intuitel\IntuitelAdaptor::markLearnerUpdatePollTime()
     */
    public function markLearnerUpdatePollTime(array $users, $time) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records_list('intuitel_polltimes', 'userid', $users);
        foreach ($users as $user) {
            $record = new \stdClass();
            $record->userid = $user;
            $record->polltime = $time;
            $DB->insert_record('intuitel_polltimes', $record, null, true);
        }
        $transaction->allow_commit();
    }

    public function getLMSId() {
        return get_config('block_intuitel','LMS_Id');
    }

    public function getLMSProfile() {
        global $CFG;
        $lmsName = get_site()->fullname;
        $properties = array();
        $properties['lmsName'] = $lmsName;
        $properties['lNameFormality'] = 3; // By default 3 Formality level how the learner should be addressed by TUG: 0 – neutral, 1 - forename, 2 – surname, 3 - forename and surname
        $properties['lmsType'] = 'moodle';
        $properties['lmsId'] = $this->getLMSId();
        $properties['lmsMediaLevel'] = 'va'; //Level of multimedia support as a collation of characters. v – video, a – audio  Final format should be decided by June, 2013.
        $properties['loreLevel'] = 1; //Level of recommendation, with one of the values: 0, 1 Level “0” means that LORE has to be emulated via TUG.
        $properties['useLevel'] = 1; // Level of user score extraction, binary: 0, 1 A “0” denotes that USE has to be emulated via TUG.
        $properties['comStyle'] = 0; // Specifies the method used for learner updates and how INTUITEL sends LORE and TUG messages. 0 – Pull, 1 – Push, 2 – Push with learner polling
        //Proposal for Services endpoints
//  	$properties['lore_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/lore";
//  	$properties['tug_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/tug";
// 		$properties['mapping_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/mapping";
// 		$properties['use_perf_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/use_perf";
// 		$properties['use_env_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/use_env";
// 		$properties['lmsprofile_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/lmsprofile";
// 		$properties['login_end_point'] = "$CFG->wwwroot/blocks/intuitel/rest.php/auth";

        return $properties;
    }

    public function generateHtmlForTugAndLore(\SimpleXMLElement $doc, $courseid) {
        // delegates to locallib.php
        return intuitel_generateHtmlForTugAndLore($doc, $courseid);
    }

    public function logTugAnswer($courseid, $native_user_id, $mid, $info) {
        global $CFG;
        if ($CFG->version >= 2014051200) {
            require_once dirname(dirname(dirname(__FILE__))) . '/classes/event/tug_response.php';
            \block_intuitel\event\tug_response::create_from_parts($courseid, $native_user_id, $mid, $info)->trigger();
        } else {
            add_to_log($courseid, 'INTUITEL', 'TUG answer', '', $info);
        }
    }

    public function logTugDismiss($courseid, $native_user_id, $mid, $info) {
        global $CFG;
        if ($CFG->version >= 2014051200) {
            require_once dirname(dirname(dirname(__FILE__))) . '/classes/event/tug_dismissed.php';
            \block_intuitel\event\tug_dismissed::create_from_parts($courseid, $native_user_id, $mid, 'DISMISS mId=' . $mid)->trigger();
        } else {
            add_to_log($courseid, 'INTUITEL', 'TUG_RESPONSE', '', 'DISMISS mId=' . $mid);
        }
    }

    public function logTugView($courseid, $native_user_id, $mid, $info) {
        global $CFG;
        if ($CFG->version >= 2014051200) {
            require_once dirname(dirname(dirname(__FILE__))) . '/classes/event/tug_viewed.php';
            \block_intuitel\event\tug_viewed::create_from_parts($courseid, $native_user_id, $mid, $info)->trigger();
        } else {
            add_to_log($courseid, 'INTUITEL', 'IntuitelTUG', '', $info);
        }
    }

    public function logLoreView($courseid, $native_user_id, $mid, $info) {
        global $CFG;
        if ($CFG->version >= 2014051200) {
            require_once dirname(dirname(dirname(__FILE__))) . '/classes/event/lore_viewed.php';
            \block_intuitel\event\lore_viewed::create_from_parts($courseid, $native_user_id, $mid, $info)->trigger();
        } else {
            add_to_log($courseid, 'INTUITEL', 'IntuitelLORE', '', $info);
        }
    }

}

/**
 * Factory class for Moodle plattform
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleAdaptorFactory extends IntuitelAdaptorFactory {

    var $adaptorForNullCourse = null;

    public function getInstanceForCourse(\stdClass $course = null) {
        if ($course == null && $this->adaptorForNullCourse == null) {
            $this->adaptorForNullCourse = moodleAdaptor::getAdaptorForCourse(null);
        }
        if ($course == null) {
            return $this->adaptorForNullCourse;
        }
        // TODO: move static method here andx remove from moodleAdaptor
        return moodleAdaptor::getAdaptorForCourse($course);
    }

    public function getInstanceForCourseLoID(LOId $courseLOId) {
        return moodleAdaptor::getAdaptorForCourseLOId($courseLOId);
    }

}

/**
 * Auto register the factory with Intuitel
 */
Intuitel::registerAdaptorFactory(new MoodleAdaptorFactory());

// Autoregister LO factories
Intuitel::registerFactory(new CourseLOFactory());
Intuitel::registerFactory(new SectionLOFactory());
Intuitel::registerFactory(new AssignmentLOFactory());
Intuitel::registerFactory(new AssignLOFactory());
Intuitel::registerFactory(new BookLOFactory());
Intuitel::registerFactory(new ChatLOFactory());
Intuitel::registerFactory(new ChoiceLOFactory());
Intuitel::registerFactory(new DataLOFactory());
Intuitel::registerFactory(new FeedbackLOFactory());
Intuitel::registerFactory(new FolderLOFactory());
Intuitel::registerFactory(new ForumLOFactory());
Intuitel::registerFactory(new GlossaryLOFactory());
Intuitel::registerFactory(new ImscpLOFactory());
Intuitel::registerFactory(new LessonLOFactory());
Intuitel::registerFactory(new LtiLOFactory());
Intuitel::registerFactory(new SurveyLOFactory());
Intuitel::registerFactory(new ScormLOFactory());
//2014-03-26 JPC: Labels are almost never used as knowledge objects.
Intuitel::registerFactory(new LabelLOFactory());
Intuitel::registerFactory(new PageLOFactory());
Intuitel::registerFactory(new QuizLOFactory());
Intuitel::registerFactory(new ResourceFileLOFactory());
Intuitel::registerFactory(new UrlLOFactory());
Intuitel::registerFactory(new QuestLOFactory());
Intuitel::registerFactory(new QuestournamentLOFactory());
Intuitel::registerFactory(new WikiLOFactory());
Intuitel::registerFactory(new WorkshopLOFactory());
Intuitel::registerIdFactory(new MoodleIDFactory());
