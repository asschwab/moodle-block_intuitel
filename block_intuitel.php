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
 * Block intuitel for Moodle
 *
 * Module developed at the University of Valladolid
 * this module is provides as-is without any guarantee. Use it as your own risk.
 * @package block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright (c) 2014, INTUITEL Consortium
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
use intuitel\Intuitel;

require_once(dirname(__FILE__) . '/model/Intuitel.php');
require_once(dirname(__FILE__) . '/locallib.php');

/**
 * Block for inserting INTUITEL tutorship in courses
 *
 *
 * @package    block_intuitel
 * @copyright  2014 Juan Pablo de Castro, Elena Verdu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_intuitel extends block_base {
    /**
     * Initialise the block.
     */
    public function init() {
        $this->title = get_string('intuitel', 'block_intuitel');
        // Register this course as enabled.
        // pagetypepattern  course-view-*  main course.
        // course-*	any course page.
        //        *	any page.
    }

    public function applicable_formats() {
        return array('site-index' => false, 'all' => true);
    }

    public function instance_create() {
        $this->configure_block_in_all_pages();

        return true;
    }

    public function has_config() {
        return true;
    }

    public function instance_delete() {
        return true;
    }

    /**
     * If this block belongs to a course context, then return that course id.
     * Otherwise, return 0.
     *
     * @return int the course id.
     */
    public function get_owning_course() {
        if (empty($this->instance->parentcontextid)) {
            return 0;
        }
        $parentcontext = context::instance_by_id($this->instance->parentcontextid);
        if ($parentcontext->contextlevel != CONTEXT_COURSE) {
            return 0;
        }
        $coursecontext = $parentcontext->get_course_context(true);

        if (!$coursecontext) {
            return 0;
        }

        return $coursecontext->instanceid;
    }

    public function instance_config_save($data, $nolongerused = false) {
        /* 		if (empty($data->courseid)) {
          $data->courseid = $this->get_owning_course();
          } */
        parent::instance_config_save($data);
    }

    public function get_content() {
        global $USER, $CFG, $DB;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            $this->content = '';

            return $this->content;
        }
        // Register enviroment values.
        $this->register_environment_values();

        if ($this->page->course != get_site()) { // In a "real" course.
            // General information.
            $this->content->text = get_string('welcome', 'block_intuitel');
            // Monitor the current activity and update TUG and LORE messages.
            /* 			if ($this->page->cm instanceof cm_info) // In any activity page.
              && $this->page->context->id == $this->instance->parentcontextid)
              { */
            $cmrawdata = $this->page->cm; // Module raw data.
            $isentrypage = strchr($this->page->url, '/view.php?') != false;
            $isactivitypage = strchr($this->page->url, '/mod/') != false;

            $moduledata = $this->page->activityrecord;
            $course = $this->page->course;
            if ($isactivitypage  // Include course pages in the scope of an activity to show recommendations.
                    || $isentrypage // Include course pages named view.php.
            ) {
                $this->content->text .= $this->generate_intuitel_block_code($course, $cmrawdata);
            } else {
                $this->content->text = get_string('page_not_monitored', 'block_intuitel');
            }
            /* } */

            return $this->content;
        } else {
            $this->content->text = get_string('error_not_in_course', 'block_intuitel');

            return $this->content;
        }
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function configure_block_in_all_pages() {
        global $DB;
        $this->instance->defaultweight = -10;
        $this->instance->showinsubcontexts = true;
        $this->instance->pagetypepattern = '*';
        $DB->update_record('block_instances', $this->instance);
    }

    public function register_environment_values() {
        global $USER;
        $adaptor = Intuitel::getAdaptorInstance();
        // User agent.
        // The function get_user_device_type() was deprecated in Moodle 2.6.
        $device = class_exists('core_useragent') ? (core_useragent::get_user_device_type()) : get_user_device_type();
        $adaptor->registerEnvironment('dType', $device, $USER, time());
    }

    public function generate_intuitel_block_code($course, $cmrawdata) {
        global $CFG, $OUTPUT;

        $cmid = empty($cmrawdata) ? '' : $cmrawdata->id;
        $queryargs = array('courseid' => $course->id, 'cmid' => $cmid, '_intuitel_intent' => 'LEARNERUPDATE');
        $query = "courseid=$course->id&cmid=$cmid&_intuitel_intent=LEARNERUPDATE";
        $url = $CFG->wwwroot . '/blocks/intuitel/IntuitelProxy.php';

        $blockstrategy = $CFG->block_intuitel_no_javascript_strategy;
        if ($blockstrategy == 'iFrame' || $blockstrategy == 'testiFrame') {
            $noscriptcode = $this->generate_iframe_code($url, $query);
        } else if ($blockstrategy == 'inline' || $blockstrategy == 'testinline') {
            $noscriptcode = $this->generate_inline_code($queryargs);
        }
        // Disable javascript if testing no_script strategies.
        if ($blockstrategy != 'testinline' && $blockstrategy != 'testiFrame') {
            $dependencies = array('io', 'io-form', 'transition');

            $geolocation = $this->is_geolocation_enabled();
            if ($geolocation) {
                $dependencies[] = 'gallery-geo';
            }

            $module = array(
                'name' => 'M.local_intuitel',
                'fullpath' => '/blocks/intuitel/module.js',
                'requires' => $dependencies,
            );
            $this->page->requires->css('/blocks/intuitel/script/gallery-ratings/assets/gallery-ratings-core.css');
            $this->page->requires->css('/blocks/intuitel/script/gallery-ratings/assets/gallery-ratings.css');
            $jsarguments['cfg']['query_string'] = $query;
            $jsarguments['cfg']['intuitel_proxy'] = $url;
            $jsarguments['cfg']['geolocate'] = $geolocation ? 'yes' : 'no';

            $this->page->requires->js_module(
                    array('name' => 'gallery-ratings',
                        'fullpath' => '/blocks/intuitel/script/gallery-ratings/gallery-ratings.js',
                    )
            );
            $this->page->requires->js_init_call('M.local_intuitel.init', $jsarguments, true, $module);
            $this->page->requires->js_init_call('M.core_message.init_defaultoutputs');
            $noscript = "<noscript>$noscriptcode</noscript> ";
            $initialtext = '<div id="INTUITEL_loading_icon" style="display:visible">'
                    . $OUTPUT->pix_icon('i/loading', 'Loading INTUITEL messages') . '</div>' .
                    '<div id="INTUITEL_render_area" style="display:none"></div>';
        } else {
            $initialtext = '';
            $noscript = $noscriptcode; // Test even if browser supports scripting.
        }

        $initialtext .= $noscript;
        $contenttext = html_writer::div($initialtext, '', array('id' => 'intuitel_block', 'style' => 'align:left'));

        return $contenttext;
    }
    /**
     * Get the configuration applicable for geolocating users
     * @global type $CFG
     * @return bool
     */
    public function is_geolocation_enabled() {
        global $CFG;
        // Check local configuration.
        if (isset($this->config->geolocation)) {
            return $this->config->geolocation;
        } else {  // Use global config.
            return $CFG->block_intuitel_allow_geolocation;
        }
    }

    public function generate_iframe_code($url, $query) {
        $proxyrequest = "$url?$query&includeHeaders=true";
        $iframe = "<iframe frameborder=\"0\" scrolling=\"yes\" src=\"$proxyrequest\" >Can't use Intuitel in this Browser</iframe>";

        return $iframe;
    }

    public function generate_inline_code($args) {
        global $USER;
        $html = intuitel_forward_learner_update_request($args['cmid'], $args['courseid'], $USER->id);

        return $html;
    }

}
