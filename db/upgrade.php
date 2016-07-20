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
defined('MOODLE_INTERNAL') || die;

/**
 * xmldb_assignfeedback_points_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_assignfeedback_points_upgrade($oldversion) {
    global $DB;
    $result = true;

    $plugintype = 'assignfeedback';
    $pluginname = 'points';

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

    return $result;
}
