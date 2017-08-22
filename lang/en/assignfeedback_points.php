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
$string['adjustedscore'] = 'Adjusted score';
$string['aligncenter'] = 'Center';
$string['alignjustify'] = 'Justify';
$string['alignleft'] = 'Left';
$string['alignright'] = 'Right';
$string['alignscoresgrades_help'] = 'This setting allows you to specify the alignment of students\' scores and grades';
$string['alignscoresgrades'] = 'Text alignment';
$string['allowselectable_help'] = 'If this setting is enabled, jQuery\'s selectable functionality can be used to selected multiple students at once.';
$string['allowselectable'] = 'Enable jQuery selectable';
$string['assigngrade'] = 'Assignment: {$a}';
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
$string['case'] = 'Case';
$string['circle'] = 'Circle';
$string['cleanup'] = 'Clean up';
$string['commenttext_help'] = 'A brief decription of why these points are being awarded to the selected user(s)';
$string['commenttext'] = 'Comment';
$string['contactingserver'] = 'Contacting server ...';
$string['count'] = 'Count';
$string['coursegrade'] = 'Course grade: {$a}';
$string['criteria'] = 'Criteria';
$string['default_help'] = 'If set, this feedback method will be enabled by default for all new assignments.';
$string['default'] = 'Enabled by default';
$string['defaultfilename'] = '{$a->filename}.{$a->method}-{$a->settings}';
$string['delete'] = 'Delete';
$string['developmentsettings'] = 'Development settings';
$string['enabled_help'] = 'If enabled, the marker can award points to other users.';
$string['enabled'] = 'Enabled';
$string['exportfilename_help'] = 'A name for the settings file for this plugin. The file will be created and sent to your browser when you click the "Import" button. The file is an XML file, so the extension ".xml" will be added to the file name automatically.';
$string['exportfilename'] = 'Export file name';
$string['feedback_help'] = 'Messages will be displayed here regarding the transfer, via AJAX to the Moodle server, of data about points awarded.

You can control whether this item is visible or hidden using the "Show AJAX feedback" item in the "Settings" section at the bottom of this page.';
$string['feedback'] = 'Feedback';
$string['filenameseparator'] = '-';
$string['fixvowels_help'] = 'This setting specifies how to treat long vowels, specifically "ou", "aa", "ii", "uu", "ee", "oo", in romanized Japanese.

**No**
: The vowels in romanized names will not be altered.

**Shorten long vowels to single letters**
: Long vowels will be shortened to single vowel letters
: aa => a, ii => i, uu => u, ee => e, oo => oh, ou => o

**Display long vowels using macrons**
: Long vowels will be converted to macron letters
: aa => ā, ii => ī, uu => ū, ee => ē, oo => ō, ou => ō

Here are some examples of names with long vowels:

* Kousuke (康介), Maako (真亜子), Taro (太郎), Tooru (徹), Yuuki (優希)
* Ooizumi (大泉), Satou (佐藤), Niitsu (新津)

In some cases, consecutive vowels are not altered because they represent distinct characters in the original Japanese. Consider the following examples:

* Takaaki (貴明), Keiichi (圭一)
* Iita (井板), Fujii (藤井), Koura (小浦), Inoue (井上), Matsuura (松浦)';
$string['fixvowels'] = 'Fix vowels';
$string['fixvowelsshorten'] = 'Shorten long vowels to single letters, aiueo';
$string['fixvowelsmacrons'] = 'Display long vowels using macrons, āīūēō';
$string['gradeprecision_help'] = 'The number of decimal places to be displayed in the assignment, activity and course grades for each student. If this precision is set to zero, then no decimal places will be displayed.';
$string['gradeprecision'] = 'Grade precision';
$string['guidetotal'] = 'Guide total: {$a}';
$string['head'] = 'Head';
$string['horizontal'] = 'Horizontal';
$string['importfile_help'] = 'A settings file that was created previously using the "Export" functionality of this plugin. Settings from the import file will overwrite the current settings for this plugin.';
$string['importfile'] = 'Import file';
$string['increment_help'] = 'the incremental difference, in points, between successive "Points" buttons';
$string['increment'] = 'Points increment';
$string['islands'] = 'Islands';
$string['join'] = 'Join';
$string['layouts_help'] = 'Use these settings to setup, save, load, and delete layouts for the user-map';
$string['layouts'] = 'Layouts';
$string['length_help'] = 'These settings specify how to format long names.

If the number of characters in this name field exceeds the "Length" value here, then the name will be reformatted as HEAD JOIN TAIL, where HEAD is the "Head" number of characters from the beginning of the name, JOIN is the "Join" string, and TAIL is the "Tail" number of characters from the end of the name.';
$string['length'] = 'Length';
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
: The user tiles will be shuffled randonly and placed in new positions. Because the shuffling is random, some tiles may not move.

**Resize**
: The user-map will be shrunk or enlarged to completely surround all the user-tiles.

**Rotate**
: The entire user-map will be rotated by a ¼ turn in an anti-clockwise direction.';
$string['mapaction'] = 'User-map action';
$string['mapmode_help'] = 'the action that will be taken when you click on a user in the user-map';
$string['mapmode'] = 'User-map mode';
$string['maxpoints_help'] = 'the number of points awarded by the "Points" button with the highest value';
$string['maxpoints'] = 'Maximum points';
$string['minpoints_help'] = 'the number of points awarded by the "Points" button with the lowest value - this can be a negative number';
$string['minpoints'] = 'Minimum points';
$string['modulegrade'] = 'Activity grade: {$a}';
$string['multipleusers_help'] = 'If this setting is enabled, more than one student can be selected when points are awarded. Otherwise, only a single student can be awarded points at one time.';
$string['multipleusers'] = 'Select multiple users';
$string['names'] = 'Names';
$string['nametoken_help'] = 'Specify the token that represents this name in the name format string, and then select a name field to be displayed from the user profile.

In addition, the following formatting options are available:

**Delimiter, Start and Count**
: If a "Delimiter" character is specified, the name field will be split into parts on this character. The name parts are indexed starting at "1". The START and COUNT settings specify the range of name parts that will be extracted for display.

**Length, Head, Join and Tail**
: If a length is specified, then names that are longer than this number of characters will be truncated as HEAD JOIN TAIL, where HEAD is the "Head" number of characters from the beginning of the name, JOIN is the "Join" string, and TAIL is the "Tail" number of characters from the end of the name..

**Style**
: If required, you can specify the HTML style tag to be used when displaying this name.

**Case**
: If required, you can force the case to be used when displaying this name.

**Romanize**
: If this setting is enabled, then where possible, Japanese, Korean and Chinese names will be converted to their English equivalents.';
$string['nametoken'] = 'Name token [{$a}]';
$string['nametokenjoindefault'] = ' ... ';
$string['nametokentokendefault'] = 'name';
$string['nametokensadd'] = 'Add a name token';
$string['nameformat_help'] = 'This string defines the display format for student names. The name format string can include field names, name tokens, newline tokens, and any other characters.

**Field names**
: A field name is one of the following fields from the user profile: firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename

**Name tokens**
: A name token is a short string of characters that acts as a placeholder in the name format string. Each name token is defined in a "Name token [...]" defintion. A name token definition defines which field from the user profile to display and may also specify how the field should be shortened and formatted.

**Newline tokens**
: A newline token is a short string that acts as a placeholder for a newline, or line break, in the format string. Usually, a newline token is just one character, but it can be any string of any length.

**Other characters**
: Character strings other than those defined as name tokens or newline tokens will appear unchanged in the student names. Such characters may be useful for separating parts of names, and adding titles and punctuation to names.

If the format string is empty, then a default name is generated based on the "fullnamedisplay" string for the current display language.';
$string['nameformat'] = 'Name format';
$string['newlinetoken_help'] = 'A newline token is a short string that acts as a placeholder for a newline, or line break, in the format string. Usually, a newline token is just one character, but it can be any string of any length.

Commonly used newline tokens are a single space (" "); a symbol, such as a vertical bar ("|") or slash ("/"); or a punctuation mark, such as a period (".") or exclamation mark ("!").';
$string['newlinetoken'] = 'Newline token';
$string['newlinetokendefault'] = ' ';
$string['nameseparator'] = ' = ';
$string['newcomment'] = 'New comment ...';
$string['newlineempty'] = '(this text box is currently empty)';
$string['newlinespace'] = '(this text box currently contains a space)';
$string['nopointsyet'] = 'No points have been awarded to this user yet.';
$string['nousersfound'] = 'Oops, no users found.';
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
$string['pointsperrow_help'] = 'The number of points buttons per row.';
$string['pointsperrow'] = 'Points row width';
$string['pointsrange'] = 'Points range';
$string['pointstoday'] = 'Points (today): {$a}';
$string['pointstotal'] = 'Points (total): {$a}';
$string['pointstype_help'] = 'This setting specifies how point awards are combined to calculate grades for this assignment. The following aggregation methods are available:

**sum**
: The assignment grade will be set to the sum of all points awarded. When using this method, the points act as incremental points.

**newest**
: The assignment grade will be set to the value of the most recent point award.

**maximum**
: The assignment grade will be set to the maximum number of points awarded.

**average**
: The assignment grade will be set to the average number of points awarded.

**median**
: The assignment grade will be set to the median value of points awarded. The median value is the midpoint value if the awards are arranged from lowest to highest.

**mode**
: The assignment grade will be set to the mode value of points awarded. The mode value is the most frequently occurring value in the awards.

**minimum**
: The assignment grade will be set to the minimum number of points awarded.

**oldest**
: The assignment grade will be set to the value of the oldest, i.e. the first, point award.

***Note:*** Points aggregation is only used for "Simple Direct Grading". It is not used for "Advanced grading" methods, such as a "Rubric" or "Marking guide".';
$string['pointstype'] = 'Points aggregation';
$string['pointstypeaverage'] = 'average';
$string['pointstypemaximum'] = 'maximum';
$string['pointstypemedian'] = 'median';
$string['pointstypeminimum'] = 'minimum';
$string['pointstypemode'] = 'mode';
$string['pointstypenewest'] = 'newest';
$string['pointstypeoldest']  = 'oldest';
$string['pointstypesum'] = 'sum';
$string['propercase'] = 'Proper Case';
$string['remarks'] = 'Remarks';
$string['reporttitleguide'] = 'Marking report';
$string['reporttitlepoints'] = 'Points report';
$string['reporttitlerubric'] = 'Rubric report';
$string['reset'] = 'Reset';
$string['resize'] = 'Resize';
$string['romanize_help'] = 'Specify whether this name field should be romanized.

**No**
: The name will not be romanized.

**Standardize romaji**
: Romanized strings will be converted to standard Hepburn romanization, including the following transformations:
: si => shi, ti => chi, tu => tsu, nb => mb
: jya => ja, jyu => ju, jyo => jo
: sya => sha, syu => shu, syo => sho
: kiyou => kyou
: riyou => ryou, riyuu => ryuu
: jiyou => jou, jiyuu => juu, jiyun => jun
: shiyou => shou, shiyuu => shuu, shiyun => shun,

**Romanize hiragana**
: Any hiragana characters, e.g. ひらがな, will be converted to their romaji equivalents.

**Romanize full-width katakana**
: Any full-width katakana characters, e.g. カタカナ, will be converted to their romaji equivalents.

**Romanize half-width katakana**
: Any half-width katakana characters, e.g. ｶﾀｶﾅ, will be converted to their romaji equivalents.';
$string['romanize'] = 'Romanize';
$string['romanizehiragana'] = 'Romanize hiragana';
$string['romanizekatakanafull'] = 'Romanize full-width katakana';
$string['romanizekatakanahalf'] = 'Romanize half-width katakana';
$string['romanizeromaji'] = 'Standardize romaji';
$string['rotate'] = 'Rotate';
$string['rubrictotal'] = 'Rubric total: {$a}';
$string['save'] = 'Save';
$string['score'] = 'Score';
$string['sendimmediately_help'] = 'If this setting is enabled, points will be awarded and sent to Moodle (via AJAX) as soon as the teacher clicks or taps a student\'s name or image. Otherwise, points will be sent to Moodle when the teacher clicks the "Award points" button at the bottom of this page.';
$string['sendimmediately'] = 'Send points immediately';
$string['separate'] = 'Separate';
$string['settings'] = 'Settings for Feedback points';
$string['setup'] = 'Setup';
$string['showassigngrade_help'] = 'If this setting is enabled, each student\'s raw grade for this assignment will be displayed.

The grade can be displayed as a raw grade, as a percent, or as a fraction of the total.';
$string['showassigngrade'] = 'Show assignment grades';
$string['showcomments_help'] = 'If this setting is enabled, the top ten most frequently used comments will be displayed for selection from a drop down menu.';
$string['showcomments'] = 'Show frequent comments';
$string['showcoursegrade_help'] = 'If this setting is enabled, each student\'s course grade will be displayed.

The grade can be displayed as a raw grade, as a percent, as a fraction of the total, or exactly as it appears in the gradebook.';
$string['showcoursegrade'] = 'Show course grades';
$string['showelement_help'] = 'If this setting is enabled, the checkboxes or radio buttons used to select users will be shown in the user tiles.';
$string['showelement'] = 'Show form element';
$string['showfeedback_help'] = 'This setting controls whether feedback about AJAX operations is displayed.

**No**
: Feedback about AJAX operations is displayed

**Yes**
: Feedback about AJAX operations is not displayed

**Automatic**
: Feedback about AJAX operations appears only when an AJAX operation is in progress. Otherwise, it is hidden.';
$string['showfeedback'] = 'Show AJAX feedback';
$string['showguideremarks_help'] = 'If this setting is enabled, the feeback remarks for each Marking Guide criteria score for each student will be displayed.';
$string['showguideremarks'] = 'Show Marking Guide remarks';
$string['showguidescores_help'] = 'If this setting is enabled, the individual scores for each Marking Guide criteria for each student will be displayed.';
$string['showguidescores'] = 'Show Marking Guide scores';
$string['showguidetotal_help'] = 'If this setting is enabled, the total Marking Guide score for each student will be displayed.';
$string['showguidetotal'] = 'Show Marking Guide total';
$string['showlink_help'] = 'If this setting is enabled, a link that goes directly to this page for awarding points will be added on the teacher\'s main view page for this assignment.';
$string['showlink'] = 'Show link from view page';
$string['showmodulegrade_help'] = 'If this setting is enabled, each student\'s adjusted gradebook grade for this assignment activity will be displayed.

The grade can be displayed as a raw grade, as a percent, as a fraction of the total, or exactly as it appears in the gradebook.';
$string['showmodulegrade'] = 'Show activity grades';
$string['showpicture_help'] = 'If this setting is enabled, the students\' pictures will be shown in the list of students to whom points are awarded.';
$string['showpicture'] = 'Show user pictures';
$string['showpointstoday_help'] = 'If this setting is enabled, the number of points awarded today to each student will be displayed.

Usually, this setting is only required when using Simple Direct Grading method with incremental points, but it may also be useful after switching from the using Simple Direct Grading to using another grading method.';
$string['showpointstoday'] = 'Show points (today)';
$string['showpointstotal_help'] = 'If this setting is enabled, the total number of points awarded will be displayed.

Usually, this setting is only required when using Simple Direct Grading method, but it may also be useful after switching from the using Simple Direct Grading to using another grading method.';
$string['showpointstotal'] = 'Show points (total)';
$string['showresetbuttons_help'] = 'If this setting is enabled, Reset buttons will be added to each row in the Advanced grading form. Clicking a Reset button will deselect the score and remove the comment in that row.';
$string['showresetbuttons'] = 'Show Reset buttons';
$string['showrubricremarks_help'] = 'If this setting is enabled, the feedback remarks for each Rubric criteria for each student will be displayed.';
$string['showrubricremarks'] = 'Show Rubric remarks';
$string['showrubricscores_help'] = 'If this setting is enabled, the individual scores for each Rubric criteria for each student will be displayed.';
$string['showrubricscores'] = 'Show Rubric scores';
$string['showrubrictotal_help'] = 'If this setting is enabled, the total Rubric score for each student will be displayed.';
$string['showrubrictotal'] = 'Show Rubric totals';
$string['shuffle'] = 'Shuffle';
$string['sortby'] = 'Sort by';
$string['singlespace'] = '(single white space)';
$string['split_help'] = 'These settings are optional. They specify how to extract part of a user name field.

If a "Delimiter" character is specified, the name field will be split into parts on this character. The parts are indexed starting at "1". The START and COUNT settings specify the range of parts that will be extracted for display.

**Start**
: If the START setting is positive, it specifies the starting part counting forward from the ***beginning*** of this name field.
: If the START setting is negative, it specifies the starting part counting back from the ***end*** of this name field.
: If the START setting is zero or blank, the selected range will start at the first part of this name field.


**Count**
: If the COUNT setting is positive, it specifies the final part counting ***forward*** from the START part.
: If the COUNT setting is negative, it specifies the final part counting back from the ***end*** of this name field.
: If the COUNT setting is zero or blank, the selected range will finish at the last part of this name field.';
$string['split'] = 'Delimiter';
$string['square'] = 'Square';
$string['start'] = 'Start';
$string['style_help'] = 'Specify the HTML tag and text case to be used when this name is displayed.';
$string['style'] = 'Style';
$string['subtotal'] = 'Sub-total';
$string['tail'] = 'Tail';
$string['textforgradebook'] = '{$a->timeawarded} ({$a->points} pts) {$a->comment}';
$string['textsettings_help'] = 'These settings specify how to format long text strings.

If the number of characters in a string exceeds the "Length" value here, then the string will be reformatted as HEAD JOIN TAIL, where HEAD is the "Head" number of characters from the beginning of the name, JOIN is the "Join" string, and TAIL is the "Tail" number of characters from the end of the string.';
$string['textsettings'] = 'Text length';
$string['totals'] = 'Totals';
$string['timeawarded'] = 'Time awarded';
$string['timecancelled'] = 'Time cancelled';
$string['undo'] = 'Undo';
$string['undomanypointsmanyusers'] = 'Cancelled award of {$a->points} points to {$a->usercount} users: {$a->userlist}';
$string['undomanypointsoneuser'] = 'Cancelled award of {$a->points} points to {$a->usercount} user: {$a->userlist}';
$string['undoonepointmanyusers'] = 'Cancelled award of {$a->points} point to {$a->usercount} users: {$a->userlist}';
$string['undoonepointoneuser'] = 'Cancelled award of {$a->points} point to {$a->usercount} user: {$a->userlist}';
$string['updatescoresmanyusers'] = '{$a->type} scores were updated for {$a->usercount} users: {$a->userlist}';
$string['updatescoresoneuser'] = '{$a->type} scores were updated for {$a->userlist}';
$string['uppercase'] = 'UPPER CASE';
$string['vertical'] = 'Vertical';
$string['viewgradingpage'] = 'To view the full grading page for this user, {$a}';
