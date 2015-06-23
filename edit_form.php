<?php

class block_intuitel_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Boolean to enable/disable geolocation.
        $mform->addElement('checkbox', 'config_geolocation', get_string('intuitel_allow_geolocation', 'block_intuitel'));
        $mform->setDefault('config_geolocation', true);
        $mform->setType('config_geolocation', PARAM_BOOL);
    }
}