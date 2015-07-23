<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Administrative settings for INTUITEL for Moodle
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once('model/KLogger.php');

if ($ADMIN->fulltree) {

    // TODO check why default values are not automatically set.
    $settings->add(new admin_setting_configtext('block_intuitel/LMS_Id', get_string('intuitel_intuitel_LMS_id', 'block_intuitel'),
            get_string('config_intuitel_intuitel_LMS_id', 'block_intuitel'), $CFG->siteidentifier, PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configtextarea('block_intuitel/allowed_intuitel_ips',
            get_string('allowed_intuitel_ips', 'block_intuitel'), get_string('config_allowed_intuitel_ips', 'block_intuitel'),
            'localhost'));
    $settings->add(new admin_setting_configtext('block_intuitel/servicepoint_url',
            get_string('intuitel_servicepoint_urls', 'block_intuitel'),
            get_string('config_intuitel_servicepoint_urls', 'block_intuitel'), null));
    $settings->add(new admin_setting_configcheckbox('block_intuitel/allow_geolocation',
            get_string('intuitel_allow_geolocation', 'block_intuitel'),
            get_string('config_intuitel_allow_geolocation', 'block_intuitel'), '1'));

    /*
     * For development mode.
     */
    $settings->add(new admin_setting_heading('block_intuitel_debugging_header', 'Developer\'s section', 'For developers only.'));

    $settings->add(new admin_setting_configcheckbox('block_intuitel/report_from_logevent',
            get_string('intuitel_report_from_logevent', 'block_intuitel'),
            get_string('config_intuitel_report_from_logevent', 'block_intuitel'), true));
    $settings->add(new admin_setting_configcheckbox('block_intuitel/debug_server',
            get_string('intuitel_debug_server', 'block_intuitel'), get_string('config_intuitel_debug_server', 'block_intuitel'),
            false));

    $settings->add(new admin_setting_configselect('block_intuitel/no_javascript_strategy',
            get_string('intuitel_no_javascript_strategy', 'block_intuitel'),
            get_string('config_intuitel_no_javascript_strategy', 'block_intuitel'), 'iFrame',
            array('iFrame' => 'If no JavaScript: INTUITEL messages in an iFrame.',
    'inline' => 'If no JavaScript: Get INTUITEL response and insert in page. Slows page generation even when Scripting is enabled!',
    'testiFrame' => 'Ignore JavaScript: INTUITEL messages in an iFrame.',
    'testinline' => 'Ignore JavaScript: Get INTUITEL response and insert in page.')));
    $settings->add(new admin_setting_configtext('block_intuitel/graphviz_command',
            'Path to graphviz dot command with optional params except -Tformat.',
            'Intuitel block can draw graphs about recent activity of individual estudents for debugging or researching purpouses.',
            'dot'));
    $settings->add(new admin_setting_configfile('block_intuitel/debug_file', get_string('intuitel_debug_file', 'block_intuitel'),
            get_string('config_intuitel_debug_file', 'block_intuitel'), sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'intuitel.log'));
    $settings->add(new admin_setting_configselect('block_intuitel/debug_level',
            get_string('intuitel_debug_level', 'block_intuitel'), get_string('config_intuitel_debug_level', 'block_intuitel'),
            KLogger::ERROR,
            array(
        KLogger::DEBUG => 'Debug messages.',
        KLogger::INFO => 'Info messages.',
        KLogger::ERROR => 'Error messages.',
        KLogger::OFF => 'No logging.',
    )));
}
