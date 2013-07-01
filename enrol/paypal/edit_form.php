<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds new instance of enrol_paypal to specified course
 * or edits current instance.
 *
 * @package    enrol_paypal
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class enrol_paypal_edit_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_paypal'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_paypal'), $options);
        $mform->setDefault('status', $plugin->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_paypal'), array('size'=>4));
        $mform->setType('cost', PARAM_RAW); // Use unformat_float to get real value.
        $mform->setDefault('cost', format_float($plugin->get_config('cost'), 2, true));

        $paypalcurrencies = $plugin->get_currencies();
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_paypal'), $paypalcurrencies);
        $mform->setDefault('currency', $plugin->get_config('currency'));

        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_paypal'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid'));


        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_paypal'), array('optional' => true, 'defaultunit' => 86400));
        $mform->setDefault('enrolperiod', $plugin->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_paypal');

        $mform->addElement('date_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_paypal'), array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_paypal');

        $mform->addElement('date_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_paypal'), array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_paypal');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Course pre-requisites
        $mform->addElement('header', '', get_string('courseavailabilityconditions', 'enrol_self'));
        $courseoptions = array();
        global $CFG, $DB;
        $sql = "select c.id,c.fullname,cc.name
                  from {$CFG->prefix}course  c
             LEFT JOIN {$CFG->prefix}course_categories cc
                    ON cc.id = c.category
                 WHERE c.id != 1
                   AND c.id != {$context->instanceid}
              ORDER BY cc.name,cc.id,c.fullname";

        if ($courses = $DB->get_records_sql($sql)) {

            $sql = "SELECT c.id, c.fullname, ca.courseid,ca.sourcecourseid
                      FROM {$CFG->prefix}course_availability ca
                      JOIN course c
                        ON (c.id = ca.sourcecourseid)
                     WHERE courseid={$context->instanceid}
                       AND ca.enrolinstanceid={$instance->id}";
            $course_availability = $DB->get_records_sql($sql);
            foreach($courses as $cid => $c) {
                $mform->addElement('checkbox','condition['.$c->id.']',$c->fullname);
                $mform->setDefault('condition['.$c->id.']', isset($course_availability[$c->id]));
            }
        }

        // Custom profile field to group mapping

        $categories = $DB->get_records('user_info_category', null, 'sortorder ASC');
        $options = array('' => get_string('none'));
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('user_info_field', array('categoryid'=>$category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    $options[$field->id] = format_string($field->name);
                }
            }
        }
        $mform->addElement('select', 'customfield', get_string('customfield', 'enrol_paypal'), $options);
        $mform->setDefault('customfield', $plugin->get_config('customfield'));

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_paypal');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost)) {
            $errors['cost'] = get_string('costerror', 'enrol_paypal');
        }

        return $errors;
    }
}
