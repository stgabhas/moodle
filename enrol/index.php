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
 * This page shows all course enrolment options for current user.
 *
 * @package    core_enrol
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once("$CFG->libdir/formslib.php");

$id = required_param('id', PARAM_INT);

if (!isloggedin()) {
    // do not use require_login here because we are usually coming from it,
    // it would also mess up the SESSION->wantsurl
    redirect(get_login_url());
}

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

// Everybody is enrolled on the frontpage
if ($course->id == SITEID) {
    redirect("$CFG->wwwroot/");
}

if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
    print_error('coursehidden');
}

$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/enrol/index.php', array('id'=>$course->id));

// do not allow enrols when in login-as session
if (\core\session\manager::is_loggedinas() and $USER->loginascontext->contextlevel == CONTEXT_COURSE) {
    print_error('loginasnoenrol', '', $CFG->wwwroot.'/course/view.php?id='.$USER->loginascontext->instanceid);
}

// get all enrol forms available in this course
$enrols = enrol_get_plugins(true);
$enrolinstances = enrol_get_instances($course->id, true);
$forms = array();
foreach($enrolinstances as $instance) {
    if (!isset($enrols[$instance->enrol])) {
        continue;
    }
    $form = $enrols[$instance->enrol]->enrol_page_hook($instance);

    // somente os enrol-instances com nome "Oferta 1,2,3"
    if (strstr($instance->name, 'Oferta') &&
        // somente o enrol-instance que está com as inscrições em andamento
        (time() >= $instance->enrolstartdate) &&
        (time() <= $instance->enrolenddate)) {

        // para o usuário autenticado,
        // soma as notas de cada curso em que ele está inscrito
        $sql = "SELECT e.id as enrolid, e.name as enrolname, e.courseid, SUM(gg.finalgrade) as notatotal
                  FROM {user_enrolments} ue
                  JOIN {enrol} e
                    ON ue.enrolid = e.id
                  JOIN {grade_items} gi
                    ON (gi.courseid = e.courseid)
             LEFT JOIN {grade_grades} gg
                    ON (gi.id = gg.itemid AND ue.userid = gg.userid)
                 WHERE ue.userid = :userid
                   AND e.enrol = :enrol
              GROUP BY e.id, e.courseid";
        $params = array('userid' => $USER->id, 'enrol' => 'self');
        if (!$grade_grades = $DB->get_records_sql($sql, $params)) {
            // nao tem inscrição em nenhum curso
            $forms[$instance->id] = $form;
        } else {
            // se ele se inscreveu em algum curso
            $instancias_pendentes = array();
            foreach ($grade_grades as $gg) {
                // se o usuario nao recebeu nenhuma nota
                // ou recebeu tudo ou um zero
                if (is_null($gg->notatotal) OR $gg->notatotal == 0) {
                    $instancias_pendentes[] = $gg;
                } else {
                    continue;
                }
            }
            // pelo menos um curso nao completado (desistente)
            // instancias pendentes são aquelas que o usuário nao tem nota ou tem nota zero
            if ($instancias_pendentes) {
                $pendente = false;
                list($oferta, $oferta_atual) = explode(' ', $instance->name);
                foreach ($instancias_pendentes as $ip) {
                    list($oferta, $oferta_nao_cursada) = explode(' ', $ip->enrolname);
                    if (($oferta_atual - $oferta_nao_cursada) > 1) {
                        // se existe um "espaço" de pelo menos uma oferta
                        // entre a atual e a que o usuário desistiu ou nao completou
                        continue;
                    } else {
                        // se o usuário desistiu de uma oferta 
                        // imediatamente anterior à atual
                        $pendente = true;
                    }
                }
                // se o usuário desistiu de uma oferta 
                // imediatamente anterior à atual
                if ($pendente) {
                    $forms[$instance->id] = '<h2>Você não pode se inscrever neste curso pois desistiu de uma oferta imediatamente anterior à esta.</h2>';
                    continue;
                }
            }
        }
        list($course_curso, $course_turma) = explode('-',$course->fullname);
        $sql = "SELECT e.id as enrolid, e.name as enrolname, e.courseid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e
                    ON ue.enrolid = e.id
                  JOIN {course} c
                 WHERE c.fullname LIKE ':course_curso%'
                   AND e.name = :oferta";

        if ($DB->record_exists_sql($sql, array('course_curso' => $course_curso, 'oferta' => $instance->name))) {
            $forms[$instance->id] = '<h2>Você não pode se inscrever neste curso pois já  está inscrito em uma outra turma desta mesma oferta neste mesmo curso.</h2>';
        } else {
            if ($form) {
                $forms[$instance->id] = $form;
            }
        }
    } else {
        if ($form) {
            $forms[$instance->id] = $form;
        }
    }
}

// Check if user already enrolled
if (is_enrolled($context, $USER, '', true)) {
    if (!empty($SESSION->wantsurl)) {
        $destination = $SESSION->wantsurl;
        unset($SESSION->wantsurl);
    } else {
        $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
    }
    redirect($destination);   // Bye!
}

$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('enrolmentoptions','enrol'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('enrolmentoptions','enrol'));

$courserenderer = $PAGE->get_renderer('core', 'course');
echo $courserenderer->course_info_box($course);

//TODO: find if future enrolments present and display some info

foreach ($forms as $form) {
    echo $form;
}

if (!$forms) {
    if (isguestuser()) {
        notice(get_string('noguestaccess', 'enrol'), get_login_url());
    } else {
        notice(get_string('notenrollable', 'enrol'), "$CFG->wwwroot/index.php");
    }
}

echo $OUTPUT->footer();
