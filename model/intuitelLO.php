<?php
namespace intuitel;

/**
 * Class to allow type-checking with learning Object identifier
 * @author juacas
 */
class LOId
{
	var $id;	// learning
	function __construct($loId)
	{
		$this->id=$loId;
	}
	/**
	 * Automatic casting to string
	 */
	public function __toString()
	{
		return $this->id;
	}
	/**
	 * @return string the learning Object as a String
	 */
	function id()
	{
		return $this->id;
	}
}
/**
 * Class to allow type-checking with User identifier
 * @author juacas
 */
class UserId
{
	/**
	 * String representation
	 * @var string
	 */
	var $id;	// string
	function __construct($userId)
	{
		$this->id=$userId;
	}
	function __toString()
	{
		return $this->id;
	}
	/**
	 * @return string the learning Object as a String
	 */
	function id()
	{
		return $this->id;
	}
}
/**
 * Base class for all Intuitel Learning Objects
 * @author juacas
 */
abstract class intuitelLO{
	/**
	 * 
	 * @var LOId
	 */
	var $loId;              //Learning Object identifier
	var $loName;            //Learning Object title
	var $media;       // comma separated list of media types contained in the LO as specified in the INTUITEL Pedagogical Ontology
	var $lang;            // lang of the course if forced by the teacher (in ISO 639-1 format)
	var $loType;       // knowledge type of the learning object as specified in the INTUITEL Pedagogical Ontology
	var $learningTime;    //Usually null in Moodle as this is not a metadata of known activities and resources
	var $typicalAgeL;     //Usually null in Moodle as this is not a metadata of known activities and resources
	var $typicalAgeU;     //Usually null in Moodle as this is not a metadata of known activities and resources
	var $size;			//Size of the LO in kilobyte (int)
	/**
	 * @var LOId
	 */
	var $hasParent;		  // learning object identifier of parent learning object
	/**
	 * @var array(LOId)
	 */
	var $hasChild;		// array containing intuitel learning object identifieres of Chidren or null if no children
						// FIX D1.1 refers this property as hasChild
	/**
	 * @var LOId
	 */
	var $hasPrecedingSib; // learning object identifier of the preceding sibling
	/**
	 * @var LOId
	 */
	var $hasFollowingSib; // learning object identifier of the following sibling

	/**
	 * Compulsory attributes are set
	 * @param LOId $id :
	 * @param string $name
	 * @param LOId $hasParent
	 * @param LOId $hasPrecedingSib
	 */
	function __construct(LOId $id, $name,LOId $hasParent=null,LOId $hasPrecedingSib=null)
	{   //constructor
		$this->loId=$id;
		$this->loName=$name;
		$this->hasParent=$hasParent;
		$this->hasPrecedingSib=$hasPrecedingSib;
	}
	
	/**
	 * Compares the attributes and values received as param with the ones of the object and returns true if they match exactly (logical AND operation)
	 * @param array $attributes  attributename=>value
	 * @return boolean true if item  matches conditions
	 */
	function match(array $attributes)
	{
		foreach ($attributes as $name=>$value)
		{
		    if (isset($this->$name))
		    {
		        
		 // TODO implement test for hasChildren multivalued: $value will be an array
		 // should iterate to find every item in the array
		    $prop=$this->$name;
		    if (is_array($value))
		    {
		        foreach ($value as $multival)
		        {
		            if (array_search($multival, $prop)===false)
		                return false; // every item in array $value must be present in $prop
		        }    
		    }
		    else
			if (($prop != $value) 
				&& 
				($prop!=null && $value!="")
				)
				{
					return false;
				}
				
		    }
		    else // property unknown
		    {
		        return false;
		    }
		}
		return true;
	}
	
	function getloId(){

		return $this->loId;
	}
	
	function getloName(){
		
		return $this->loName;
	}
	
	function getChildren()
	{
		return $this->hasChild;
	}
	
	function getParent(){
		return $this->hasParent;
	}
	
	function getPrecedingSib(){
		return $this->hasPrecedingSib;
	}
	
	function getFollowingSib(){
		return $this->hasFollowingSib;

	}
	
	function getMediaType()
	{
		return $this->media;
	}
	
	function getloType()
	{
		return $this->loType;
	}
	
	function getLang(){
		return $this->lang;
	}
	
	function getSize(){
		return $this->size;
	}
	
	function sethasChildren(array $childrenLoId)
	{
		$this->hasChild=$childrenLoId;
	}
	function sethasFollowingsib($loId){
		$this->hasFollowingSib=$loId;
	}
	function setLang($lang){
		$this->lang=$lang;
	}
	
	function setMediaType($mediatype){
		
		$this->media=$mediatype;
	}
	
	function setloType($type){
		
		$this->loType=$type;
	}
	
	function setSize($size){
		
		$this->size=$size;
	}
	
	/**
	 * Return an array with not-null attributes
	 * @return array notnull values
	 */
	public function getValuedAttributes()
	{
		$attributes=array();
		$attributeNames='loId,loName,loType,media,lang,learningTime,size,hasChild,hasPrecedingSib,hasFollowingSib,hasParent,typicalAgeL,typicalAgeU';
		$names=preg_split('/,/', $attributeNames);
		foreach ($names as $name)
		{
			if ($this->$name)
				$attributes[$name]=$this->$name;
		}
		return $attributes;
	}
}

/**
 * Intuitel LO at course level, it will contain knowledge objects (Sections, resources and activities)
 * @author elever
 *
 */
class CourseLO extends intuitelLO
{
	function __construct(LOId $id, $name,LOId $hasParent=null,LOId $hasPrecedingSib=null)
	{
		parent::__construct($id, $name,$hasParent,$hasPrecedingSib);
		$this->hasFollowingSib=null;
		
		// Params defined for knowledge objects, at lower levels, initialized to null for course level
		$this->loType=null;
		$this->media=null;
		$this->learningTime=null;
		$this->typicalAgeL=null;
		$this->typicalAgeU=null;
		$this->size=null;
	}

}


/**
 * Intuitel LO corresponding to a section in Moodle, a child of the course containing a summary and/or activities and resources
 * Empty sections in Moodle (not having resources/activities or a summary) are not considered INTUITEL LOs
 * @author elever
 *
 */

class SectionLO extends intuitelLO
{
	function __construct(LOId $id, $name,LOId $hasParent,LOId $hasPrecedingSib=null)
	{
		parent::__construct($id, $name,$hasParent,$hasPrecedingSib);
	}
}

/**
 * INTUITEL LO corresponding to a course_module (a resource or an activity)
 * @author elever
 *
 */
class module extends intuitelLO{
	
}

/**
 * INTUITEL LO corresponding to an activity (requiring interaction from students: interactive or cooperative knowledge type)
 * @author elever
 *
 */
class activity extends module{
	
}

/**
 * INTUITEL LO corresponding to a resource (usually receptive knowledge type)
 * @author elever
 *
 */
class resource extends module{
	

}



?>