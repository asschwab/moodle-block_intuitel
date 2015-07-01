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
 * Class Model for INTUITEL
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;

/**
 * Class to allow type-checking with learning Object identifier
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class LOId {

    /**
     * the learning Object as a String
     * @var string
     */
    var $id;

    /**
     * the learning Object identified by a String
     * @param string $loId
     */
    function __construct($loId) {
        $this->id = $loId;
    }

    /**
     * Automatic casting to string
     */
    public function __toString() {
        return $this->id;
    }

    /**
     * the learning Object as a String
     *
     * @return string the learning Object as a String
     */
    function id() {
        return $this->id;
    }

}

/**
 * Class to allow type-checking with User identifier
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UserId {

    /**
     * String representation
     * @var string
     */
    var $id; // string

    function __construct($userId) {
        $this->id = $userId;
    }

    function __toString() {
        return $this->id;
    }

    /**
     * @return string the learning Object as a String
     */
    function id() {
        return $this->id;
    }

}

/**
 * Base class for all Intuitel Learning Objects
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class intuitelLO {

    /**
     * Learning Object identifier.
     * @var LOId
     */
    var $loId;

    /**
     * Learning Object title.
     * @var string
     */
    var $loName;

    /**
     * comma separated list of media types contained in the LO as specified in the INTUITEL Pedagogical Ontology.
     * @var string
     */
    var $media;

    /**
     * lang of the course if forced by the teacher (in ISO 639-1 format).
     * @var string
     */
    var $lang;

    /**
     * knowledge type of the learning object as specified in the INTUITEL Pedagogical Ontology.
     * @var string
     */
    var $loType;

    /**
     * Usually null in Moodle as this is not a metadata of known activities and resources.
     * @var int
     */
    var $learningTime;

    /**
     * Usually null in Moodle as this is not a metadata of known activities and resources.
     * @var int
     */
    var $typicalAgeL;

    /**
     * Usually null in Moodle as this is not a metadata of known activities and resources.
     * @var int
     */
    var $typicalAgeU;

    /**
     * Size of the LO in kilobyte (int)
     * @var int
     */
    var $size;

    /**
     * learning object identifier of parent learning object.
     * @var LOId
     */
    var $hasParent;

    /**
     * array containing intuitel learning object identifieres of Chidren or null if no children.
     * FIX D1.1 refers this property as hasChild.
     * @var array(LOId)
     */
    var $hasChild;  // array containing intuitel learning object identifieres of Chidren or null if no children
    // FIX D1.1 refers this property as hasChild
    /**
     * learning object identifier of the preceding sibling
     * @var LOId
     */
    var $hasPrecedingSib;

    /**
     * learning object identifier of the following sibling
     * @var LOId
     */
    var $hasFollowingSib;

    /**
     * Compulsory attributes are set
     * @param LOId $id :
     * @param string $name
     * @param LOId $hasParent
     * @param LOId $hasPrecedingSib
     */
    function __construct(LOId $id, $name, LOId $hasParent = null, LOId $hasPrecedingSib = null) {   //constructor
        $this->loId = $id;
        $this->loName = $name;
        $this->hasParent = $hasParent;
        $this->hasPrecedingSib = $hasPrecedingSib;
    }

    /**
     * Compares the attributes and values received as param with the ones of the object and returns true if they match exactly (logical AND operation)
     * @param array $attributes  attributename=>value
     * @return boolean true if item  matches conditions
     */
    function match(array $attributes) {
        foreach ($attributes as $name => $value) {
            if (isset($this->$name)) {

                // TODO implement test for hasChildren multivalued: $value will be an array
                // should iterate to find every item in the array
                $prop = $this->$name;
                if (is_array($value)) {
                    foreach ($value as $multival) {
                        if (array_search($multival, $prop) === false)
                            return false; // every item in array $value must be present in $prop
                    }
                }
                else
                if (($prop != $value) &&
                        ($prop != null && $value != "")
                ) {
                    return false;
                }
            } else { // property unknown
                return false;
            }
        }
        return true;
    }

    function getloId() {

        return $this->loId;
    }

    function getloName() {

        return $this->loName;
    }

    function getChildren() {
        return $this->hasChild;
    }

    function getParent() {
        return $this->hasParent;
    }

    function getPrecedingSib() {
        return $this->hasPrecedingSib;
    }

    function getFollowingSib() {
        return $this->hasFollowingSib;
    }

    function getMediaType() {
        return $this->media;
    }

    function getloType() {
        return $this->loType;
    }

    function getLang() {
        return $this->lang;
    }

    function getSize() {
        return $this->size;
    }

    function sethasChildren(array $childrenLoId) {
        $this->hasChild = $childrenLoId;
    }

    function sethasFollowingsib($loId) {
        $this->hasFollowingSib = $loId;
    }

    function setLang($lang) {
        $this->lang = $lang;
    }

    function setMediaType($mediatype) {

        $this->media = $mediatype;
    }

    function setloType($type) {

        $this->loType = $type;
    }

    function setSize($size) {

        $this->size = $size;
    }

    /**
     * Return an array with not-null attributes
     * @return array notnull values
     */
    public function getValuedAttributes() {
        $attributes = array();
        $attributeNames = 'loId,loName,loType,media,lang,learningTime,size,hasChild,hasPrecedingSib,hasFollowingSib,hasParent,typicalAgeL,typicalAgeU';
        $names = preg_split('/,/', $attributeNames);
        foreach ($names as $name) {
            if ($this->$name)
                $attributes[$name] = $this->$name;
        }
        return $attributes;
    }

}

/**
 * Intuitel LO at course level, it will contain knowledge objects (Sections, resources and activities)
 * @author elever
 *
 */
class CourseLO extends intuitelLO {

    function __construct(LOId $id, $name, LOId $hasParent = null, LOId $hasPrecedingSib = null) {
        parent::__construct($id, $name, $hasParent, $hasPrecedingSib);
        $this->hasFollowingSib = null;

        // Params defined for knowledge objects, at lower levels, initialized to null for course level
        $this->loType = null;
        $this->media = null;
        $this->learningTime = null;
        $this->typicalAgeL = null;
        $this->typicalAgeU = null;
        $this->size = null;
    }

}

/**
 * Intuitel LO corresponding to a section in Moodle.
 *
 * This is a child of the course containing a summary and/or activities and resources
 * Empty sections in Moodle (not having resources/activities or a summary) are not considered INTUITEL LOs
 * @author elever
 */
class SectionLO extends intuitelLO {

    function __construct(LOId $id, $name, LOId $hasParent, LOId $hasPrecedingSib = null) {
        parent::__construct($id, $name, $hasParent, $hasPrecedingSib);
    }

}

/**
 * INTUITEL LO corresponding to a course_module (a resource or an activity)
 * @author elever
 *
 */
class module extends intuitelLO {

}

/**
 * INTUITEL LO corresponding to an activity (requiring interaction from students: interactive or cooperative knowledge type)
 * @author elever
 *
 */
class activity extends module {

}

/**
 * INTUITEL LO corresponding to a resource (usually receptive knowledge type)
 * @author elever
 *
 */
class resource extends module {

}

?>