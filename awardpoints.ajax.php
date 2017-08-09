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
 * This script handles an AJAX request to award points
 * to students for the assignfeedback_points plugin
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//define('AJAX_SCRIPT', true);

// include required files
require_once('../../../../config.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');
require_once($CFG->dirroot.'/mod/assign/feedback/points/locallib.php');

// verify session key
require_sesskey();

$id = optional_param('id', 0, PARAM_INT);
if (function_exists('get_course_and_cm_from_cmid')) {
    // Moodle >= 2.8
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');
} else {
    // Moodle <= 2.7
    $cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
}

// setup context and assignment objects
if (class_exists('context_module')) {
    $context = context_module::instance($cm->id);
} else {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
}
$assign = new assign($context, $cm, $course);

// check login and access rights
require_login($course, false, $cm);
if (! $assign->can_grade()) {
    print_error('nopermission');
}

// create points feedback object
$points = new assign_feedback_points($assign, 'points');

// process incoming formdata
list($multipleusers, $groupid, $map, $feedback, $userlist, $grading) = $points->process_formdata();

// send feedback, if any
echo $feedback;
