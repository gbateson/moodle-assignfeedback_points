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

$settingname = 'default';
$default = 0;
$label   = new lang_string($settingname, $pluginname);
$help    = new lang_string($settingname.'_help', $pluginname);
$setting = new admin_setting_configcheckbox("$pluginname/$settingname", $label, $help, $default);
$settings->add($setting);

// integer fields
$settingnames = array('minpoints' => -1, 'maxpoints' => 2, 'increment' => 1);
foreach ($settingnames as $settingname => $default) {
    $label   = new lang_string($settingname, $pluginname);
    $help    = new lang_string($settingname.'_help', $pluginname);
    $setting = new admin_setting_configtext("$pluginname/$settingname", $label, $help, $default, PARAM_INT, 4);
    if (method_exists($setting, 'set_advanced_flag_options')) {
        // Moodle >= 2.6
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    }
    $settings->add($setting);
}

// boolean fields
$settingnames = array('sendimmediately' =>  1, 'multipleusers'   => 0, 'showelement'  => 0,
                      'showpicture'     =>  1, 'showfullname'    => 0, 'showusername' => 0,
                      'showpointstoday' =>  1, 'showpointstotal' => 1, 'showcomments' => 1,
                      'showlink'        =>  1);
foreach ($settingnames as $settingname => $default) {
    $label   = new lang_string($settingname, $pluginname);
    $help    = new lang_string($settingname.'_help', $pluginname);
    $setting = new admin_setting_configcheckbox("$pluginname/$settingname", $label, $help, $default);
    if (method_exists($setting, 'set_advanced_flag_options')) {
        // Moodle >= 2.6
        $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
        $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    }
    $settings->add($setting);
}

unset($pluginname, $settingnames, $settingname, $label, $help, $setting, $enabled, $default);