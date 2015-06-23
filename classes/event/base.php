<?php
// This file is part of Intuitel
//

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

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
    }
}
