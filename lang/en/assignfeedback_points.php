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

$string['absent'] = 'Absent';
$string['addmorenames'] = 'Add more names';
$string['allowselectable_help'] = 'If this setting is enabled, jQuery\'s selectable functionality can be used to selected multiple students at once.';
$string['allowselectable'] = 'Allow selectable functionality';
$string['automatically'] = 'Automatically';
$string['averagepoints'] = 'Awards: {$a->count} (Avg: {$a->average} pts)';
$string['award'] = 'Award';
$string['awardby'] = 'Awarded by';
$string['awardmanypointsmanyusers'] = '{$a->points} points were awarded to {$a->usercount} users: {$a->userlist}';
$string['awardmanypointsoneuser'] = '{$a->points} points were awarded to {$a->userlist}';
$string['awardnopoints'] = 'No points were awarded';
$string['awardonepointmanyusers'] = 'One point was awarded to {$a->usercount} users: {$a->userlist}';
$string['awardonepointoneuser'] = 'One point was awarded to {$a->userlist}';
$string['awardpoints'] = 'Award points';
$string['awardto_help'] = 'The user(s) to whom the points will be awarded';
$string['awardto'] = 'Recipient user(s)';
$string['cancelby'] = 'Cancelled by';
$string['circle'] = 'Circle';
$string['cleanup'] = 'Clean up';
$string['commenttext_help'] = 'A brief decription of why these points are being awarded to the selected user(s)';
$string['commenttext'] = 'Comment';
$string['contactingserver'] = 'Contacting server ...';
$string['default_help'] = 'If set, this feedback method will be enabled by default for all new assignments.';
$string['default'] = 'Enabled by default';
$string['delete'] = 'Delete';
$string['enabled_help'] = 'If enabled, the marker can award points to other users.';
$string['enabled'] = 'Enabled';
$string['feedback_help'] = 'Messages will be displayed here regarding the transfer, via AJAX to the Moodle server, of data about point awards.

You can control whether this item is visible or hidden using the the "Show AJAX feedback" item in the "Settings" section at the bottom of this page.';
$string['feedback'] = 'Feedback';
$string['firstnamecase_help'] = 'This setting allows you to specify the case used when displaying a student\'s first name.

**Original case**
: The first name will not be modified and will appear exactly as it does in the user profile.

**Lower case**
: The first name will displayed in lower case, e.g. *john* or *jane*

**Proper case**
: The first name will be displayed in proper case, in which the first letter of each word is upper case, while other letters are lower case, e.g. *John* or *Jane*

**Upper case**
: The first name will displayed in upper case. e.g. *JOHN* or *JANE*';
$string['firstnamecase'] = 'Case of first name';
$string['fix'] = 'Fix';
$string['gradeassign'] = 'Assignment: {$a}';
$string['gradecourse'] = 'Gradebook: {$a}';
$string['horizontal'] = 'Horizontal';
$string['increment_help'] = 'the number of points in each step between the minimum number of points and the maximum number of points';
$string['increment'] = 'Points increment';
$string['incremental'] = 'Incremental';
$string['islands'] = 'Islands';
$string['lastnamecase_help'] = 'This setting allows you to specify the case used when displaying a student\'s last name.

**Original case**
: The last name will not be modified and will appear exactly as it does in the user profile.

**Proper case**
: The last name will be displayed in proper case, in which the first letter of each word is upper case, while other letters are lower case, e.g. *Smith*

**Lower case**
: The last name will displayed in lower case, e.g. *smith*

**Upper case**
: The last name will be displayed in upper case, e.g. *SMITH*';
$string['lastnamecase'] = 'Case of last name';
$string['layouts_help'] = 'Use these settings to setup, save, load, and delete layouts for the user-map';
$string['layouts'] = 'Layouts';
$string['lines'] = 'Lines';
$string['load'] = 'Load';
$string['lowercase'] = 'lower case';
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
: The entire user-map will be rotated by ¼, ½, ¾ or a full turn.';
$string['mapaction'] = 'User-map action';
$string['maxpoints_help'] = 'the maximum number of points for any single award of points';
$string['mapmode'] = 'User-map mode';
$string['mapmode_help'] = 'the action that will be taken when you click on a user in the user-map';
$string['maxpoints'] = 'Maximum points';
$string['minpoints_help'] = 'the minimum number of points for any single award of points - this can be a negative number';
$string['minpoints'] = 'Minimum points';
$string['multipleusers_help'] = 'If this setting is enabled, more than one student can be selected when points are awarded. Otherwise, only a single student can be awarded points at one time.';
$string['multipleusers'] = 'Select multiple users';
$string['nameformat_help'] = 'Enter the name fields as you wish them displayed. The names are entered here as *name1* *name2* ... and are defined below.';
$string['nameformat'] = 'Name format';
$string['newcomment'] = 'New comment ...';
$string['nousersfound'] = 'Oops, no users found.';
$string['nopointsyet'] = 'No points have been awarded to this user yet.';
$string['numberofislands'] = 'Number of islands';
$string['numberoflines'] = 'Number of lines';
$string['originalcase'] = 'Original case';
$string['peopleperisland'] = 'People per island';
$string['peopleperline'] = 'People per line';
$string['percent'] = 'percent';
$string['percent100'] = 'full';
$string['percent25'] = '¼';
$string['percent50'] = '½';
$string['percent75'] = '¾';
$string['points_help'] = 'The number of points to be awarded';
$string['points'] = 'Points';
$string['pointsreporttitle'] = 'Points report';
$string['pointstoday'] = 'Points (today): {$a}';
$string['pointstotal'] = 'Points (total): {$a}';
$string['pointstype_help'] = 'the type of points you wish to award, either incremental points or a final total';
$string['pointstype'] = 'Type of points';
$string['pointstypeincremental'] = 'Incremental points';
$string['pointstypetotal'] = 'Total points';
$string['propercase'] = 'Proper Case';
$string['reset'] = 'Reset';
$string['resize'] = 'Resize';
$string['romanizenames_help'] = 'If this setting is enabled, names will be romanized using modifed Hepburn romanization, in which macrons are used to show long vowel sounds.';
$string['romanizenames'] = 'Romanize names';
$string['rotate'] = 'Rotate';
$string['save'] = 'Save';
$string['scoreguide'] = 'Guide score: {$a}';
$string['scorerubric'] = 'Rubric score: {$a}';
$string['sendimmediately_help'] = 'If this setting is enabled, points will be awarded and sent to Moodle (via AJAX) as soon as the teacher clicks or touches a student\'s name or image. Otherwise, points will be sent to Moodle when the teacher clicks the "Award points" button at the bottom of this page.';
$string['sendimmediately'] = 'Send points immediately';
$string['separate'] = 'Separate';
$string['settings'] = 'Settings for feedback points';
$string['setup'] = 'Setup';
$string['showcomments_help'] = 'If this setting is enabled, the top ten most frequently used comments will be displayed for selection from a drop down menu.';
$string['showcomments'] = 'Show frequent comments';
$string['showelement_help'] = 'If this setting is enabled, the checkbox or radio button used to select each student will be shown alongside the name and/or picture of the student.';
$string['showelement'] = 'Show form element';
$string['showfeedback_help'] = 'This setting controls whether feedback about AJAX operations is displayed.

**No**
: Feedback about AJAX operations is displayed

**Yes**
: Feedback about AJAX operations is not displayed

**Automatic**
: Feedback about AJAX operations appears only when an AJAX operation is in progress. Otherwise, it is hidden.';
$string['showfeedback'] = 'Show AJAX feedback';
$string['showgradeassign_help'] = 'If this setting is enabled, each student\'s raw grade for this assignment will be displayed.';
$string['showgradeassign'] = 'Show assignment grades';
$string['showgradecourse_help'] = 'If this setting is enabled, each student\'s adjusted gradebook grade for this assignment will be displayed.';
$string['showgradecourse'] = 'Show gradebook grades';
$string['showlink_help'] = 'If this setting is enabled, a link that goes directly to the page for awarding points will be added on the teacher\'s main view page for this assignment.';
$string['showlink'] = 'Show link from view page';
$string['showpicture_help'] = 'If this setting is enabled, the students\' pictures will be shown in the list of students to whom points are awarded.';
$string['showpicture'] = 'Show user pictures';
$string['showpointstoday_help'] = 'If this setting is enabled, the number of incremental points awarded today to each student will be displayed.

Usually, this setting is only required when using Simple Direct Grading method with incremental points, but it may also be useful after switching from the using Simple Direct Grading to using another grading method.';
$string['showpointstoday'] = 'Show points (today)';
$string['showpointstotal_help'] = 'If this setting is enabled, the total number of points awarded to each student will be displayed.

Usually, this setting is only required when using Simple Direct Grading method, but it may also be useful after switching from the using Simple Direct Grading to using another grading method.';
$string['showpointstotal'] = 'Show points (total)';
$string['showrealname_help'] = 'If this setting is enabled, the students\' real names will be shown in the list of students to whom points are awarded.';
$string['showrealname'] = 'Show real names';
$string['showscoreguide_help'] = 'If this setting is enabled, the Marking Guide score for each student will be displayed.

Usually, this setting is only required when using the Marking Guide advanced grading method, but it may also be useful after switching from the using a Marking Guide to using another grading method.';
$string['showscoreguide'] = 'Show guide score';
$string['showscorerubric_help'] = 'If this setting is enabled, the Rubric score for each student will be displayed.

Usually, this setting is only required when using the Rubric advanced grading method, but it may also be useful after switching from the using a Rubric to using another grading method.';
$string['showscorerubric'] = 'Show rubric score';
$string['showusername_help'] = 'If this setting is enabled, the students\' login usernames will be shown in the list of students to whom points are awarded.';
$string['showusername'] = 'Show usernames';
$string['shuffle'] = 'Shuffle';
$string['splitrealname_help'] = 'If this setting is enabled, the students\' default real name, if used, will be split into two lines, in order to minimize the width of the student tiles.';
$string['splitrealname'] = 'Split real names';
$string['square'] = 'Square';
$string['subtotal'] = 'Sub-total';
$string['textforgradebook'] = '{$a->timeawarded} ({$a->points} pts) {$a->comment}';
$string['timeawarded'] = 'Time awarded';
$string['timecancelled'] = 'Time cancelled';
$string['uppercase'] = 'UPPER CASE';
$string['undo'] = 'Undo';
$string['undomanypointsmanyusers'] = 'Cancelled award of {$a->points} points to {$a->usercount} users: {$a->userlist}';
$string['undomanypointsoneuser'] = 'Cancelled award of {$a->points} points to {$a->usercount} user: {$a->userlist}';
$string['undoonepointmanyusers'] = 'Cancelled award of {$a->points} point to {$a->usercount} users: {$a->userlist}';
$string['undoonepointoneuser'] = 'Cancelled award of {$a->points} point to {$a->usercount} user: {$a->userlist}';
$string['vertical'] = 'Vertical';

$string['case_help'] = 'Specificy the case to be used when this name is displayed.';
$string['case'] = 'Case';
$string['field_help'] = 'Select a name field form the user profile';
$string['field'] = 'Field';
$string['head_help'] = 'Specify the number of characters from the start of the name that will be displayed if the name exceeds the maximum allowde length.';
$string['head'] = 'Head';
$string['length_help'] = 'The maximum allowed length of this name field.';
$string['length'] = 'Length';
$string['prefix_help'] = 'Specify the prefix to be added at the start of this name field.';
$string['prefix'] = 'Prefix';
$string['romanize_help'] = 'Specify whether this name field should be romainzed.';
$string['romanize'] = 'Romanize';
$string['style_help'] = 'Specify the HTML tag to be used to style this name field.';
$string['style'] = 'Style';
$string['suffix_help'] = 'Specify the suffix to be added at the end of this name field.';
$string['suffix'] = 'Suffix';
$string['tail_help'] = 'Specify the number of characters from the end of the name that will be displayed if the name exceeds the maximum allowde length.';
$string['tail'] = 'Tail';
