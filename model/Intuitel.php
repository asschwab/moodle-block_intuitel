<?php
namespace intuitel;
require_once ('intuitelAdaptor.php');
require_once ('exceptions.php');
require_once ('LOFactory.php');
require_once ('idFactory.php');

define('INTUITEL_MAX_GRADE', 6);
define('INTUITEL_MIN_GRADE', 1);

/**
 * Entry point for INTUITEL services 
 * @author juacas
 *
 */
class Intuitel
{
	/**
	 * Known LOFactories to create INTUITEL instances out of LMS native data
	 * @var array
	 */
	static $factories=array();
	/**
	 * Installed platform-dependant adaptor
	 * @var IntuitelAdaptorFactory
	*/
	static $adaptor_factory=null;
	/**
	 * Installed platform-dependant idFactory
	 * @var idFactory
	 */
	static $idFactory=null;
	
	/**
	 * Get the list of INUITEL-enabled courses in the LMS
	 * @return array(intuitel\CourseLO)
	 */
	public static function getIntuitelEnabledCourses()
	{
		return Intuitel::getAdaptorInstanceForCourse(null)->getIntuitelEnabledCourses();
	}
	
	/**
	 * From the type of LO, the LOFactory descendant adequate for its creation is returned.
	 * @param string $type Indicating the type of LO
	 * @return LOFactory|null (LOFactory descendants)
	 * @throws UnknownLOTypeException if the type is not explicitly supported.
	 **/
	public static function getLOFactory($type)
	{
		if (array_key_exists($type,self::$factories))
		{
			$factory = self::$factories[$type];
			return $factory;
		}
		else
			throw new UnknownLOTypeException($type);
	
	}
	
	/**
	 * @return idFactory
	 */
	public static function getIDFactory()
	{
		return self::$idFactory;
	}
	/**
	 * Function that initiates the static variable so that the correct type of LO is associated to the LOFactory (course, section...)
	 * @param LOFactory $factory
	 */
	public static function registerIdFactory(IdFactory $factory)
	{
		self::$idFactory = $factory;
	}
	/**
	 * Function that initiates the static variable so that the correct type of LO is associated to the LOFactory (course, section...)
	 * @param LOFactory $factory
	 */
	public static function registerFactory(LOFactory $factory)
	{
		self::$factories[$factory->getFactoryKey()] = $factory;
	}
	/**
	 * Initializes the static variable to serve as Singleton for Adaptors
	 * @param IntuitelAdaptor $adaptor
	 */
	public static function registerAdaptorFactory(IntuitelAdaptorFactory $adaptor_factory)
	{
		self::$adaptor_factory=$adaptor_factory;
	}
	/**
	 * Returns a instance of the proper adaptor initialized for a $course
	 * @param stdClass $course native record representing a course
	 * @return IntuitelAdaptor valid adaptor instance
	 */
	public static function getAdaptorInstanceForCourse(\stdClass $course=null)
	{
		$adaptor = self::$adaptor_factory->getInstanceForCourse($course);
		return $adaptor;
	}
	/**
	 * Returns a instance of the proper adaptor initialized for a coureLOId
	 * @param LOId $courseLOId INTUITEL identificator representing a course
	 * @return a valid adaptor instance
	 * @throws UnknownLOException
	 */
	public static function getAdaptorInstanceForCourseLOId(LOId $courseLOId)
	{
		$adaptor = self::$adaptor_factory->getInstanceForCourseLoID($courseLOId);
		return $adaptor;
	}
	
	public static function getAdaptorInstance(){
		return self::getAdaptorInstanceForCourse(null);
	}
}