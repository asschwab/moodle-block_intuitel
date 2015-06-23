<?php //$Id: block_intuitel.php
/******************************************************
 * Module developed at the University of Valladolid
* this module is provides as-is without any guarantee. Use it as your own risk.
*
* @author Juan Pablo de Castro, Elena Verdú.
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package intuitel
********************************************************/
use intuitel\Intuitel;
require_once (dirname(__FILE__).'/model/Intuitel.php');
require_once (dirname(__FILE__).'/locallib.php');
class block_intuitel extends block_base {
	function init() {
		$this->title = get_string('intuitel', 'block_intuitel');
		// register this course as enabled
		//pagetypepattern  course-view-*  main course
		//					course-*	any course page
		//					*			any page
		
	}
	function applicable_formats() {
		return array('site-index' => false, 'all' => true);
	}
	
	function instance_create()
	{
	    $this->configure_block_in_all_pages();
		return true;
	}
	function has_config() {
		return true;
	}
	
	function instance_delete() {
	//	print('DELETING block from '.$this->context->course);
		return true;
	}
	/**
	 * If this block belongs to a course context, then return that course id.
	 * Otherwise, return 0.
	 * @return integer the course id.
	 */
	public function get_owning_course()
	{
		if (empty($this->instance->parentcontextid)) {
			return 0;
		}
		$parentcontext = context::instance_by_id($this->instance->parentcontextid);
		if ($parentcontext->contextlevel != CONTEXT_COURSE ) {
			return 0;
		}
		$course_context = $parentcontext->get_course_context(true);
		
		if (!$course_context) {
			return 0;
		}
		return $course_context->instanceid;
	}

	function instance_config_save($data, $nolongerused = false) {
// 		if (empty($data->courseid)) {
// 			$data->courseid = $this->get_owning_course();
// 		}
        parent::instance_config_save($data);
	}

	function get_content()
	{
		global $USER, $CFG, $DB;
		if ($this->content !== NULL) {
			return $this->content;
		}
		$this->content = new stdClass;
		$this->content->text = '';
		$this->content->footer = '';

		if (empty($this->instance)) {
			$this->content = '';
			return $this->content;
		}
        // Register enviroment values
        $this->register_environment_values();
        
		if ($this->page->course!= get_site()) // in a "real" course
		{
			// General information
			$this->content->text = get_string('welcome', 'block_intuitel');
			//var_dump($this->page->cm);
			// Monitor the current activity and update TUG and LORE messages.
//			if ($this->page->cm instanceof cm_info) // in any activity page
// 			&& $this->page->context->id == $this->instance->parentcontextid)
// 			{
				$cm_rawdata = $this->page->cm; // Module raw data
				$is_entry_page = strchr($this->page->url,'/view.php?')!=false;
				$is_activity_page = strchr($this->page->url,'/mod/')!=false;;
				$module_data = $this->page->activityrecord;
				$course = $this->page->course;
				if (
				    //$cm_rawdata!==null ||
				    $is_activity_page || // include course pages in the scope of an activity to show recommendations
				    $is_entry_page // include course pages named view.php
		              ) 
						$this->content->text .= $this->generate_intuitel_block_code($course, $cm_rawdata);
				else
				    $this->content->text = get_string('page_not_monitored', 'block_intuitel');	
// 			}
			
			return $this->content;
		}
		else
		{
			$this->content->text = get_string('error_not_in_course', 'block_intuitel');
			return $this->content;
		}	
	}

	function instance_allow_multiple()
	{
		return false;
	}
	function configure_block_in_all_pages()
	{
	    global $DB;
	    $this->instance->defaultweight=-10;
	    $this->instance->showinsubcontexts=true;
	    $this->instance->pagetypepattern='*';
	    $DB->update_record('block_instances', $this->instance);
	}
	function register_environment_values()
	{
	    global $USER;
	    $adaptor= Intuitel::getAdaptorInstance();
	    // user agent
	    $device = class_exists('core_useragent')?(core_useragent::get_user_device_type()):get_user_device_type(); // get_user_device_type() was deprecated in Moodle 2.6
	    $adaptor->registerEnvironment('dType',$device,$USER,time());
	}
	function generate_intuitel_block_code($course,$cm_rawdata)
	{
		global $CFG,$OUTPUT;
		
		
		$cmid = empty($cm_rawdata)?'':$cm_rawdata->id;
		$query_args = array('courseid'=>$course->id,'cmid'=>$cmid,'_intuitel_intent'=>'LEARNERUPDATE');
		$query = "courseid=$course->id&cmid=$cmid&_intuitel_intent=LEARNERUPDATE";
		$url = $CFG->wwwroot.'/blocks/intuitel/IntuitelProxy.php';
	     
		
		
		$block_strategy = $CFG->block_intuitel_no_javascript_strategy;
		if ($block_strategy=='iFrame' || $block_strategy=='testiFrame')
		{
		    $no_script_code = $this->generate_iframe_code($url,$query);
		}
		else if ($block_strategy=='inline' || $block_strategy=='testinline')
		{
		    $no_script_code = $this->generate_inline_code($query_args);
		} 
		// disable javascript if testing no_script strategies
		if ($block_strategy != 'testinline' && $block_strategy!='testiFrame')
		{
		    // 		$this->page->requires->js('/blocks/intuitel/script/gallery-ratings.js');
		    $dependencies= array('io','io-form','transition');//,'gallery-ratings'
		    
		    $geolocation = $this->is_geolocation_enabled();
		    if ($geolocation)
		        $dependencies[]='gallery-geo';
		    
		    $module = array(
		        'name'      => 'M.local_intuitel',
		        'fullpath'  => '/blocks/intuitel/module.js',
		        'requires'  => $dependencies,
		    );
		    $this->page->requires->css('/blocks/intuitel/script/gallery-ratings/assets/gallery-ratings-core.css');
		    $this->page->requires->css('/blocks/intuitel/script/gallery-ratings/assets/gallery-ratings.css');
		    $jsarguments['cfg']['query_string'] = $query;
		    $jsarguments['cfg']['intuitel_proxy'] = $url;
		    $jsarguments['cfg']['geolocate'] = $geolocation?'yes':'no';
		   
		    $this->page->requires->js_module(array('name'=>'gallery-ratings','fullpath'=>'/blocks/intuitel/script/gallery-ratings/gallery-ratings.js'));
		    $this->page->requires->js_init_call('M.local_intuitel.init',$jsarguments,true,$module);
		    $this->page->requires->js_init_call('M.core_message.init_defaultoutputs');
		    $no_script="<noscript>$no_script_code</noscript> ";
		    $initial_text= '<div id="INTUITEL_loading_icon" style="display:visible">'.$OUTPUT->pix_icon('i/loading', 'Loading INTUITEL messages').'</div>'.
		                  '<div id="INTUITEL_render_area" style="display:none"></div>';
		}
		else
		{
		    $initial_text='';
		    $no_script=$no_script_code; // test even if browser supports scripting
		}    
		
		
		$initial_text.= $no_script;
		$content_text= html_writer::div($initial_text,'',array('id'=>'intuitel_block','style'=>'align:left'));
		return $content_text;
	}
	function is_geolocation_enabled()
	{
	    global $CFG;
	    // check local configuration
	    if (isset($this->config->geolocation))
	    {
	        return $this->config->geolocation;
	    }
	    else  // use global config
	    return $CFG->block_intuitel_allow_geolocation;
	}
	function generate_iframe_code($url,$query)
	{
	    $proxy_request= "$url?$query&includeHeaders=true";
	    // seamless=\"seamless\"
	    $iframe = "<iframe frameborder=\"0\" scrolling=\"yes\" src=\"$proxy_request\" >Can't use Intuitel in this Browser</iframe>";
	    return $iframe;
	}
	function generate_inline_code($args)
	{
	   global $USER;
	   $html = forward_learner_update_request($args['cmid'],$args['courseid'], $USER->id);
	   return $html;
	}
}
?>
