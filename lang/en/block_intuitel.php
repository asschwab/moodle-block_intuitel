<?php
$string['pluginname']=$string['intuitel']='Intelligent Tutor';
$string['error_not_in_course']='Tutorship block should work into a course.';
$string['welcome']='Tutorship brought to you by <a href="http://eduvalab.uva.es/en/projects/intuitel-intelligent-tutoring-interface-technology-enhanced-learning">INTUITEL</a>.';
$string['intuitel:myaddinstance']=$string['block/intuitel:myaddinstance']= 'Add INTUITEL block to My Site';
$string['intuitel:addinstance']=$string['block/intuitel:addinstance']= 'Add the block to a course enabling it for INTUITEL';
$string['intuitel:externallyedit']=$string['block/intuitel:externallyedit']='Allow the Intuitel External SLOM Editor to authenticate this user';
$string['intuitel:myaddinstance'] = 'Add INTUITEL block to My Home';
$string['intuitel:view']=$string['block/intuitel:view']= 'View TUG and LORE messages and interact with them.';
$string['allowed_intuitel_ips'] = 'List of IPs allowed to send INTUITEL events to this LMS.';
$string['config_allowed_intuitel_ips'] = 'All addresses entered here are allowed to send requests for users and content information in this platform. One entry on each line. An entry with \'*\' allows all ips to access the server (do not use in production settings).';
$string['intuitel_servicepoint_urls'] = 'Service Point URL for using INTUITEL services.';
$string['config_intuitel_servicepoint_urls'] = 'Base URL for the INTUITEL REST service point.';
$string['config_intuitel_intuitel_LMS_id'] = 'Identification of this Moodle platform in the INTUITEL network. All content and users are identified using this value. Shouldn\'t be changed after starting the interaction with INTUITEL.';
$string['intuitel_intuitel_LMS_id'] = 'Identification prefix for this Moodle instance';
$string['intuitel_debug_server'] = 'Ignore INTUITEL server and use simulation';
$string['config_intuitel_debug_server'] = 'For debugging purposes, use a simulation of INTUITEL servers instead of a real one.';
$string['intuitel_report_from_logevent'] = 'Experimental: Report to INTUITEL all events since last report.';
$string['config_intuitel_report_from_logevent'] = 'Experimental: Get the list of events from the event log instead of reporting only one event. May help if some events are missed.';
$string['intuitel_no_javascript_strategy'] = 'Experimental: If Javascript is not available. Implement intuitel block as iFrame or as inline inclussion.';
$string['config_intuitel_no_javascript_strategy'] = 'iFrame insertion may cause aesthetical defects. Inline inclussion will penalize the loading speed of every page even when JavaScript is available because content is always built for potential handicaped browsers.';
$string['intuitel_debug_level'] = 'Development: Debug level of messages logged.';
$string['config_intuitel_debug_level'] = 'Only messages with importance greater than set are logged.';

$string['intuitel_debug_file'] = 'Development: Log file.';
$string['config_intuitel_debug_file'] = 'Directory and file specified must have write permissions.';

$string['intuitel_allow_geolocation'] = 'Allow geolocation of the users.';
$string['config_intuitel_allow_geolocation'] = 'INTUITEL will use user\'s position to make better content recomendations.';

$string['dismiss'] = 'Close';
$string['personalized_recommendations'] = 'Your personalized recommendations:';
$string['page_not_monitored'] = 'This page is not monitored by INTUITEL';
$string['submit'] = 'Submit';
$string['advice_duration'] = 'It seems that {$a->duration} seconds is not enough time for content {$a->loId}.
	Don\'t you think you should review it a bit more?';
$string['advice_grade'] = 'It seems that you have not achieved a great result in the activity {$a->loId} ({$a->grade} out of {$a->grademax}). It is recommended that you review the previous contents and repeat the test.';
$string['advice_revisits'] = 'You have already visited content {$a->loId} {$a->count} times. If you have problems to understand it, contact your teacher. He/she will help you.';
$string['congratulate_grade'] ='You achieved a good result ({$a->grade} out of {$a->grademax}) in activity {$a->loId}. Well done! go on this way! ';
$string['remember_already_graded'] ='You achieved a good result ({$a->grade} out of {$a->grademax}) in this activity. You don\'t need to repeat it! ';
$string['advice_outofsequence']='It is recommended to take {$a->previousLoId} before attempting {$a->currentLoId} – please rethink your selection';

// ERROR strings
$string['protocol_error_intuitel_node_malfunction'] = 'INTUITEL server located at {$a->service_point} is not responding properly. Please report to administrators. Exception: {$a->message}';