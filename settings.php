<?php
defined('MOODLE_INTERNAL') || die;
require_once 'model/KLogger.php';

if ($ADMIN->fulltree) {
	
	//TODO check why default values are not automatically set
	$settings->add(new admin_setting_configtext('block_intuitel_LMS_Id',
			get_string('intuitel_intuitel_LMS_id', 'block_intuitel'),
			get_string('config_intuitel_intuitel_LMS_id', 'block_intuitel'),
			$CFG->siteidentifier,PARAM_RAW_TRIMMED));
	
    $settings->add(new admin_setting_configtextarea('block_intuitel_allowed_intuitel_ips',
    												get_string('allowed_intuitel_ips', 'block_intuitel'),
    						                       	get_string('config_allowed_intuitel_ips', 'block_intuitel'),
    												'localhost'));
    $settings->add(new admin_setting_configtext('block_intuitel_servicepoint_url',
    												get_string('intuitel_servicepoint_urls', 'block_intuitel'),
    												get_string('config_intuitel_servicepoint_urls', 'block_intuitel'),
    												 null));
    $settings->add(new admin_setting_configcheckbox('block_intuitel_allow_geolocation',
        get_string('intuitel_allow_geolocation', 'block_intuitel'),
        get_string('config_intuitel_allow_geolocation', 'block_intuitel'),
        '1'));

    /**
     * For development mode
     */
    $settings->add(new admin_setting_heading('block_intuitel_debugging_header', 'Developer\'s section', 'For developers only.'));
    
    $settings->add(new admin_setting_configcheckbox('block_intuitel_report_from_logevent',
        get_string('intuitel_report_from_logevent', 'block_intuitel'),
        get_string('config_intuitel_report_from_logevent', 'block_intuitel'),
        true));
    $settings->add(new admin_setting_configcheckbox('block_intuitel_debug_server',
    		get_string('intuitel_debug_server', 'block_intuitel'),
    		get_string('config_intuitel_debug_server', 'block_intuitel'),
    		false));
    
    $settings->add(new admin_setting_configselect('block_intuitel_no_javascript_strategy',
        get_string('intuitel_no_javascript_strategy', 'block_intuitel'),
        get_string('config_intuitel_no_javascript_strategy','block_intuitel'),
        'iFrame',
        array('iFrame'=>'If no JavaScript: INTUITEL messages in an iFrame.','inline'=>'If no JavaScript: Get INTUITEL response and insert in page. Slows page generation even when Scripting is enabled!','testiFrame'=>'Ignore JavaScript: INTUITEL messages in an iFrame.','testinline'=>'Ignore JavaScript: Get INTUITEL response and insert in page.')));
    $settings->add(new admin_setting_configtext('block_intuitel_graphviz_command',
            'Path to graphviz dot command with optional params except -Tformat.',
            'Intuitel block can draw graphs about recent activity of individual estudents for debugging or researching purpouses.',
            'dot'));
    $settings->add(new admin_setting_configfile('block_intuitel_debug_file',
         get_string('intuitel_debug_file', 'block_intuitel'),
        get_string('config_intuitel_debug_file','block_intuitel'),
        sys_get_temp_dir().DIRECTORY_SEPARATOR.'intuitel.log'));
    $settings->add(new admin_setting_configselect('block_intuitel_debug_level',
        get_string('intuitel_debug_level', 'block_intuitel'),
        get_string('config_intuitel_debug_level','block_intuitel'),
        KLogger::ERROR,
        array(
            KLogger::DEBUG=>'Debug messages.',
            KLogger::INFO=>'Info messages.',
            KLogger::ERROR=>'Error messages.',
            KLogger::OFF=>'No logging.',
            )));
}
