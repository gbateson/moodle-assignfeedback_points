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
 * This file contains a renderer for the assignment class
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the assign module.
 *
 * @package assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_points_renderer extends plugin_renderer_base {

    /**
     * Render a header for the page to assign incremental points
     *
     * @param assignfeedback_points_header $header
     * @return string The html response
     */
    public function render_assignfeedback_points_header($header) {
        $output = '';
        $plugin = 'assignfeedback_points';
        $output .= $this->container(get_string('userswithnewfeedback', $plugin, $header->userswithnewfeedback));
        $output .= $this->container(get_string('filesupdated', $plugin, $header->feedbackfilesupdated));
        $output .= $this->container(get_string('filesadded', $plugin, $header->feedbackfilesadded));
        $url = new moodle_url('view.php', array('id'=>$header->cmid, 'action'=>'grading'));
        $output .= $this->continue_button($url);
        return $output;
    }
}

