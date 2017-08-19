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
 * This script handles an AJAX request to report points
 * for one or more students for the assignfeedback_points plugin
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
require_once($CFG->dirroot.'/mod/assign/feedback/points/renderer.php');

// verify session key
require_sesskey();

// the main input parameters for this script
$id = optional_param('id', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

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

// cache the plugin name - it's quite long ;-)
$plugin = 'assignfeedback_points';

// get $output renderer
$output = $PAGE->get_renderer('assignfeedback_points');

// create points feedback object
$points = new assign_feedback_points($assign, 'points');

// get config settings
$config = $points->get_all_config($plugin);

// get grading settings
$grading =  assign_feedback_points::get_grading_instance($config, $context);

// generate the report
if ($grading->method=='') {
    echo $output->report_simple_grading($plugin, $points, $userid);
} else {
    echo $output->report_advanced_grading($plugin, $points, $userid, $grading);
}
