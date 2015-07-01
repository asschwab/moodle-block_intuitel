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

/**
 * Form to edit block settings
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_intuitel_edit_form extends block_edit_form {

    /**
     * Definitio of the settings form
     * @param form $mform
     */
    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Boolean to enable/disable geolocation.
        $mform->addElement('checkbox', 'config_geolocation', get_string('intuitel_allow_geolocation', 'block_intuitel'));
        $mform->setDefault('config_geolocation', true);
        $mform->setType('config_geolocation', PARAM_BOOL);
    }
}