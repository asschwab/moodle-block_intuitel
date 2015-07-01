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
require_once('intuitelLO.php');
require_once('intuitelAdaptor.php');
/**
 * Factory object to allow the extension of the Intuitel for Moodle plugin
 * 
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class LOFactory{
	/**
	 * Builds a intuitelLO out of its Learning Object Id (loId)
	 * @param loId $loid
	 * @return intuitelLO new intuitelLO instance
	 */
	abstract function createLO(LOId $loid);
	/**
	 * Builds a intuitelLO out of the raw data from the LMS
	 * @param \stdClass $rawData
	 * @return intuitelLO
	 */
	abstract function createLOFromNative($rawData);
	/**
	 * @return string ifentification of the factory type
	 */
	abstract function getFactoryKey();
	/**
	 * @return true if the type is to be ignored
	 */
	abstract function toBeIgnored();
}