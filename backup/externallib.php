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
 * External backup/restore API
 *
 * @package moodlecore
 * @subpackage backup
 * @category   external
 * @author     Antonio Carlos Mariani, Daniel Neis Araujo
 * @copyright  2014 onwards Universidade Federal de Santa Catarina (www.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/externallib.php");

class core_backup_external extends external_api {

    public static function restore_activity_parameters() {
        return new external_function_parameters(
                array('shortname' => new external_value(PARAM_TEXT, 'Course shortname', VALUE_DEFAULT, ''),
                      'username' => new external_value(PARAM_TEXT, 'Username', VALUE_DEFAULT, ''),
                     )
        );
    }

    public static function restore_activity($shortname='', $username='') {
        global $CFG, $USER, $DB;

        $params = self::validate_parameters(self::restore_activity_parameters(),
                        array('shortname' => $shortname, 'username' => $username));

        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        if(!$course = $DB->get_record('course', array('shortname'=>$shortname))) {
            return get_string('unknown_course', 'local_exam_remote', $shortname);
        }
        if(!$user = $DB->get_record('user', array('username'=>$username))) {
            return get_string('unknown_user', 'local_exam_remote', $username);
        }

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        if (!has_capability('moodle/restore:restoreactivity', $context, $user)) {
            return get_string('no_permission', 'local_exam_remote', 'moodle/restore:restoreactivity');
        }

        if(!isset($_FILES['backup_file']) || !isset($_FILES['backup_file']['tmp_name'])) {
            return get_string('backup_file_not_found', 'local_exam_remote');
        }

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        check_dir_exists($CFG->dataroot . '/temp/backup');
        $rand_backup_path = 'activity_restore_' . date('YmdHis') . '_' . rand();
        $zip = new zip_packer();
        $zip->extract_to_pathname($_FILES['backup_file']['tmp_name'],  $CFG->dataroot . '/temp/backup/' . $rand_backup_path);

        $adminid = $DB->get_field('user', 'id', array('username'=>'admin'));
        $controller = new restore_controller($rand_backup_path, $course->id, backup::INTERACTIVE_NO, backup::MODE_GENERAL,
                                             $adminid, backup::TARGET_EXISTING_ADDING);
        $controller->execute_precheck();
        $controller->execute_plan();

        return 'OK';

    }

    public static function restore_activity_returns() {
        return new external_value(PARAM_TEXT, 'Restore results. OK if restore went well.');
    }
}
