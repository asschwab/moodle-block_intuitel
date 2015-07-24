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
 * REST interface.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('model/exceptions.php');
require_once('locallib.php');
block_intuitel_disable_moodle_page_exception_handler();
use intuitel\ProtocolErrorException;

$pathinfo = $_SERVER ['PATH_INFO'];
$intuitelrest = substr($pathinfo, 1);
switch ($intuitelrest) {
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

        throw new ProtocolErrorException("Unknown service end-point '$intuitelrest'");
}
require($script);
