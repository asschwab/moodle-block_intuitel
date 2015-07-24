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

require_once(dirname(dirname(dirname(__FILE__))) . '/model/intuitelLO.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/LOFactory.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/model/intuitelAdaptor.php');

/**
 * Class with common methods for activities and resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ModuleLOFactory extends LOFactory {

    function toBeIgnored() {
        return false;
    }

    function createLO(LOId $loId) {
        // get the id of the section in Moodle
        $id = Intuitel::getIDFactory()->getIdfromLoId($loId);
        $cm = block_intuitel_get_cm($id);
        if ($cm == false) {
            throw new UnknownLOException;
        } else {
            $courseinfo = get_fast_modinfo(block_intuitel_get_cm($id)->course);
            $cm_info = $courseinfo->get_cm($id);

            //set the data of the object from Moodle internal data
            $LOModule = $this->createLOFromNative($cm_info);

            return $LOModule;
        }
    }

    /**
     * Creates an intuitel module object corresponding to a Moodle activity or resource
     * @param \cm_info $rawData Moodle internal info for a course module
     * @see \intuitel\LOFactory::createLOFromNative()
     * @return \intuitel\module : course module object
     */
    function createLOFromNative($rawData) {
        if ($this->toBeIgnored()) {
            return null;
        }
        $loId = Intuitel::getIDFactory()->getLoIdfromId('module', $rawData->id);
        $course_info = $rawData->get_modinfo();
        $hasParent = Intuitel::getIDFactory()->getLoIdfromId('section', $rawData->section);
        $precedingSibId = $this->getPrecedingSib($rawData);

        if ($precedingSibId == null) {
            $hasPrecedingSib = null;
        } else {
            $hasPrecedingSib = Intuitel::getIDFactory()->getLoIdfromId('module', $precedingSibId);
        }

        $LOmodule = $this->createInstance($loId, $rawData->name, $hasParent, $hasPrecedingSib);

        //optional attributes-values
        //Get the following activity or resource
        $followingSibId = $this->getFollowingSib($rawData);

        if ($followingSibId == null) {
            $hasfollowingSib = null;
        } else {
            $hasfollowingSib = Intuitel::getIDFactory()->getLoIdfromId('module', $followingSibId);
        }

        $LOmodule->sethasFollowingsib($hasfollowingSib);

        //get the lang
        $lang = $this->getLang($rawData);
        $LOmodule->setLang($lang);

        //set the Knowledge type
        $knowledgeType = $this->getLOType();
        $LOmodule->setloType($knowledgeType);

        //set the Media type
        if (get_class($this) == 'intuitel\ResourceFileLOFactory') {
            $mediaType = $this->getMediaType($rawData);
        } else {
            $mediaType = $this->getMediaType();
        }
        $LOmodule->setMediaType($mediaType);

        //if a resource, get the size of the file
        if (get_class($this) == 'intuitel\ResourceFileLOFactory' || get_class($this) == 'intuitel\FolderLOFactory') {
            $filesize = $this->getSize($rawData);
            $LOmodule->setSize($filesize);
        }

        return $LOmodule;
    }

    function createInstance($loId, $name, $hasParent, $hasPrecedingSib) {
        return new module($loId, $name, $hasParent, $hasPrecedingSib);
    }

    /**
     * For an activity LO, retrieves the moodle id of the previous activity/resource (course_module) in the section of the activity
     * or null if this is the first activity in the section
     * @param \cm_info $rawData Moodle internal info for a course module
     * @return string|NULL  id of the previous activity/resource (Course_module)
     */
    private function getPrecedingSib($rawData) {

        $course_info = $rawData->get_modinfo(); //get the course_modinfo object with all data of the course
        $section = $course_info->get_section_info($rawData->sectionnum);
        $cmsId = explode(',', $section->sequence); //array with the ids of the cms in that section
        $position = array_keys($cmsId, $rawData->id); //position of the cm in the sequence
        $pos = $position[0];
        $num_mods = count($cmsId);

        while (true) {
            if ($pos == 0) {   //it is the first in the section, not previous cm
                $precedingSib = null;
                break;
            } else {     // get the previous cm in the sequence
                $precedingSib = $cmsId[$pos - 1];
                $factory = Intuitel::getAdaptorInstance()->getGuessedFactory($course_info->get_cm($precedingSib));
                if (!$factory->toBeIgnored()) {
                    break;
                }
            }
            $pos--;
        } //end of while
        return $precedingSib;
    }

    /**
     * For an activity LO, retrieves the moodle id of the following activity/resource (course_module) in the section of the activity
     * or null if this is the last activity in that section
     * @param \cm_info $rawData Moodle internal info for a course module
     * @return string|NULL  id of the following activity/resource (Course_module)
     */
    private function getFollowingSib($rawData) {

        $course_info = $rawData->get_modinfo(); //get the course_modinfo object with all data of the course
        $section = $course_info->get_section_info($rawData->sectionnum);
        $cmsId = explode(',', $section->sequence); //array with the ids of the cms in that section
        $position = array_keys($cmsId, $rawData->id); //position of the cm in the sequence
        $pos = $position[0];
        $num_mods = count($cmsId);

        while (true) {
            // 	if($position[0]==(count($cmsId)-1)){   //it is the last in the section, not previous cm
            if ($pos == $num_mods - 1) {   //it is the last in the section, not previous cm
                $FollowingSib = null;
                break;
            } else {     // get the previous cm in the sequence
                $FollowingSib = $cmsId[$pos + 1];
                // check if type is ignored
                $factory = Intuitel::getAdaptorInstance()->getGuessedFactory($course_info->get_cm($FollowingSib));
                if (!$factory->toBeIgnored()) {
                    break;
                }
            }
            $pos++;
        }// end of while
        return $FollowingSib;
    }

    private function getLang($rawData) {
        $course_info = $rawData->get_modinfo();
        $lang = block_intuitel_get_course_lang($course_info);
        return $lang;
    }

    /**
     * According to the INTUITEL PO the knowledgetype is retrieved for each type of LO
     */
    abstract function getLOType();

    /**
     * According to the INTUITEL PO the Media Type is retrieved for each type of LO
     * @param \cm_info $rawData | null
     */
    abstract function getMediaType(\cm_info $rawData = null);
}

// Activities
/**
 * General Moodle activity.
 *
 * Father of all modules having:
 * 	* interactivity
 * 	* grading
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ActivityLOFactory extends ModuleLOFactory {

    function createInstance($loId, $name, $hasParent, $hasPrecedingSib) {
        return new activity($loId, $name, $hasParent, $hasPrecedingSib);
    }

    function getLOType() {
        return null;
    }

    function getMediaType(\cm_info $rawData = null) {
        return null;
    }

}
/**
 * Class with common methods for assignment activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class AssignmentLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'assignment';
    }

    function getLOType() {
        return 'HandInAssignment';
    }

}
/**
 * Class with common methods for assign activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class AssignLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'assign';
    }

    function getLOType() {
        return 'HandInAssignment';
    }

}
/**
 * Class with common methods for chat activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ChatLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'chat';
    }

    function getLOType() {
        return 'CooperativeKnowledge ';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'ChatSynchronous';
    }

}
/**
 * Class with common methods for choice activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ChoiceLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'choice';
    }

    function getLOType() {
        return 'InteractiveKnowledge';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'FormInteraction';
    }

}
/**
 * Class with common methods for data activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class DataLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'data';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'FormInteraction';
    }

}
/**
 * Class with common methods for feedback activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class FeedbackLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'feedback';
    }

    function getLOType() {
        return 'InteractiveKnowledge';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'FormInteraction';
    }

}
/**
 * Class with common methods for forums of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ForumLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'forum';
    }

    function getLOType() {
        return 'CooperativeKnowledge';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'NewsgroupAsynchronous';
    }

}
/**
 * Class with common methods for glossary activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class GlossaryLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'glossary';
    }

}
/**
 * Class with common methods for IMS activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ImscpLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'imscp';
    }

}
/**
 * Class with common methods for lesson module of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class LessonLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'lesson';
    }

}
/**
 * Class with common methods for LTI activities and resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class LtiLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'lti';
    }

}
/**
 * Class with common methods for quizzes activities of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class QuizLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'quiz';
    }

    function getLOType() {
        return 'AssignmentInteractive';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'FormInteraction';
    }

}
/**
 * Class with common methods for questournament activity of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class QuestLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'quest';
    }

    function getLOType() {
        return 'AssignmentInteractive';
    }

}
/**
 * Class with common methods for questournament 2 activities of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class QuestournamentLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'questournament';
    }

    function getLOType() {
        return 'AssignmentInteractive';
    }

}
/**
 * Class with common methods for SCORM activities of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ScormLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'scorm';
    }

}
/**
 * Class with common methods for Survey activities of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class SurveyLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'survey';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'FormInteraction';
    }

}
/**
 * Class with common methods for Wiki activities of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class WikiLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'wiki';
    }

    function getLOType() {
        return 'PlannedCooperation';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'AsynchronousCommunication';
    }

}
/**
 * Class with common methods for Workshop activities of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class WorkshopLOFactory extends ActivityLOFactory {

    function getFactoryKey() {
        return 'workshop';
    }

    function getLOType() {
        return 'AssignmentInteractive';
    }

}

// Resources
/**
 * General Moodle resource
 *
 * Father of all modules not requiring actions by the student (passive learning)
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class ResourceLOFactory extends ModuleLOFactory {

    function createInstance($loId, $name, $hasParent, $hasPrecedingSib) {
        return new resource($loId, $name, $hasParent, $hasPrecedingSib);
    }

    function getLOType() {
        return null;
    }

    function getMediaType(\cm_info $rawData = null) {
        return null;
    }

}
/**
 * Class with common methods for Label resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class LabelLOFactory extends ResourceLOFactory {

    function getFactoryKey() {
        return 'label';
    }

    function toBeIgnored() {
        return true; // labels are not considered LOs but contents of a section
    }

}
/**
 * Class with common methods for Folder resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class FolderLOFactory extends ResourceLOFactory {

    function getFactoryKey() {
        return 'folder';
    }

    function getSize($rawData) {
        $context = \context_module::instance($rawData->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_folder', 'content', 0, 'sortorder DESC, id ASC', false);
        $folderSize = 0;

        foreach ($files as $file) {
            $fileSize = $file->get_filesize(); // filesize in bytes
            $folderSize = $folderSize + $fileSize; // folder size in Bytes
        }

        return (int) ($folderSize / 1024);
    }

    function getLOType() {
        return 'ReceptiveKnowledge';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'PresentationMedia';
    }

}
/**
 * Class with common methods for Page resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class PageLOFactory extends ResourceLOFactory {

    function getFactoryKey() {
        return 'page';
    }

    function getLOType() {
        return 'ReceptiveKnowledge';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'PresentationMedia';
    }

}
/**
 * Class with common methods for Resource resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ResourceFileLOFactory extends ResourceLOFactory {

    function getFactoryKey() {
        return 'resource';
    }

    function getSize($rawData) {
        $context = \context_module::instance($rawData->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
        if (count($files) < 1) {
            $fileSize = 0;
        } else {
            $file = reset($files);  // takes the first one that is the main file (the other ones can be uploaded and are shown when modifying but are not shown when displaying the resource -> think Moodle developers will amend this)
            unset($files);
            $fileSize = $file->get_filesize(); // filesize in bytes
            $fileSize = $fileSize / 1024; // filesize in KB
        }

        return (int) $fileSize;
    }

    function getLOType() {
        return 'ReceptiveKnowledge';
    }

    /**
     * (non-PHPdoc)
     * @param \cm_info $rawData
     * @see \intuitel\ResourceLOFactory::getMediaType()
     * @return string
     */
    function getMediaType(\cm_info $rawData = null) {
        //return 'PresentationMedia';
        $context = \context_module::instance($rawData->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        $file = reset($files);  // takes the first one that is the main file (the other ones can be uploaded and are shown when modifying but are not shown when displaying the resource -> think Moodle developers will amend this)
        unset($files);
        $mimetype = $file->get_mimetype(); // filesize in bytes

        if (file_mimetype_in_typegroup($mimetype, array('web_image', 'image'))) { //this is an imagen
            $mediatype = 'PhotoPresentation';  // could be also a DrawingPresentation.
        } else if (file_mimetype_in_typegroup($mimetype, array('video', 'web_video'))) {
            $mediatype = 'VideoPresentation';
        } else if (file_mimetype_in_typegroup($mimetype, array('audio', 'web_audio'))) {
            $mediatype = 'AudioPresentation';
        } else if ($mimetype = 'text/plain') {
            $mediatype = 'TextPresentation';
        } else {
            $mediatype = 'PresentationMedia';
        }

        return $mediatype;
    }

}
/**
 * Class with common methods for URL resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UrlLOFactory extends ResourceLOFactory {

    function getFactoryKey() {
        return 'url';
    }

}
/**
 * Class with common methods for Book resources of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class BookLOFactory extends ResourceLOFactory {

    function getFactoryKey() {
        return 'book';
    }

    function getLOType() {
        return 'ReceptiveKnowledge';
    }

    function getMediaType(\cm_info $rawData = null) {
        return 'PresentationMedia';
    }

}

/**
 * Class for Learning Objects whose type is not known, not registered.
 * 
 * Class with common methods for LOs of Moodle.
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class GenericLOFactory extends ModuleLOFactory {

    function getFactoryKey() {
        throw new ErrorException('This factory can\'t be used in an IntuitelAdaptor. Use it as standalone factory.');
    }

    function getLOType() {
        return null;
    }

    function getMediaType(\cm_info $rawData = null) {
        return null;
    }

}
