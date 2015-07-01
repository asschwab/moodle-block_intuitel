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

require_once(dirname(dirname(dirname(__FILE__))).'/model/intuitelLO.php');
require_once(dirname(dirname(dirname(__FILE__))).'/model/LOFactory.php');
require_once(dirname(dirname(dirname(__FILE__))).'/model/intuitelAdaptor.php');

/**
 * The Intuitel factory implementation for Moodle.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class SectionLOFactory extends LOFactory{
    function toBeIgnored()
    {
        return false;
    }
	function getFactoryKey()
	{
		return "section";
	}

	/**
	 *
	 * @see \intuitel\LOFactory::createLO()
	 * @return \intuitel\section|null    null if section id exists but the section is empty
	 * @throws UnknownLOException  exception launched if there is not exist in the LMS an object for that loId
	 **/

	function createLO(LOId $loId){
		// get the id of the section in Moodle
		$id=Intuitel::getIDFactory()->getIdfromLoId($loId);
		$section_info = get_moodle_section($id);
		if($section_info==false){
			throw new UnknownLOException;
		}else{
			$section = $this->createLOFromNative($section_info);
			return $section;
		}
	}

	/**
	 * Function that creates an object \intuitel\SectionLO from the info of the Moodle section (corresponding to a row of table course_sections). If the section summary or sequence of cmids are empty, the function returns NULL
	 * @see \intuitel\LOFactory::createLOFromNative()
	 * @param stdClass object $section_info corresponding to a row of course_section table in Moodle
	 * @return \intuitel\SectionLO | NULL
	 */
	function createLOFromNative($section_info)
	{
		global $CFG;
		require_once $CFG->dirroot.'/course/lib.php'; // for get_section_name()

		$loId=Intuitel::getIDFactory()->getLoIdfromId('section', $section_info->id);

		if($this->isSectionEmpty($section_info)){
			//throw new EmptySectionException('The section is empty');
			$LOsection=null;
		}else{
			$hasParent= Intuitel::getIDFactory()->getLoIdfromId('course',$section_info->course);   //LOId of the course where the section is
			global $CFG;


			$name= \get_section_name($section_info->course,$section_info);

			// get the previous not empty section id
			$precedingSibId=$this->getPrecedingSib($section_info);

			if($precedingSibId==null){

				$hasPrecedingSib=null;

			}else{

				$hasPrecedingSib=Intuitel::getIDFactory()->getLoIdfromId('section',$precedingSibId);
			}

			//create the object with mandatory attributes
			$LOsection= new SectionLO($loId,$name,$hasParent,$hasPrecedingSib);

			//OPTIONAL ATTRIBUTES

			//Get an array with children of the section
			$childrenids=$this->getChildren($section_info);
			$hasChildren=array();
			foreach ($childrenids as $child)
				{
					$hasChildren[]=Intuitel::getIDFactory()->getLoIdfromId('module',$child);
				}

			$LOsection->sethasChildren($hasChildren);

			//Get the following not empty section
			$followingSibId=$this->getFollowingSib($section_info);

			if($followingSibId==null){
				$hasfollowingSib=null;
			}else{
				$hasfollowingSib=Intuitel::getIDFactory()->getLoIdfromId('section',$followingSibId);
			}

			$LOsection->sethasFollowingsib($hasfollowingSib);

			$lang=$this->getLang($section_info);
			$LOsection->setLang($lang);
		}
		return $LOsection;
	}

	/**
	 * Returns the id in Moodle of the previous section with content (there is data in the summary)
	 * @param stdClass $rawData : row of course_section table in Moodle
	 * @return string|null $PrecedingSib : id of the previous non-empty section
	 */

	private function getPrecedingSib($rawData){

		$course_info = get_fast_modinfo($rawData->course);  //needs to obtain information about the course to get the preceeding section
		$sections=$course_info->get_section_info_all();  //Sections is an array of the sections of the course and the index indicates the order in the course

		$sectionposition=$rawData->section;

		if($sectionposition==0){ // Current section in first position, there is no a previous one.

			$precedingSib=null;

		}else{

			//find previous section not empty
			$i=1;
			$allpreviousareempty=false;
			do{

				$precedingSection=$sections[$sectionposition-$i];

				if(($sectionposition-$i)<0)  //if we are in first section
					{
						if(($this->isSectionEmpty($precedingSection))==true)
								$allpreviousareempty=true;
						break;
					}

				$i++;
			}while(($this->isSectionEmpty($precedingSection))==true);

			if($allpreviousareempty==true){

				$precedingSib=null;

			}else{

				$precedingSib=$precedingSection->id;

			}

		}

		return $precedingSib;
	}

	/**
	 * Returns the id in Moodle of the following section with content (there is data in the summary)
	 * @param stdClass $rawData : row of course_section table in Moodle
	 * @return string|null $FollowingSib : id of the following non-empty section
	 */
	private function getFollowingSib($rawData){

		$course_info = get_fast_modinfo($rawData->course);  //needs to obtain information about the course to get the preceeding section
		$sections=$course_info->get_section_info_all();  //Sections is an array of the sections of the course and the index indicates the order in the course

		$sectionposition=$rawData->section;

		$numsections=count($sections);

		if($sectionposition==($numsections-1)){ // Current section in last position, there is no a following one.

			$followingSib=null;

		}else{

			//find not empty following section
			$i=1;
			$allfollowingareempty=false;
			do{

				$followingSection=$sections[$sectionposition+$i];
				if(($sectionposition+$i)==($numsections-1))  //if we are in last section
				{
                                    if (($this->isSectionEmpty($followingSection)) == true) {
                        $allfollowingareempty = true;
                    }
                    break;
                }
				$i++;
			}while(($this->isSectionEmpty($followingSection))==true);

			if($allfollowingareempty==true){

				$followingSib=null;

			}else{

				$followingSib=$followingSection->id;

			}

		}

		return $followingSib;
	}


/**
 * Retrieves the native ids of children of the section
 * @param stdClass $rawData : row of course_section table in Moodle
 * @return array of native Id|NULL : null if no children, array of LOId of children
 */
	private function getChildren($rawData){

		$course_info = get_fast_modinfo($rawData->course);
		$section= $course_info->get_section_info($rawData->section);

		$hasChildren=array();

		if($section->sequence != null)
		{
			$cmodules=explode(',',$section->sequence);
		}
		else
		{
		    $cmodules=array();
		}
		$hasChildren=array();
		foreach ($cmodules as $child_id)
		{
		    $cm_info=$course_info->get_cm($child_id);
		    if ($cm_info->modname == 'label') { // label in not considered as a LO
                continue;
            }
            $hasChildren[] = $child_id;
        }
		return $hasChildren;
	}

	private function getMedia($loId){
		// get the id of the section in Moodle
		$id=Intuitel::getIDFactory()->getIdfromLoId($loId);
		$section_info = get_section_info($id);
		$media=getMediaType($section_info->summary); //array containing the media types contained in the summary of the section
		return $media;
	}

	/**
	 * Get the lang of the course if forced by the teacher, in another case returns null.
	 * @author elever
	 * @param stdClass object $rawData corresponding to a row of course_section table in Moodle
	 * @return string|NULL string indicating lang of the course or null if no course has been indicated.
	 */
	private function getLang($rawData){
		$course_info = get_fast_modinfo($rawData->course);
		$lang= get_course_lang($course_info);
		return $lang;
	}

	/**
	 * Returns true if the section is considered empty, that is, both next conditions are not met:
	 * 		- Empty summary
	 * 		- No course_modules included in that section (cmids sequence is empty)
	 * @param  \section_info $sectionInfo
	 * @return boolean
	 */
	private function isSectionEmpty($sectionInfo){

            if (($sectionInfo->summary == '') && ($sectionInfo->sequence == '')) {
            $isempty = true;
        } else {
            $isempty = false;
        }
        return $isempty;
    }

}