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
 * Exceptions thrown in INTUITEL code.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;
class IntuitelException extends \Exception
{
	var $statusCode=400;
	public function getStatusCode()
	{
		return $this->statusCode;
	}
}
class UnknownLOTypeException extends IntuitelException
{

}
class UnknownLOException extends IntuitelException
{

}
class UnknownIDException extends IntuitelException
{

}
class AccessDeniedException extends IntuitelException
{
	var $statusCode=401;
}
class UnknownUserException extends IntuitelException
{

}
class ProtocolErrorException extends IntuitelException
{
	function __construct($message,$statusCode=400)
	{
		parent::__construct ( $message );
		$this->statusCode=$statusCode;
	}
}