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
 * This file contains the class for backup of this feedback plugin
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup points feedback.
 *
 * This just records the text and format.
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_assignfeedback_points_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to feedback element.
     *
     * called by method: "define_structure()"
     * of class: "backup_assign_activity_structure_step"
     * in file: "mod/assign/backup/moodle2/backup_assign_stepslib.php"
     *
     * @return backup_subplugin_element
     */
    protected function define_grade_subplugin_structure() {

        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration
        ////////////////////////////////////////////////////////////////////////

        // assignfeedback_points
        $points     = new backup_nested_element('points');
        $fieldnames = array('id', 'assignid', 'gradeid'); // excluded fields
        $fieldnames = $this->get_fieldnames('assignfeedback_points', $fieldnames);
        $point      = new backup_nested_element('point', array('id'), $fieldnames);

        // assignfeedback_points_maps
        $maps       = new backup_nested_element('maps');
        $fieldnames = array('id', 'assignid', 'courseid'); // excluded fields
        $fieldnames = $this->get_fieldnames('assignfeedback_points_maps', $fieldnames);
        $map        = new backup_nested_element('map', array('id'), $fieldnames);

        // assignfeedback_points_coords
        $coords     = new backup_nested_element('coords');
        $fieldnames = array('id', 'mapid'); // excluded fields
        $fieldnames = $this->get_fieldnames('assignfeedback_points_coords', $fieldnames);
        $coord      = new backup_nested_element('coord', array('id'), $fieldnames);

        ////////////////////////////////////////////////////////////////////////
        // build the tree in the order needed for restore
        ////////////////////////////////////////////////////////////////////////

        $subplugin->add_child($wrapper);
        $wrapper->add_child($points);
        $points->add_child($point);

        if ($assign = $this->get_named_parent('assign')) {
            $assign->add_child($maps);
            $maps->add_child($map);
            $map->add_child($coords);
            $coords->add_child($coord);
        }

        ////////////////////////////////////////////////////////////////////////
        // data sources
        ////////////////////////////////////////////////////////////////////////

        // assignfeedback_points
        $params = array('gradeid' => backup::VAR_PARENTID);
        $point->set_source_table('assignfeedback_points', $params);

        // assignfeedback_points_maps
        $params = array('assignid' => backup::VAR_PARENTID);
        $map->set_source_table('assignfeedback_points_maps', $params);

        // assignfeedback_points_coords
        $params = array('mapid' => backup::VAR_PARENTID);
        $coord->set_source_table('assignfeedback_points_coords', $params);

        ////////////////////////////////////////////////////////////////////////
        // id annotations (foreign keys on non-parent tables)
        ////////////////////////////////////////////////////////////////////////

        $point->annotate_ids('user', 'awardby');
        $point->annotate_ids('user', 'awardto');
        $map->annotate_ids('groups', 'groupid');
        $map->annotate_ids('user', 'userid');
        $coord->annotate_ids('user', 'userid');

        return $subplugin;
    }

    /**
     * get_named_parent
     *
     * @param string $name the required optigroup name
     * @return an optigroup with specified name, or FALSE
     */
    protected function get_named_parent($name)   {
        $element = $this->optigroup;
        while ($element = $element->get_parent()) {
            if ($element->get_name()==$name) {
                return $element;
            }
        }
        return null; // shouldn't happen !!
    }

    /**
     * get_fieldnames
     *
     * @uses $DB
     * @param string $tablename the name of the Moodle table (without prefix)
     * @param array $excluded_fieldnames these field names will be excluded
     * @return array of field names
     */
    protected function get_fieldnames($tablename, array $excluded_fieldnames)   {
        global $DB;
        $fieldnames = array_keys($DB->get_columns($tablename));
        return array_diff($fieldnames, $excluded_fieldnames);
    }
}
