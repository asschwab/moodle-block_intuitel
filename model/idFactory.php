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
 * Factory object.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;
/**
 * Mapping of IntuitelLOid to LO ids
 * An IntuitelLOid will be obtained from LO objects
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class idFactory{

	/**
	 * Function that maps a LMS id into an Intuitel Id
	 * The format of the id for Intuitel is:
	 * For course: 'CO' + id of the course
	 * For section: 'SE' + id of the section (e.g. in course_sections table of Moodle)
	 * For resource or activity: 'CM' + id of the course_module
	 * @param integer $id
	 * @param string $type type of the id (not necessarily matching the types of LOFactories nor LObjects.
	 * @return LOId $loId
	 */
	abstract function getLoIdfromId($type,$id);
	/**
	 * This function returns the native id of the course, section, module,... corresponding to the input Intuitel LOid
	 * @param LOId $loId Intuitel LOid
	 * @return int id in moodle of the LO according to its type (course, section, ..)
	 */
	abstract function getIdfromLoId(LOId $loId);
	/**
	 *
	 * @param LOId $loId
	 * @throws UnknownIDException
	 * @return array:string parts of the id string
	 */
	abstract function getIdParts(LOId $loId);
	/**
	 *  get the leaning Object type corresponding the lo with that loID
	 *  returns a type suitable to query for a LOFactory
	 * @param LoId $loId
	 * @return string|NULL
	 */
	abstract function getType(LOId $loId);
	/**
	 * Generate a new messageId
	 * @return string
	 */
	abstract public function getNewMessageUUID();

	/**
	 * Gets an unique ID for an user in this LMS
	 * @param unknown $native_user_id
	 * @return UserID
	 */
	abstract public function getUserId($native_user_id);
	/**
	 * Gets a Regular Expression to extract Intuitel local Ids from a text
	 */
	abstract public function getIDRegExpr();
}
