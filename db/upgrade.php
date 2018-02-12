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
 * Upgrade code for the feedback_points module.
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_assignfeedback_points_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignfeedback_points_upgrade($oldversion) {
    global $CFG, $DB, $USER;
    $result = true;

    $plugintype = 'assignfeedback';
    $pluginname = 'points';
    $plugin = $plugintype.'_'.$pluginname;

    $dbman = $DB->get_manager();

    $newversion = 2015120640;
    if ($result && $oldversion < $newversion) {

        $table = 'assignfeedback_points';
        $table = new xmldb_table($table);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table->add_field('id',            XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assignid',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('gradeid',       XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('awardby',       XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('awardto',       XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('cancelby',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('points',        XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('pointstype',    XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('latitude',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('longitude',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('commenttext',   XMLDB_TYPE_TEXT,    null, null, null,          null, null);
        $table->add_field('commentformat', XMLDB_TYPE_INTEGER,  '4', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('timeawarded',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('timecancelled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('timecreated',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_field('timemodified',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null,  '0');
        $table->add_key('primary',         XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('assipoin_ass_fk', XMLDB_KEY_FOREIGN, array('assignid'), 'assign',        array('id'));
        $table->add_key('assipoin_gra_fk', XMLDB_KEY_FOREIGN, array('gradeid'),  'assign_grades', array('id'));
        $table->add_key('assipoin_aby_fk', XMLDB_KEY_FOREIGN, array('awardby'),  'user',          array('id'));
        $table->add_key('assipoin_ato_fk', XMLDB_KEY_FOREIGN, array('awardto'),  'user',          array('id'));
        $table->add_index('assipoin_asspoi_ix', XMLDB_INDEX_NOTUNIQUE, array('assignid', 'pointstype'));
        $dbman->create_table($table);

        $table = 'assignfeedback_points_maps';
        $table = new xmldb_table($table);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table->add_field('id',         XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name',       XMLDB_TYPE_CHAR,   '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('groupid',    XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('assignid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('context',    XMLDB_TYPE_INTEGER,  '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mapwidth',   XMLDB_TYPE_INTEGER,  '6', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mapheight',  XMLDB_TYPE_INTEGER,  '6', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userwidth',  XMLDB_TYPE_INTEGER,  '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userheight', XMLDB_TYPE_INTEGER,  '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('privacy',    XMLDB_TYPE_INTEGER,  '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary',             XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('assipoinmaps_use_fk', XMLDB_KEY_FOREIGN, array('userid'),   'user',   array('id'));
        $table->add_key('assipoinmaps_gro_fk', XMLDB_KEY_FOREIGN, array('groupid'),  'group',  array('id'));
        $table->add_key('assipoinmaps_ass_fk', XMLDB_KEY_FOREIGN, array('assignid'), 'assign', array('id'));
        $dbman->create_table($table);

        $table = 'assignfeedback_points_coords';
        $table = new xmldb_table($table);
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table->add_field('id',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mapid',  XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('y',      XMLDB_TYPE_INTEGER,  '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('x',      XMLDB_TYPE_INTEGER,  '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary',             XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('assipoincoor_map_fk', XMLDB_KEY_FOREIGN, array('mapid'),  'assignfeedback_points_maps', array('id'));
        $table->add_key('assipoincoor_use_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user',                       array('id'));
        $dbman->create_table($table);

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2016071751;
    if ($result && $oldversion < $newversion) {

        $table = new xmldb_table('assignfeedback_points');
        if ($dbman->table_exists($table)) {

            $fields = array(
                new xmldb_field('pointstype',    XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'points'),
                new xmldb_field('cancelby',      XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'awardto'),
                new xmldb_field('timecancelled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified'),
            );
            foreach ($fields as $field) {
                if ($dbman->field_exists($table, $field)) {
                    continue;
                }
                if (! $dbman->field_exists($table, $field->getPrevious())) {
                    $field->setPrevious(null);
                }
                $dbman->add_field($table, $field);

                // post-add processing
                switch ($field->getName()) {

                    case 'pointstype':
                        // get all unique assign(ment) ids
                        // in the "assignfeedback_points" table
                        $select = 'assignid, COUNT(*) AS countassignid';
                        if ($ids = $DB->get_records_sql("SELECT $select FROM {assignfeedback_points} GROUP BY assignid", array())) {

                            // get all assign(ment) ids using TOTAL pointstype (=1)
                            list($select, $params) = $DB->get_in_or_equal(array_keys($ids));
                            $select = "assignment $select AND plugin = ? AND subtype = ? AND name = ? AND value = ?";
                            array_push($params, 'points', 'assignfeedback', 'pointstype', '1');
                            if ($ids = $DB->get_records_select('assign_plugin_config', $select, $params, '', 'assignment AS assignid, value AS pointstype')) {

                                // set all pointstype for awards in assign(ments) using the TOTAL pointstype (=1)
                                list($select, $params) = $DB->get_in_or_equal(array_keys($ids));
                                $DB->set_field_select('assignfeedback_points', 'pointstype', 1, "assignid $select", $params);
                            }
                        }
                        break;

                    case 'timecancelled':
                        $params = array('Undo');
                        $DB->execute('UPDATE {assignfeedback_points} SET cancelby = awardby WHERE commenttext = ?', $params);
                        $DB->execute('UPDATE {assignfeedback_points} SET timecancelled = timeawarded WHERE commenttext = ?', $params);
                        break;
                }
            }

            $indexes = array(
                new xmldb_index('assipoin_asspoi_ix', XMLDB_INDEX_NOTUNIQUE, array('assignid', 'pointstype')),
                new xmldb_index('assipoin_asstim_ix', XMLDB_INDEX_NOTUNIQUE, array('assignid', 'timeawarded')),
                new xmldb_index('assipoin_asstim2_ix', XMLDB_INDEX_NOTUNIQUE, array('assignid', 'timecancelled')),
            );
            foreach ($indexes as $index) {
                if ($dbman->index_exists($table, $index)) {
                    continue;
                }
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017062082;
    if ($result && $oldversion < $newversion) {
        require_once($CFG->dirroot.'/mod/assign/feedbackplugin.php');
        require_once($CFG->dirroot.'/mod/assign/feedback/points/locallib.php');

        // get name fields used in default name format for this Moodle site
        $defaultnames = assign_feedback_points::get_all_user_fields();
        $defaultnames = fullname((object)$defaultnames);
        $defaultnames = explode(' ', $defaultnames);

        // initialize arrays for config settings
        $configids = array();
        $newconfigs = array();

        // default values for the old config fields
        $oldconfigs = array('showrealname'  => '',
                            'splitrealname' => 0,
                            'romanizenames' => 0,
                            'firstnamecase' => 0,
                            'lastnamecase'  => 0);

        // fetch values of all old config fields
        $table = 'assign_plugin_config';
        $select = 'subtype = ? AND plugin = ? AND name IN (?, ?, ?, ?, ?)';
        $params = array('assignfeedback', 'points');
        $params = array_merge($params, array_keys($oldconfigs));
        if ($configs = $DB->get_records_select($table, $select, $params, 'id')) {
            foreach ($configs as $configid => $config) {
                $name = $config->name;
                $value = $config->value;
                $assignid = $config->assignment;
                if (empty($newconfigs[$assignid])) {
                    $newconfigs[$assignid] = (object)$oldconfigs;
                }
                $configids[] = $configid;
                $newconfigs[$assignid]->$name = $value;
            }

            $defaulttoken = get_string('nametokentokendefault', $plugin);
            $defaultjoin = get_string('nametokenjoindefault', $plugin);
            $defaultcase = 0; // original case

            foreach ($newconfigs as $assignid => $config) {
                $nameformat = array();
                $namenewline = ' ';
                $namefields = array();
                if ($config->showrealname) {
                    if ($config->showrealname=='default') {
                        $fields = $defaultnames;
                    } else {
                        $fields = array($config->showrealname);
                    }
                    foreach ($fields as $field) {
                        switch (true) {
                            case is_numeric(strpos($field, 'firstname')): $case = $config->firstnamecase; break;
                            case is_numeric(strpos($field, 'lastname')) : $case = $config->lastnamecase;  break;
                            default: $case = $defaultcase;
                        }
                        $token = $defaulttoken.(count($namefields) + 1);
                        $nameformat[] = $token;
                        $namefields[] = array('token'  => $token,
                                              'field'  => $field,
                                              'length' => 0,
                                              'head'   => 0,
                                              'join'   => $defaultjoin,
                                              'tail'   => 0,
                                              'style'  => '',
                                              'case'   => $case,
                                              'romanize' => $config->romanizenames);
                    }
                }
                if ($nameformat = implode($namenewline, $nameformat)) {
                    $namefields = base64_encode(serialize($namefields));
                    xmldb_assignfeedback_points_insert($configids, $table, $assignid, 'nameformat', $nameformat);
                    xmldb_assignfeedback_points_insert($configids, $table, $assignid, 'namechar',   $namenewline);
                    xmldb_assignfeedback_points_insert($configids, $table, $assignid, 'namefields', $namefields);
                }
            }
        }
        if (count($configids)) {
            $DB->delete_records_list($table, 'id', $configids);
        }
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017062284;
    if ($result && $oldversion < $newversion) {
        $fields = array('names'       => 'namefields',
                        'newlinechar' => 'namechar');
        xmldb_assignfeedback_points_rename($fields);
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017062586;
    if ($result && $oldversion < $newversion) {

        // rename field
        $fields = array('namechar' => 'namenewline');
        xmldb_assignfeedback_points_rename($fields);

        // delete all unrecognized config settings for this plugin
        $table = 'assign_plugin_config';
        $params = array('pointstype',      'minpoints',
                        'increment',       'maxpoints',
                        'sendimmediately', 'multipleusers',
                        'showelement',     'showpicture',
                        'nameformat',      'namenewline',   'namefields',
                        'showpointstoday', 'showpointstotal',
                        'showrubricscores', 'showguidescores',
                        'showassigngrade', 'showcoursegrade',
                        'showcomments',    'showfeedback',
                        'showlink',        'allowselectable',
                        'enabled'); // used by the "assign" mod
        list($select, $params) = $DB->get_in_or_equal($params, SQL_PARAMS_QM, '', false); // NOT IN
        $select = "subtype = ? AND plugin = ? AND name $select";
        array_unshift($params, 'assignfeedback', 'points');
        $DB->delete_records_select($table, $select, $params);

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017062692;
    if ($result && $oldversion < $newversion) {
        xmldb_assignfeedback_points_names('namefields', $plugin);
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017062896;
    if ($result && $oldversion < $newversion) {
        $table = 'assign_plugin_config';

        // rename config settings: $old => $new
        $fields = array('namefields'  => 'nametokens',
                        'namenewline' => 'newlinetoken');
        xmldb_assignfeedback_points_rename($fields);

        // reduce the size of the "pointstype" field from "10" to "4"
        $table = new xmldb_table('assignfeedback_points');
        $field = new xmldb_field('pointstype', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $index = new xmldb_index('assipoin_asspoi_ix', XMLDB_INDEX_NOTUNIQUE, array('assignid', 'pointstype'));
        if ($dbman->field_exists($table, $field)) {
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }
            $dbman->change_field_type($table, $field);
            if (! $dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017070607;
    if ($result && $oldversion < $newversion) {
        $fields = array('showrubricscore' => 'showrubricscores',
                        'showguidescore'  => 'showguidescores',
                        'showgradecourse' => 'showcoursegrade',
                        'showgradeassign' => 'showassigngrade');
        xmldb_assignfeedback_points_rename($fields);
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017070608;
    if ($result && $oldversion < $newversion) {
        $fields = array('showrubricscores'   => 'showrubrictotal',
                        'showrubriccriteria' => 'showrubricscores',
                        'showguidescores'    => 'showguidetotal',
                        'showguidecriteria'  => 'showguidescores');
        xmldb_assignfeedback_points_rename($fields);
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017070813;
    if ($result && $oldversion < $newversion) {
        // add fixvowels setting to each name token
        xmldb_assignfeedback_points_names('nametokens', $plugin);
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017081628;
    if ($result && $oldversion < $newversion) {

        // ==================================================
        // fix set sitewide default settings
        // ==================================================

        set_config('nameformat',       '', $plugin);
        set_config('newlinetoken',     '', $plugin);
        set_config('showelement',     '0', $plugin);
        set_config('multipleusers',   '0', $plugin);
        set_config('sendimmediately', '1', $plugin);
        set_config('allowselectable', '1', $plugin);
        set_config('showlink',        '1', $plugin);

        // ==================================================
        // fix settings in individual Assign(ment) activities
        // ==================================================
        //
        $table = 'assign_plugin_config';

        // ensure "nameformat" and "nameformat" do not contain "0"
        $select = 'plugin = ? AND subtype = ? AND (name = ? OR name = ?) AND value = ?';
        $params = array($pluginname, $plugintype, 'nameformat', 'newlinetoken', '0');
        $DB->set_field_select($table, 'value', '', $select, $params);

        // ensure "showelement" and "multipleusers" are switched OFF
        $select = 'plugin = ? AND subtype = ? AND (name = ? OR name = ?)';
        $params = array($pluginname, $plugintype, 'showelement', 'multipleusers');
        $DB->set_field_select($table, 'value', '0', $select, $params);

        // ensure "sendimmediately", "allowselectable" and "showlink" are switched ON
        $select = 'plugin = ? AND subtype = ? AND (name = ? OR name = ? OR name = ?)';
        $params = array($pluginname, $plugintype, 'sendimmediately', 'allowselectable', 'showlink');
        $DB->set_field_select($table, 'value', '1', $select, $params);

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2017092761;
    if ($result && $oldversion < $newversion) {

        // ==================================================
        // Rename "showrubricformcriteria" config settings
        // because "showrubricformcriteria_locked" is too
        // long for the "name" field in "assign_plugin_config".
        // For consistency, we also rename the corresponding
        // fields for marking guides, "showguideformcriteria".
        // ==================================================

        $names = array('guide', 'rubric');
        $types = array('', '_adv', '_locked');

        foreach ($names as $name) {
            $oldname = 'show'.$name.'formcriteria';
            $newname = 'show'.$name.'formlabels';
            foreach ($types as $type) {
                $DB->set_field('assign_plugin_config',
                               'name', $newname.$type,
                               array('plugin'  => $pluginname,
                                     'subtype' => $plugintype,
                                     'name'    => $oldname.$type));
                $DB->set_field('config_plugins',
                               'name', $newname.$type,
                                array('plugin' => $plugin,
                                      'name'   => $oldname.$type));
            }
        }

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2018021265;
    if ($result && $oldversion < $newversion) {

        // ==================================================
        // fix values for "field" name in $config->nametokens
        // ==================================================

        $table = 'assign_plugin_config';
        $select = 'subtype = ? AND plugin = ? AND name = ?';
        $params = array('assignfeedback', 'points', 'nametokens');
        if ($configs = $DB->get_records_select($table, $select, $params)) {
            foreach ($configs as $config) {
                $tokens = unserialize(base64_decode($config->value));
                $i_max = count($tokens) - 1;
                $update = false;
                for ($i=$i_max; $i>=0; $i--) {
                    $remove = true;
                    if (is_array($tokens[$i])) {
                        if (array_key_exists('field', $tokens[$i])) {
                            $field = $tokens[$i]['field'];
                            // fix/remove superflous field text
                            // e.g. firstname(firstnamephonetic
                            // e.g. lastnamephonetic)
                            if ($pos = strpos($field, '(')) {
                                $field = substr($field, $pos + 1);
                                $update = true;
                            }
                            if (strpos($field, ')')) {
                                $field = substr($field, 0, $pos - 1);
                                $update = true;
                            }
                            if (property_exists($USER, $field)) {
                                $remove = false;
                            } else {
                                $tokens[$i]['field'] = $field;
                            }
                        }
                    }
                    if ($remove) {
                        array_splice($tokens, $i, 1);
                        $update = true;
                    }
                }
                if ($update) {
                    $config->value = base64_encode(serialize($tokens));
                    $DB->update_record($table, $config);
                }
            }
        }

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    $newversion = 2018021266;
    if ($result && $oldversion < $newversion) {

        // ==================================================
        // remove duplicate plugin config settings
        // ==================================================

        $select = 'COUNT(*) AS countrecords, '.
                  $DB->sql_concat('assignment', "'_'", 'name').' AS assignment_name';
        $from   = 'assign_plugin_config';
        $where  = 'subtype = ? AND plugin = ?';
        $group  = 'assignment, name';
        $having = 'countrecords > ?';
        $params = array('assignfeedback', 'points', 1);
        $configs = "SELECT $select FROM $from WHERE $where GROUP BY $group HAVING $having";
        if ($configs = $DB->get_records_sql($configs, $params)) {
            foreach ($configs as $config) {
                list($assignment, $name) = explode('_', $config->assignment_name);
                $params = array('subtype'    => 'assignfeedback',
                                'plugin'     => 'points',
                                'assignment' => $assignment,
                                'name'       => $name);
                $ids = $DB->get_records($from, $params, 'id DESC');
                $ids = array_keys($ids);
                array_pop($ids); // keep id of most recent record
                list($select, $where) = $DB->get_in_or_equal($ids);
                $DB->delete_records_select($from, "id $select", $params);
            }
        }

        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }


    return $result;
}

/**
 * xmldb_assignfeedback_points_names
 *
 * @param string $configname
 * @return bool
 */
function xmldb_assignfeedback_points_names($configname, $plugin) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/assign/feedbackplugin.php');
    require_once($CFG->dirroot.'/mod/assign/feedback/points/locallib.php');

    // ensure all namefields contain at least the default settings
    $table = 'assign_plugin_config';
    $params = array('subtype' => 'assignfeedback',
                    'plugin'  => 'points',
                    'name'    => $configname);
    if ($configs = $DB->get_records($table, $params)) {
        $strman = get_string_manager();
        $defaults = assign_feedback_points::get_nametoken_setting_defaults($strman, $plugin);
        foreach ($configs as $configid => $config) {
            $names = unserialize(base64_decode($config->value));
            if (isset($names) && is_array($names)) {
                $i_max = count($names);
                for ($i = ($i_max - 1); $i >= 0; $i--) {
                    if (empty($names[$i]['field'])) {
                        array_splice($names, $i, 1);
                    } else {
                        $names[$i] = array_merge($defaults, $names[$i]);
                    }
                }
            } else {
                $names = array(); // shouldn't happen !!
            }
            $config->value = base64_encode(serialize($names));
            $DB->update_record($table, $config);
        }
    }
}

/**
 * xmldb_assignfeedback_points_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignfeedback_points_rename($fields) {
    global $DB;
    $table = 'assign_plugin_config';
    foreach ($fields as $oldname => $newname) {
        $params = array('subtype' => 'assignfeedback',
                        'plugin'  => 'points',
                        'name'    => $oldname);
        $DB->set_field($table, 'name', $newname, $params);
    }
}

/**
 * xmldb_assignfeedback_points_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignfeedback_points_insert(&$configids, $table, $assignid, $name, $value) {
    global $DB;
    if ($configid = array_pop($configids)) {
        $DB->set_field($table, 'name',  $name,  array('id' => $configid));
        $DB->set_field($table, 'value', $value, array('id' => $configid));
    } else {
        $config = (object)array('assignment' => $assignid,
                                'plugin'     => 'points',
                                'subtype'    => 'assignfeedback',
                                'name'       => $name,
                                'value'      => $value);
        $config->id = $DB->insert_record($table, $config);
    }
}