<?php
require_once 'model/exceptions.php';
require_once 'locallib.php';
disable_moodle_page_exception_handler();
use intuitel\ProtocolErrorException;
$pathInfo = $_SERVER ['PATH_INFO'];
$intuitelRest = substr ( $pathInfo, 1 );
switch ($intuitelRest) {
	case "USE/performance" :
		$script = 'use_perf.php';
		break;
	case "USE/environment" :
		$script = 'use_env.php';
		break;
	case "login" :
		$script = 'auth.php';
		break;
	case "TUG" :
		$script = 'tug.php';
		break;
	case "LORE" :
		$script = 'lore.php';
		break;
	case "learners" :
		$script = 'learner.php';
		break;
	case 'lmsprofile' :
		$script = 'lmsprofile.php';
		break;
	case 'mapping' :
		$script = 'mapping.php';
		break;
	default :
		
		throw new ProtocolErrorException ( "Unknown service end-point '$intuitelRest'" );
}
include ($script);
