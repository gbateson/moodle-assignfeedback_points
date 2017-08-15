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

$pluginname = 'assignfeedback_points';

$name    = 'default';
$default = 0;
$label   = new lang_string($name, $pluginname);
$help    = new lang_string($name.'_help', $pluginname);
$setting = "$pluginname/$name";
$setting = new admin_setting_configcheckbox($setting, $label, $help, $default);
$settings->add($setting);

$defaults = array(
    // $name => array($type, $length, $default)
    'pointstype'         => array(PARAM_INT, 1, 0),
    'minpoints'          => array(PARAM_INT, 4, 1),
    'increment'          => array(PARAM_INT, 4, 1),
    'maxpoints'          => array(PARAM_INT, 4, 2),
    'pointsperrow'       => array(PARAM_INT, 4, 0),
    'showcomments'       => array(PARAM_INT, 1, 1),
    'nameformat'         => array(PARAM_TEXT, 12, ''),
    'newlinetoken'       => array(PARAM_TEXT, 4, ''),
    //'nametokens'         => array(PARAM_TEXT, 4, ''),
    'showpicture'        => array(PARAM_INT, 1, 0),
    'textlength'         => array(PARAM_INT, 4, 0),
    'texthead'           => array(PARAM_INT, 4, 0),
    'textjoin'           => array(PARAM_TEXT, 4, '...'),
    'texttail'           => array(PARAM_INT, 4, 0),
    'alignscoresgrades'  => array(PARAM_INT, 1, 0),
    'showresetbuttons'   => array(PARAM_INT, 1, 0),
    'showpointstoday'    => array(PARAM_INT, 1, 1),
    'showpointstotal'    => array(PARAM_INT, 1, 1),
    'showrubricscores'   => array(PARAM_INT, 1, 0),
    'showrubricremarks'  => array(PARAM_INT, 1, 0),
    'showrubrictotal'    => array(PARAM_INT, 1, 1),
    'showguidescores'    => array(PARAM_INT, 1, 0),
    'showguideremarks'   => array(PARAM_INT, 1, 0),
    'showguidetotal'     => array(PARAM_INT, 1, 1),
    'showassigngrade'    => array(PARAM_INT, 1, 0),
    'showmodulegrade'    => array(PARAM_INT, 1, 0),
    'showcoursegrade'    => array(PARAM_INT, 1, 0),
    'gradeprecision'     => array(PARAM_INT, 4, 0),
    'showfeedback'       => array(PARAM_INT, 1, 0),
    'showelement'        => array(PARAM_INT, 1, 0),
    'multipleusers'      => array(PARAM_INT, 1, 0),
    'sendimmediately'    => array(PARAM_INT, 1, 1),
    'allowselectable'    => array(PARAM_INT, 1, 1),
    'showlink'           => array(PARAM_INT, 1, 1));

foreach ($defaults as $name => $default) {

    $type = $default[0];
    $length = $default[1];
    $default = $default[2];

    if (substr($name, 0, 4)=='text') {
        $label = new lang_string(substr($name, 4), $pluginname);
        $help  = new lang_string('textsettings_help', $pluginname);
    } else {
        $label = new lang_string($name, $pluginname);
        $help  = new lang_string($name.'_help', $pluginname);
    }
    $name = "$pluginname/$name";

    switch (true) {
        case ($type==PARAM_INT && $length==1):
            $setting = new admin_setting_configcheckbox($name, $label, $help, $default, $type);
            break;
        case ($type==PARAM_INT && $length==4):
            $setting = new admin_setting_configtext($name, $label, $help, $default, $type, $length);
            break;
        case ($type==PARAM_TEXT):
            $setting = new admin_setting_configtext($name, $label, $help, $default, $type, $length);
            break;
        default:
            $setting = null; // shouldn't happen !!
    }

    if ($setting && method_exists($setting, 'set_advanced_flag_options')) {
        // Moodle >= 2.6
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    }

    if ($setting) {
        $settings->add($setting);
    }
}

unset($pluginname, $defaults, $default, $name, $length, $label, $help, $setting);