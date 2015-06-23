<?php
namespace intuitel;
/**
 * Mapping of IntuitelLOid to LO ids
 * An IntuitelLOid will be obtained from LO objects
 * @author juacas
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
