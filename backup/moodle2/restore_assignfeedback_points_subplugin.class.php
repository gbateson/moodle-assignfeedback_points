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
 * Restore subplugin class.
 *
 * Provides the necessary information needed to restore
 * one assign_submission subplugin.
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore subplugin class.
 *
 * Provides the necessary information needed to restore
 * one assignfeedback subplugin.
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_assignfeedback_points_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin at assign level
     * @return array
     */
    protected function define_grade_subplugin_structure() {
        $paths = array();

        $name = $this->get_namefor('grade');
        $path = $this->get_pathfor('/feedback_points');
        $paths[] = new restore_path_element($name, $path);

        $name = 'assign_map';
        $path = '/activity/assign/maps/map';
        $paths[] = new restore_path_element($name, $path);

        $name = 'assign_map_coord';
        $path = '/activity/assign/maps/map/coords/coord';
        $paths[] = new restore_path_element($name, $path);

        return $paths;
    }

    /**
     * Processes one feedback_points element.
     * @param mixed $data
     */
    public function process_assignfeedback_points_grade($data) {
        global $DB;
        $data = (object)$data;
        $data->assignid = $this->get_new_parentid('assign');
        $data->gradeid = $this->get_mappingid('grade', $data->gradeid);
        $data->awardby = $this->get_mappingid('user', $data->awardby);
        $data->awardto = $this->get_mappingid('user', $data->awardto);
        $DB->insert_record('assignfeedback_points', $data);
    }

    /**
     * Processes one assign_map element.
     * @param mixed $data
     */
    public function process_assign_map($data) {
        global $DB;
        $data = (object)$data;
        $data->assignid = $this->get_new_parentid('assign');
        $data->userid = (empty($data->userid) ? 0 : $this->get_mappingid('user', $data->userid));
        $data->groupid = (empty($data->groupid) ? 0 : $this->get_mappingid('group', $data->groupid));
        $this->set_mapping('assign_map', $data->id, $DB->insert_record('assignfeedback_points_maps', $data));
    }

    /**
     * Processes one assign_map_coord element.
     * @param mixed $data
     */
    public function process_assign_map_coord($data) {
        global $DB;
        $data = (object)$data;
        $data->mapid = $this->get_new_parentid('assign_map');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('assignfeedback_points_coords', $data);
    }
}
