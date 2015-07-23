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

require_once(dirname(dirname(dirname(__FILE__))) . '/model/LOFactory.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/Intuitel.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/intuitelLO.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/intuitelAdaptor.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/idFactory.php');

/**
 * Course representation as LearningObject
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class CourseLOFactory extends LOFactory {

    /**
     * @return true if the type is to be ignored
     */
    function toBeIgnored() {
        return false;
    }

    function getFactoryKey() {
        return "course";
    }

    /**
     * Creates an Intuitel LO of type course
     * @param LOId $loId
     * @return \intuitel\course
     */
    function createLO(LOId $loId) {
        $hasParent = null; // Courses do not have parents.
        $hasPrecedingSib = null; // Courses do not have following LOs.
        // Get the id of the course in Moodle.
        $id = Intuitel::getIDFactory()->getIdfromLoId($loId);

        // get information of the course
        try {
            $course_fast_info = get_fast_modinfo($id);
            $course = $this->createLOFromNative($course_fast_info);

            return $course;
        } catch (\dml_missing_record_exception $ex) {
            throw new UnknownLOException();
        }
    }

    /**
     * From a moodle course_modinfo object, it creates an intuitel LO
     * @author elever
     * @see \intuitel\LOFactory::createLOFromNative()
     * @param \course_modinfo $rawData : moodle course_modinfo object
     * @return CourseLO $course : intuitelLO course object
     */
    function createLOFromNative($rawData) {
        $lmscourse = $rawData->get_course();
        $courseLoid = Intuitel::getIDFactory()->getLoIdfromId('course', $lmscourse->id);
        // course object is created, constructor sets the compulsory attributes: LOId, loName, hasPrecedingSib, hasParent; and the optional: hasFollowingSib
        $course = new CourseLO($courseLoid, $lmscourse->fullname, null, null);

        // optional attributes

        $courseLang = $this->getLang($rawData);
        $course->setLang($courseLang);

        $childrenLoId = $this->getChildren($rawData);
        $course->sethasChildren($childrenLoId);

        return $course;
    }

    /**
     * Returns the LOId of the sections (not empty, with a summary or with cms) of a course
     * @param course_modinfo $rawData : course_modinfo object
     * @return \intuitel\LOId : array with LOID of children
     */
    private function getChildren($rawData) {

        $sections = $rawData->get_section_info_all();
        $hasChildren = array();

        foreach ($sections as $section) {
            $sectionFactory = new SectionLOFactory();
            $sectionLO = $sectionFactory->createLOFromNative($section);
            if ($sectionLO != null) { //empty section: no name or summary
                $sectionLOid = $sectionLO->getloId();
                $hasChildren[] = $sectionLOid;
            }
        }

        return $hasChildren;
    }

    /**
     * retrieves the lang of the course forced by teacher
     * @param course_modinfo $rawData : moodle course_modinfo object of the course
     * @return string|NULL : iso 639-1 code of the forced language for the course (null if not forced language)
     */
    private function getLang($rawData) {

        $lang = intuitel_get_course_lang($rawData);
        return $lang;
    }

}
