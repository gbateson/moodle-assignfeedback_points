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

// cache the plugin name - it's quite long ;-)
$plugin = 'assignfeedback_points';

// get the points type and description strings
$pointstype = $points->get_config('pointstype');
$pointstypes = array(0 => get_string('incrementalpoints', $plugin),
                     1 => get_string('totalpoints',       $plugin));

// the date formats for the timeawarded + timecancelled
$newdateformat = get_string('strftimerecent'); // e.g. 26 Aug, 09:16
if (strpos($newdateformat, '%m')===false) {
    $newfixmonth = false;
} else {
    $newfixmonth = true;
    $newdateformat = str_replace('%m', 'MM', $newdateformat);
}

$olddateformat = get_string('strftimerecentfull'); // Fri, 26 Aug 2016, 9:16 am
if (strpos($olddateformat, '%m')===false) {
    $oldfixmonth = false;
} else {
    $oldfixmonth = true;
    $olddateformat = str_replace('%m', 'MM', $olddateformat);
}

$startnewdates = mktime(0, 0, 0, 8, 8, date('Y')); // 1st day of current year

// start main $table in report
$table = new html_table();

// create $table headers
$table->head = array(
    '', // no heading for the index column
    get_string('timeawarded',   $plugin),
    get_string('awardby',       $plugin),
    get_string('commenttext',   $plugin),
    get_string('pointstype',    $plugin),
    get_string('points',        $plugin),
    get_string('total'),
    get_string('timecancelled', $plugin),
    get_string('cancelby',      $plugin)
);

// specify alignment of text for each column
$table->align = array(
    'center',
    'right',  'left',   'left',
    'center', 'center', 'center',
    'left',   'left'
);

// initialize loop variables
$count = 0;
$total = 0;
$fullnames = array();
$removecomment = true;
$removecancelled = true;

// add data rows to the $table
if ($userid = optional_param('userid', 0, PARAM_INT)) {
    if ($awards = $DB->get_records('assignfeedback_points', array('awardto' => $userid), 'timeawarded')) {
        foreach ($awards as $award) {

            // update $total, if required
            if ($award->timecancelled) {
                // do nothing
            } else if ($pointstype==0) {
                $total += $award->points;
            } else {
                $total = $award->points;
            }

            // get user info, if required
            if ($award->awardby && ! array_key_exists($award->awardby, $fullnames)) {
                $params = array('id' => $award->awardby);
                $fullnames[$award->awardby] = fullname($DB->get_record('user', $params));
            }
            if ($award->cancelby && ! array_key_exists($award->cancelby, $fullnames)) {
                $params = array('id' => $award->cancelby);
                $fullnames[$award->cancelby] = fullname($DB->get_record('user', $params));
            }

            // set date format
            if ($award->timeawarded >= $startnewdates) {
                $dateformat = $newdateformat;
                $fixmonth = $newfixmonth;
            } else {
                $dateformat = $olddateformat;
                $fixmonth = $oldfixmonth;
            }

            // initialize the new row
            $row = new html_table_row();

            // index
            $count++;
            $cell = new html_table_cell("$count.");
            $cell->header = true;
            $row->cells[] = $cell;

            // timeawarded and awardby
            $userdate = userdate($award->timeawarded, $dateformat);
            if ($fixmonth) {
                $m = strftime(' %m', $award->timeawarded);
                $m = ltrim(str_replace(array(' 0', ' '), '', $m));
                $userdate = str_replace('MM', $m, $userdate);
            }
            $row->cells[] = $userdate;
            $row->cells[] = $fullnames[$award->awardby];

            // commenttext
            if ($award->commenttext=='') {
                $row->cells[] = '';
            } else {
                $row->cells[] = $award->commenttext;
                $removecomment = false;
            }

            // pointstype, points, and total
            $row->cells[] = $pointstypes[$award->pointstype];
            $row->cells[] = $award->points;
            $row->cells[] = $total;

            // timecancelled and cancelby
            if (empty($award->timecancelled)) {
                $row->cells[] = '';
                $row->cells[] = '';
            } else {
                $userdate = userdate($award->timecancelled, $dateformat);
                if ($fixmonth) {
                    $m = strftime(' %m', $award->timecancelled);
                    $m = ltrim(str_replace(array(' 0', ' '), '', $m));
                    $userdate = str_replace('MM', $m, $userdate);
                }
                $row->cells[] = $userdate;
                $row->cells[] = $fullnames[$award->cancelby];
                $removecancelled = false;
            }
            $table->data[] = $row;
        }
    }
}

if ($count==0) {
    $report = html_writer::tag('p', get_string('nopointsyet', $plugin));
} else {
    // remove "cancelled" or "comment" columns, if they are not needed
    if ($removecomment || $removecancelled) {
        if ($removecomment) {
            array_splice($table->head,  3, 1);
            array_splice($table->align, 3, 1);
        }
        if ($removecancelled) {
            array_splice($table->head,  -2);
            array_splice($table->align, -2);
        }
        foreach ($table->data as $i => $row) {
            if ($removecomment) {
                array_splice($row->cells, 3, 1);
            }
            if ($removecancelled) {
                array_splice($row->cells, -2);
            }
            $table->data[$i] = $row;
        }
    }
    $report = html_writer::table($table);
}

// send $report to browser
echo $report;
