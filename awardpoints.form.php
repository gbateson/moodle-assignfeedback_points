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

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

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

    protected $integerfields = array('minpoints'       => -1, 'increment'       => 1, 'maxpoints'    => 2);
    protected $booleanfields = array('sendimmediately' =>  1, 'multipleusers'   => 0, 'showelement'  => 0,
                                     'showpicture'     =>  1, 'showfullname'    => 0, 'showusername' => 0,
                                     'showpointstoday' =>  1, 'showpointstotal' => 1, 'showcomments' => 1,
                                     'showlink'        =>  1);

    protected $js_safe_replacements = array('\\'   => '\\\\',  "'"  =>"\\'", '"'=>'\\"',  // slashes and quotes
                                            "\r\n" => '\\n',   "\r" =>'\\n', "\n"=>'\\n', // newlines (win, mac, nix)
                                            "\0"   => '\\0',   '</' =>'<\\/');            // other replacements

    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {

        $mform = $this->_form;
        $custom = $this->_customdata;
        $plugin = 'assignfeedback_points';

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
        $this->add_heading($mform, $name, $plugin, true);
        $this->add_field_groups($mform, $custom, $plugin);
        $this->add_field_feedback($mform, $custom, $plugin);
        $this->add_field_mapaction($mform, $custom, $plugin);
        $this->add_field_awardto($mform, $custom, $plugin);
        $this->add_field_points($mform, $custom, $plugin);
        $this->add_field_commenttext($mform, $custom, $plugin);

        // ========================
        // layouts section
        // ========================
        //
        $this->add_heading($mform, 'layouts', $plugin, false);
        $this->add_field_layouts($mform, $custom, $plugin);

        // ========================
        // settings section
        // ========================
        //
        $this->add_heading($mform, 'settings', 'moodle', false);

        $this->add_field_pointstype($mform, $custom, $plugin);

        // add the integer fields (min/max number of points)
        $options = array('size' => 4, 'maxsize' => 4);
        foreach ($this->integerfields as $name => $default) {
            $this->add_setting($mform, $custom, $plugin, $name, 'text', $default, $options);
        }

        // add the boolean fields (show fullname/picture, etc)
        foreach ($this->booleanfields as $name => $default) {
            if ($name=='showfullname') {
                $fields = assign_feedback_points::format_user_name_fields();
                $this->add_setting($mform, $custom, $plugin, $name, 'select', $default, $fields, PARAM_ALPHA);
            } else {
                $this->add_setting($mform, $custom, $plugin, $name, 'checkbox', $default);
            }
        }

        // disable "showpointstoday" if we are not using incremental points
        $mform->disabledIf('showpointstoday', 'pointstype', 'ne', '0');

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

        $mform->addElement('hidden', 'mapid', $custom->mapid);
        $mform->setType('mapid', PARAM_INT);
        $mform->addElement('hidden', 'mapwidth', $custom->mapwidth, array('id' => 'id_mapwidth'));
        $mform->setType('mapwidth', PARAM_INT);
        $mform->addElement('hidden', 'mapheight', $custom->mapheight, array('id' => 'id_mapheight'));
        $mform->setType('mapheight', PARAM_INT);
        $mform->addElement('hidden', 'userwidth', $custom->userwidth, array('id' => 'id_userwidth'));
        $mform->setType('userwidth', PARAM_INT);
        $mform->addElement('hidden', 'userheight', $custom->userheight, array('id' => 'id_userheight'));
        $mform->setType('userheight', PARAM_INT);
        $mform->addElement('hidden', 'groupid', $custom->groupid);
        $mform->setType('groupid', PARAM_INT);

        // ========================
        // buttons
        // ========================
        //
        $this->add_action_buttons(true, get_string('awardpoints', $plugin));

        // ========================
        // jQuery (javascript)
        // ========================
        //
        $this->add_field_jquery($mform, $custom, $plugin);
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
     * add_heading
     *
     * @param object  $mform
     * @param string  $name
     * @param string  $plugin
     * @param boolean $expanded (optional, default=TRUE)
     * @param string  $suffix   (optional, default="_hdr")
     */
    private function add_heading($mform, $name, $plugin, $expanded=true, $suffix='_hdr') {
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name.$suffix, $label);
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($name.$suffix, $expanded);
        }
    }

    /**
     * add_field_groups
     *
     * @param object  $mform
     * @param string  $custom
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
        if (preg_match('/(<label[^>]*>.*<\/label>).*?(<select[^>]*name="([^"]*)"[^>]*>.*<\/select>)/', $groups, $groups)) {
            $name = $groups[3]; // "group"
            $groups = $groups[1].' '.$groups[2];
            $mform->addElement('static', $name, '', $groups);
        }
    }

    /**
     * add_field_feedback
     *
     * @param object  $mform
     * @param string  $custom
     * @param string  $plugin
     */
    private function add_field_feedback($mform, $custom, $plugin) {
        $name = 'feedback';
        $label = get_string($name);
        if ($custom->feedback=='') {
            $custom->feedback = html_writer::tag('span', '', array('id' => 'feedback'));
        }
        $mform->addElement('static', $name.'feedback', get_string('feedback'), $custom->feedback);
    }

    /**
     * add_field_awardto
     *
     * @param object  $mform
     * @param string  $custom
     * @param string  $plugin
     */
    private function add_field_awardto($mform, $custom, $plugin) {
        global $DB, $OUTPUT;

        $name = 'awardto';
        $label = get_string($name, $plugin);

        // get userids passed via $custom values
        $userids = array_keys($custom->$name);

        // are there any users?
        $usersfound = (empty($userids) ? false : true);

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

        // get points total for each user, if required
        if ($usersfound && $custom->config->showpointstotal) {
            $select = "p.$name";
            $from   = '{assignfeedback_points} p';
            list($where, $params) = $DB->get_in_or_equal($userids);
            $where  = "p.assignid = ? AND p.$name $where";
            array_unshift($params, $custom->assignid);
            if ($custom->config->pointstype==0) {
                $select .= ', SUM(p.points) AS pointstotal';
                $where  .= " GROUP BY p.$name";
            } else {
                $select .= ', p.points AS pointstotal';
                $where  .= ' AND p.timeawarded = (SELECT MAX(timeawarded) '.
                                                 'FROM {assignfeedback_points} t '.
                                                 "WHERE p.assignid = t.assignid AND p.$name = t.$name)";
            }
            $pointstotal = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params);
        } else {
            $pointstotal = false;
        }
        if ($pointstotal===false) {
            $pointstotal = array();
        }

        // when using incremental points (pointstype==0)
        // get points today for each user, if required
        if ($usersfound && $custom->config->pointstype==0 && $custom->config->showpointstoday) {
            $select = "$name, SUM(points) AS pointstoday";
            $from   = '{assignfeedback_points}';
            list($where, $params) = $DB->get_in_or_equal($userids);
            $where  = "assignid = ? AND timeawarded > ? AND $name $where";
            array_unshift($params, $custom->assignid, time() - DAYSECS);
            $pointstoday = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where GROUP BY $name", $params);
        } else {
            $pointstoday = false;
        }
        if ($pointstoday===false) {
            $pointstoday = array();
        }

        $elements = array();
        foreach ($custom->$name as $userid => $user) {
            $text = array();
            if ($custom->config->showpicture) {
                $params = array('courseid' => $custom->courseid, 'link' => false);
                $text[] = $OUTPUT->user_picture($user, $params);
            }
            if ($custom->config->showfullname) {
                $field = $custom->config->showfullname;
                $fields = assign_feedback_points::get_all_user_name_fields();
                if (in_array($field, $fields) && property_exists($user, $field) && $user->$field) {
                    $text[] = html_writer::tag('em', $user->$field, array('class' => 'name'));
                } else {
                    $text[] = html_writer::tag('em', fullname($user), array('class' => 'name'));
                }
            }
            if ($custom->config->showusername || count($text)==0) {
                $text[] = html_writer::tag('em', $user->username, array('class' => 'name'));
            }
            if ($custom->config->showpointstotal) {
                $points = (isset($pointstotal[$userid]) ? $pointstotal[$userid] : 0);
                $points = get_string('pointstotal', $plugin, $points);
                $text[] = html_writer::tag('em', $points, array('class' => 'pointstotal'));
            }
            if ($custom->config->showpointstoday) {
                $points = (isset($pointstoday[$userid]) ? $pointstoday[$userid] : 0);
                $points = get_string('pointstoday', $plugin, $points);
                $text[] = html_writer::tag('em', $points, array('class' => 'pointstoday'));
            }
            $text = implode(html_writer::empty_tag('br'), $text);

            if ($custom->config->multipleusers) {
                $elements[] = $mform->createElement('checkbox', $name.'['.$userid.']', $userid, $text);
            } else {
                $elements[] = $mform->createElement('radio', $name, $userid, $text, $userid);
            }
            if (empty($coords[$userid])) {
                $x = '';
                $y = '';
            } else {
                $x = $coords[$userid]->x;
                $y = $coords[$userid]->y;
            }
            $elements[] = $mform->createElement('hidden', $name.'x['.$userid.']', $x, array('id' => 'id_awardtox_'.$userid));
            $elements[] = $mform->createElement('hidden', $name.'y['.$userid.']', $y, array('id' => 'id_awardtoy_'.$userid));
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
     * add_field_mapaction
     *
     * @param object  $mform
     * @param string  $custom
     * @param string  $plugin
     */
    private function add_field_mapaction($mform, $custom, $plugin) {
        $name = 'mapaction';
        $label = get_string($name, $plugin);
        $mapactions = array('none'     => get_string('none'),
                            'reset'    => get_string('reset',    $plugin),
                            'cleanup'  => get_string('cleanup',  $plugin),
                            'separate' => get_string('separate', $plugin),
                            'shuffle'  => get_string('shuffle',  $plugin),
                            'resize'   => get_string('resize',   $plugin),
                            'rotate'   => get_string('rotate',   $plugin));
        $elements = array();
        foreach ($mapactions as $value => $text) {
            $elements[] = $mform->createElement('radio', $name, '', $text, $value);
        }
        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);
        $mform->setType($name, PARAM_ALPHA);
        $mform->setDefault($name, 'none');
    }

    /**
     * add_field_points
     *
     * @param object  $mform
     * @param string  $custom
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
        // add a reset element if necessary
        if (($min<0 && $max<0) || ($min>0 && $max>0)) {
            $elements[] = $mform->createElement('radio', $name, 0, get_string('reset'), 0);
        }
        // add one element for each point value
        for ($i=$min; $i<($max + $inc); $i+=$inc) {
            if ($i > $max) {
                $i = $max;
            }
            $elements[] = $mform->createElement('radio', $name, $i, $i, $i);
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
     * @param string  $custom
     * @param string  $plugin
     */
    private function add_field_commenttext($mform, $custom, $plugin) {
        global $DB, $USER;

        $name = 'commenttext';
        $label = get_string($name, $plugin);
        $options = array('size' => '40', 'maxsize' => 255);

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

    private function add_field_pointstype($mform, $custom, $plugin, $default=0) {
        $name = 'pointstype';
        $label = get_string($name, $plugin);
        $options = array(0 => get_string('incrementalpoints', $plugin),
                         1 => get_string('totalpoints',       $plugin));
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        if (isset($custom->config->$name)) {
            $default = $custom->config->$name;
        }
        $mform->setDefault($name, $default);
    }

    /**
     * add_field_layouts
     *
     * @param object  $mform
     * @param string  $custom
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

        $table = 'assignfeedback_points_maps';
        $params = array('userid' => $USER->id, 'assignid' => $custom->assignid);
        $layouts = $DB->get_records_menu($table, $params, 'name', 'id,name');

        if ($layouts) {
            $elements[] = $mform->createElement('radio', $name, 'load', get_string('load', $plugin), 'load');
            $elements[] = $mform->createElement('select', $name.'loadid', '', $layouts);
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
        }

        $elements[] = $mform->createElement('radio', $name, 'setup', get_string('setup',  $plugin), 'setup');
        $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));

        $types = array('square', 'circle');
        foreach ($types as $type) {
            $elements[] = $mform->createElement('radio', $name.'setup', $type, get_string($type, $plugin), $type, array('class' => 'indent'));
            $elements[] = $mform->createElement('radio', $name.$type, '100',     get_string('percent100', $plugin), 100);
            $elements[] = $mform->createElement('radio', $name.$type, '75',      get_string('percent75',  $plugin), 75);
            $elements[] = $mform->createElement('radio', $name.$type, '50',      get_string('percent50',  $plugin), 50);
            $elements[] = $mform->createElement('radio', $name.$type, '25',      get_string('percent25',  $plugin), 25);
            $elements[] = $mform->createElement('radio', $name.$type, 'percent', get_string('percent',    $plugin), 'percent');
            $elements[] = $mform->createElement('text',  $name.$type.'percent',  get_string('percent',    $plugin), array('size' => 3));
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
            $elements[] = $mform->createElement('radio', $name.'setup', $type, get_string($type, $plugin), $type, array('class' => 'indent'));
            $elements[] = $mform->createElement('select', $name.$type.'type',     '', $options[0]);
            $elements[] = $mform->createElement('select', $name.$type.'numtype',  '', $options[1]);
            $elements[] = $mform->createElement('text',   $name.$type.'numvalue', '', array('size' => 3));
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
        }

        $elements[] = $mform->createElement('radio', $name, 'save',   get_string('save',   $plugin), 'save');
        $elements[] = $mform->createElement('text',  $name.'savename', get_string('save',  $plugin), array('size' => 24));

        if ($layouts) {
            $elements[] = $mform->createElement('static', '', '', html_writer::empty_tag('br'));
            $elements[] = $mform->createElement('radio', $name, 'delete', get_string('delete', $plugin), 'delete');
            $elements[] = $mform->createElement('select', $name.'deleteid', '', $layouts);
        }

        $mform->addGroup($elements, $name.'elements', $label, '', false);
        $mform->addHelpButton($name.'elements', $name, $plugin);

        $mform->setType($name, PARAM_ALPHA);
        $mform->setType($name.'loadid', PARAM_INT);
        $mform->setType($name.'setup', PARAM_ALPHANUM);
        $mform->setType($name.'squarepercent', PARAM_INT);
        $mform->setType($name.'circlepercent', PARAM_INT);
        $mform->setType($name.'linestype', PARAM_INT);
        $mform->setType($name.'linesnumtype', PARAM_INT);
        $mform->setType($name.'linesnumvalue', PARAM_INT);
        $mform->setType($name.'islandstype', PARAM_INT);
        $mform->setType($name.'islandsnumtype', PARAM_INT);
        $mform->setType($name.'islandsnumvalue', PARAM_INT);
        $mform->setType($name.'savename', PARAM_TEXT);

        $mform->setDefault($name.'setup', 'square');
        $mform->setDefault($name.'square', '75');
        $mform->setDefault($name.'circle', '75');
        $mform->setDefault($name.'linestype', '0');
        $mform->setDefault($name.'linesnumtype', '0');
        $mform->setDefault($name.'islandstype', '0');
        $mform->setDefault($name.'islandsnumtype', '0');

        if ($layouts) {
            $mform->setDefault($name, 'load');
            $mform->disabledIf($name.'loadid',   $name, 'ne', 'load');
        } else {
            $mform->setDefault($name, 'setup');
        }

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

        $mform->disabledIf($name.'savename', $name, 'ne', 'save');

        if ($layouts) {
            $mform->disabledIf($name.'deleteid', $name, 'ne', 'delete');
        }
    }

    /**
     * add_setting
     *
     * @param $mform
     * @param $custom
     * @param $plugin
     * @param $name of field
     * @param $type of QuickForm field
     * @param $default (optiona, dedfault = null)
     * @param $options (optional, default = null)
     * @param $paramtype (optional, default=PARAM_INT)
     * @todo Finish documenting this function
     */
    private function add_setting($mform, $custom, $plugin, $name, $type, $default, $options=null, $paramtype=PARAM_INT) {
        $label = get_string($name, $plugin);
        $mform->addElement($type, $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, $paramtype);
        if (isset($custom->config->$name)) {
            $mform->setDefault($name, $custom->config->$name);
        } else if (isset($default)) {
            $mform->setDefault($name, $default);
        }
    }

    /**
     * add_field_jquery
     *
     * add jQuery javascript to make users draggable in a resizable container
     *
     * @param object  $mform
     * @param string  $custom
     * @param string  $plugin
     */
    private function add_field_jquery($mform, $custom, $plugin) {

        $contacting_server_msg = get_string('contactingserver', $plugin);
        $awardpoints_ajax_php = '/mod/assign/feedback/points/awardpoints.ajax.php';
        $awardpoints_ajax_php = new moodle_url($awardpoints_ajax_php, array('id' => $custom->cm->id));

        $js = '';
        $js .= '<script type="text/javascript">'."\n";
        $js .= '//<![CDATA['."\n";

        $js .= '    if (typeof(window.PTS)=="undefined") {'."\n";
        $js .= '        window.PTS = {};'."\n";
        $js .= '    }'."\n";

        $js .= '    PTS.elementtype           = "'.($custom->config->multipleusers ? 'checkbox' : 'radio').'";'."\n";
        $js .= '    PTS.elementdisplay        = "'.($custom->config->showelement  ? ' ' : 'none').'";'."\n";

        $js .= '    PTS.pointstype            = '.$custom->config->pointstype.",\n";
        $js .= '    PTS.showpointstoday       = '.($custom->config->showpointstoday ? 'true' : 'false').";\n";
        $js .= '    PTS.showpointstotal       = '.($custom->config->showpointstotal ? 'true' : 'false').";\n";

        $js .= '    PTS.sendimmediately       = '.($custom->config->sendimmediately ? 'true' : 'false').";\n";
        $js .= '    PTS.showpointstoday       = '.($custom->config->showpointstoday ? 'true' : 'false').";\n";
        $js .= '    PTS.showpointstotal       = '.($custom->config->showpointstotal ? 'true' : 'false').";\n";

        $js .= '    PTS.layouts_container     = "div#fgroup_id_layoutselements"'.",\n";

        $js .= '    PTS.mapaction_container   = "div#fgroup_id_mapactionelements fieldset.fgroup";'."\n";
        $js .= '    PTS.mapaction_min_width   = "48"'.";\n";
        $js .= '    PTS.mapaction_min_height  = "18"'.";\n";

        $js .= '    PTS.user_container        = "div#fgroup_id_awardtoelements fieldset.fgroup";'."\n";
        $js .= '    PTS.user_min_width        = "60"'.";\n";
        $js .= '    PTS.user_min_height       = "18"'.";\n";

        $js .= '    PTS.points_container      = "div#fgroup_id_pointselements fieldset.fgroup";'."\n";
        $js .= '    PTS.points_min_width      = "48"'.";\n";
        $js .= '    PTS.points_min_height     = "24"'.";\n";

        $js .= '    PTS.contacting_server_msg = "'.$this->js_safe($contacting_server_msg).'";'."\n";
        $js .= '    PTS.awardpoints_ajax_php  = "'.$this->js_safe($awardpoints_ajax_php).'";'."\n";
        $js .= '    PTS.groupid               = "'.$custom->groupid.'";'."\n";
        $js .= '    PTS.sesskey               = "'.sesskey().'";'."\n";

        $js .= '    PTS.cleanup               = {duration : 400};'."\n";
        $js .= '    PTS.separate              = {duration : 400, grid : {x : 12, y : 8}};'."\n";
        $js .= '    PTS.rotate                = {duration : 400, timeout : 400}'."\n";
        $js .= '    PTS.resize                = {duration : 400};'."\n";
        $js .= '    PTS.shuffle               = {duration : 400};'."\n";

        $js .= '//]]>'."\n";
        $js .= '</script>'."\n";

        $mform->addElement('html', $js);
    }

    /**
     * js_safe
     *
     * @param string $str
     */
    private function js_safe($str) {
        return strtr($str, $this->js_safe_replacements);
    }
}
