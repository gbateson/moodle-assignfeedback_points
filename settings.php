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
 * This file defines the admin settings for this plugin
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/assign/assignmentplugin.php');
require_once($CFG->dirroot.'/mod/assign/feedbackplugin.php');
require_once($CFG->dirroot.'/mod/assign/feedback/points/locallib.php');

$pluginname = 'assignfeedback_points';
$pluginclass = 'assign_feedback_points';

$name    = 'default';
$default = 0; // i.e. disabled
$label   = new lang_string($name, $pluginname);
$help    = new lang_string($name.'_help', $pluginname);
$setting = new admin_setting_configcheckbox("$pluginname/$name", $label, $help, $default);
$settings->add($setting);

$method = 'get_text_options';
$textsize = call_user_func(array($pluginclass, $method));
$textsize = $textsize['size'];

$method = 'get_defaultvalues';
$defaults = call_user_func(array($pluginclass, $method), $pluginname);

$checkboxes = array(
    'showpicture',      'showresetbuttons',
    'showpointstoday',  'showpointstotal',   'showcomments',
    'showrubricscores', 'showrubricremarks', 'showrubrictotal',
    'showguidescores',  'showguideremarks',  'showguidetotal',
    'showassigngrade',  'showmodulegrade',   'showcoursegrade',
    'showfeedback',     'showelement',       'multipleusers',
    'sendimmediately',  'allowselectable',   'showlink',
);

foreach ($defaults as $name => $default) {

    if ($name=='nametokens') {
        continue;
    }

    if (substr($name, 0, 4)=='text') {
        $label = new lang_string(substr($name, 4), $pluginname);
        $help  = new lang_string('textsettings_help', $pluginname);
    } else {
        $label = new lang_string($name, $pluginname);
        $help  = new lang_string($name.'_help', $pluginname);
    }

    if (substr($name, 0, 4)=='show' && substr($name, -5)=='grade') {
        $method = 'get_showgrade_options';
    } else {
        $method = 'get_'.$name.'_options';
    }

    $setting = "$pluginname/$name";
    switch (true) {
        case (in_array($name, $checkboxes)):
            $setting = new admin_setting_configcheckbox($setting, $label, $help, $default);
            break;
        case method_exists($pluginclass, $method):
            $options = call_user_func(array($pluginclass, $method), $pluginname);
            $setting = new admin_setting_configselect($setting, $label, $help, $default, $options);
            break;
        case (is_integer($default)):
            $setting = new admin_setting_configtext($setting, $label, $help, $default, PARAM_INT, $textsize);
            break;
        case (is_string($default)):
            $setting = new admin_setting_configtext($setting, $label, $help, $default, PARAM_TEXT, $textsize);
            break;
        default:
            $setting = null; // shouldn't happen !!
    }

    if ($setting) {
        if (method_exists($setting, 'set_advanced_flag_options')) {
            // Moodle >= 2.6
            $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
            $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
        }
        $settings->add($setting);
    }
}

unset($pluginname, $checkboxes, $textsize, $defaults, $default, $setting, $name, $label, $help, $size, $options);