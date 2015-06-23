<?php
namespace intuitel;

require_once(dirname(dirname(dirname(__FILE__))).'/model/intuitelLO.php');
require_once(dirname(dirname(dirname(__FILE__))).'/model/LOFactory.php');
require_once(dirname(dirname(dirname(__FILE__))).'/model/intuitelAdaptor.php');

/**
 * Class with common methods for activities and resources of Moodle.
 * @author elever
 *
 */


abstract class ModuleLOFactory extends LOFactory{
    function toBeIgnored()
    {
        return false;
    }
	function createLO(LOId $loId)
	{
		// get the id of the section in Moodle
		$id=Intuitel::getIDFactory()->getIdfromLoId($loId);
		$cm=get_cm($id);
		if($cm==false){
			throw new UnknownLOException;
		}else{
			$courseinfo= get_fast_modinfo(get_cm($id)->course);
			$cm_info=$courseinfo->get_cm($id);
			
			//set the data of the object from Moodle internal data
			$LOModule = $this->createLOFromNative($cm_info);
			
			return $LOModule;
		}
	}


	/**
	 * Creates an intuitel module object corresponding to a Moodle activity or resource 
	 * @param \cm_info $rawData Moodle internal info for a course module
	 * @see \intuitel\LOFactory::createLOFromNative()
	 * @return \intuitel\module : course module object 
	 */
	function createLOFromNative($rawData)
	{
	    if ($this->toBeIgnored())
	        return null;
		$loId=Intuitel::getIDFactory()->getLoIdfromId('module', $rawData->id);
		$course_info=$rawData->get_modinfo();
		$hasParent= Intuitel::getIDFactory()->getLoIdfromId('section',$rawData->section);
		$precedingSibId= $this->getPrecedingSib($rawData);

		if($precedingSibId==null)
			$hasPrecedingSib=null;
		else
			$hasPrecedingSib=Intuitel::getIDFactory()->getLoIdfromId('module',$precedingSibId);

		$LOmodule =  $this->createInstance($loId,$rawData->name,$hasParent,$hasPrecedingSib);
	
		//optional attributes-values

		//Get the following activity or resource
		$followingSibId=$this->getFollowingSib($rawData);

		if($followingSibId==null){
			$hasfollowingSib=null;
		}else{
			$hasfollowingSib=Intuitel::getIDFactory()->getLoIdfromId('module',$followingSibId);
		}

		$LOmodule->sethasFollowingsib($hasfollowingSib);

		//get the lang
		$lang=$this->getLang($rawData);
		$LOmodule->setLang($lang);

		//set the Knowledge type
		$knowledgeType=$this->getLOType();
		$LOmodule->setloType($knowledgeType);
		
		//set the Media type
		if(get_class($this)=='intuitel\ResourceFileLOFactory'){
			$mediaType=$this->getMediaType($rawData);
		}else{
			$mediaType=$this->getMediaType();
		}
		$LOmodule->setMediaType($mediaType);
		
		//if a resource, get the size of the file
		if(get_class($this)=='intuitel\ResourceFileLOFactory'||get_class($this)=='intuitel\FolderLOFactory'){
			$filesize=$this->getSize($rawData);
			$LOmodule->setSize($filesize);
		}
				
		return $LOmodule;
	}
	
	function createInstance($loId,$name,$hasParent,$hasPrecedingSib)
	{
		return new module($loId,$name,$hasParent,$hasPrecedingSib);
	}
	

	/**
	 * For an activity LO, retrieves the moodle id of the previous activity/resource (course_module) in the section of the activity
	 * or null if this is the first activity in the section
	 * @param \cm_info $rawData Moodle internal info for a course module
	 * @return string|NULL  id of the previous activity/resource (Course_module)
	 */
	private function getPrecedingSib($rawData){

		$course_info=$rawData->get_modinfo(); //get the course_modinfo object with all data of the course
		$section= $course_info->get_section_info($rawData->sectionnum);
		$cmsId=explode(',',$section->sequence); //array with the ids of the cms in that section
		$position=array_keys($cmsId,$rawData->id); //position of the cm in the sequence
		$pos=$position[0];
		$num_mods=count($cmsId);
		
		while(true){
			if($pos==0){   //it is the first in the section, not previous cm
				$precedingSib=null;
				break;
			}else{  			// get the previous cm in the sequence
				
				$precedingSib=$cmsId[$pos-1];
				$factory=Intuitel::getAdaptorInstance()->getGuessedFactory($course_info->get_cm($precedingSib));
				if (!$factory->toBeIgnored())
				{
					break;
				}
			}
			$pos--;
		} //end of while
		return $precedingSib;
	}

	/**
	 * For an activity LO, retrieves the moodle id of the following activity/resource (course_module) in the section of the activity
	 * or null if this is the last activity in that section
	 * @param \cm_info $rawData Moodle internal info for a course module
	 * @return string|NULL  id of the following activity/resource (Course_module)
	 */
	private function getFollowingSib($rawData){

		$course_info=$rawData->get_modinfo(); //get the course_modinfo object with all data of the course
		$section= $course_info->get_section_info($rawData->sectionnum);
		$cmsId=explode(',',$section->sequence); //array with the ids of the cms in that section
		$position=array_keys($cmsId,$rawData->id); //position of the cm in the sequence
		$pos=$position[0];
		$num_mods=count($cmsId);

		while(true)
		{
			// 	if($position[0]==(count($cmsId)-1)){   //it is the last in the section, not previous cm
		    if($pos==$num_mods-1){   //it is the last in the section, not previous cm
					$FollowingSib=null;
					break;
			}else{  			// get the previous cm in the sequence

				$FollowingSib=$cmsId[$pos+1];
				// check if type is ignored
				$factory=Intuitel::getAdaptorInstance()->getGuessedFactory($course_info->get_cm($FollowingSib));
				if (!$factory->toBeIgnored())
				{
				    break;
				}
			}
			$pos++;
		}// end of while
		return $FollowingSib;
	}


	private function getLang($rawData){
		$course_info=$rawData->get_modinfo();
		$lang= get_course_lang($course_info);
		return $lang;

	}
	
	/**
	 * According to the INTUITEL PO the knowledgetype is retrieved for each type of LO
	 */
	abstract function getLOType();
	
	/**
	 * According to the INTUITEL PO the Media Type is retrieved for each type of LO
	 * @param \cm_info $rawData | null
	 */
	abstract function getMediaType(\cm_info $rawData=null);
		
	
}


// Activities
/**
 * General Moodle activity
 * Father of all modules having:
 * 	* interactivity
 * 	* grading
 * @author juacas
 *
 */
abstract class ActivityLOFactory extends ModuleLOFactory{
	
	function createInstance($loId,$name,$hasParent,$hasPrecedingSib)
	{
		return new activity($loId,$name,$hasParent,$hasPrecedingSib);
	}
	function getLOType(){
		return null;
	}
	
	function getMediaType(\cm_info $rawData=null){
		return null;
	}
}


class AssignmentLOFactory extends ActivityLOFactory
{

	function getFactoryKey()
	{
		return 'assignment';
	}
	
	function getLOType(){
		return 'HandInAssignment';
	}


}

class AssignLOFactory extends ActivityLOFactory
{

	function getFactoryKey()
	{
		return 'assign';
	}

	function getLOType(){
		return 'HandInAssignment';
	}


}

class ChatLOFactory extends ActivityLOFactory
{
	function getFactoryKey(){
		return 'chat';
	}

	function getLOType(){
		return 'CooperativeKnowledge ';
	}

	function getMediaType(\cm_info $rawData=null){
		return 'ChatSynchronous';
	}
}

class ChoiceLOFactory extends ActivityLOFactory
{
	function getFactoryKey(){
		return 'choice';
	}
	
	function getLOType(){
		return 'InteractiveKnowledge';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'FormInteraction';
	}
	
	
}

class DataLOFactory extends ActivityLOFactory
{
	function getFactoryKey(){
		return 'data';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'FormInteraction';
	}
	
}

class FeedbackLOFactory extends ActivityLOFactory{

	function getFactoryKey(){
		return 'feedback';
	}

	function getLOType(){
		return 'InteractiveKnowledge';
	}

	function getMediaType(\cm_info $rawData=null){
		return 'FormInteraction';
	}
}

class ForumLOFactory extends ActivityLOFactory{

	function getFactoryKey(){
		return 'forum';
	}

	function getLOType(){
		return 'CooperativeKnowledge';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'NewsgroupAsynchronous';
	}
}

class GlossaryLOFactory extends ActivityLOFactory
{

	function getFactoryKey()
	{
		return 'glossary';
	}
	

}

class ImscpLOFactory extends ActivityLOFactory{

	function getFactoryKey(){
		return 'imscp';
	}


}

class LessonLOFactory extends ActivityLOFactory
{
	function getFactoryKey()
	{
		return 'lesson';
	}
}

class LtiLOFactory extends ActivityLOFactory
{
	function getFactoryKey()
	{
		return 'lti';
	}
}

class QuizLOFactory extends ActivityLOFactory
{
	function getFactoryKey()
	{
		return 'quiz';
	}
	
	function getLOType(){
		return 'AssignmentInteractive';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'FormInteraction';
	}
}

class QuestLOFactory extends ActivityLOFactory
{
	function getFactoryKey(){
		return 'quest';
	}
	
	function getLOType(){
		return 'AssignmentInteractive';
	}

}
class QuestournamentLOFactory extends ActivityLOFactory
{
    function getFactoryKey(){
        return 'questournament';
    }

    function getLOType(){
        return 'AssignmentInteractive';
    }

}

class ScormLOFactory extends ActivityLOFactory
{
	function getFactoryKey()
	{
		return 'scorm';
	}
}

class SurveyLOFactory extends ActivityLOFactory
{
	function getFactoryKey()
	{
		return 'survey';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'FormInteraction';
	}
}

class WikiLOFactory extends ActivityLOFactory
{
	function getFactoryKey()
	{
		return 'wiki';
	}
	
	function getLOType(){
		return 'PlannedCooperation';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'AsynchronousCommunication';
	}
}

class WorkshopLOFactory extends ActivityLOFactory
{
	function getFactoryKey(){
		return 'workshop';
	}

	function getLOType(){
		return 'AssignmentInteractive';
	}

}

// Resources
/**
 * General Moodle resource
 * Father of all modules not requiring actions by the student (passive learning)
 *
 */
abstract class ResourceLOFactory extends ModuleLOFactory
{
	function createInstance($loId,$name,$hasParent,$hasPrecedingSib)
	{
		return new resource($loId,$name,$hasParent,$hasPrecedingSib);
	}
	function getLOType(){
		return null;
	}
	
	function getMediaType(\cm_info $rawData=null){
		return null;
	}
}

class LabelLOFactory extends ResourceLOFactory
{
	function getFactoryKey()
	{
		return 'label';
	}
	
	function toBeIgnored()
	{
	return true; // labels are not considered LOs but contents of a section
	}
}

class FolderLOFactory extends ResourceLOFactory
{
	function getFactoryKey()
	{
		return 'folder';
	}
	
	function getSize($rawData){
		$context= \context_module::instance($rawData->id);
		$fs= get_file_storage();
		$files = $fs->get_area_files($context->id, 'mod_folder', 'content', 0, 'sortorder DESC, id ASC', false);
		$folderSize = 0;	
		
		foreach ($files as $file){
				$fileSize = $file->get_filesize(); // filesize in bytes
				$folderSize = $folderSize+$fileSize ; // folder size in Bytes
		}
		
		return (int)($folderSize/1024);
	}
	
	function getLOType(){
		return 'ReceptiveKnowledge';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'PresentationMedia';
	}
}

class PageLOFactory extends ResourceLOFactory
{
	function getFactoryKey()
	{
		return 'page';
	}
	
	function getLOType(){
		return 'ReceptiveKnowledge';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'PresentationMedia';
	}

}

class ResourceFileLOFactory extends ResourceLOFactory
{
	function getFactoryKey()
	{
		return 'resource';
	}
	

	function getSize($rawData){
		$context= \context_module::instance($rawData->id);
		$fs= get_file_storage();
		$files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
		if (count($files) < 1) {
			$fileSize=0;
		} else {
			$file = reset($files);  // takes the first one that is the main file (the other ones can be uploaded and are shown when modifying but are not shown when displaying the resource -> think Moodle developers will amend this)
			unset($files);
			$fileSize = $file->get_filesize(); // filesize in bytes
			$fileSize = $fileSize / 1024; // filesize in KB
		}

		return (int)$fileSize;
	}
	
	function getLOType(){
		return 'ReceptiveKnowledge';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \intuitel\ResourceLOFactory::getMediaType()
	 */
	function getMediaType(\cm_info $rawData=null){
		//return 'PresentationMedia';
		$context= \context_module::instance($rawData->id);
		$fs= get_file_storage();
		$files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);
		
		$file = reset($files);  // takes the first one that is the main file (the other ones can be uploaded and are shown when modifying but are not shown when displaying the resource -> think Moodle developers will amend this)
		unset($files);
		$mimetype= $file->get_mimetype(); // filesize in bytes

		if(file_mimetype_in_typegroup($mimetype, array('web_image','image'))){ //this is an imagen
				$mediatype='PhotoPresentation';  // could be also a DrawingPresentation. 
		}else if(file_mimetype_in_typegroup($mimetype, array('video','web_video'))){
				$mediatype='VideoPresentation';
		}else if(file_mimetype_in_typegroup ($mimetype, array('audio','web_audio'))){
				$mediatype='AudioPresentation';
		}else if($mimetype='text/plain'){
				$mediatype='TextPresentation';
		}else{
				$mediatype='PresentationMedia';
		}
				
		return $mediatype;
		
	}
}


class UrlLOFactory extends ResourceLOFactory
{
	function getFactoryKey()
	{
		return 'url';
	}
	
	
}

class BookLOFactory extends ResourceLOFactory
{
	function getFactoryKey()
	{
		return 'book';
	}
	
	function getLOType(){
		return 'ReceptiveKnowledge';
	}
	
	function getMediaType(\cm_info $rawData=null){
		return 'PresentationMedia';
	}
}
/**
 * Class for Learning Objects whose type is not known, not registered.
 * @author elever
 *
 */
class GenericLOFactory extends ModuleLOFactory
{
	function getFactoryKey()
	{
		throw new ErrorException('This factory can\'t be used in an IntuitelAdaptor. Use it as standalone factory.');
	}
	
	function getLOType(){
		return null;
	}
	
	function getMediaType(\cm_info $rawData=null){
		return null;
	}
}

?>