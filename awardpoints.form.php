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
 * This file contains the forms to award points to students
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/** get required PHP libraries */
require_once($CFG->dirroot.'/grade/grading/form/lib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/mod/assign/feedback/points/locallib.php');

/**
 * Upload modified grading worksheet
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_points_award_points_form extends moodleform {

    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {

        $mform = $this->_form;
        $custom = $this->_customdata;
        $plugin = 'assignfeedback_points';

        // we don't the need form change checker (Moodle >= 2.3)
        // if we're using AJAX to send results
        if ($custom->config->sendimmediately==1) {
            $mform->disable_form_change_checker();
        }

        // ========================
        // set form id (for CSS)
        // ========================
        //
        $this->set_form_id($mform);

        // ========================
        // award points section
        // ========================
        //
        $name = 'awardpoints';
        assign_feedback_points::add_heading($mform, $name, $plugin, true);

        $this->add_field_groups($mform, $custom, $plugin);
        $this->add_field_feedback($mform, $custom, $plugin);
        $this->add_field_mapaction($mform, $custom, $plugin);
        $this->add_field_mapmode($mform, $custom, $plugin);
        $this->add_field_awardto($mform, $custom, $plugin);
        if ($custom->grading->method=='') {
            // simple direct grading
            $this->add_field_points($mform, $custom, $plugin);
            $this->add_field_commenttext($mform, $custom, $plugin);
        } else {
            // an advanced grading method e.g. "rubric" or "guide"
            $this->add_field_advancedgrading($mform, $custom, $plugin);
        }

        // ========================
        // layouts section
        // ========================
        //
        assign_feedback_points::add_heading($mform, 'layouts', $plugin, false);
        $this->add_field_layouts($mform, $custom, $plugin);

        // ========================
        // settings section
        // - Points range
        // - Display names
        // - Display totals
        // - Development
        // - jquery (PTS settings)
        // ========================
        //
        assign_feedback_points::add_settings($mform, $plugin, $custom);

        // ========================
        // report section
        // ========================
        //
        assign_feedback_points::add_heading($mform, 'report', 'moodle', false);
        $this->add_field_report($mform, $custom, $plugin);

        // ========================
        // export section
        // ========================
        //
        assign_feedback_points::add_heading($mform, 'export', 'grades', false);

        $name = 'exportfilename';
        $label = get_string($name, $plugin);
        $mform->addElement('text', $name, $label, array('size' => 60));
        $mform->setType($name, PARAM_FILE);
        $mform->setDefault($name, $custom->$name);
        $mform->addHelpButton($name, $name, $plugin);

        $name = 'export';
        $label = get_string($name, 'grades');
        $mform->addElement('submit', $name.'button', $label);

        // ========================
        // import section
        // ========================
        //
        assign_feedback_points::add_heading($mform, 'import', 'grades', false);

        $name = 'importfile';
        $label = get_string($name, $plugin);
        $mform->addElement('filepicker', $name, $label);
        $mform->addHelpButton($name, $name, $plugin);

        $name = 'import';
        $label = get_string($name, 'grades');
        $mform->addElement('submit', $name.'button', $label);

        // ========================
        // hidden fields
        // ========================
        //
        $mform->addElement('hidden', 'id', $custom->cmid);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', 'viewpluginpage');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'pluginaction', 'awardpoints');
        $mform->setType('pluginaction', PARAM_ALPHA);

        $mform->addElement('hidden', 'plugin', 'points');
        $mform->setType('plugin', PARAM_PLUGIN);

        $mform->addElement('hidden', 'pluginsubtype', 'assignfeedback');
        $mform->setType('pluginsubtype', PARAM_PLUGIN);

        $names = array('mapid', 'mapwidth', 'mapheight', 'userwidth', 'userheight', 'groupid');
        foreach ($names as $name) {
            $mform->addElement('hidden', $name, $custom->$name, array('id' => 'id_'.$name));
            $mform->setType($name, PARAM_INT);
        }

        // ========================
        // buttons
        // ========================
        //
        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * set_form_id
     *
     * @param object  $mform
     */
    private function set_form_id($mform) {
        $attributes = $mform->getAttributes();
        $attributes['id'] = get_class($this);
        $mform->setAttributes($attributes);
    }

    /**
     * add_field_groups
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_groups($mform, $custom, $plugin) {
        $url = array('id'            => $custom->cmid,
                     'plugin'        => 'points',
                     'pluginsubtype' => 'assignfeedback',
                     'action'        => 'viewpluginpage',
                     'pluginaction'  => 'awardpoints');
        $url = new moodle_url('/mod/assign/view.php', $url);
        $groups = groups_print_activity_menu($custom->cm, $url->out(), true);
        $search = '/(<label[^>]*>.*<\/label>).*?(<select[^>]*name="([^"]*)"[^>]*>.*<\/select>)/s';
        // $1 : <label ...> ... </label>
        // $2 : <select ...> ... </select>
        // $3 : element name (usually "group")
        if (preg_match($search, $groups, $groups)) {
            $name = $groups[3]; // "group"
            $groups = $groups[1].' '.$groups[2];
            $mform->addElement('static', $name, '', $groups);
        }
    }

    /**
     * add_field_feedback
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_feedback($mform, $custom, $plugin) {
        $name = 'feedback';
        $label = get_string($name, $plugin);
        if ($custom->$name=='') {
            $custom->$name = html_writer::tag('span', '', array('id' => 'feedback'));
        }
        $mform->addElement('static', $name.'feedback', $label, $custom->$name);
        $mform->addHelpButton($name.'feedback', $name, $plugin);
    }

    /**
     * add_field_mapaction
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_mapaction($mform, $custom, $plugin) {
        $name = 'mapaction';
        $label = get_string($name, $plugin);
        $mapactions = array('none'     => get_string('none'),
                            'reset'    => get_string('reset',    $plugin),
                            'resize'   => get_string('resize',   $plugin),
                            'cleanup'  => get_string('cleanup',  $plugin),
                            'separate' => get_string('separate', $plugin),
                            'rotate'   => get_string('rotate',   $plugin),
                            'shuffle'  => get_string('shuffle',  $plugin),
                            'sortby'   => get_string('sortby',   $plugin));
        $elements = array();
        foreach ($mapactions as $value => $text) {
            $elements[] = $mform->createElement('radio', $name, '', $text, $value);
            if ($value == 'sortby') {
                $options = assign_feedback_points::get_sortby_options($plugin, $custom);
                $elements[] = $mform->createElement('select', $value.'menu', '', $options);
            }
        }
        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);
        $mform->setType($name, PARAM_ALPHA);
        $mform->setDefault($name, 'none');
    }

    /**
     * add_field_mapmode
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_mapmode($mform, $custom, $plugin) {
        $name = 'mapmode';
        $label = get_string($name, $plugin);
        $mapmodes = array('award'  => get_string('award',  $plugin),
                          'select' => get_string('select'),
                          'absent' => get_string('absent', $plugin),
                          'report' => get_string('report'));
        $elements = array();
        foreach ($mapmodes as $value => $text) {
            $elements[] = $mform->createElement('radio', $name, '', $text, $value);
        }
        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);
        $mform->setType($name, PARAM_ALPHA);
        $mform->setDefault($name, 'award');
    }

    /**
     * add_field_awardto
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_awardto($mform, $custom, $plugin) {
        global $CFG, $DB, $OUTPUT;

        $name = 'awardto';
        $label = get_string($name, $plugin);

        // get userids passed via $custom values
        $userids = array_keys($custom->$name);

        // are there any users?
        $usersfound = (empty($userids) ? false : true);

        // get precision for $grades
        if (isset($custom->config->gradeprecision)) {
            $gradeprecision = $custom->config->gradeprecision;
        } else {
            $gradeprecision = 0;
            if ($usersfound && ($custom->config->showassigngrade ||
                                $custom->config->showmodulegrade ||
                                $custom->config->showcoursegrade)) {
                $params = array('courseid' => $custom->courseid,
                                'name'     => 'decimalpoints');
                if ($DB->record_exists('grade_settings', $params)) {
                    $gradeprecision = $DB->get_field('grade_settings', 'value', $params);
                } else if (isset($CFG->grade_decimalpoints)) {
                    $gradeprecision = $CFG->grade_decimalpoints;
                }
            }
        }

        // get coords of each user in this usermap
        if ($usersfound) {
            list($select, $params) = $DB->get_in_or_equal($userids);
            $select = "mapid = ? AND userid $select";
            array_unshift($params, $custom->mapid);
            $coords = $DB->get_records_select($plugin.'_coords', $select, $params, 'userid', 'userid,x,y');
        } else {
            $coords = false;
        }
        if ($coords===false) {
            $coords = array();
        }

        // get points today for each user, if required
        if ($usersfound && $custom->config->showpointstoday && $custom->grading->method=='') {
            $select = "$name, SUM(points) AS pointstoday";
            $from   = '{assignfeedback_points}';
            list($where, $params) = $DB->get_in_or_equal($userids);
            $where  = "assignid = ? AND pointstype = ? AND timeawarded > ? AND cancelby = ? AND $name $where";
            array_unshift($params, $custom->assignid, $custom->config->pointstype, time() - DAYSECS, 0);
            $pointstoday = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where GROUP BY $name", $params);
        } else {
            $pointstoday = false;
        }
        if ($pointstoday===false) {
            $pointstoday = array();
        }

        // get total points for each user, if required
        if ($usersfound && $custom->config->showpointstotal && $custom->grading->method=='') {
            $select = "p.$name";
            $from   = '{assignfeedback_points} p';
            list($where, $params) = $DB->get_in_or_equal($userids);
            $where  = "p.assignid = ? AND p.pointstype = ? AND p.timecancelled = ? AND p.$name $where";
            array_unshift($params, $custom->assignid, $custom->config->pointstype, 0);
            if ($custom->config->pointstype==0) {
                // incremental points
                $select .= ', SUM(p.points) AS pointstotal';
                $where  .= " GROUP BY p.$name";
            } else {
                // total points (select only most recent award)
                $select .= ', p.points AS pointstotal, p.id';
                $where  .= ' AND p.id = ('.
                                'SELECT MAX(pp.id) '.
                                  'FROM {assignfeedback_points} pp '.
                                 'WHERE p.assignid = pp.assignid '.
                                   'AND p.pointstype = pp.pointstype '.
                                   'AND p.timecancelled = pp.timecancelled '.
                                   "AND p.$name = pp.$name ".
                                   'AND pp.timeawarded = ('.
                                       'SELECT MAX(ppp.timeawarded) '.
                                         'FROM {assignfeedback_points} ppp '.
                                        'WHERE pp.assignid = ppp.assignid '.
                                          'AND pp.pointstype = ppp.pointstype '.
                                          'AND pp.timecancelled = ppp.timecancelled '.
                                          "AND pp.$name = ppp.$name".
                                        ')'.
                                ')';
            }
            $pointstotal = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params);
        } else {
            $pointstotal = false;
        }
        if ($pointstotal===false) {
            $pointstotal = array();
        }

        // get advanced grading instances, if necessary
        // these are used to fetch rubric/guide scores
        $gradingwhere = '';
        $gradingparams = array();
        if ($usersfound && $custom->grading->method) {

            // get most recent grading instances for all users
            $select = 'gi.id, gi.status, gi.timemodified, ag.userid';
            $from   = '{grading_instances} gi '.
                      'JOIN {assign_grades} ag ON ag.id = gi.itemid '.
                      'JOIN {grading_definitions} gd ON gd.id = gi.definitionid '.
                      'JOIN {grading_areas} ga ON ga.id = gd.areaid AND ga.activemethod = gd.method';
            list($where, $params) = $DB->get_in_or_equal($userids);
            $where  = "ga.contextid = ? AND ag.assignment = ? AND gi.status IN (?, ?, ?) AND ag.userid $where";
            array_unshift($params, $custom->context->id, $custom->assignid,
                            gradingform_instance::INSTANCE_STATUS_ACTIVE,
                            gradingform_instance::INSTANCE_STATUS_NEEDUPDATE,
                            gradingform_instance::INSTANCE_STATUS_ARCHIVE);
            $order  = 'ag.userid ASC, gi.status ASC, gi.timemodified DESC';

            if ($instances = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params)) {
                $userid = 0;
                foreach ($instances as $id => $instance) {
                    if ($instance->userid==$userid) {
                        unset($instances[$id]);
                    } else {
                        $userid = $instance->userid;
                        $instances[$id] = $userid;
                    }
                }
                $instances = array_flip($instances);

                list($gradingwhere, $gradingparams) = $DB->get_in_or_equal($instances);
            }
        }

        // get rubric scores for each user, if required
        if ($gradingwhere && $custom->config->showrubricscores && $custom->grading->method=='rubric') {
            $select = $DB->sql_concat('grf.id', "'_'", 'ag.userid');
            $select = "$select as id, ag.userid AS $name, ".
                      'grf.criterionid, grf.levelid, grf.remark, grf.remarkformat';
            $from   = '{gradingform_rubric_fillings} grf'.
                      ' JOIN {grading_instances} gi ON gi.id = grf.instanceid'.
                      ' JOIN {assign_grades} ag ON ag.id = gi.itemid';
            $order  = 'ag.userid ASC, grf.id ASC';
            $rubricscores = $DB->get_records_sql("SELECT $select FROM $from WHERE gi.id $gradingwhere ORDER BY $order", $gradingparams);
        } else {
            $rubricscores = false;
        }

        if ($rubricscores===false) {
            $rubricscores = array();
        } else {
            // format the rubric scores
            foreach (array_keys($rubricscores) as $id) {
                $userid = substr($id, strpos($id, '_') + 1);
                if (empty($rubricscores[$userid])) {
                    $rubricscores[$userid] = array();
                }
                $criterionid = $rubricscores[$id]->criterionid;
                $levelid = $rubricscores[$id]->levelid;
                if ($remark = $rubricscores[$id]->remark) {
                    $remark = html_to_text(format_text($remark, $rubricscores[$id]->remarkformat));
                }
                $rubricscores[$userid][$criterionid] = (object)array('levelid' => $levelid,
                                                                     'remark' => $remark);
                unset($rubricscores[$id]);
            }
        }

        // get total of rubric totals for each user, if required
        if ($gradingwhere && $custom->config->showrubrictotal && $custom->grading->method=='rubric') {
            $select = "ag.userid AS $name, ROUND(SUM(grl.score),0) AS rubrictotal";
            $from   = '{gradingform_rubric_levels} grl'.
                      ' JOIN {gradingform_rubric_fillings} grf ON grl.id = grf.levelid'.
                      ' JOIN {grading_instances} gi ON grf.instanceid = gi.id'.
                      ' JOIN {assign_grades} ag ON gi.itemid = ag.id';
            $group  = 'ag.userid';
            $rubrictotal = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE gi.id $gradingwhere GROUP BY $group", $gradingparams);
        } else {
            $rubrictotal = false;
        }
        if ($rubrictotal===false) {
            $rubrictotal = array();
        }

        // get guide scores for each user, if required
        if ($gradingwhere && $custom->config->showguidescores && $custom->grading->method=='guide') {
            $select = $DB->sql_concat('ggf.id', "'_'", 'ag.userid');
            $select = "$select as id, ag.userid AS $name, ".
                      'ggf.criterionid, ggf.score, ggf.remark, ggf.remarkformat';
            $from   = '{gradingform_guide_fillings} ggf'.
                      ' JOIN {grading_instances} gi ON gi.id = ggf.instanceid'.
                      ' JOIN {assign_grades} ag ON ag.id = gi.itemid';
            $order  = 'ag.userid';
            $guidescores = $DB->get_records_sql("SELECT $select FROM $from WHERE gi.id $gradingwhere", $gradingparams);
        } else {
            $guidescores = false;
        }

        if ($guidescores===false) {
            $guidescores = array();
        } else {
            foreach (array_keys($guidescores) as $id) {
                $userid = substr($id, strpos($id, '_') + 1);
                if (empty($guidescores[$userid])) {
                    $guidescores[$userid] = array();
                }
                $criterionid = $guidescores[$id]->criterionid;
                $score = $guidescores[$id]->score;
                if ($remark = $guidescores[$id]->remark) {
                    $remark = html_to_text(format_text($remark, $guidescores[$id]->remarkformat));
                }
                $guidescores[$userid][$criterionid] = (object)array('score' => $score,
                                                                    'remark' => $remark);
                unset($guidescores[$id]);
            }
        }

        // get total of guide scores for each user, if required
        if ($usersfound && $custom->config->showguidetotal && $custom->grading->method=='guide') {
            $select = "ag.userid AS $name, ROUND(SUM(ggf.score),0) AS guidetotal";
            $from   = '{gradingform_guide_fillings} ggf'.
                      ' JOIN {grading_instances} gi ON ggf.instanceid = gi.id'.
                      ' JOIN {assign_grades} ag ON gi.itemid = ag.id';
            $group  = 'ag.userid';
            $guidetotal = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE gi.id $gradingwhere GROUP BY $group", $gradingparams);
        } else {
            $guidetotal = false;
        }
        if ($guidetotal===false) {
            $guidetotal = array();
        }

        // cache the DB search values, for easy access later
        $custom->grading->where = $gradingwhere;
        $custom->grading->params = $gradingparams;
        $custom->grading->precision = $gradeprecision;

        // get assignment grades, if required
        if ($usersfound && $custom->config->showassigngrade) {
            $select = 'userid, grade';
            $from   = '{assign_grades}';
            list($where, $params) = $DB->get_in_or_equal($userids);
            $where = "assignment = ? AND userid $where";
            array_unshift($params, $custom->assignid);
            $maxgrade = $DB->get_field('assign', 'grade', array('id' => $custom->assignid));
            $assigngrade = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params);
            foreach ($assigngrade as $userid => $grade) {
                $assigngrade[$userid] = assign_feedback_points::format_grade(
                                        $custom->config->showassigngrade,
                                        $grade,
                                        $maxgrade,
                                        $gradeprecision);
            }
        } else {
            $assigngrade = false;
        }
        if ($assigngrade===false) {
            $assigngrade = array();
        }

        // get module grades, if required
        if ($usersfound && $custom->config->showmodulegrade) {
            $gradeitem = grade_item::fetch(array('courseid' => $custom->courseid,
                                                 'itemtype' => 'mod',
                                                 'itemmodule' => 'assign',
                                                 'iteminstance' => $custom->cm->instance));
            $modulegrade = grade_grade::fetch_users_grades($gradeitem, $userids, true);
            foreach ($modulegrade as $userid => $grade) {
                $modulegrade[$userid] = assign_feedback_points::format_grade(
                                        $custom->config->showmodulegrade,
                                        $grade->rawgrade,
                                        $grade->rawgrademax,
                                        $gradeprecision,
                                        $gradeitem);
            }
        } else {
            $modulegrade = array();
        }

        // get course grades, if required
        if ($usersfound && $custom->config->showcoursegrade) {
            $gradeitem = grade_item::fetch_course_item($custom->courseid);
            $coursegrade = grade_grade::fetch_users_grades($gradeitem, $userids, true);
            foreach ($coursegrade as $userid => $grade) {
                $coursegrade[$userid] = assign_feedback_points::format_grade(
                                        $custom->config->showcoursegrade,
                                        $grade->finalgrade, // rawgrade is empty
                                        $grade->rawgrademax,
                                        $gradeprecision,
                                        $gradeitem);
            }
        } else {
            $coursegrade = array();
        }

        $linebreak = html_writer::empty_tag('br');
        $increment = strlen($linebreak) + 1;

        // CSS classes for scores and grades
        $numericalign = 'numeric';
        if ($custom->config->alignscoresgrades) {
            $numericalign .= ' align'.$custom->config->alignscoresgrades;
        }

        $elements = array();
        foreach ($custom->$name as $userid => $user) {

            $text = array();
            if ($custom->config->showpicture) {
                $params = array('courseid' => $custom->courseid, 'link' => false);
                $text[] = $OUTPUT->user_picture($user, $params);
            }
            if ($user->displayname) {
                $text[] = html_writer::tag('em', $user->displayname, array('class' => 'name'));
            }
            if ($custom->config->showpointstotal && $custom->grading->method=='') {
                $value = (isset($pointstotal[$userid]) ? $pointstotal[$userid] : 0);
                $value = get_string('pointstotal', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign pointstotal"));
            }
            if ($custom->config->showpointstoday && $custom->grading->method=='') {
                $value = (isset($pointstoday[$userid]) ? $pointstoday[$userid] : 0);
                $value = get_string('pointstoday', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign pointstoday"));
            }
            if ($custom->config->showrubricscores && $custom->grading->method=='rubric') {
                $criteria =& $custom->grading->definition->rubric_criteria;
                foreach ($criteria as $criterionid => $criterion) {
                    $score = 0;
                    $remark = '';
                    if (isset($rubricscores[$userid][$criterionid])) {
                        $levelid = $rubricscores[$userid][$criterionid]->levelid;
                        if (isset($criterion['levels'][$levelid])) {
                            $score = $criterion['levels'][$levelid]['score'] - $criterion['minscore'];
                            $remark = $rubricscores[$userid][$criterionid]->remark;
                        }
                    }
                    $score = round($score, $gradeprecision);
                    if ($criterion['maxscore']) {
                        $score .= '/'.round($criterion['maxscore'] - $criterion['minscore'], $gradeprecision);
                    }
                    $value = $criterion['descriptiontext'].': '.$score;
                    $text[] = html_writer::tag('em', $value, array('class' => "$numericalign rubricscores criterion-$criterionid"));
                }
                unset($criteria);
            }
            if ($custom->config->showrubrictotal && $custom->grading->method=='rubric') {
                $minscore = $custom->grading->definition->minscore;
                $maxscore = $custom->grading->definition->maxscore;
                if (isset($rubrictotal[$userid])) {
                    $value = round($rubrictotal[$userid] - $minscore, $gradeprecision);
                } else {
                    $value = 0;
                }
                $value .= '/'.round($maxscore - $minscore, $gradeprecision);
                $value = get_string('rubrictotal', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign rubrictotal"));
            }
            if ($custom->config->showguidescores && $custom->grading->method=='guide') {
                $criteria =& $custom->grading->definition->guide_criteria;
                foreach ($criteria as $criterionid => $criterion) {
                    if (empty($guidescores[$userid][$criterionid])) {
                        $score = 0;
                        $remark = '';
                    } else {
                        $score = $guidescores[$userid][$criterionid]->score;
                        $remark = $guidescores[$userid][$criterionid]->remark;
                    }
                    $score = round($score, $gradeprecision);
                    if ($criterion['maxscore']) {
                        $score .= '/'.round($criterion['maxscore'], $gradeprecision);
                    }
                    $value = $criterion['shortnametext'].': '.$score;
                    $text[] = html_writer::tag('em', $value, array('class' => "$numericalign guidescores criterion-$criterionid"));
                }
                unset($criteria);
            }
            if ($custom->config->showguidetotal && $custom->grading->method=='guide') {
                $maxscore = $custom->grading->definition->maxscore;
                if (isset($guidetotal[$userid])) {
                    $value = round($guidetotal[$userid], $gradeprecision);
                } else {
                    $value = 0;
                }
                $value .= '/'.round($maxscore, $gradeprecision);
                $value = get_string('guidetotal', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign guidetotal"));
            }
            if ($custom->config->showassigngrade) {
                $value = (isset($assigngrade[$userid]) ? $assigngrade[$userid] : 0);
                $value = get_string('assigngrade', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign assigngrade"));
            }
            if ($custom->config->showmodulegrade) {
                $value = (isset($modulegrade[$userid]) ? $modulegrade[$userid] : 0);
                $value = get_string('modulegrade', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign modulegrade"));
            }
            if ($custom->config->showcoursegrade) {
                $value = (isset($coursegrade[$userid]) ? $coursegrade[$userid] : 0);
                $value = get_string('coursegrade', $plugin, $value);
                $text[] = html_writer::tag('em', $value, array('class' => "$numericalign coursegrade"));
            }

            // Convert the $text array to a string.
            // Also, we append a space to prevent &nbsp; being added
            // by "HTML_QuickForm_Renderer_Tableless->finishForm()"
            // in "lib/pear/HTML/QuickForm/Renderer/Tableless.php"
            $text = implode('', $text).' ';

            if ($custom->config->multipleusers) {
                $elements[] = $mform->createElement('checkbox', $name.'['.$userid.']', $userid, $text);
            } else {
                $elements[] = $mform->createElement('radio', $name, '', $text, $userid);
            }
            if (empty($coords[$userid])) {
                $x = '';
                $y = '';
            } else {
                $x = $coords[$userid]->x;
                $y = $coords[$userid]->y;
            }
            $elements[] = $mform->createElement('hidden', $name.'x['.$userid.']', $x, array('id' => 'id_'.$name.'x_'.$userid));
            $elements[] = $mform->createElement('hidden', $name.'y['.$userid.']', $y, array('id' => 'id_'.$name.'y_'.$userid));
        }

        if (empty($elements)) {
            $msg = get_string('nousersfound', $plugin);
            $params = array('class' => 'nousersfound');
            $msg = html_writer::tag('span', $msg, $params);
            $elements[] = $mform->createElement('static', '', '', $msg);
        }

        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);

        if ($custom->config->multipleusers) {
            foreach ($custom->$name as $userid => $user) {
                $mform->setType($name.'['.$userid.']', PARAM_INT);
                $mform->setDefault($name.'['.$userid.']', 0);
            }
        } else {
            $mform->setType($name, PARAM_INT);
            $mform->setDefault($name, 0);
        }
        foreach ($custom->$name as $userid => $user) {
            $mform->setType($name.'x['.$userid.']', PARAM_INT);
            $mform->setType($name.'y['.$userid.']', PARAM_INT);
        }
    }

    /**
     * add_field_points
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_points($mform, $custom, $plugin) {
        $name = 'points';
        $label = get_string($name, $plugin);
        $min = (empty($custom->config->minpoints) ? 0 : $custom->config->minpoints);
        $inc = (empty($custom->config->increment) ? 0 : $custom->config->increment);
        $max = (empty($custom->config->maxpoints) ? 0 : $custom->config->maxpoints);
        if ($max > $min) {
            $inc = max(1, $inc);
        } else {
            $inc = min(-1, $inc);
        }
        $elements = array();
        // add one element for each point value
        for ($i=$min; $i<($max + $inc); $i+=$inc) {
            if ($i > $max) {
                $i = $max;
            }
            $elements[] = $mform->createElement('radio', $name, '', $i, $i);
        }
        // append a reset element if necessary
        if (($min<0 && $max<0) || ($min>0 && $max>0)) {
            $elements[] = $mform->createElement('radio', $name, '', get_string('reset'), 0);
        }
        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, 0);
    }

    /**
     * add_field_commenttext
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_commenttext($mform, $custom, $plugin) {
        global $DB, $USER;

        $name = 'commenttext';
        $label = get_string($name, $plugin);
        $options = array('size' => '40', 'maxsize' => 255, 'style' => 'width: auto;');

        if ($custom->config->showcomments) {
            $commenttext_NOT_LIKE = $DB->sql_like('commenttext', '?', false, false, true);
            $undo = get_string('undo', $plugin);
            $sql = '';
            $sql .= 'SELECT commenttext, COUNT(*) AS frequency ';
            $sql .= 'FROM {assignfeedback_points} ';
            $sql .= 'WHERE awardby = ? AND commenttext <> ? AND '.$commenttext_NOT_LIKE;
            $sql .= 'GROUP BY commenttext ';
            $sql .= 'ORDER BY frequency DESC, commenttext ASC ';
            $comments = $DB->get_records_sql_menu($sql, array($USER->id, '', "$undo%"), 0, 10);
        } else {
            $comments = false;
        }
        if ($comments) {
            foreach ($comments as $comment => $frequency) {
                $comments[$comment] = "($frequency) $comment";
            }
            $comments[''] = get_string('newcomment', $plugin);
            $elements = array();
            $elements[] = $mform->createElement('select', $name.'menu', '', $comments);
            $elements[] = $mform->createElement('text',  $name, '', $options);
            $mform->addGroup($elements, $name.'elements', $label, ' ', false);
            $mform->addHelpButton($name.'elements', $name, $plugin);
            $mform->disabledIf($name, $name.'menu', 'ne', '');
            $mform->setType($name.'menu', PARAM_TEXT);
            $mform->setType($name, PARAM_TEXT);
            $mform->setDefault($name.'menu', '');
            $mform->setDefault($name, '');
        } else {
            $mform->addElement('text', $name, $label, $options);
            $mform->addHelpButton($name, $name, $plugin);
            $mform->setType($name, PARAM_TEXT);
            $mform->setDefault($name, '');
        }
    }

    /**
     * add_field_advancedgrading
     *
     * this code mimics $custom->assignment->get_grading_instance()
     * which is a protected method in "mod/assign/locallib.php"
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     * @param string  $name (optional, default="advancedgrading")
     */
    private function add_field_advancedgrading($mform, $custom, $plugin, $name='advancedgrading') {

        // form elements are produced by the "display_xxx()" method
        // in the "grade/grading/form/xxx/renderer.php" script file

        if ($custom->grading->instance) {
            $instance = $custom->grading->instance;
            $label = $custom->grading->controller->get_definition()->name;
            $mform->addElement('grading', $name, $label, array('gradinginstance' => $instance));
            $mform->addElement('hidden', $name.'instanceid', $instance->get_id());
            $mform->setType($name.'instanceid', PARAM_INT);
        } else {
            // shouldn't happen !!
            $text = $custom->grading->controller->form_unavailable_notification();
            $label = get_string('gradingmanagement', 'grading');
            $mform->addElement('static', $name, $label, $text);
        }
    }

    /**
     * add_field_layouts
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_layouts($mform, $custom, $plugin) {
        global $DB, $USER;

        // square   full 3/4 1/2 1/4 percent
        // circle   full 3/4 1/2 1/4 percent
        // lines    [ vertical  horizontal ]
        //          number of lines:     [_]
        //          people per line:     [_]
        // islands  shape  [ round  square ]
        //          number of islands:   [_]
        //          people per island:   [_]
        // save current layout: name [_____]
        // load saved layout: [____________]
        // delete current layout

        $name = 'layouts';
        $label = get_string($name, $plugin);
        $elements = array();

        $short_text_options = array('size'  => 3,
                                    'style' => 'width: auto;');
        $long_text_options  = array('size'  => 24,
                                    'style' => 'width: auto;');

        $table = 'assignfeedback_points_maps';
        $params = array('userid' => $USER->id, 'assignid' => $custom->assignid);
        $layouts = $DB->get_records_menu($table, $params, 'name', 'id,name');

        if ($layouts) {
            $elements[] = $mform->createElement('radio', $name, '', get_string('load', $plugin), 'load');
            $elements[] = $mform->createElement('select', $name.'loadid', '', $layouts);
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
        }

        $elements[] = $mform->createElement('radio', $name, '', get_string('setup',  $plugin), 'setup');
        $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));

        $types = array('square', 'circle');
        foreach ($types as $type) {
            $elements[] = $mform->createElement('radio', $name.'setup', '', get_string($type, $plugin), $type, array('class' => 'indent'));
            $elements[] = $mform->createElement('radio', $name.$type,   '', get_string('percent100', $plugin), 100);
            $elements[] = $mform->createElement('radio', $name.$type,   '', get_string('percent75',  $plugin),  75);
            $elements[] = $mform->createElement('radio', $name.$type,   '', get_string('percent50',  $plugin),  50);
            $elements[] = $mform->createElement('radio', $name.$type,   '', get_string('percent25',  $plugin),  25);
            $elements[] = $mform->createElement('radio', $name.$type,   '', get_string('percent',    $plugin), 'percent');
            $elements[] = $mform->createElement('text',  $name.$type.'percent', get_string('percent', $plugin), $short_text_options);
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
        }

        $types = array('lines'   => array(0 => array(0 => get_string('horizontal',      $plugin),
                                                     1 => get_string('vertical',        $plugin)),
                                          1 => array(0 => get_string('numberoflines',   $plugin),
                                                     1 => get_string('peopleperline',   $plugin))),
                       'islands' => array(0 => array(0 => get_string('circle',          $plugin),
                                                     1 => get_string('square',          $plugin)),
                                          1 => array(0 => get_string('numberofislands', $plugin),
                                                     1 => get_string('peopleperisland', $plugin))));
        foreach ($types as $type => $options) {
            $elements[] = $mform->createElement('radio',  $name.'setup', '', get_string($type, $plugin), $type, array('class' => 'indent'));
            $elements[] = $mform->createElement('select', $name.$type.'type',     '', $options[0]);
            $elements[] = $mform->createElement('select', $name.$type.'numtype',  '', $options[1]);
            $elements[] = $mform->createElement('text',   $name.$type.'numvalue', '', $short_text_options);
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
        }

        $elements[] = $mform->createElement('radio', $name, '', get_string('save', $plugin), 'save');
        $elements[] = $mform->createElement('text',  $name.'savename', get_string('save', $plugin), $long_text_options);

        if ($layouts) {
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
            $elements[] = $mform->createElement('radio', $name, '', get_string('delete', $plugin), 'delete');
            $elements[] = $mform->createElement('select', $name.'deleteid', '', $layouts);
        }

        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);

        $mform->setType($name,                 PARAM_ALPHA);
        $mform->setType($name.'loadid',          PARAM_INT);
        $mform->setType($name.'setup',      PARAM_ALPHANUM);
        $mform->setType($name.'squarepercent',   PARAM_INT);
        $mform->setType($name.'circlepercent',   PARAM_INT);
        $mform->setType($name.'linestype',       PARAM_INT);
        $mform->setType($name.'linesnumtype',    PARAM_INT);
        $mform->setType($name.'linesnumvalue',   PARAM_INT);
        $mform->setType($name.'islandstype',     PARAM_INT);
        $mform->setType($name.'islandsnumtype',  PARAM_INT);
        $mform->setType($name.'islandsnumvalue', PARAM_INT);
        $mform->setType($name.'savename',       PARAM_TEXT);

        if ($layouts) {
            $mform->setDefault($name, 'load');
            $mform->setDefault($name.'loadid', $custom->mapid);
            $mform->disabledIf($name.'loadid', $name, 'ne', 'load');
        } else {
            $mform->setDefault($name, 'save');
            $mform->setDefault($name.'savename', get_string('default'));
        }
        $mform->disabledIf($name.'savename', $name, 'ne', 'save');

        $mform->setDefault($name.'setup',     'square');
        $mform->setDefault($name.'square',        '75');
        $mform->setDefault($name.'circle',        '75');
        $mform->setDefault($name.'linestype',      '0');
        $mform->setDefault($name.'linesnumtype',   '0');
        $mform->setDefault($name.'islandstype',    '0');
        $mform->setDefault($name.'islandsnumtype', '0');

        $mform->disabledIf($name.'setup',           $name, 'ne', 'setup');
        $mform->disabledIf($name.'square',          $name, 'ne', 'setup');
        $mform->disabledIf($name.'squarepercent',   $name, 'ne', 'setup');
        $mform->disabledIf($name.'circle',          $name, 'ne', 'setup');
        $mform->disabledIf($name.'circlepercent',   $name, 'ne', 'setup');
        $mform->disabledIf($name.'lines',           $name, 'ne', 'setup');
        $mform->disabledIf($name.'linestype',       $name, 'ne', 'setup');
        $mform->disabledIf($name.'linesnumtype',    $name, 'ne', 'setup');
        $mform->disabledIf($name.'linesnumvalue',   $name, 'ne', 'setup');
        $mform->disabledIf($name.'islands',         $name, 'ne', 'setup');
        $mform->disabledIf($name.'islandstype',     $name, 'ne', 'setup');
        $mform->disabledIf($name.'islandsnumtype',  $name, 'ne', 'setup');
        $mform->disabledIf($name.'islandsnumvalue', $name, 'ne', 'setup');

        $mform->disabledIf($name.'square',          $name.'setup',  'ne', 'square');
        $mform->disabledIf($name.'squarepercent',   $name.'setup',  'ne', 'square');
        $mform->disabledIf($name.'squarepercent',   $name.'square', 'ne', 'percent');

        $mform->disabledIf($name.'circle',          $name.'setup',  'ne', 'circle');
        $mform->disabledIf($name.'circlepercent',   $name.'setup',  'ne', 'circle');
        $mform->disabledIf($name.'circlepercent',   $name.'circle', 'ne', 'percent');

        $mform->disabledIf($name.'lines',           $name.'setup',  'ne', 'lines');
        $mform->disabledIf($name.'linestype',       $name.'setup',  'ne', 'lines');
        $mform->disabledIf($name.'linesnumtype',    $name.'setup',  'ne', 'lines');
        $mform->disabledIf($name.'linesnumvalue',   $name.'setup',  'ne', 'lines');

        $mform->disabledIf($name.'islands',         $name.'setup',  'ne', 'islands');
        $mform->disabledIf($name.'islandstype',     $name.'setup',  'ne', 'islands');
        $mform->disabledIf($name.'islandsnumtype',  $name.'setup',  'ne', 'islands');
        $mform->disabledIf($name.'islandsnumvalue', $name.'setup',  'ne', 'islands');

        if ($layouts) {
            $mform->setDefault($name.'deleteid', $custom->mapid);
            $mform->disabledIf($name.'deleteid', $name, 'ne', 'delete');
        }
    }

    /**
     * add_field_report
     *
     * add report of point awards for a single user
     *
     * @param object  $mform
     * @param object  $custom
     * @param string  $plugin
     */
    private function add_field_report($mform, $custom, $plugin) {
        if ($custom->grading->method=='') {
            $title = 'reporttitlepoints';
        } else {
            $title = 'reporttitle'.$custom->grading->method;
        }
        $params = array('id' => 'id_report_container',
                        'title' => get_string($title, $plugin));
        $report = html_writer::tag('div', '', $params);
        $mform->addElement('html', $report);
    }
}
