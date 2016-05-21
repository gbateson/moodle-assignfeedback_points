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
 * This file contains the definition for the library class for point feedback plugin
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for point feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_points extends assign_feedback_plugin {

    protected $integerfields = array('minpoints'       => -1, 'increment'       =>  1, 'maxpoints'    => 2);
    protected $booleanfields = array('sendimmediately' =>  1, 'multipleusers'   =>  0, 'showelement'  => 0,
                                     'showpicture'     =>  1, 'showfullname'    => '', 'showusername' => 0,
                                     'showpointstoday' =>  1, 'showpointstotal' =>  1, 'showcomments' => 1,
                                     'showlink'        =>  1);

    /**
     * Get the name of the feedback points plugin.
     * @return string
     */
    public function get_name() {
        $plugin = 'assignfeedback_points';
        return get_string('pluginname', $plugin);
    }

    /**
     * Get the default setting for feedback points plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $plugin = 'assignfeedback_points';

        // get the site wide defaults for this $plugin
        $config = get_config($plugin);

        // override with settings for this assign(ment) activity
        if ($this->assignment->has_instance()) {
            foreach ($this->get_config() as $name => $value) {
                $config->$name = $value;
            }
        }

        // add header for new section
        // (because there are quite a few settings)
        $name = 'settings';
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name, $label);
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($name, false);
        }

        $name = 'pointstype';
        $label = get_string($name, $plugin);
        $options = array(0 => get_string('incrementalpoints', $plugin),
                         1 => get_string('totalpoints',       $plugin));
        $mform->addElement('select', $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, PARAM_INT);
        if (isset($config->$name)) {
            $mform->setDefault($name, $config->$name);
        }

        // add the integer fields (min/max number of points)
        $options = array('size' => 4, 'maxsize' => 4);
        foreach ($this->integerfields as $name => $default) {
            $this->add_setting($mform, $config, $plugin, $name, 'text', $default, $options);
        }

        // add the boolean fields (show fullname/picture, etc)
        foreach ($this->booleanfields as $name => $default) {
            if ($name=='showfullname') {
                $fields = self::format_user_name_fields();
                $this->add_setting($mform, $config, $plugin, $name, 'select', $default, $fields, PARAM_ALPHA);
            } else {
                $this->add_setting($mform, $config, $plugin, $name, 'checkbox', $default);
            }
        }

        // disable "showpointstoday" if we are not using incremental points
        $mform->disabledIf('showpointstoday', 'pointstype', 'ne', '0');
   }

    /**
     * add_setting
     *
     * @param $mform
     * @param $config
     * @param $plugin
     * @param $name of field
     * @param $type of QuickForm field
     * @param $default (optional, default = null)
     * @param $options (optional, default = null)
     * @param $paramtype (optional, default=PARAM_INT)
     * @todo Finish documenting this function
     */
    private function add_setting($mform, $config, $plugin, $name, $type, $default=null, $options=null, $paramtype=PARAM_INT) {
        $label = get_string($name, $plugin);
        $mform->addElement($type, $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, $paramtype);
        if (isset($config->$name)) {
            $default = $config->$name;
        }
        if (isset($default)) {
            $mform->setDefault($name, $default);
        }
        $name_adv = $name.'_adv';
        if (isset($config->$name_adv)) {
            $mform->setAdvanced($name, $config->$name_adv);
        }
        $mform->disabledIf($name, $plugin.'_enabled', 'notchecked');
    }

    /**
     * Save the settings for feedback points plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $name = 'pointstype';
        $this->set_config($name, empty($data->$name) ? 0 : intval($data->$name));

        foreach ($this->integerfields as $name => $default) {
            $this->set_config($name, empty($data->$name) ? 0 : intval($data->$name));
        }

        foreach ($this->booleanfields as $name => $default) {
            if ($name=='showfullname') {
                $fields = self::format_user_name_fields();
                if (isset($data->$name) && array_key_exists($data->$name, $fields)) {
                    $this->set_config($name, $data->$name);
                } else {
                    $this->set_config($name, '');
                }
            } else {
                $this->set_config($name, empty($data->$name) ? 0 : 1);
            }
        }

        return true;
    }

    /**
     * Save the settings for feedback points plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings_if_present(stdClass $data) {
        $name = 'pointstype';
        if (isset($data->$name)) {
            $this->set_config($name, intval($data->$name));
        }

        foreach ($this->integerfields as $name => $default) {
            if (isset($data->$name)) {
                $this->set_config($name, intval($data->$name));
            }
        }

        foreach ($this->booleanfields as $name => $default) {
            if (isset($data->$name)) {
                $this->set_config($name, empty($data->$name) ? 0 : 1);
            }
        }

        return true;
    }

    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main assignment page.
     *
     * @return string
     */
    public function view_header() {
        $output = '';

        // check if link is required or not ...
        if ($this->get_config('showlink')) {

            // create URL of the page for awarding incremental points
            $cm = $this->assignment->get_course_module();
            $params = array('id'            => $cm->id,
                            'plugin'        => 'points',
                            'pluginsubtype' => 'assignfeedback',
                            'action'        => 'viewpluginpage',
                            'pluginaction'  => 'awardpoints');
            $url = new moodle_url('/mod/assign/view.php', $params);

            // format HTML for output
            $output .= html_writer::start_tag('p');
            $output .= html_writer::tag('b', get_string('choosegradingaction', 'assign')).': ';
            $output .= html_writer::link($url, get_string('awardpoints', 'assignfeedback_points'));
            $output .= html_writer::end_tag('p');
        }

        return $output;
    }

    /**
     * Return a list of the grading actions performed by this plugin
     *
     * @return array The list of grading actions
     */
    public function get_grading_actions() {
        return array('awardpoints' => get_string('awardpoints', 'assignfeedback_points'));
    }

    /**
     * Print a sub page in this plugin
     *
     * @param string $action - The plugin action
     * @return string The response html
     */
    public function view_page($action) {
        switch ($action) {
            case 'awardpoints': return $this->award_points();
            default: return ''; // shouldn't happen !!
        }
    }

    /**
     * Award incremental points
     *
     * @return string The response html
     */
    public function award_points() {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        $name = 'awardpoints';
        $plugin = 'assignfeedback_points';
        $title = get_string($name, $plugin);

        require_capability('mod/assign:grade', $this->assignment->get_context());
        require_once($CFG->dirroot.'/mod/assign/feedback/points/awardpoints.form.php');

        $renderer = $this->assignment->get_renderer();
        $instance = $this->assignment->get_instance();
        $context  = $this->assignment->get_context();
        $course   = $this->assignment->get_course();
        $cm       = $this->assignment->get_course_module();

        // cancel if necessary - mimic is_cancelled() in "lib/formslib.php"
        if (optional_param('cancel', false, PARAM_RAW)) {
            $params = array('id' => $cm->id, 'action' => 'grading');
            redirect(new moodle_url('view.php', $params));
            return;
        }

        $params = array(
            'id' => '8',
            'plugin' => 'points',
            'pluginsubtype' => 'assignfeedback',
            'action' => 'viewpluginpage',
            'pluginaction' => 'awardpoints'
        );
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', $params));

        // add jQuery to this page
        if (method_exists($PAGE->requires, 'jquery')) {
            // Moodle >= 2.5
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('ui.touch-punch', $plugin);
        } else {
            // Moodle <= 2.4
            $jquery = '/mod/assign/feedback/points/jquery';
            $PAGE->requires->js($jquery.'/jquery.js', true);
            $PAGE->requires->js($jquery.'/jquery-ui.js', true);
            $PAGE->requires->js($jquery.'/jquery-ui.touch-punch.js', true);
        }
        $PAGE->requires->js('/mod/assign/feedback/points/awardpoints.js');

        // process incoming formdata, and fetch output settings
        // $multipleusers, $groupid, $map, $feedback, $userlist
        list($multipleusers, $groupid, $map, $feedback, $userlist) = $this->process_formdata();

        $custom = (object)array(
            'cm'         => $cm,
            'cmid'       => $cm->id,
            'mapid'      => $map->id,
            'mapwidth'   => $map->mapwidth,
            'mapheight'  => $map->mapheight,
            'userwidth'  => $map->userwidth,
            'userheight' => $map->userheight,
            'groupid'    => $map->groupid,
            'context'    => $context,
            'courseid'   => $course->id,
            'assignid'   => $instance->id,
            'config'     => $this->get_config(),
            'awardto'    => $userlist,
            'feedback'   => $feedback
        );
        $mform = new assignfeedback_points_award_points_form(null, $custom);

        $output = '';
        $output .= $renderer->render(new assign_header($instance, $context, false, $cm->id, $title));
        $output .= $renderer->render(new assign_form('awardpoints', $mform));
        $output .= $renderer->render_footer();

        return $output;
    }

    /**
     * Process the incoming formdata
     *
     * @return array($multipleusers, $groupid, $map, $feedback, $userlist)
     */
    public function process_formdata() {
        global $DB, $USER;

        $plugin   = 'assignfeedback_points';
        $instance = $this->assignment->get_instance();
        $cm       = $this->assignment->get_course_module();

        $ajax = optional_param('ajax', 0, PARAM_INT);
        $undo = optional_param('undo', 0, PARAM_INT);

        // get multipleusers setting that was used to create incoming form data
        if ($ajax) {
            $multipleusers = 0; // i.e. one user at a time
        } else {
            $multipleusers = $this->get_config('multipleusers');
        }

        // simulate data_submitted(), but detect $_GET as well as $_POST
        if ($undo) {
            $data = (empty($_GET) ? false : (object)fix_utf8($_GET));
        } else {
            $data = (empty($_POST) ? false : (object)fix_utf8($_POST));
        }

        // get original groupid
        $groupid = optional_param('groupid', false, PARAM_INT);
        if ($groupid===false) {
            $groupid = groups_get_activity_group($cm, false);
            if ($groupid===false) {
                $groupid = 0;
            }
        }

        // get userlist for original $groupid
        $userlist = $this->assignment->list_participants($groupid, false);

        // process incoming $data, if any
        if ($data) {

            if ($ajax) {
                // don't save settings
            } else if ($undo) {
                $this->save_settings_if_present($data);
            } else {
                $this->save_settings($data);
            }

            $time = time();

            // get/update user map
            $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id, true);
            $mapid = $map->id;

            // register incoming points in assignfeedback_points table
            $points        = optional_param('points',          0,  PARAM_INT);
            $commenttext   = optional_param('commenttextmenu', '', PARAM_TEXT);
            $commentformat = optional_param('commentformat',   0,  PARAM_INT);
            $action        = optional_param('action',          '', PARAM_ALPHA);

            // if commenttext was not selected from the drop down menu
            // try to get it from the text input element
            if ($commenttext=='') {
                $commenttext = optional_param('commenttext',   '', PARAM_TEXT);
            }

            // set up layouts, if required
            $name = 'layouts';
            $update = false;
            switch (optional_param($name, '', PARAM_ALPHA)) {

                case 'load':
                    $table = $plugin.'_maps';
                    if ($id = optional_param($name.'loadid', 0, PARAM_INT)) {
                        if ($id==$mapid) {
                            // do nothing - this is the current map
                        } else {
                            $params = array('id' => $id, 'assignid' => $instance->id, 'userid' => $USER->id);
                            if ($DB->record_exists($table, $params)) {
                                $map = $DB->get_record($table, $params);
                                $mapid = $map->id;
                                $update = true;
                            }
                        }
                    }
                    break;

                case 'setup':
                    $update = true;

                    $mapwidth = 0;
                    $mapheight = 0;
                    $userwidth = $map->userwidth;
                    $userheight = $map->userheight;

                    $user_container_padding = 8;

                    $table = $plugin.'_coords';
                    if ($coords = $DB->get_records($table, array('mapid' => $mapid))) {

                        // remove any $coords for users that are
                        // no longer in the group using this map
                        $userids = array();
                        foreach ($coords as $coord) {
                            $userid = $coord->userid;
                            if (array_key_exists($userid, $userlist)) {
                                $userids[$userid] = true; // keep this $userid
                            } else {
                                $DB->delete_records($table, array('id' => $coord->id));
                                unset($coords[$coord->id]);
                            }
                        }

                        // add any users that are missing from $coords
                        $userids = array_diff_key($userlist, $userids);
                        foreach (array_keys($userids) as $userid) {
                            $coord = (object)array(
                                'mapid' => $map->id,
                                'userid' => $userid,
                                'x' => 0,
                                'y' => 0
                            );
                            $coord->id = $DB->insert_record($table, $coords);
                            $coords[$coord->id] = $coord;
                        }

                        // tidy up
                        unset($userids, $userid);
                    }

                    if ($count = count($coords)) {

                        switch (optional_param($name.'setup', '', PARAM_ALPHA)) {

                            case 'square':

                                switch (optional_param($name.'square', '', PARAM_ALPHANUM)) {
                                    case '100'    : $percent = 100; break;
                                    case  '75'    : $percent =  75; break;
                                    case  '50'    : $percent =  50; break;
                                    case  '25'    : $percent =  25; break;
                                    case 'percent': $percent = optional_param($name.'squarepercent', '', PARAM_INT); break;
                                    default       : $percent = 0;
                                }

                                // sanity check on $percent value
                                $percent = min(100, max(1, $percent));

                                // the number of users in a full square
                                $fullcount = ceil($count * (100 / $percent));

                                // set number of sides, $i_max
                                switch (true) {
                                    case ($percent > 75): $i_max = 4; break;
                                    case ($percent > 25): $i_max = 3; break;
                                    case ($percent >  0): $i_max = 1; break;
                                    default: $i_max = 0; // shouldn't happen !!
                                }

                                // calculate how many students on each side of the square
                                //     [0] : top side    (the most number of students)
                                //     [1] : left side   (a similar number to right side)
                                //     [2] : right side  (a similar number to left side)
                                //     [3] : bottom side (the remaining number of students)
                                $i = 0;
                                $counts = array();
                                for ($i=0; $i<$i_max; $i++) {
                                    if ($i==0) {
                                        $counts[$i] = min($count, floor($fullcount / $i_max));
                                    } else {
                                        $counts[$i] = ceil($count / ($i_max - $i));
                                    }
                                    $count -= $counts[$i];
                                }

                                // switch sides so students can be seated sequentially
                                //     [0] : left side   (a similar number to right side)
                                //     [1] : top side    (the most number of students)
                                //     [2] : right side  (a similar number to left side)
                                //     [3] : bottom side (the remaining number of students)
                                if ($i_max==1) {
                                    $counts[1] = 0;
                                }
                                $i = $counts[1];
                                $counts[1] = $counts[0];
                                $counts[0] = $i;

                                // adjust the coordinates for each student
                                for ($i=0; $i<$i_max; $i++) {
                                    $usercount = $counts[$i];
                                    if ($i==0) {
                                        $x = 0;
                                        $y = $usercount * $userheight;
                                        $mapwidth = $x;
                                        $mapheight = $y;
                                    }
                                    if ($i==2) {
                                        $x -= $userwidth;
                                        $y += $userheight;
                                    }
                                    for ($u=0; $u<$usercount; $u++) {
                                        if ($coord = array_shift($coords)) {
                                            $coord->x = $x;
                                            $coord->y = $y;
                                            $DB->update_record($table, $coord);
                                            $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                            $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                            switch ($i) {
                                                case 0: $y -= $userheight; break;
                                                case 1: $x += $userwidth;  break;
                                                case 2: $y += $userheight; break;
                                                case 3: $x -= $userwidth;  break;
                                            }
                                        }
                                    }
                                }
                                break;

                            case 'circle':
                                switch (optional_param($name.'circle', '', PARAM_ALPHANUM)) {
                                    case '100'    : $percent = 100; break;
                                    case  '75'    : $percent =  75; break;
                                    case  '50'    : $percent =  50; break;
                                    case  '25'    : $percent =  25; break;
                                    case 'percent': $percent = optional_param($name.'circlepercent', '', PARAM_INT); break;
                                    default       : $percent = 0;
                                }

                                // sanity check on $percent value
                                $percent = min(100, max(1, $percent));

                                // the number of users in a full circle
                                $usercount = ceil($count * (100 / $percent));

                                // calculate radius, $r, of a circle big enough to hold all users
                                // later we add $r to all calculated (x, y) coordinates
                                // Note: PHP prefers radians to degrees (360° = 2π radians)
                                $radians_per_user = deg2rad(360 / $usercount);
                                $r = sqrt(pow($userwidth, 2) + pow($userheight, 2)) / (2 * sin($radians_per_user / 2));

                                // if there is an odd number of users
                                // we want to rotate by a quarter turn (=90°)
                                $offset = (($usercount % 2) ? deg2rad(90) : 0);

                                for ($u=0; $u<$usercount; $u++) {
                                    if ($u < (($usercount - $count) / 2)) {
                                        continue;
                                    }
                                    if ($coord = array_shift($coords)) {
                                        $x = round($r * (1 + cos(($u * $radians_per_user) + $offset)));
                                        $y = round($r * (1 + sin(($u * $radians_per_user) + $offset)));
                                        $coord->x = $x;
                                        $coord->y = $y;
                                        $DB->update_record($table, $coord);
                                        $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                        $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                        $update = true;
                                    }
                                }
                                break;;

                            case 'lines':
                                $type     = optional_param($name.'linestype',    0, PARAM_INT);
                                $numtype  = optional_param($name.'linesnumtype', 0, PARAM_INT);
                                $numvalue = optional_param($name.'linesnumvalue', 0, PARAM_INT);

                                if ($numvalue==0) {
                                    switch ($type) {
                                        case 0: $numvalue = $custom->mapwidth / $custom->userwidth; break; // horizontal
                                        case 1: $numvalue = $custom->mapheight / $custom->userheight; break; // vertical
                                    }
                                }

                                // $line_max : number of lines
                                // $user_max : number of cols
                                switch ($numtype) {
                                    case 0: // number of lines
                                            $user_max = ceil($count / $numvalue);
                                            $line_max = $numvalue;
                                            break;
                                    case 1: // users per line
                                            $user_max = $numvalue;
                                            $line_max = ceil($count / $numvalue);
                                            break;
                                }

                                $update = true;

                                $padding = 24;
                                $mapwidth = 0;
                                $mapheight = 0;
                                $userwidth = $map->userwidth;
                                $userheight = $map->userheight;

                                for ($line=0; $line<$line_max; $line++) {
                                    switch ($type) {
                                        case 0: $x = 0;
                                                $y = ($userheight + $padding) * ($line_max - $line - 1);
                                                break;
                                        case 1: $x = ($userwidth + $padding) * $line;
                                                $y = $userheight * ($user_max - 1);
                                                break;
                                    }
                                    for ($user=0; $user<$user_max; $user++) {
                                        if ($coord = array_shift($coords)) {
                                            $coord->x = $x;
                                            $coord->y = $y;
                                            $DB->update_record($table, $coord);
                                            $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                            $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                            switch ($type) {
                                                case 0: $x += $userwidth;  break;
                                                case 1: $y -= $userheight; break;
                                            }
                                        }
                                    }
                                }
                                break;

                            case 'islands':
                                $type     = optional_param($name.'islandstype',     0, PARAM_INT);
                                $numtype  = optional_param($name.'islandsnumtype',  0, PARAM_INT);
                                $numvalue = optional_param($name.'islandsnumvalue', 0, PARAM_INT);

                                // $island_max : number of islands
                                // $user_max   : number of users per island
                                switch ($numtype) {
                                    case 0: // number of islands
                                            $user_max = ceil($count / $numvalue);
                                            $island_max = $numvalue;
                                            break;
                                    case 1: // users per island
                                            $user_max = $numvalue;
                                            $island_max = ceil($count / $numvalue);
                                            break;
                                }

                                $update = true;

                                $padding = 24;
                                $mapwidth = 0;
                                $mapheight = 0;
                                $userwidth = $map->userwidth;
                                $userheight = $map->userheight;

                                if ($type==0) {
                                    // calculate radius, $r, of a circle big enough to hold all users
                                    $radians_per_user = deg2rad(360 / $user_max);
                                    $r = sqrt(pow($userwidth, 2) + pow($userheight, 2)) / (2 * sin($radians_per_user / 2));
                                    $offset = 0;
                                    $offset += deg2rad(270) + ($radians_per_user / 2);
                                    //$offset -= ($radians_per_user * ($count % $user_max) / 2);
                                }

                                $p = array();
                                for ($u=0; $u<$user_max; $u++) {
                                    switch ($type) {
                                        case 0: // circle
                                            $x = round($r * (1 + cos(($u * $radians_per_user) + $offset)));
                                            $y = round($r * (1 + sin(($u * $radians_per_user) + $offset)));
                                            break;
                                        case 1: // square
                                            $x = (($u % 2)==0 ? 0 : $userwidth);
                                            $y = (intval($u / 2) * $userheight);
                                            break;
                                        default:
                                            continue; // shouldn't happen !!
                                    }
                                    $p[] = (object)array('x' => $x, 'y' => $y);
                                }

                                // compact the coordinates
                                while ($this->compact_coords('x', $userwidth, $p) ||
                                       $this->compact_coords('y', $userheight, $p));

                                // set island width/height
                                $islandwidth = 0;
                                $islandheight =  0;
                                $islandpadding = 24;
                                for ($u=0; $u<$user_max; $u++) {
                                    $islandwidth = max($islandwidth, $p[$u]->x + $userwidth);
                                    $islandheight = max($islandheight, $p[$u]->y + $userheight);
                                }

                                $x_start = 0;
                                $y_start = 0;
                                for ($i=0; $i<$island_max; $i++) {
                                    if ($x_start > ($map->mapwidth - $islandwidth)) {
                                        $x_start = 0;
                                        $y_start += ($islandheight + $islandpadding);
                                    }
                                    if ($type==0 && ($i+1)==$island_max) {
                                        $segment = (($count % $user_max) / 2);
                                    } else {
                                        $segment = 0;
                                    }
                                    for ($u=0; $u<$user_max; $u++) {
                                        if ($segment && (($u+1) > $segment) && (($u+1) <= ($user_max - $segment))) {
                                            continue;
                                        }
                                        $x = $x_start + $p[$u]->x;
                                        $y = $y_start + $p[$u]->y;
                                        if ($coord = array_shift($coords)) {
                                            $coord->x = $x;
                                            $coord->y = $y;
                                            $DB->update_record($table, $coord);
                                            $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                            $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                            $update = true;
                                        }
                                    }
                                    $x_start += ($islandwidth + $islandpadding);
                                }
                                break;
                        }
                    }

                    if ($update) {
                        // update mapwidth/height
                        $map->mapwidth = $mapwidth;
                        $map->mapheight = $mapheight;
                        $DB->update_record($plugin.'_maps', $map);

                        // prevent calculated values being
                        // overwritten by values from browser
                        unset($_POST['awardtox']);
                        unset($_POST['awardtoy']);
                        unset($_POST['mapwidth']);
                        unset($_POST['mapheight']);
                    }

                    break;

                case 'save' :
                    $table = $plugin.'_maps';
                    if ($name = optional_param($name.'savename', '', PARAM_TEXT)) {
                        $i = 1;
                        while ($DB->record_exists($table, array('assignid' => $instance->id, 'userid' => $USER->id, 'name' => $name))) {
                            $i++;
                            if ($i==2) {
                                $name = "$name ($i)";
                            } else {
                                $name = preg_replace('/\([0-9]+\)$/', "($i)", $name);
                            }
                        }
                        unset($map->id);
                        $map->name = $name;
                        $map->id = $DB->insert_record($table, $map);
                        $mapid = $map->id;
                        $update = true;
                    }
                    break;

                case 'delete':
                    $table = $plugin.'_maps';
                    if ($id = optional_param($name.'deleteid', 0, PARAM_INT)) {
                        $params = array('id' => $id, 'assignid' => $instance->id, 'userid' => $USER->id);
                        if ($DB->record_exists($table, $params)) {
                            $DB->delete_records($table, $params);
                            $DB->delete_records($plugin.'_coords', array('mapid' => $id));
                            if ($id==$mapid) {
                                $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
                                $mapid = $map->id;
                            }
                        }
                    }
                    break;
            }

            // remove all layout settings because
            // we do not want them in the outgoing form
            $names = preg_grep('/^layout/', array_keys($_POST));
            foreach ($names as $name) {
                unset($_POST[$name]);
            }

            $name = 'awardto';
            if ($multipleusers) {
                $userids = optional_param_array($name, array(), PARAM_INT);
            } else if ($userid = optional_param($name, 0, PARAM_INT)) {
                $userids = array($userid => 1);
            } else {
                $userids = array();
            }

            $x = optional_param_array('awardtox', array(), PARAM_INT);
            $y = optional_param_array('awardtoy', array(), PARAM_INT);

            // store current coordinates for each user
            $table = 'assignfeedback_points_coords';
            foreach (array_keys($x) as $userid) {
                if (isset($y[$userid])) {
                    $params = array('mapid' => $map->id, 'userid' => $userid);
                    if ($coords = $DB->get_records($table, $params)) {
                        $coords = reset($coords);
                    } else {
                        $coords = (object)$params;
                    }
                    $coords->x = $x[$userid];
                    $coords->y = $y[$userid];
                    if (isset($coords->id)) {
                        $coords->id = $DB->update_record($table, $coords);
                    } else {
                        $coords->id = $DB->insert_record($table, $coords);
                    }
                }
            }

            // initialize "feedback" details
            $feedback = (object)array('points'     => $points,
                                      'usercount'  => 0,
                                      'userlist'   => '',
                                      'stringname' => '');

            // setup undo, if required
            if ($undo==0) {

                // set undo comment text
                $undo_commenttext = get_string('undo', $plugin);
                if ($commenttext) {
                    $undo_commenttext .= ": $commenttext";
                }

                // initialize parameters for "undo" link
                $undoparams = array('undo'          => 1,
                                    'id'            => $cm->id,
                                    'plugin'        => 'points',
                                    'pluginsubtype' => 'assignfeedback',
                                    'action'        => 'viewpluginpage',
                                    'pluginaction'  => 'awardpoints',
                                    'sesskey'       => sesskey(),
                                    'group'         => $groupid,
                                    'groupid'       => $groupid,
                                    'mapid'         => $mapid,
                                    'points'        => -$points,
                                    'multipleusers' => $multipleusers,
                                    'commenttext'   => $undo_commenttext);
            }

            // do we want to send notifications to students ?
            $name = 'sendnotifications';
            $sendnotifications = $this->get_config($name);

            if ($sendnotifications===false) {
                $name = 'sendstudentnotifications';
                if (isset($instance->$name)) {
                    $sendnotifications = $instance->$name;
                } else {
                    $sendnotifications = get_config('assign', $name);
                }
            }

            // disable notifications during development
            $sendnotifications = 0;

            $name = 'pointstype';
            $pointstype = $this->get_config($name);

            // add points for each user
            foreach ($userids as $userid => $checked) {

                if ($checked==0) {
                    continue; // shouldn't happen !!
                }

                // get associated assign_grades record id
                $params = array('assignment' => $instance->id,
                                'userid'     => $userid);
                if ($assigngrade = $DB->get_records('assign_grades', $params, 'attemptnumber DESC')) {
                    $assigngrade = reset($assigngrade); // most recent assignment grade
                } else {
                    $assigngrade = (object)array(
                        'assignment'    => $instance->id,
                        'userid'        => $userid,
                        'timecreated'   => $time,
                        'timemodified'  => $time,
                        'grader'        => $USER->id,
                        'grade'         => 0.00,
                        'attemptnumber' => 0
                    );
                    $assigngrade->id = $DB->insert_record('assign_grades', $assigngrade);
                }

                // add new assignfeedback_points record
                $assignfeedbackpoints = (object)array(
                    'assignid'      => $instance->id,
                    'gradeid'       => $assigngrade->id,
                    'awardby'       => $USER->id,
                    'awardto'       => $userid,
                    'points'        => $points,
                    'latitude'      => 0,
                    'longitude'     => 0,
                    'commenttext'   => $commenttext,
                    'commentformat' => $commentformat,
                    'timecreated'   => $time,
                    'timeawarded'   => $time,
                    'timemodified'  => $time
                );
                $assignfeedbackpoints->id = $DB->insert_record('assignfeedback_points', $assignfeedbackpoints);

                // append this userid to the "undo" parameters
                if ($undo==0) {
                    if ($multipleusers) {
                        $undoparams['awardto['.$userid.']'] = 1;
                    } else {
                        $undoparams['awardto'] = $userid;
                    }
                }

                // append "feedback" details
                $feedback->usercount++;
                $feedback->userlist .= ($feedback->userlist=='' ? '' : ', ').fullname($userlist[$userid]);

                if ($pointstype==0) { // incremental points
                    $params = array('assignid' => $instance->id, 'awardto' => $userid);
                    $grade = $DB->get_field('assignfeedback_points', 'SUM(points)', $params);
                    if (empty($grade)) {
                        $grade = 0.0;
                    }
                } else {
                    $grade = $points;
                }

                $gradedata = $this->get_grade_data($assigngrade, $grade, $sendnotifications);
                $this->assignment->save_grade($userid, $gradedata);
            }

            if ($feedback->userlist) {
                switch (true) {
                    case ($feedback->points==1 && $feedback->usercount==1): $stringname = 'awardonepointoneuser'; break;
                    case ($feedback->points==1 && $feedback->usercount<>1): $stringname = 'awardonepointmanyusers'; break;
                    case ($feedback->points<>1 && $feedback->usercount==1): $stringname = 'awardmanypointsoneuser'; break;
                    case ($feedback->points<>1 && $feedback->usercount<>1): $stringname = 'awardmanypointsmanyusers'; break;
                    default: $stringname = 'awardnopoints'; // shouldn't happen !!
                }
                $feedback = get_string($stringname, $plugin, $feedback);
                if ($undo==0 && $points) {
                    $link = new moodle_url('/mod/assign/view.php', $undoparams);
                    $link = html_writer::link($link, get_string('undo', $plugin), array('id' => 'undolink'));
                    $feedback .= " $link";
                }
            } else {
                $feedback = '';
            }
            $feedback = html_writer::tag('span', $feedback, array('id' => 'feedback'));

            // get latest groupid (it may have changed)
            $groupid = groups_get_activity_group($cm, true);
            if ($groupid===false) {
                $groupid = 0;
            }

            if ($groupid != $map->groupid) {
                $userlist = $this->assignment->list_participants($groupid, false);
                $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
                // it is necessary to adjust $_POST so that old map
                // coordinates are not used for new user maps in
                // _process_submission() in "lib/formslib.php"
                unset($_POST['awardtox']);
                unset($_POST['awardtoy']);
                unset($_POST['groupid']);
                unset($_POST['mapid']);
                unset($_POST['mapwidth']);
                unset($_POST['mapheight']);
                unset($_POST['userwidth']);
                unset($_POST['userheight']);
                unset($_POST['mapprivacy']);
            }
        } else {
            $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
            $feedback = '';
        }

        return array($multipleusers, $groupid, $map, $feedback, $userlist);
    }

    /**
     * get_grade_data
     *
     * @param  object $assigngrade
     * @return object
     */
    protected function get_grade_data($assigngrade, $grade, $sendnotifications) {

        $gradedata = (object)array(
            'id'              => $assigngrade->id,
            'grade'           => $grade,
            'applytoall'      => 0,
            'attemptnumber'   => $assigngrade->attemptnumber,
            'sendstudentnotifications' => $sendnotifications,
        );

        // the "assignment->save_grade()" method
        // will call the "save()" method of each feedback plugin,
        // so we must ensure that the $gradedata object includes
        // the expected properties for each activated feedback plugin
        $plugins = $this->assignment->get_feedback_plugins();
        foreach ($plugins as $plugin) {
            if (! $plugin->is_enabled()) {
                continue;
            }
            if (! $plugin->is_visible()) {
                continue;
            }
            switch ($plugin->get_type()) {

                case 'comments':
                    $gradedata->assignfeedbackcomments_editor = array(
                        'text' => '',
                        'format' => FORMAT_HTML
                    );
                    break;

                case 'editpdf':
                    $gradedata->editpdf_source_userid = 0;
                    break;

                case 'file':
                    $gradedata->files_0_filemanager = null;
                    break;
            }
        }

        return $gradedata;
    }

    /**
     * compact_coords
     *
     * @param string  $direction "x" or "y"
     * @param integer $size of tile in this $direction
     * @param array   $coords
     * @return void
     */
    public function compact_coords($direction, $size, $coords) {
        usort($coords, array($this, 'usort_coords_'.$direction));
        $gap = 0;
        $previous = 0;
        foreach ($coords as $c => $coord) {
            if ($coord->$direction > $previous) {
                $gap += ($coord->$direction - $previous);
            }
            $previous = ($coord->$direction + $size);
            if ($gap) {
                $coords[$c]->$direction -= $gap;
            }
        }
        return ($gap > 0);
    }

    /**
     * usort_coords_x
     *
     * @param object  $a
     * @param object  $b
     * @return integer -1 : $a < $b, 0 : $a==$b, 1 : $a > $b
     */
    public function usort_coords_x($a, $b) {
        if ($a->x < $b->x) {
            return -1;
        }
        if ($a->x > $b->x) {
            return 1;
        }
        if ($a->y < $b->y) {
            return -1;
        }
        if ($a->y > $b->y) {
            return 1;
        }
        return 0; // shouldn't happen !!
    }

    /**
     * usort_coords_y
     *
     * @param object  $a
     * @param object  $b
     * @return integer -1 : $a < $b, 0 : $a==$b, 1 : $a > $b
     */
    public function usort_coords_y($a, $b) {
        if ($a->y < $b->y) {
            return -1;
        }
        if ($a->y > $b->y) {
            return 1;
        }
        if ($a->x < $b->x) {
            return -1;
        }
        if ($a->x > $b->x) {
            return 1;
        }
        return 0; // shouldn't happen !!
    }

    /**
     * This plugin does not save through the normal interface so this returns false.
     *
     * @param stdClass $grade The grade.
     * @param stdClass $data  Form data from the feedback form.
     * @return boolean - False
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        return false;
    }

    /**
     * If this plugin adds to the gradebook comments field,
     * it must specify the format of the text of the comment
     *
     * Sadly, only one feedback plugin can push comments to the gradebook
     * and that is a site-wide setting on the assignment settings page.
     *
     * @param stdClass $grade record from assign_grades table
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        return FORMAT_MOODLE;
    }

    /**
     * If this plugin adds to the gradebook comments field,
     * it must format the text of the comment
     *
     * Only one feedback plugin can push comments to the gradebook
     * and that is chosen via the settings page for the assignment module.
     * Site admin -> Plugins ->︎ Activity modules ->︎ Assignment ->︎ Assignment settings
     * Feedback plugin (assign | feedback_plugin_for_gradebook)
     *
     * @param stdClass $grade record from assign_grades table
     * @return string
     */
    public function text_for_gradebook(stdClass $grade) {
        global $DB;
        $text = array();
        if ($this->assignment->has_instance()) {
            $params = array('assignid' => $this->assignment->get_instance()->id, 'awardto'  => $grade->userid);
            if ($awards = $DB->get_records('assignfeedback_points', $params, 'timeawarded ASC')) {
                $maxcommentlength = 16;
                $dateformat = get_string('strftimerecent', 'langconfig');
                foreach ($awards as $award) {

                    // format each component
                    $award->timeawarded = userdate($award->timeawarded, $dateformat);
                    $award->points      = number_format($award->points);
                    $award->title       = format_text($award->commenttext, $award->commentformat);
                    $award->title       = strip_tags($award->title); // neutralize title text

                    // truncate long comments, if necessary
                    // (the full comment is used as the title)
                    if ($this->textlib('strlen', $award->title) <= $maxcommentlength) {
                        $award->comment = $award->title;
                    } else {
                        $award->comment = $this->textlib('substr', $award->title, 0, $maxcommentlength).' ...';
                    }

                    // wrap each component in a span with an appropriate CSS class
                    $award->timeawarded = html_writer::tag('span', $award->timeawarded, array('class' => 'timeawarded'));
                    $award->points      = html_writer::tag('span', $award->points,      array('class' => 'points'));
                    $award->comment     = html_writer::tag('span', $award->comment,     array('class' => 'comment'));

                    $feedback = get_string('textforgradebook', 'assignfeedback_points', $award);
                    $text[] = html_writer::tag('li', $feedback, array('class' => 'feedback', 'title' => $award->title));
                }
            }
        }
        if (empty($text)) {
            return '';
        }
        return html_writer::tag('ol', implode($text), array('class' => 'assignfeedback_points'));
    }

    /**
     * The assignment has been deleted - remove the plugin specific data
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        if ($this->assignment->has_instance()) {
            $assign = $this->assignment->get_instance();
            $params = array('assignid' => $assign->id);
            if ($mapids = $DB->get_records('assignfeedback_points_maps', $params, 'id', 'id,assignid')) {
                $mapids = array_keys($mapids);
                $DB->delete_records_list('assignfeedback_points_coords', 'mapid', $mapids);
                $DB->delete_records_list('assignfeedback_points_maps',   'id',    $mapids);
            }
            $DB->delete_records('assignfeedback_points', $params);
        }
        return true;
    }

    /**
     * get_usermap
     *
     * @param object  $cm
     * @param integer $userid
     * @param integer $groupid
     * @param integer $instanceid id of assign(ment) record
     * @param boolean $update (optional, default=FALSE)
     * @return object $map record from "assignfeedback_points_maps" table
     */
    protected function get_usermap($cm, $userid, $groupid, $instanceid, $update=false) {
        global $DB;

        $table = 'assignfeedback_points_maps';
        if ($mapid = optional_param('mapid', 0, PARAM_INT)) {
            $params = array('id' => $mapid,
                            'userid' => $userid,
                            'groupid' => $groupid,
                            'assignid' => $instanceid);
            $map = $DB->get_record($table, $params);
        } else {
            $map = false;
        }

        if ($map==false) {
            $params = array('userid' => $userid,
                            'groupid' => $groupid,
                            'assignid' => $instanceid);
            if ($map = $DB->get_records($table, $params, 'context DESC, privacy ASC', '*')) {
                $map = reset($map); // i.e. the most private user-map
            }
        }
        if ($map==false) {
            $mapname = get_string('default');
            if ($groupid) {
                $mapname .= ': '.groups_get_group_name($groupid);
            }
            $map = (object)array(
                'name'       => $mapname,
                'userid'     => $userid,
                'groupid'    => $groupid,
                'assignid'   => $instanceid,
                'context'    => CONTEXT_MODULE,
                'mapwidth'   => 0,
                'mapheight'  => 0,
                'userwidth'  => 0,
                'userheight' => 0,
                'privacy'    => 0
            );
        }

        // update map details
        if ($update) {
            $map->name       = optional_param('mapname',    $map->name,      PARAM_TEXT);
            $map->context    = optional_param('mapcontext', $map->context,    PARAM_INT);
            $map->mapwidth   = optional_param('mapwidth',   $map->mapwidth,   PARAM_INT);
            $map->mapheight  = optional_param('mapheight',  $map->mapheight,  PARAM_INT);
            $map->userwidth  = optional_param('userwidth',  $map->userwidth,  PARAM_INT);
            $map->userheight = optional_param('userheight', $map->userheight, PARAM_INT);
            $map->privacy    = optional_param('mapprivacy', $map->privacy,    PARAM_INT);
        }

        if (isset($map->id)) {
            $DB->update_record($table, $map);
        } else {
            $map->id = $DB->insert_record($table, $map);
        }

        return $map;
    }


    /**
     * get_all_user_name_fields
     *
     * return an array of user field names
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_all_user_name_fields() {
       if (function_exists('get_all_user_name_fields')) {
           // Moodle >= 2.6
           return get_all_user_name_fields();
       } else {
           // Moodle <= 2.5
           return array('firstname' => 'firstname', 'lastname' => 'lastname');
       }
    }

    /**
     * format_user_name_fields
     *
     * return an array of formatted user fields
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function format_user_name_fields() {
        $fields = array('' => '', 'default' => 1);
        $fields += self::get_all_user_name_fields();
        foreach (array_keys($fields) as $field) {
            if ($field) {
                $fields[$field] = get_string($field);
            }
        }
        return $fields;
    }

    /**
     * textlib
     *
     * a wrapper method to offer consistent API for textlib class
     * in Moodle 2.0 - 2.1, $textlib is first initiated, then called
     * in Moodle 2.2 - 2.5, we use only static methods of the "textlib" class
     * in Moodle >= 2.6, we use only static methods of the "core_text" class
     *
     * @param string $method
     * @param mixed any extra params that are required by the textlib $method
     * @return result from the textlib $method
     * @todo Finish documenting this function
     */
    public static function textlib() {
        if (class_exists('core_text')) {
            // Moodle >= 2.6
            $textlib = 'core_text';
        } else if (method_exists('textlib', 'textlib')) {
            // Moodle 2.0 - 2.1
            $textlib = textlib_get_instance();
        } else {
            // Moodle 2.2 - 2.5
            $textlib = 'textlib';
        }
        $args = func_get_args();
        $method = array_shift($args);
        $callback = array($textlib, $method);
        return call_user_func_array($callback, $args);
    }
}
