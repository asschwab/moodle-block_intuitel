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
 * Events for the block intuitel for Moodle
 *
 * Module developed at the University of Valladolid
 * this module is provides as-is without any guarantee. Use it as your own risk.
 * @package block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright (c) 2014, INTUITEL Consortium http://www.intuitel.eu
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
/**
 * The block_intuitel abstract base event.
 *
 * @package    block_intuitel
 * @copyright  2015 Juan Pablo de Castro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_intuitel\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The block_intuitel abstract base event class.
 *
 * Most block_intuitel events can extend this class.
 *
 * @package    block_intuitel
 * @since      Moodle 2.7
 * @copyright  2015 Juan Pablo de Castro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base extends \core\event\base {

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;


    /**
     * Sets the legacy event log data.
     *
     * @param string $action The current action
     * @param string $info A detailed description of the change. But no more than 255 characters.
     * @param string $url The url to the assign module instance.
     */
    public function set_legacy_logdata($action = '', $info = '', $url = '') {
          $this->legacylogdata = array($this->courseid, 'INTUITEL', $action, $url, $info);
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        if (isset($this->legacylogdata)) {
            return $this->legacylogdata;
        }

        return null;
    }
}
