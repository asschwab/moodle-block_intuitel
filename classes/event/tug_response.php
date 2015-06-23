<?php
// This file is part of Intuitel
//

/**
 * The block_intuitel abstract tug_response event.
 *
 * @package    block_intuitel
 * @copyright  2015 Juan Pablo de Castro
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_intuitel\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The block intuitel processed a TUG response by an user
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
class tug_response extends base {
    /**
     * 
     * @param int $courseid
     * @param int $userid
     * @param string $mId
     * @return unknown
     */
    public static function create_from_parts($courseid,$userid, $mId,$info) {
        $data = array(
            'relateduserid' => $userid,
            'context' => \context_course::instance($courseid),
            'userid' => $userid,
            'courseid' => $courseid,
            'other' => array(
                'info' => $info,
                'mid' => $mId,
            ),
        );
        /** @var tug_response $event */
        $event = self::create($data);
        $event->set_legacy_logdata('TUG answer', $info, '');  
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        //$this->data['objecttable'] = 'assign_grades';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return "TUG message dismissed by student.";
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' responded: '' a TUG message with mid '$this->mid' " .
            "in the course '$this->courseid'.";
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
