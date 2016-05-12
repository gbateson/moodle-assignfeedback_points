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
 * Strings for component 'assignfeedback_points', language 'en'
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Feedback points';

$string['awardmanypointsmanyusers'] = '{$a->points} points were awarded to {$a->usercount} users: {$a->userlist}';
$string['awardmanypointsoneuser'] = '{$a->points} points were awarded to {$a->userlist}';
$string['awardnopoints'] = 'No points were awarded';
$string['awardonepointmanyusers'] = 'One point was awarded to {$a->usercount} users: {$a->userlist}';
$string['awardonepointoneuser'] = 'One point was awarded to {$a->userlist}';
$string['awardpoints'] = 'Award points';
$string['awardto_help'] = 'The user(s) to whom the points will be awarded';
$string['awardto'] = 'Recipient user(s)';
$string['circle'] = 'Circle';
$string['cleanup'] = 'Clean up';
$string['commenttext_help'] = 'A brief decription of why these points are being awarded to the selected user(s)';
$string['commenttext'] = 'Comment';
$string['contactingserver'] = 'Contacting server ...';
$string['default_help'] = 'If set, this feedback method will be enabled by default for all new assignments.';
$string['default'] = 'Enabled by default';
$string['delete'] = 'Delete';
$string['enabled_help'] = 'If enabled, the marker can award points to other users.';
$string['enabled'] = 'Incremental points';
$string['horizontal'] = 'Horizontal';
$string['increment_help'] = 'the number of points in each step between the minimum number of points and the maximum number of points';
$string['increment'] = 'Points increment';
$string['incrementalpoints'] = 'Incremental points';
$string['islands'] = 'Islands';
$string['layouts_help'] = 'Use these settings to setup, save, load, and delete layouts for the user-map';
$string['layouts'] = 'Layouts';
$string['lines'] = 'Lines';
$string['load'] = 'Load';
$string['mapaction_help'] = 'Click these buttons to perform operations on the user-map.

**Reset**
: All user-tiles will be moved back to their original position.

**Clean up**
: Each tile will be moved to its nearest tidy position.

**Separate**
: User-tiles that overlap will be moved apart in the direction of the smallest overlap.

**Shuffle**
: Users will be shuffled randonly and placed in different tiles. The tiles themselves will not be moved.

**Resize**
: User-tiles will be shrunk or enlarged to match the current size of the user-map.

**Rotate**
: The entire user-map will be rotated by ¼, ½, or ¾ or a full turn.';
$string['mapaction'] = 'User-map action';
$string['maxpoints_help'] = 'the maximum number of points for any single award of points';
$string['maxpoints'] = 'Maximum points';
$string['minpoints_help'] = 'the minimum number of points for any single award of points - this can be a negative number';
$string['minpoints'] = 'Minimum points';
$string['multipleusers_help'] = 'If this setting is enabled, more than one student can be selected when points are awarded. Otherwise, only a single student can be awarded points at one time.';
$string['multipleusers'] = 'Select multiple users';
$string['newcomment'] = 'New comment ...';
$string['nousersfound'] = 'Oops, no users found.';
$string['numberofislands'] = 'Number of islands';
$string['numberoflines'] = 'Number of lines';
$string['peopleperisland'] = 'People per island';
$string['peopleperline'] = 'People per line';
$string['percent'] = 'percent';
$string['percent100'] = 'full';
$string['percent25'] = '¼';
$string['percent50'] = '½';
$string['percent75'] = '¾';
$string['points_help'] = 'The number of points to be awarded';
$string['points'] = 'Number of points';
$string['pointstoday'] = 'Today: {$a}';
$string['pointstotal'] = 'Total: {$a}';
$string['pointstype_help'] = 'the type of points you wish to award, either incremental points or a final total';
$string['pointstype'] = 'Type of points';
$string['reset'] = 'Reset';
$string['resize'] = 'Resize';
$string['rotate'] = 'Rotate';
$string['save'] = 'Save';
$string['sendimmediately_help'] = 'If this setting is enabled, points will be awarded and sent to Moodle (via AJAX) as soon as the teacher clicks or touches a student\'s name or image. Otherwise, points will be sent to Moodle when the teacher clicks the "Award points" button at the bottom of this page.';
$string['sendimmediately'] = 'Send points immediately';
$string['separate'] = 'Separate';
$string['settings'] = 'Settings for feedback points';
$string['setup'] = 'Setup';
$string['showcomments_help'] = 'If this setting is enabled, the top ten most frequently used comments will be displayed for selection from a drop down menu.';
$string['showcomments'] = 'Show frequent comments';
$string['showelement_help'] = 'If this setting is enabled, the checkbox or radio button used to select each student will be shown alongside the name and/or picture of the student.';
$string['showelement'] = 'Show form element';
$string['showfullname_help'] = 'If this setting is enabled, the student full names will be shown in the list of students to whom points are awarded.';
$string['showfullname'] = 'Show user name';
$string['showlink_help'] = 'If this setting is enabled, a link that goes directly to the page for awarding points will be added on the teacher\'s main view page for this assignment.';
$string['showlink'] = 'Show link from view page';
$string['showpicture_help'] = 'If this setting is enabled, the students\' pictures will be shown in the list of students to whom points are awarded.';
$string['showpicture'] = 'Show user picture';
$string['showpointstoday_help'] = 'If this setting is eneabled, the number points awarded today to each student will be displayed.';
$string['showpointstoday'] = 'Show points awarded today';
$string['showpointstotal_help'] = 'If this setting is eneabled, the total number of points ever awarded to each student will be displayed.';
$string['showpointstotal'] = 'Show total points awarded';
$string['showusername_help'] = 'If this setting is enabled, the students\' login usernames will be shown in the list of students to whom points are awarded.';
$string['showusername'] = 'Show username';
$string['shuffle'] = 'Shuffle';
$string['square'] = 'Square';
$string['textforgradebook'] = '{$a->timeawarded} ({$a->points} pts) {$a->comment}';
$string['totalpoints'] = 'Total points';
$string['undo'] = 'Undo';
$string['vertical'] = 'Vertical';
