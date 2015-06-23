<?php
namespace intuitel;

// require_once('Intuitel.php');
require_once('intuitelLO.php');
require_once('intuitelAdaptor.php');


/**
 * 
 * @author juacas
 *
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







?>