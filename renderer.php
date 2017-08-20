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
 * This file contains a renderer for the assignment class
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the assign module.
 *
 * @package assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_points_renderer extends plugin_renderer_base {

    /**
     * Render a header for the page to assign incremental points
     *
     * @param assignfeedback_points_header $header
     * @return string The html response
     */
    public function render_assignfeedback_points_header($header) {
        $output = '';
        $plugin = 'assignfeedback_points';
        $output .= $this->container(get_string('userswithnewfeedback', $plugin, $header->userswithnewfeedback));
        $output .= $this->container(get_string('filesupdated', $plugin, $header->feedbackfilesupdated));
        $output .= $this->container(get_string('filesadded', $plugin, $header->feedbackfilesadded));
        $url = new moodle_url('view.php', array('id'=>$header->cmid, 'action'=>'grading'));
        $output .= $this->continue_button($url);
        return $output;
    }

    /**
     * Render a report for simple grading points awarded to a single user
     *
     * @param string $plugin name
     * @param object assign_feedback_points (see "locallib.php")
     * @param integer $userid
     * @return string The html response
     */
    public function report_simple_grading($plugin, $points, $userid) {
        global $DB;

        // start main $table in report
        $table = new html_table();
        $table->id = 'id_assignfeedback_points_report';

        // add data rows to the $table
        if ($userid) {

            // initialize loop variables
            $count = 0;
            $total = 0;
            $fullnames = array();
            $addcomment = false;
            $addcancelled = false;
            $lastactiveawardid = 0;

            if ($awards = $DB->get_records('assignfeedback_points', array('awardto' => $userid), 'timeawarded')) {

                // get the points type and description strings
                $pointstype = $points->get_config('pointstype');
                $pointstypes = assign_feedback_points::get_pointstype_options($plugin);

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

                $startnewdates = mktime(0, 0, 0, 1, 1, date('Y')); // 1st day of current year

                // locate id of most recent active points record
                // check whether "comment" or "cancel" columns are required
                // and build cache of required $fullnames
                foreach ($awards as $award) {
                    if ($award->timecancelled==0 && $pointstype==$award->pointstype) {
                        $lastactiveawardid = $award->id;
                    }
                    if ($award->commenttext) {
                        $addcomment = true;
                    }
                    if ($award->timecancelled) {
                        $addcancelled = true;
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
                }

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
                    'center', // index column
                    'right',  'left',   'left',
                    'center', 'center', 'center',
                    'left',   'left'
                );

                // remove "comment" and "cancel" columns,
                // if they are not needed
                if ($addcomment==false) {
                    array_splice($table->head,  3, 1);
                    array_splice($table->align, 3, 1);
                }
                if ($addcancelled==false) {
                    array_splice($table->head,  -2);
                    array_splice($table->align, -2);
                }

                // add data rows to $table
                foreach ($awards as $award) {

                    // set $rowclass and update $total, if required
                    $rowclass = 'inactive';
                    if ($award->timecancelled==0 && $pointstype==$award->pointstype) {
                        if ($award->pointstype==0) {
                            $total += $award->points;
                            $rowclass = 'active';
                        } else if ($award->pointstype==1) {
                            $total = $award->points;
                            if ($award->id==$lastactiveawardid) {
                                $rowclass = 'active';
                            }
                        }
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
                    if ($addcomment) {
                        if (empty($award->commenttext)) {
                            $row->cells[] = '';
                        } else {
                            $row->cells[] = $award->commenttext;
                        }
                    }

                    // pointstype, points, and total
                    $row->cells[] = $pointstypes[$award->pointstype];
                    $row->cells[] = $award->points;
                    $row->cells[] = $total;

                    // timecancelled and cancelby
                    if ($addcancelled) {
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
                    }

                    $row->attributes['class'] = $rowclass;
                    $table->data[] = $row;
                }
            }
        }

        // send report content to browser
        if (count($table->data)) {
            return html_writer::table($table);
        } else {
            return html_writer::tag('p', get_string('nopointsyet', $plugin));
        }
    }

    /**
     * Render a report for advanced grading scores for a single user
     *
     * @param string $plugin name
     * @param object assign_feedback_points - see class definition in "locallib.php"
     * @param integer $userid
     * @param object $grading information - see get_grading_instance() in "locallib.php"
     * @return string The html response
     */
    public function report_advanced_grading($plugin, $points, $userid, $grading) {
        global $DB, $USER;

        $config = $points->get_all_config($plugin);

        $length = $config->textlength;
        $head   = $config->texthead;
        $join   = $config->textjoin;
        $tail   = $config->texttail;

        // start report $table
        $table = new html_table();
        $table->id = 'id_assignfeedback_points_report';

        // create $table headers
        $table->head = array(
            get_string('criteria',      $plugin),
            get_string('remarks',       $plugin),
            get_string('score',         $plugin).' / '.get_string('total'),
            get_string('adjustedscore', $plugin)
        );

        // specify alignment of text for each column
        $table->align = array('left', 'left', 'center', 'center');

        // remove "Adjusted score" column, if it is not needed
        if ($grading->definition->minscore==0) {
            array_splice($table->head,  -1);
            array_splice($table->align, -1);
        }

        // use grading description as $table caption
        if ($grading->definition->descriptiontext) {
            $table->caption = $grading->definition->descriptiontext;
        }

        // extract data about this user from the DB
        $grade = $points->get_assign_grade($userid, true);
        $instance = $grading->controller->get_or_create_instance(0, $USER->id, $grade->id);

        $filling = 'get_'.$grading->method.'_filling';
        $filling = $instance->$filling();

        // initialize totals
        $total = null;
        $mintotal = $grading->definition->minscore;
        $maxtotal = $grading->definition->maxscore;

        // loop through the criteria for this grading definition
        $criteria = $grading->method.'_criteria';
        $criteria =& $grading->definition->$criteria;
        foreach ($criteria as $criterionid => $criterion) {

            // initialize the new row
            $row = new html_table_row();

            // criterion name
            switch ($grading->method) {
                case 'guide': $name = 'shortnametext'; break;
                case 'rubric': $name = 'descriptiontext'; break;
                default: $name = null; // shouldn't happen !!
            }
            $row->cells[] = new html_table_cell($name===null ? '&nbsp;' : $criterion[$name]);

            $maxscore = $criterion['maxscore'];
            $minscore = $criterion['minscore'];

            $score = null;
            $remark = null;
            if (array_key_exists($criterionid, $filling['criteria'])) {
                switch ($grading->method) {
                    case 'guide':
                        $score = $filling['criteria'][$criterionid]['score'];
                        break;
                    case 'rubric':
                        $levelid = $filling['criteria'][$criterionid]['levelid'];
                        $score = $criterion['levels'][$levelid]['score'];
                        break;
                }
                if ($remark = assign_feedback_points::format_text($filling['criteria'][$criterionid], 'remark')) {
                    $remark = assign_feedback_points::shorten_text($remark, $length, $head, $tail, $join, true);
                }
            }

            // increment the $total
            if (isset($score)) {
                if ($total===null) {
                    $total = 0;
                }
                $total += $score;
            }

            // add the remark for this criteria
            $row->cells[] = ($remark===null ? '&nbsp;' : $remark);

            // add the score for this criteria
            $row->cells[] = ($score===null ? '&nbsp;' : "$score/$maxscore");

            // add the the adjusted score for this criteria
            if ($grading->definition->minscore) {
                $row->cells[] = ($score===null ? '&nbsp;' : ($score - $minscore).'/'.($maxscore - $minscore));
            }

            // add this $row to the $table
            $row->attributes['class'] = 'active';
            $table->data[] = $row;
        }

        if (isset($total)) {
            // add totals row

            $row = new html_table_row();

            $maxscore = $grading->definition->maxscore;
            $minscore = $grading->definition->minscore;

            $cell = new html_table_cell(get_string('total'));
            $cell->header = true;
            $row->cells[] = $cell;

            $cell = new html_table_cell('&nbsp;');
            $row->cells[] = $cell;

            $cell = new html_table_cell("$total/$maxscore");
            $cell->header = true;
            $row->cells[] = $cell;

            if ($minscore) {
                $cell = new html_table_cell(($total - $minscore).'/'.($maxscore - $minscore));
                $cell->header = true;
                $row->cells[] = $cell;
            }

            // add this $row to the $table
            $row->attributes['class'] = 'totals';
            $table->data[] = $row;
        }

        // set up a $link to the grading page for this user
        $params = array('id' => $points->get_course_module_id(),
                        'userid' => $userid,
                        'action' => 'grade');
        $link = new moodle_url('/mod/assign/view.php', $params);

        $text = get_string('clickhere');
        $text = assign_feedback_points::textlib('strtolower', $text);

        $params = array('onclick' => 'this.target="grading";');
        $link = html_writer::link($link, $text, $params);
        $link = get_string('viewgradingpage', $plugin, $link);

        // add multi-column cell with link to grading page
        $cell = new html_table_cell($link);
        $cell->colspan = count($table->head);
        $row = new html_table_row(array($cell));
        $table->data[] = $row;

        return html_writer::table($table);
    }
}

