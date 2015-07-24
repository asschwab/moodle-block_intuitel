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
 * The block_intuitel tug viewed event.
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Juan Pablo de Castro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_intuitel\event;

require_once('base.php');

defined('MOODLE_INTERNAL') || die();

/**
 * The block intuitel displayed a LORE message to the user
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string info
 *      - string mId
 * }
 *
 * @package    block_intuitel
 * @since      Moodle 2.7
 * @copyright  2015 Juan Pablo de Castro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lore_viewed extends base {

    /**
     * Factory method for events
     * @param int $courseid
     * @param int $userid
     * @param string $mid
     * @param array $info
     * @return lore_viewed
     */
    public static function create_from_parts($courseid, $userid, $mid, $info) {
        $data = array(
            'relateduserid' => $userid,
            'context' => \context_course::instance($courseid),
            'userid' => $userid,
            'courseid' => $courseid,
            'other' => array(
                'info' => $info,
                'mid' => $mid,
            ),
        );
        /* @var lore_viewed $event */
        $event = self::create($data);
        $event->set_legacy_logdata('IntuitelLORE', $info, '');
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return "LORE rendered for the student.";
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' received a LORE recommendation with mid ".$this->data['other']['mid'] .
                "in the course '$this->courseid'. " . $this->data['other']['info'];
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['mid'])) {
            throw new \coding_exception('The \'mid\' value must be set in other.');
        }
        if (!isset($this->other['info'])) {
            throw new \coding_exception('The \'info\' value must be set in other.');
        }
    }

}
