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
require_once(dirname(dirname(dirname(__FILE__))) . '/model/intuitelAdaptor.php');

/**
 * Mapping of IntuitelLOid to Section/Course/Module ids.
 *
 * An IntuitelLOid will be obtained from SectionLO/CourseLO or course-module objects
 * A CourseLO, SectionLO or course-module object will be obtained from a Intuitel LOid
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleIDFactory extends idFactory {

    /**
     * Function that maps a LMS id into an Intuitel Id
     * The format of the id for Intuitel is:
     * For course: 'CO' + id of the course
     * For section: 'SE' + id of the section (e.g. in course_sections table of Moodle)
     * For resource or activity: 'CM' + id of the course_module
     * @param string $type type of the id (not necessarily matching the types of LOFactories nor LObjects.
     * @param integer $id
     * @return LOId $loId
     */
    function getLoIdfromId($type, $id) {

        $loId = null;

        if ($type == 'course') {
            $loId = 'CO' . (string) $id;
        } else if ($type == 'section') {
            $loId = 'SE' . (string) $id;
        } else if ($type == 'module') {
            $loId = 'CM' . (string) $id;
        } else {
            // print_error() evp pending to define the type of error
            throw new UnknownLOTypeException($type);
        }
        $loId = Intuitel::getAdaptorInstanceForCourse(null)->getLMSId() . "-" . $loId;

        return new LOId($loId);
    }

    /**
     * This function returns the id of the course, section, module,... corresponding to the Intuitel LOid
     * @param LOId $loId Intuitel LOid
     * @return int id in moodle of the LO according to its type (course, section, ..)
     */
    function getIdfromLoId(LOId $loId) {
        $splitted = $this->getIdParts($loId);

        $id_part = $splitted[1];
        return (int) substr($id_part, 2);
    }

    /**
     *
     * @param LOId $loId
     * @throws UnknownIDException
     * @return array:string parts of the id string
     */
    function getIdParts(LOId $loId) {
        $pattern = '/-/';
        $splitted = preg_split($pattern, $loId->id());
        $platform_part = $splitted[0];
        if ($platform_part != Intuitel::getAdaptorInstanceForCourse(null)->getLMSId()) {
            throw new UnknownIDException("this id doesn't belong to this platform ID=\"$loId->id\"");
        }
        return $splitted;
    }

    /**
     *  get the leaning Object type corresponding the lo with that loID
     *  returns a type suitable to query for a LOFactory
     * @param LoId $loId
     * @return string|NULL
     */
    function getType(LOId $loId) {
        $parts = $this->getIdParts($loId);
        $id_part = $parts[1];
        $startloId = substr($id_part, 0, 2);
        if ($startloId == 'CM') {
            return block_intuitel_get_cm_type(substr($id_part, 2));
        } else if ($startloId == 'SE') {
            return 'section';
        } else if ($startloId == 'CO') {
            return 'course';
        } else {
            return null;
        }
    }

    /**
     * Generate a new messageId based on LMSId and a local counter.
     * @deprecated
     * @return string
     */
    public function getNewMessageUUID_old() {
        $counter = get_config('intuitel', 'muid');
        if (!isset($counter)) {
            $counter = 0;
        }
        $counter++;
        set_config('muid', $counter, 'intuitel');
        return Intuitel::getAdaptorInstanceForCourse(null)->getLMSId() . "-MSG-" . ($counter);
    }

    public function getNewMessageUUID() {
        return UUID::v4();
    }

    /**
     * Generates an object UserId from the native username
     * @return UserId
     * @see \intuitel\idFactory::getUserId()
     */
    public function getUserId($native_user_id) {
        $user_details = get_complete_user_data('id', $native_user_id);
        return new UserId($user_details->username);
    }

    public function getIDRegExpr() {
        $lmsid = Intuitel::getAdaptorInstance()->getLMSId();

        return "/$lmsid-([a-zA-z]+)([0-9]+)/";
    }

}

/**
 * UUID class
 *
 * The following class generates VALID RFC 4122 COMPLIANT
 * Universally Unique IDentifiers (UUID) version 3, 4 and 5.
 *
 * UUIDs generated validates using OSSP UUID Tool, and output
 * for named-based UUIDs are exactly the same. This is a pure
 * PHP implementation.
 *
 * @author Andrew Moore
 * @link http://www.php.net/manual/en/function.uniqid.php#94959
 * @copyright  2013 Andrew Moore
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class UUID {

    /**
     * Generate v3 UUID
     *
     * Version 3 UUIDs are named based. They require a namespace (another
     * valid UUID) and a value (the name). Given the same namespace and
     * name, the output is always the same.
     *
     * @param	uuid	$namespace
     * @param	string	$name
     */
    public static function v3($namespace, $name) {
        if (!self::is_valid($namespace)) {
            return false;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = md5($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
                // 32 bits for "time_low"
                substr($hash, 0, 8),
                // 16 bits for "time_mid"
                substr($hash, 8, 4),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 3
                (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
                // 48 bits for "node"
                substr($hash, 20, 12)
        );
    }

    /**
     *
     * Generate v4 UUID
     *
     * Version 4 UUIDs are pseudo-random.
     */
    public static function v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate v5 UUID
     *
     * Version 5 UUIDs are named based. They require a namespace (another
     * valid UUID) and a value (the name). Given the same namespace and
     * name, the output is always the same.
     *
     * @param	uuid	$namespace
     * @param	string	$name
     */
    public static function v5($namespace, $name) {
        if (!self::is_valid($namespace))
            return false;

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
                // 32 bits for "time_low"
                substr($hash, 0, 8),
                // 16 bits for "time_mid"
                substr($hash, 8, 4),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 5
                (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
                // 48 bits for "node"
                substr($hash, 20, 12)
        );
    }

    public static function is_valid($uuid) {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
                        '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }

}
