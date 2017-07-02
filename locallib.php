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
 * This file contains the definition for the library class for point feedback plugin
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for point feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_points
 * @copyright 2015 Gordon Bateson {@link http://github.com/gbateson}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_points extends assign_feedback_plugin {

    const CASE_ORIGINAL = 0;
    const CASE_PROPER   = 1;
    const CASE_LOWER    = 2;
    const CASE_UPPER    = 3;

    const NAME_COUNT_MAX = 4;
    const NAME_COUNT_ADD = 1;

    const JAPANESE_NAME_STRING = '/^((t?chi|s?shi|t?tsu)|((by|t?ch|jy|k?ky|py|ry|s?sh|s?sy|w|y)[auo])|((b?b|d|f|g|h|j|k?k|m|n|p?p|r|s?s|t?t|z)[aiueo])|[aiueo]|[mn])+$/';

    const ROMANIZE_NO  = 0;
    const ROMANIZE_YES = 1;
    const ROMANIZE_FIX = 2;

    const POINTSTYPE_SUM     = 0; // sum of awards (i.e. incremental points)
    const POINTSTYPE_NEWEST  = 1; // newest (=most recent) award (i.e. grade)
    const POINTSTYPE_MAXIMUM = 2; // maximum award
    const POINTSTYPE_AVERAGE = 3; // average award
    const POINTSTYPE_MEDIAN  = 4; // middle award
    const POINTSTYPE_MODE    = 5; // most popular award
    const POINTSTYPE_MINIMUM = 6; // minimum award
    const POINTSTYPE_OLDEST  = 7; // oldest (=first) award

    const THEME_TYPE_LABEL = 1; // templateable theme
    const THEME_TYPE_SPAN  = 2; // non-templateable theme

    /**
     * Get the name of the feedback points plugin.
     * @return string
     */
    public function get_name() {
        $plugin = 'assignfeedback_points';
        return get_string('pluginname', $plugin);
    }

    /**
     * Get the all config settings for this feedback points object
     * and supply defaults values for any settings not yet defined
     *
     * @param $plugin name (usually "assignfeedback_points")
     * @return object
     */
    public function get_all_config($plugin) {

        $defaults = self::get_defaultvalues($plugin);

        // get the site wide defaults for this $plugin
        $config = get_config($plugin);

        // add defaults for integer fields
        foreach ($defaults as $name => $value) {
            if (! property_exists($config, $name)) {
                $config->$name = $value;
            }
        }

        // override with settings for this assign(ment) activity
        if ($this->assignment->has_instance()) {
            foreach ($this->get_config() as $name => $value) {
                if (property_exists($config, $name)) {
                    $config->$name = $value;
                }
            }
        }

        // unpack nametokens, if necessary
        if (is_string($config->nametokens)) {
            if (empty($config->nametokens)) {
                $config->nametokens = array();
            } else {
                $config->nametokens = unserialize(base64_decode($config->nametokens));
            }
        }

        // override with settings from incoming form data
        foreach ($defaults as $name => $value) {
            switch (true) {
                case is_array($value):
                    $value = self::optional_param_array($name, null, PARAM_TEXT);
                    break;
                case is_string($value):
                    $value = optional_param($name, null, PARAM_TEXT);
                    break;
                case is_integer($value):
                    $value = optional_param($name, null, PARAM_INT);
                    break;
                default:
                    $value = null; // shouldn't happen !!
            }
            if (isset($value)) {
                $config->$name = $value;
            }
        }

        // unset nametokens if necessary
        if (isset($config->nametokens)) {
            $i_max = count($config->nametokens);
            for ($i=($i_max - 1); $i>=0; $i--) {
                if (empty($config->nametokens[$i]['field'])) {
                    array_splice($config->nametokens, $i, 1);
                    array_splice($_POST['nametokens'], $i, 1);
                }
            }
        }

        unset($config->default);
        unset($config->enabled);

        return $config;
    }

    /**
     * Get the default setting for feedback points plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        $plugin = 'assignfeedback_points';
        $config = $this->get_all_config($plugin);
        self::add_settings($mform, $plugin, $config);
   }

    /**
     * add_heading
     *
     * @param object  $mform
     * @param string  $name
     * @param string  $plugin
     * @param boolean $expanded (optional, default=TRUE)
     * @param string  $suffix   (optional, default="_hdr")
     */
    static public function add_heading($mform, $name, $plugin, $expanded=true, $suffix='_hdr') {
        $label = get_string($name, $plugin);
        $mform->addElement('header', $name.$suffix, $label);
        if (method_exists($mform, 'setExpanded')) {
            $mform->setExpanded($name.$suffix, $expanded);
        }
    }

    /**
     * add_settings
     *
     * @param object $mform
     * @param string $plugin
     * @param object $config
     * @param object $custom (optional, default=null
     * @todo Finish documenting this function
     */
    static public function add_settings($mform, $plugin, $config, $custom=null) {
        global $OUTPUT;

        // add header for new section
        // (because there are quite a few settings)
        if ($custom==null) {
            self::add_heading($mform, 'settings', $plugin, false);
        }

        if ($custom) {
            self::add_heading($mform, 'picturesandnames', $plugin, false);
        }

        self::add_setting($mform, $config, 'showpicture', 'checkbox', 0);
        self::add_setting($mform, $config, 'nameformat', 'text', '', self::get_text_options(20), PARAM_TEXT);
        self::add_setting($mform, $config, 'newlinetoken', 'text', '', self::get_text_options(), PARAM_TEXT);

        // nametokens
        $name = 'nametokens';

        $strman = get_string_manager();
        $types = self::get_nametoken_setting_types();
        $defaults = self::get_nametoken_setting_defaults($strman, $plugin);

        $count = (empty($config->$name) ? 0 : count($config->$name));
        if (optional_param($name.'add', '', PARAM_TEXT)){
            $count += self::NAME_COUNT_ADD;
        }
        $count = min(self::NAME_COUNT_MAX, max(0, $count));
        for ($i=0; $i<$count; $i++) {

            // define elements in this nametoken group
            $elements = array();
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'token',    'text',   0, 3);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'field',    'select', 0, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'split',    'text',   2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'start',    'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'count',    'text',   1, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'length',   'text',   2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'head',     'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'join',     'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'tail',     'text',   1, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'style',    'select', 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'case',     'select', 1, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $name, $i, 'romanize', 'select', 2);

            // add this nametoken group
            $label = get_string('nametoken', $plugin, ($i + 1));
            $mform->addElement('group', $name.$i, $label, $elements, '', false);
            $mform->addHelpButton($name.$i, 'nametoken', $plugin);

            // setType and setDefault
            $nametoken_exists = array_key_exists($i, $config->nametokens);
            foreach ($types as $setting => $type) {
                if ($nametoken_exists && array_key_exists($setting, $config->nametokens[$i])) {
                    $default = $config->nametokens[$i][$setting];
                } else {
                    $default = $defaults[$setting];
                }
                $mform->setType($name."[$i][$setting]", $type);
                $mform->setDefault($name."[$i][$setting]", $default);

                // if "field" is not specified, disable this $setting
                if ($setting=='token' || $setting=='field') {
                    // do nothing
                } else {
                    $mform->disabledIf("nametokens[$i][$setting]", "nametokens[$i][field]", 'eq', '');
                }
            }

            // if the "split" delimiter is not specified, disable "start" and "count"
            $mform->disabledIf("nametokens[$i][start]", "nametokens[$i][split]", 'eq', '');
            $mform->disabledIf("nametokens[$i][count]", "nametokens[$i][split]", 'eq', '');

            // if "length" is zero, disable "head", "tail" and "join"
            $mform->disabledIf("nametokens[$i][head]", "nametokens[$i][length]", 'eq', '0');
            $mform->disabledIf("nametokens[$i][tail]", "nametokens[$i][length]", 'eq', '0');
            $mform->disabledIf("nametokens[$i][join]", "nametokens[$i][length]", 'eq', '0');
        }

        // button to add more "nametokens"
        $label = get_string($name.'add', $plugin);
        if (self::NAME_COUNT_ADD > 1) {
            $label = str_ireplace('{no}', self::NAME_COUNT_ADD, $label);
        }
        $mform->addElement('submit', $name.'add', $label);
        $mform->registerNoSubmitButton($name.'add');

        // Points settings
        $names = array('minpoints',
                       'maxpoints',
                       'increment',
                       'pointsperrow');
        if ($custom && $custom->gradingmethod) {

            // Advanced Grading (in awardpoints_form)
            array_push($names, 'pointstype',
                               'showcomments');
            foreach ($names as $name) {
                $mform->addElement('hidden', $name, $config->$name);
                $mform->setType($name, PARAM_INT);
            }

        } else {

            // Simple Direct Grading (or Assignment settings form)

            if ($custom) {
                self::add_heading($mform, 'gradingmethodnone', 'grading', false);
            }

            $options = self::get_text_options();
            foreach ($names as $name) {
                self::add_setting($mform, $config, $name, 'text', 0, $options);
                if ($custom==null) {
                    $mform->disabledIf($name, 'advancedgradingmethod_submissions', 'ne', '');
                }
            }

            $name = 'pointstype';
            $options = self::get_pointstype_options();
            self::add_setting($mform, $config, $name, 'select', 0, $options);
            $mform->disabledIf($name, 'advancedgradingmethod_submissions', 'ne', '');

            $name = 'showcomments';
            self::add_setting($mform, $config, $name, 'checkbox', 0);
        }

        // points, scores and grades
        if ($custom) {
            self::add_heading($mform, 'pointsscoresgrades', $plugin , false);
        }

        // $names of hidden fields
        $names = array();
        $gradingmethod = 'advancedgradingmethod_submissions';

        $name = 'showpointstoday';
        if ($custom==null || $custom->gradingmethod=='') {
            self::add_setting($mform, $config, $name, 'checkbox', 0);
            $mform->disabledIf($name, $gradingmethod, 'ne', '');
        } else {
            $names[] = $name;
        }

        $name = 'showpointstoday';
        if ($custom==null || $custom->gradingmethod=='') {
            self::add_setting($mform, $config, $name, 'checkbox', 0);
            $mform->disabledIf($name, $gradingmethod, 'ne', '');
        } else {
            $names[] = $name;
        }

        $name = 'showscorerubric';
        if ($custom==null || $custom->gradingmethod=='rubric') {
            self::add_setting($mform, $config, $name, 'checkbox', 0);
            $mform->disabledIf($name, $gradingmethod, 'ne', 'rubric');
        } else {
            $names[] = $name;
        }

        $name = 'showscoreguide';
        if ($custom==null || $custom->gradingmethod=='guide') {
            self::add_setting($mform, $config, $name, 'checkbox', 0);
            $mform->disabledIf($name, $gradingmethod, 'ne', 'guide');
        } else {
            $names[] = $name;
        }

        foreach ($names as $name) {
            $mform->addElement('hidden', $name, $config->$name);
            $mform->setType($name, PARAM_INT);
        }

        self::add_setting($mform, $config, 'showgradeassign', 'checkbox', 0);
        self::add_setting($mform, $config, 'showgradecourse', 'checkbox', 0);

        // development settings (one day, these may be hidden completely)
        if ($custom) {
            self::add_heading($mform, 'developmentsettings', $plugin, false);
        }
        self::add_setting($mform, $config, 'showfeedback',    'select',   0, self::get_showfeedback_options());
        self::add_setting($mform, $config, 'showelement',     'checkbox', 0);
        self::add_setting($mform, $config, 'multipleusers',   'checkbox', 1);
        self::add_setting($mform, $config, 'sendimmediately', 'checkbox', 0);
        self::add_setting($mform, $config, 'allowselectable', 'checkbox', 1);
        self::add_setting($mform, $config, 'showlink',        'checkbox', 1);

        // disable "showpointstoday" if we are not using incremental points
        $mform->disabledIf('showpointstoday', 'pointstype', 'ne', '0');

        // ========================
        // jQuery (javascript)
        // ========================
        //
        self::add_field_jquery($mform, $plugin, $custom);
    }

    /**
     * add_setting
     *
     * @param $mform
     * @param $config
     * @param $name of field
     * @param $type of QuickForm field
     * @param $default (optional, default = null)
     * @param $options (optional, default = null)
     * @param $paramtype (optional, default=PARAM_INT)
     * @todo Finish documenting this function
     */
    static public function add_setting($mform, $config, $name, $type, $default=null, $options=null, $paramtype=PARAM_INT) {

        $plugin = 'assignfeedback_points';
        $label = get_string($name, $plugin);
        $mform->addElement($type, $name, $label, $options);
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, $paramtype);

        if (isset($config->$name)) {
            $default = $config->$name;
        }
        if (isset($default)) {
            $mform->setDefault($name, $default);
        }

        $name_adv = $name.'_adv';
        if (isset($config->$name_adv)) {
            $mform->setAdvanced($name, $config->$name_adv);
        }

        if ($mform->elementExists('feedbackplugins')) {
            $mform->disabledIf($name, $plugin.'_enabled', 'notchecked');
        }
    }

    /**
     * add_nametokens_setting
     *
     * @param object  $mform
     * @param array   $elements (baed by reference)
     * @param object  $strman
     * @param string  name of $plugin
     * @param string  $name of form element (should be "nametokens")
     * @param integer $i(ndex) of form element
     * @param string  name of $setting
     * @param string  $elementtype
     * @param $labeltype (optional, default=0) 0=none, 1=thin, 2=wide
     * @param $spacetype (optional, default=0) 0=none, 1=space, 2=newline, 3=get_string("nameseparator", $plugin)
     * @todo Finish documenting this function
     */
    static public function add_nametokens_setting($mform, &$elements, $strman, $plugin, $name, $i, $setting, $elementtype, $labeltype=0, $spacetype=0) {
        global $OUTPUT;

        if ($labeltype) {
            $label = get_string($setting, $plugin);
            if ($strman->string_exists($setting.'_help', $plugin)) {
                $label .= $OUTPUT->help_icon($setting, $plugin);
            }
            switch ($labeltype) {
                case 2: $options = array('class' => 'settingtitle wide'); break;
                case 1: $options = array('class' => 'settingtitle thin'); break;
                default: $options = null; // shouldn't happen !!
            }
            $label = html_writer::tag('div', $label, $options);
            $elements[] = $mform->createElement('static', '', '', $label);
        }

        $class = get_class();
        $method = 'get_nametoken_'.$setting.'_options';
        if (method_exists($class, $method)) {
            $options = call_user_func(array($class, $method));
        } else if ($elementtype=='text') {
            $options = self::get_text_options();
        } else {
            $options = null;
        }
        $elements[] = $mform->createElement($elementtype, $name."[$i][$setting]", '', $options);

        if ($spacetype) {
            switch ($spacetype) {
                case 3: $space = get_string('nameseparator', $plugin); break;
                case 2: $space = html_writer::empty_tag('br'); break;
                case 1: $space = ' '; break;
                default: $space = ''; // shouldn't happen !!
            }
            $elements[] = $mform->createElement('static', '', '', $space);
        }
    }

    /**
     * add_field_jquery
     *
     * add jQuery javascript to make users draggable in a resizable container
     *
     * @param object  $mform
     * @param string  $plugin
     * @param object  $custom (optional, default=null)
     */
    static public function add_field_jquery($mform, $plugin, $custom=null) {
        global $CFG, $OUTPUT;

        // add jQuery script to this page
        self::requires_jquery('/mod/assign/feedback/points/awardpoints.js', $plugin);

        $js = '';
        $js .= '<script type="text/javascript">'."\n";
        $js .= '//<![CDATA['."\n";

        $js .= '    if (typeof(window.PTS)=="undefined") {'."\n";
        $js .= '        window.PTS = {};'."\n";
        $js .= '    }'."\n";

        $js .= '    PTS.str = {};'."\n";
        $js .= '    PTS.str.showless         = "'.self::js_safe(get_string('showless', 'form')).'";'."\n";
        $js .= '    PTS.str.showmore         = "'.self::js_safe(get_string('showmore', 'form')).'";'."\n";
        $js .= '    PTS.str.newlineempty     = "'.self::js_safe(get_string('newlineempty', $plugin)).'";'."\n";
        $js .= '    PTS.str.newlinespace     = "'.self::js_safe(get_string('newlinespace', $plugin)).'";'."\n";

        if ($custom) {
            $js .= '    PTS.str.contactingserver = "'.self::js_safe(get_string('contactingserver', $plugin)).'";'."\n";

            // determine html tag used to enclose group elements
            // templatable forms on Moodle >= 2.9 use "LABEL" tags
            // non-templatable forms use "SPAN" tags

            if (method_exists($OUTPUT, 'mform_element')) {
                // Moodle >= 2.9
                $element = $mform->getElement('mapmodeelements'); // group of radio elements
                $element = $element->getElements();               // array of radio elements
                $element = $element[0];                           // the first radio element
                $element = $OUTPUT->mform_element($element, false, false, '', true);
                $group_element_tag = preg_replace('/^.*?<(\w+)[^>]*>.*$/s', '$1', $element);
            } else {
                // Moodle <= 2.8
                $group_element_tag = 'span';
            }

            if ($group_element_tag=='label') {
                // templatable theme for Moodle >= 2.9
                $theme_type          = self::THEME_TYPE_LABEL;
                $mapaction_container = '#id_awardpoints_hdr div.form-group.row:nth-child(3) div.felement';
                $mapmode_container   = '#id_awardpoints_hdr div.form-group.row:nth-child(4) div.felement';
                $user_container      = '#id_awardpoints_hdr div.form-group.row:nth-child(5) div.felement';
                $points_container    = '#id_awardpoints_hdr div.form-group.row:nth-child(6) div.felement';
                $layouts_container   = '#id_layouts_hdr div.form-group.row:nth-child(1) div.felement';
            } else {
                // non-templatable theme
                $theme_type          = self::THEME_TYPE_SPAN;
                $mapaction_container = '#fgroup_id_mapactionelements fieldset.fgroup';
                $mapmode_container   = '#fgroup_id_mapmodeelements fieldset.fgroup';
                $user_container      = '#fgroup_id_awardtoelements fieldset.fgroup';
                $points_container    = '#fgroup_id_pointselements fieldset.fgroup';
                $layouts_container   = '#fgroup_id_layoutselements';
            }

            $js .= '    PTS.gradingmethod         = "'.$custom->gradingmethod.'";'."\n";
            $js .= '    PTS.gradingcontainer      = "#fitem_id_advancedgrading";'."\n";

            $js .= '    PTS.elementtype           = "'.($custom->config->multipleusers ? 'checkbox' : 'radio').'";'."\n";
            $js .= '    PTS.elementdisplay        = "'.($custom->config->showelement  ? ' ' : 'none').'";'."\n";

            $js .= '    PTS.pointstype            = '.intval($custom->config->pointstype).";\n";
            $js .= '    PTS.pointsperrow          = '.intval($custom->config->pointsperrow).";\n";
            $js .= '    PTS.sendimmediately       = '.intval($custom->config->sendimmediately).";\n";
            $js .= '    PTS.showpointstoday       = '.intval($custom->config->showpointstoday).";\n";
            $js .= '    PTS.showpointstotal       = '.intval($custom->config->showpointstotal).";\n";
            $js .= '    PTS.showscorerubric       = '.intval($custom->config->showscorerubric).";\n";
            $js .= '    PTS.showscoreguide        = '.intval($custom->config->showscoreguide).";\n";
            $js .= '    PTS.showfeedback          = '.intval($custom->config->showfeedback).";\n";

            $js .= '    PTS.theme_type            = "'.$theme_type.'";'."\n";
            $js .= '    PTS.THEME_TYPE_SPAN       = '.self::THEME_TYPE_SPAN."\n";
            $js .= '    PTS.THEME_TYPE_LABEL      = '.self::THEME_TYPE_LABEL."\n";
            $js .= '    PTS.group_element_tag     = "'.$group_element_tag.'";'."\n";
            $js .= '    PTS.GROUP_ELEMENT_TAG     = PTS.group_element_tag.toUpperCase();'."\n";

            $js .= '    PTS.mapaction_container   = "'.$mapaction_container.'";'."\n";
            $js .= '    PTS.mapaction_min_width   = 48;'."\n";
            $js .= '    PTS.mapaction_min_height  = 18;'."\n";

            $js .= '    PTS.mapmode_container     = "'.$mapmode_container.'";'."\n";
            $js .= '    PTS.mapmode_min_width     = 48;'."\n";
            $js .= '    PTS.mapmode_min_height    = 18;'."\n";

            $js .= '    PTS.user_container        = "'.$user_container.'";'."\n";
            $js .= '    PTS.user_min_width        = 60;'."\n";
            $js .= '    PTS.user_min_height       = 18;'."\n";

            $js .= '    PTS.points_container      = "'.$points_container.'";'."\n";
            $js .= '    PTS.points_min_width      = (PTS.theme_type==PTS.THEME_TYPE_LABEL ? 48 : 36);'."\n";
            $js .= '    PTS.points_min_height     = 24;'."\n";

            $js .= '    PTS.layouts_container     = "'.$layouts_container.'"'.";\n";

            $js .= '    PTS.report_container_id   = "id_report_container";'."\n";
            $js .= '    PTS.report_container      = "#" + PTS.report_container_id;'."\n";

            $url  = '/mod/assign/feedback/points/awardpoints.ajax.php';
            $url  = new moodle_url($url, array('id' => $custom->cm->id));
            $js .= '    PTS.awardpoints_ajax_php  = "'.self::js_safe($url).'";'."\n";

            $url = '/mod/assign/feedback/points/reportpoints.ajax.php';
            $url = new moodle_url($url, array('id' => $custom->cm->id));
            $js .= '    PTS.reportpoints_ajax_php = "'.self::js_safe($url).'";'."\n";

            $js .= '    PTS.groupid               = '.intval($custom->groupid).";\n";
            $js .= '    PTS.sesskey               = "'.sesskey().'";'."\n";

            $js .= '    PTS.cleanup               = {duration : 400};'."\n";
            $js .= '    PTS.separate              = {duration : 400, grid : {x : 12, y : 8}};'."\n";
            $js .= '    PTS.rotate                = {duration : 400};'."\n";
            $js .= '    PTS.resize                = {duration : 400};'."\n";
            $js .= '    PTS.shuffle               = {duration : 400};'."\n";

            $js .= '    PTS.allowselectable       = '.intval($custom->config->allowselectable).';'."\n";
        }

        $js .= '//]]>'."\n";
        $js .= '</script>'."\n";

        $mform->addElement('html', $js);
    }

    /**
     * js_safe
     *
     * @param string $str
     */
    static public function js_safe($str) {
        static $replace = array(
            '\\'   => '\\\\',  "'"  =>"\\'", '"'=>'\\"',  // slashes and quotes
            "\r\n" => '\\n',   "\r" =>'\\n', "\n"=>'\\n', // newlines (win, mac, nix)
            "\0"   => '\\0',   '</' =>'<\\/');            // other replacements
        return strtr($str, $replace);
    }

    /**
     * Save the settings for feedback points plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        return $this->save_settings_allowmissing($data, true);
    }

    /**
     * Save the settings for feedback points plugin
     *
     * @param stdClass $data
     * @param boolean  $allowmissing
     * @return bool
     */
    public function save_settings_allowmissing(stdClass $data, $allowmissing=false) {
        $plugin = 'assignfeedback_points';
        foreach (self::get_defaultvalues($plugin) as $name => $default) {
            $this->save_setting_allowmissing($data, $name, $default, $allowmissing);
        }
        return true;
    }

    /**
     * Save a single setting for feedback points plugin
     *
     * @param stdClass $data
     * @param string   $name
     * @param integer  $paramtype
     * @param boolean  $allowmissing
     * @return void
     */
    public function save_setting_allowmissing($data, $name, $default, $allowmissing) {
        $isset = isset($data->$name);
        if ($isset || $allowmissing) {
            $class = get_class();
            $value = $default;
            if ($isset) {
                $method = 'get_'.$name.'_options';
                if (method_exists($class, $method)) {
                    $options = call_user_func(array($class, $method));
                    if (array_key_exists($data->$name, $options)) {
                        $value = $data->$name;
                    }
                } else {
                    $value = $data->$name;
                }
            }
            if (is_array($value)) {
                $value = base64_encode(serialize($value));
            }
            if (isset($value)) {
                $this->set_config($name, $value);
            }
        }
    }

    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main assignment page.
     *
     * @return string
     */
    public function view_header() {
        $output = '';

        // check if link is required or not ...
        if ($this->get_config('showlink')) {

            // create URL of the page for awarding incremental points
            $cm = $this->assignment->get_course_module();
            $params = array('id'            => $cm->id,
                            'plugin'        => 'points',
                            'pluginsubtype' => 'assignfeedback',
                            'action'        => 'viewpluginpage',
                            'pluginaction'  => 'awardpoints');
            $url = new moodle_url('/mod/assign/view.php', $params);

            // format HTML for output
            $output .= html_writer::start_tag('p');
            $output .= html_writer::tag('b', get_string('choosegradingaction', 'assign')).': ';
            $output .= html_writer::link($url, get_string('awardpoints', 'assignfeedback_points'));
            $output .= html_writer::end_tag('p');
        }

        return $output;
    }

    /**
     * Return a list of the grading actions performed by this plugin
     *
     * @return array The list of grading actions
     */
    public function get_grading_actions() {
        return array('awardpoints' => get_string('awardpoints', 'assignfeedback_points'));
    }

    /**
     * Print a sub page in this plugin
     *
     * @param string $action - The plugin action
     * @return string The response html
     */
    public function view_page($action) {
        switch ($action) {
            case 'awardpoints': return $this->award_points();
            default: return ''; // shouldn't happen !!
        }
    }

    /**
     * Award incremental points
     *
     * @return string The response html
     */
    public function award_points() {
        global $CFG, $DB, $OUTPUT, $PAGE, $USER;

        $name = 'awardpoints';
        $plugin = 'assignfeedback_points';
        $title = get_string($name, $plugin);

        require_capability('mod/assign:grade', $this->assignment->get_context());
        require_once($CFG->dirroot.'/mod/assign/feedback/points/awardpoints.form.php');

        $renderer = $this->assignment->get_renderer();
        $instance = $this->assignment->get_instance();
        $context  = $this->assignment->get_context();
        $course   = $this->assignment->get_course();
        $cm       = $this->assignment->get_course_module();

        // cancel if necessary - mimic is_cancelled() in "lib/formslib.php"
        if (optional_param('cancel', false, PARAM_RAW)) {
            $params = array('id' => $cm->id);
            redirect(new moodle_url('view.php', $params));
            return; // script finishes here
        }

        $params = array(
            'id'            => $cm->id,
            'plugin'        => 'points',
            'pluginsubtype' => 'assignfeedback',
            'action'        => 'viewpluginpage',
            'pluginaction'  => 'awardpoints'
        );
        $PAGE->set_url(new moodle_url('/mod/assign/view.php', $params));

        // process incoming formdata, and fetch output settings
        // $multipleusers, $groupid, $map, $feedback, $userlist
        list($multipleusers, $groupid, $map, $feedback, $userlist) = $this->process_formdata();

        $custom = (object)array(
            'cm'         => $cm,
            'cmid'       => $cm->id,
            'mapid'      => $map->id,
            'mapwidth'   => $map->mapwidth,
            'mapheight'  => $map->mapheight,
            'userwidth'  => $map->userwidth,
            'userheight' => $map->userheight,
            'groupid'    => $map->groupid,
            'context'    => $context,
            'courseid'   => $course->id,
            'assignid'   => $instance->id,
            'config'     => $this->get_all_config($plugin),
            'awardto'    => $userlist,
            'feedback'   => $feedback
        );
        $mform = new assignfeedback_points_award_points_form(null, $custom);

        $output = '';
        $output .= $renderer->render(new assign_header($instance, $context, false, $cm->id, $title));
        $output .= $renderer->render(new assign_form('awardpoints', $mform));
        $output .= $renderer->render_footer();

        return $output;
    }

    /**
     * Process the incoming formdata
     *
     * @return array($multipleusers, $groupid, $map, $feedback, $userlist)
     */
    public function process_formdata() {
        global $DB, $USER;

        $plugin   = 'assignfeedback_points';
        $instance = $this->assignment->get_instance();
        $cm       = $this->assignment->get_course_module();

        $ajax = optional_param('ajax', 0, PARAM_INT);
        $undo = optional_param('undo', 0, PARAM_INT);

        $config = $this->get_all_config($plugin);

        // cache the current time
        $time = time();

        // feedback string
        $feedback = null;

        // get multipleusers setting that was used to create incoming form data
        if ($ajax) {
            $multipleusers = 0; // i.e. one user at a time
        } else {
            $multipleusers = $this->get_config('multipleusers');
        }

        // handle "undo" request - i.e. cancel previously awarded points
        // get original groupid
        $groupid = optional_param('groupid', false, PARAM_INT);
        if ($groupid===false) {
            $groupid = groups_get_activity_group($cm, false);
            if ($groupid===false) {
                $groupid = 0;
            }
        }

        // get userlist for original $groupid
        $userlist = $this->assignment->list_participants($groupid, false);

        // valid name fields in user object
        $user_name_fields = self::get_all_user_name_fields();

        // format the display names for these users
        foreach ($userlist as $id => $user) {

            $defaultname = fullname($user);
            $displayname = $config->nameformat;

            foreach ($config->nametokens as $i => $nametoken) {
                $nametoken = (object)$nametoken;

                if (empty($nametoken->field)) {
                    continue;
                }
                $field = $nametoken->field;

                if (array_key_exists($field, $user_name_fields) && property_exists($user, $field)) {
                    $text = $user->$field;
                } else if ($field=='default') {
                    $text = $defaultname;
                } else {
                    continue; // shoudln't happen !!
                }

                if ($nametoken->split) {
                    $text = explode($nametoken->split, $text);
                    $start = ($nametoken->start - ($nametoken->start > 0 ? 1 : 0));
                    $count = ($nametoken->count ? $nametoken->count : count($text));
                    $text = implode('', array_splice($text, $start, $count));
                }

                if ($nametoken->romanize) {
                    $is_firstname = is_numeric(strpos($field, 'firstname'));
                    $force_standard = ($nametoken->romanize==self::ROMANIZE_FIX);
                    $text = self::fix_romanization($text, $is_firstname, $force_standard);
                }

                if ($nametoken->case) {
                    switch ($nametoken->case) {
                        case self::CASE_UPPER:  $text = self::textlib('strtoupper', $text); break;
                        case self::CASE_PROPER: $text = self::textlib('strtotitle', $text); break;
                        case self::CASE_LOWER:  $text = self::textlib('strtolower', $text); break;
                    }
                }

                if ($nametoken->length) {
                    $text = self::shorten_text($text, $nametoken->length, $nametoken->head, $nametoken->tail, $nametoken->join);
                }

                if ($nametoken->style) {
                    $text = html_writer::tag($nametoken->style, $text);
                }

                if ($nametoken->token) {
                    $search = '/\\b'.preg_quote($nametoken->token, '/').'\\b/u'; // u(nicode)
                    $displayname = preg_replace($search, $text, $displayname);
                }
            }

            if ($config->newlinetoken) {
                // https://pureform.wordpress.com/2008/01/04/matching-a-word-characters-outside-of-html-tags/
                $search = '/('.preg_quote($config->newlinetoken, '/').')+(?!([^<]+)?>)/u';
                $displayname = preg_replace($search, html_writer::empty_tag('br'), $displayname);
            }

            $userlist[$id]->defaultname = $defaultname;
            $userlist[$id]->displayname = $displayname;
        }

        if ($undo) {

            // get ids from incoming data
            $name = 'pointsid';
            $ids = self::optional_param_array($name, 0, PARAM_INT);
            if (is_scalar($ids)) {
                $ids = array($ids);
            }
            $ids = array_filter($ids);

            // initialize "feedback" details
            $feedback = (object)array('points'     => 0,
                                      'stringname' => '',
                                      'usercount'  => 0,
                                      'userlist'   => array());

            // undo the points
            foreach($ids as $id) {
                $params = array('id' => $id, 'assignid' => $instance->id);
                if ($points = $DB->get_record('assignfeedback_points', $params)) {

                    // cancel these $points
                    $points->cancelby = $USER->id;
                    $points->timemodified = $time;
                    $points->timecancelled = $time;
                    $DB->update_record('assignfeedback_points', $points);

                    // append "feedback" details
                    if (array_key_exists($points->awardto, $userlist)) {
                        $feedback->points = $points->points;
                        $feedback->userlist[] = $userlist[$points->awardto]->displayname;
                    }
                }
            }

            // set up feedback
            if ($feedback->usercount = count($feedback->userlist)) {
                $feedback->userlist = implode(', ', $feedback->userlist);
                switch (true) {
                    case ($feedback->points==1 && $feedback->usercount==1): $feedback->stringname = 'undoonepointoneuser'; break;
                    case ($feedback->points==1 && $feedback->usercount<>1): $feedback->stringname = 'undoonepointmanyusers'; break;
                    case ($feedback->points<>1 && $feedback->usercount==1): $feedback->stringname = 'undomanypointsoneuser'; break;
                    case ($feedback->points<>1 && $feedback->usercount<>1): $feedback->stringname = 'undomanypointsmanyusers'; break;
                    default: $feedback->stringname = 'awardnopoints'; // shouldn't happen !!
                }
                $feedback = get_string($feedback->stringname, $plugin, $feedback);
            } else {
                $feedback = null;
            }
        }

        // process incoming POST $data, if any
        if ($data = data_submitted()) {

            if ($ajax || $undo) {
                // don't save settings
            } else {
                $this->save_settings_allowmissing($data, true);
            }

            // get/update user map
            $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id, true);
            $mapid = $map->id;

            // get (x, y) coordinates
            $x = self::optional_param_array('awardtox', array(), PARAM_INT);
            $y = self::optional_param_array('awardtoy', array(), PARAM_INT);

            // register incoming points in assignfeedback_points table
            $mapaction     = optional_param('mapaction',       '', PARAM_ALPHA);
            $points        = optional_param('points',           0,   PARAM_INT);
            $commenttext   = optional_param('commenttextmenu', '',  PARAM_TEXT);
            $commentformat = optional_param('commentformat',    0,   PARAM_INT);

            // if commenttext was not selected from the drop down menu
            // try to get it from the text input element
            if ($commenttext=='') {
                $commenttext = optional_param('commenttext',   '', PARAM_TEXT);
            }

            // set up layouts, if required
            $name = 'layouts';
            $update_form_values = false;
            $update_dimensions  = false;
            $update_coordinates = false;
            switch (optional_param($name, '', PARAM_ALPHA)) {

                case 'load':
                    $this->update_coordinates($plugin, $map, $x, $y);
                    $table = $plugin.'_maps';
                    if ($loadid = optional_param($name.'loadid', 0, PARAM_INT)) {
                        if ($loadid==$mapid) {
                            // do nothing - this is the current map
                        } else {
                            $params = array('id' => $loadid, 'assignid' => $instance->id, 'userid' => $USER->id);
                            if ($DB->record_exists($table, $params)) {
                                $map = $DB->get_record($table, $params);
                                $mapid = $map->id;
                                $update_form_values = true;
                            }
                        }
                    }
                    break;

                case 'setup':
                    $update_form_values = true;
                    $update_dimensions  = true;

                    $mapwidth = 0;
                    $mapheight = 0;
                    $userwidth = $map->userwidth;
                    $userheight = $map->userheight;

                    $user_container_padding = 8;

                    $table = $plugin.'_coords';
                    if ($coords = $DB->get_records($table, array('mapid' => $mapid))) {

                        // remove any $coords for users that are
                        // no longer in the group using this map
                        $userids = array();
                        foreach ($coords as $coord) {
                            $userid = $coord->userid;
                            if (array_key_exists($userid, $userlist)) {
                                $userids[$userid] = true; // keep this $userid
                            } else {
                                $DB->delete_records($table, array('id' => $coord->id));
                                unset($coords[$coord->id]);
                            }
                        }

                        // add any users that are missing from $coords
                        $userids = array_diff_key($userlist, $userids);
                        foreach (array_keys($userids) as $userid) {
                            $coord = (object)array(
                                'mapid' => $map->id,
                                'userid' => $userid,
                                'x' => 0,
                                'y' => 0
                            );
                            $coord->id = $DB->insert_record($table, $coords);
                            $coords[$coord->id] = $coord;
                        }

                        // tidy up
                        unset($userids, $userid);
                    }

                    if ($count = count($coords)) {

                        switch (optional_param($name.'setup', '', PARAM_ALPHA)) {

                            case 'square':

                                switch (optional_param($name.'square', '', PARAM_ALPHANUM)) {
                                    case '100'    : $percent = 100; break;
                                    case  '75'    : $percent =  75; break;
                                    case  '50'    : $percent =  50; break;
                                    case  '25'    : $percent =  25; break;
                                    case 'percent': $percent = optional_param($name.'squarepercent', '', PARAM_INT); break;
                                    default       : $percent = 0;
                                }

                                // sanity check on $percent value
                                $percent = min(100, max(1, $percent));

                                // the number of users in a full square
                                $fullcount = ceil($count * (100 / $percent));

                                // set number of sides, $i_max
                                switch (true) {
                                    case ($percent > 75): $i_max = 4; break;
                                    case ($percent > 25): $i_max = 3; break;
                                    case ($percent >  0): $i_max = 1; break;
                                    default: $i_max = 0; // shouldn't happen !!
                                }

                                // calculate how many students on each side of the square
                                //     [0] : top side    (the most number of students)
                                //     [1] : left side   (a similar number to right side)
                                //     [2] : right side  (a similar number to left side)
                                //     [3] : bottom side (the remaining number of students)
                                $i = 0;
                                $counts = array();
                                for ($i=0; $i<$i_max; $i++) {
                                    if ($i==0) {
                                        $counts[$i] = min($count, floor($fullcount / $i_max));
                                    } else {
                                        $counts[$i] = ceil($count / ($i_max - $i));
                                    }
                                    $count -= $counts[$i];
                                }

                                // switch sides so students can be seated sequentially
                                //     [0] : left side   (a similar number to right side)
                                //     [1] : top side    (the most number of students)
                                //     [2] : right side  (a similar number to left side)
                                //     [3] : bottom side (the remaining number of students)
                                if ($i_max==1) {
                                    $counts[1] = 0;
                                }
                                $i = $counts[1];
                                $counts[1] = $counts[0];
                                $counts[0] = $i;

                                // adjust the coordinates for each student
                                for ($i=0; $i<$i_max; $i++) {
                                    $usercount = $counts[$i];
                                    if ($i==0) {
                                        $x = 0;
                                        $y = $usercount * $userheight;
                                        $mapwidth = $x;
                                        $mapheight = $y;
                                    }
                                    if ($i==2) {
                                        $x -= $userwidth;
                                        $y += $userheight;
                                    }
                                    for ($u=0; $u<$usercount; $u++) {
                                        if ($coord = array_shift($coords)) {
                                            $coord->x = $x;
                                            $coord->y = $y;
                                            $DB->update_record($table, $coord);
                                            $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                            $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                            switch ($i) {
                                                case 0: $y -= $userheight; break;
                                                case 1: $x += $userwidth;  break;
                                                case 2: $y += $userheight; break;
                                                case 3: $x -= $userwidth;  break;
                                            }
                                        }
                                    }
                                }
                                break;

                            case 'circle':
                                switch (optional_param($name.'circle', '', PARAM_ALPHANUM)) {
                                    case '100'    : $percent = 100; break;
                                    case  '75'    : $percent =  75; break;
                                    case  '50'    : $percent =  50; break;
                                    case  '25'    : $percent =  25; break;
                                    case 'percent': $percent = optional_param($name.'circlepercent', '', PARAM_INT); break;
                                    default       : $percent = 0;
                                }

                                // sanity check on $percent value
                                $percent = min(100, max(1, $percent));

                                // the number of users in a full circle
                                $usercount = ceil($count * (100 / $percent));

                                // calculate radius, $r, of a circle big enough to hold all users
                                // later we add $r to all calculated (x, y) coordinates
                                // Note: PHP prefers radians to degrees (360° = 2π radians)
                                $radians_per_user = deg2rad(360 / $usercount);
                                $r = sqrt(pow($userwidth, 2) + pow($userheight, 2)) / (2 * sin($radians_per_user / 2));

                                // if there is an odd number of users
                                // we want to rotate by a quarter turn (=90°)
                                $offset = (($usercount % 2) ? deg2rad(90) : 0);

                                for ($u=0; $u<$usercount; $u++) {
                                    if ($u < (($usercount - $count) / 2)) {
                                        continue;
                                    }
                                    if ($coord = array_shift($coords)) {
                                        $x = round($r * (1 + cos(($u * $radians_per_user) + $offset)));
                                        $y = round($r * (1 + sin(($u * $radians_per_user) + $offset)));
                                        $coord->x = $x;
                                        $coord->y = $y;
                                        $DB->update_record($table, $coord);
                                        $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                        $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                        $update = true;
                                    }
                                }
                                break;;

                            case 'lines':
                                $type     = optional_param($name.'linestype',    0, PARAM_INT);
                                $numtype  = optional_param($name.'linesnumtype', 0, PARAM_INT);
                                $numvalue = optional_param($name.'linesnumvalue', 0, PARAM_INT);

                                if ($numvalue==0) {
                                    switch ($type) {
                                        case 0: $numvalue = $custom->mapwidth / $custom->userwidth; break; // horizontal
                                        case 1: $numvalue = $custom->mapheight / $custom->userheight; break; // vertical
                                    }
                                }

                                // $line_max : number of lines
                                // $user_max : number of cols
                                switch ($numtype) {
                                    case 0: // number of lines
                                            $user_max = ceil($count / $numvalue);
                                            $line_max = $numvalue;
                                            break;
                                    case 1: // users per line
                                            $user_max = $numvalue;
                                            $line_max = ceil($count / $numvalue);
                                            break;
                                }

                                $update = true;

                                $padding = 24;
                                $mapwidth = 0;
                                $mapheight = 0;
                                $userwidth = $map->userwidth;
                                $userheight = $map->userheight;

                                for ($line=0; $line<$line_max; $line++) {
                                    switch ($type) {
                                        case 0: $x = 0;
                                                $y = ($userheight + $padding) * ($line_max - $line - 1);
                                                break;
                                        case 1: $x = ($userwidth + $padding) * $line;
                                                $y = $userheight * ($user_max - 1);
                                                break;
                                    }
                                    for ($user=0; $user<$user_max; $user++) {
                                        if ($coord = array_shift($coords)) {
                                            $coord->x = $x;
                                            $coord->y = $y;
                                            $DB->update_record($table, $coord);
                                            $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                            $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                            switch ($type) {
                                                case 0: $x += $userwidth;  break;
                                                case 1: $y -= $userheight; break;
                                            }
                                        }
                                    }
                                }
                                break;

                            case 'islands':
                                $type     = optional_param($name.'islandstype',     0, PARAM_INT);
                                $numtype  = optional_param($name.'islandsnumtype',  0, PARAM_INT);
                                $numvalue = optional_param($name.'islandsnumvalue', 0, PARAM_INT);

                                // $island_max : number of islands
                                // $user_max   : number of users per island
                                switch ($numtype) {
                                    case 0: // number of islands
                                            $user_max = ceil($count / $numvalue);
                                            $island_max = $numvalue;
                                            break;
                                    case 1: // users per island
                                            $user_max = $numvalue;
                                            $island_max = ceil($count / $numvalue);
                                            break;
                                }

                                $update = true;

                                $padding = 24;
                                $mapwidth = 0;
                                $mapheight = 0;
                                $userwidth = $map->userwidth;
                                $userheight = $map->userheight;

                                if ($type==0) {
                                    // calculate radius, $r, of a circle big enough to hold all users
                                    $radians_per_user = deg2rad(360 / $user_max);
                                    $r = sqrt(pow($userwidth, 2) + pow($userheight, 2)) / (2 * sin($radians_per_user / 2));
                                    $offset = 0;
                                    $offset += deg2rad(270) + ($radians_per_user / 2);
                                    //$offset -= ($radians_per_user * ($count % $user_max) / 2);
                                }

                                $p = array();
                                for ($u=0; $u<$user_max; $u++) {
                                    switch ($type) {
                                        case 0: // circle
                                            $x = round($r * (1 + cos(($u * $radians_per_user) + $offset)));
                                            $y = round($r * (1 + sin(($u * $radians_per_user) + $offset)));
                                            break;
                                        case 1: // square
                                            $x = (($u % 2)==0 ? 0 : $userwidth);
                                            $y = (intval($u / 2) * $userheight);
                                            break;
                                        default:
                                            continue; // shouldn't happen !!
                                    }
                                    $p[] = (object)array('x' => $x, 'y' => $y);
                                }

                                // compact the coordinates
                                while ($this->compact_coords('x', $userwidth, $p) ||
                                       $this->compact_coords('y', $userheight, $p));

                                // set island width/height
                                $islandwidth = 0;
                                $islandheight =  0;
                                $islandpadding = 24;
                                for ($u=0; $u<$user_max; $u++) {
                                    $islandwidth = max($islandwidth, $p[$u]->x + $userwidth);
                                    $islandheight = max($islandheight, $p[$u]->y + $userheight);
                                }

                                $x_start = 0;
                                $y_start = 0;
                                for ($i=0; $i<$island_max; $i++) {
                                    if ($x_start > ($map->mapwidth - $islandwidth)) {
                                        $x_start = 0;
                                        $y_start += ($islandheight + $islandpadding);
                                    }
                                    if ($type==0 && ($i+1)==$island_max) {
                                        $segment = (($count % $user_max) / 2);
                                    } else {
                                        $segment = 0;
                                    }
                                    for ($u=0; $u<$user_max; $u++) {
                                        if ($segment && (($u+1) > $segment) && (($u+1) <= ($user_max - $segment))) {
                                            continue;
                                        }
                                        $x = $x_start + $p[$u]->x;
                                        $y = $y_start + $p[$u]->y;
                                        if ($coord = array_shift($coords)) {
                                            $coord->x = $x;
                                            $coord->y = $y;
                                            $DB->update_record($table, $coord);
                                            $mapwidth = max($mapwidth, $x + $userwidth + $user_container_padding);
                                            $mapheight = max($mapheight, $y + $userheight + $user_container_padding);
                                            $update = true;
                                        }
                                    }
                                    $x_start += ($islandwidth + $islandpadding);
                                }
                                break;
                        }
                    }
                    break;

                case 'save' :
                    $table = $plugin.'_maps';
                    if ($name = optional_param($name.'savename', '', PARAM_TEXT)) {
                        if ($name==$map->name) {
                            $name = ''; // same name as current map
                        }
                    }
                    if ($name) {
                        $i = 1;
                        while ($DB->record_exists($table, array('assignid' => $instance->id, 'userid' => $USER->id, 'name' => $name))) {
                            $i++;
                            if ($i==2) {
                                $name = "$name ($i)";
                            } else {
                                $name = preg_replace('/\([0-9]+\)$/', "($i)", $name);
                            }
                        }
                        unset($map->id);
                        $map->name = $name;
                        $map->id = $DB->insert_record($table, $map);
                        $mapid = $map->id;
                        $update_form_values = true;
                        $update_dimensions  = true;
                        $update_coordinates = true;
                    }
                    break;

                case 'delete':
                    $table = $plugin.'_maps';
                    if ($deleteid = optional_param($name.'deleteid', 0, PARAM_INT)) {
                        $params = array('id' => $deleteid, 'assignid' => $instance->id, 'userid' => $USER->id);
                        if ($DB->record_exists($table, $params)) {
                            $DB->delete_records($table, $params);
                            $DB->delete_records($plugin.'_coords', array('mapid' => $id));
                            // if current map was deleted, get a new current map
                            if ($deleteid==$mapid) {
                                $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
                                $mapid = $map->id;
                                $update_form_values = true;
                            }
                        }
                    }
                    break;

                default:
                    if ($ajax) {
                        $update_coordinates = true;
                    }

            } // end switch "layouts"

            // prevent calculated values being
            // overwritten by values from browser
            if ($update_form_values) {
                $_POST['mapid'] = $mapid;
                unset($_POST['awardtox']);
                unset($_POST['awardtoy']);
                unset($_POST['mapwidth']);
                unset($_POST['mapheight']);
            }
            if ($update_coordinates) {
                $this->update_coordinates($plugin, $map, $x, $y);
            }
            if ($update_dimensions) {
                $this->update_dimensions($plugin, $map, $mapwidth, $mapheight);
            }

            // remove all layout settings because
            // we do not want them in the outgoing form
            $names = preg_grep('/^layout/', array_keys($_POST));
            foreach ($names as $name) {
            //    unset($_POST[$name]);
            }

            // initialize "feedback" details
            $feedback = (object)array('points'     => $points,
                                      'stringname' => '',
                                      'usercount'  => 0,
                                      'userlist'   => array());

            // setup undo, if required
            if ($undo==0) {

                // set undo comment text
                $undo_commenttext = get_string('undo', $plugin);
                if ($commenttext) {
                    $undo_commenttext .= ": $commenttext";
                }

                // initialize parameters for "undo" link
                $undoparams = array('undo'          => 1,
                                    'id'            => $cm->id,
                                    'plugin'        => 'points',
                                    'pluginsubtype' => 'assignfeedback',
                                    'action'        => 'viewpluginpage',
                                    'pluginaction'  => 'awardpoints',
                                    'sesskey'       => sesskey(),
                                    'group'         => $groupid,
                                    'groupid'       => $groupid,
                                    'mapid'         => $mapid,
                                    'pointsid'      => array(),
                                    'multipleusers' => $multipleusers,
                                    'commenttext'   => $undo_commenttext);
            }

            // do we want to send notifications to students ?
            $name = 'sendnotifications';
            $sendnotifications = $this->get_config($name);

            if ($sendnotifications===false) {
                $name = 'sendstudentnotifications';
                if (isset($instance->$name)) {
                    $sendnotifications = $instance->$name;
                } else {
                    $sendnotifications = get_config('assign', $name);
                }
            }

            // disable notifications during development
            $sendnotifications = 0;

            $name = 'pointstype';
            $pointstype = $this->get_config($name);

            // get advanced grading data, if any
            $gradingdata = new stdClass();
            $context = $this->assignment->get_context();
            $gradingmanager = get_grading_manager($context, 'mod_assign', 'submissions');
            if ($gradingmethod = $gradingmanager->get_active_method()) {
                $name = 'advancedgradinginstanceid';
                $gradingdata->$name = optional_param($name, 0, PARAM_INT);
                $name = 'advancedgrading';
                $gradingdata->$name = self::optional_param_array($name, array(), PARAM_TEXT);
                if ($gradingmethod=='guide') {
                    $name = 'showmarkerdesc';
                    $gradingdata->$name = optional_param($name, true, PARAM_BOOL);
                    $name = 'showstudentdesc';
                    $gradingdata->$name = optional_param($name, true, PARAM_BOOL);
                }
                $pointstype = 1; // total points
            }

            $name = 'awardto';
            $userids = self::optional_param_array($name, array(), PARAM_INT);
            if (is_scalar($userids)) {
                $userids = array($userids => 1);
            }

            // add points for each user
            foreach ($userids as $userid => $checked) {

                if ($checked==0) {
                    continue; // shouldn't happen !!
                }

                // get associated assign_grades record id
                $params = array('assignment' => $instance->id,
                                'userid'     => $userid);
                if ($gradeassign = $DB->get_records('assign_grades', $params, 'attemptnumber DESC')) {
                    $gradeassign = reset($gradeassign); // most recent assignment grade
                } else {
                    $gradeassign = (object)array(
                        'assignment'    => $instance->id,
                        'userid'        => $userid,
                        'timecreated'   => $time,
                        'timemodified'  => $time,
                        'grader'        => $USER->id,
                        'grade'         => 0.00,
                        'attemptnumber' => 0
                    );
                    $gradeassign->id = $DB->insert_record('assign_grades', $gradeassign);
                }

                // add new assignfeedback_points record
                $assignfeedbackpoints = (object)array(
                    'assignid'      => $instance->id,
                    'gradeid'       => $gradeassign->id,
                    'awardby'       => $USER->id,
                    'awardto'       => $userid,
                    'points'        => $points,
                    'pointstype'    => $pointstype,
                    'latitude'      => 0,
                    'longitude'     => 0,
                    'commenttext'   => $commenttext,
                    'commentformat' => $commentformat,
                    'timecreated'   => $time,
                    'timeawarded'   => $time,
                    'timemodified'  => $time
                );
                $assignfeedbackpoints->id = $DB->insert_record('assignfeedback_points', $assignfeedbackpoints);

                // append this pointsid to the "undo" parameters
                if ($undo==0) {
                    $undoparams['pointsid'][] = $assignfeedbackpoints->id;
                }

                // append this user to "feedback" details
                $feedback->userlist[] = $userlist[$userid]->displayname;

                if ($pointstype==self::POINTSTYPE_SUM) { // incremental points
                    $params = array('assignid'   => $instance->id,
                                    'awardto'    => $userid,
                                    'pointstype' => $pointstype);
                    $grade = $DB->get_field('assignfeedback_points', 'SUM(points)', $params);
                    if (empty($grade)) {
                        $grade = 0.0;
                    }
                } else {
                    $grade = $points;
                }

                $gradedata = $this->get_grade_data($gradeassign, $grade, $sendnotifications, $gradingdata);
                $this->assignment->save_grade($userid, $gradedata);
            }

            if ($feedback->usercount = count($feedback->userlist)) {
                $feedback->userlist = implode(', ', $feedback->userlist);
                switch (true) {
                    case ($feedback->points==1 && $feedback->usercount==1): $feedback->stringname = 'awardonepointoneuser'; break;
                    case ($feedback->points==1 && $feedback->usercount<>1): $feedback->stringname = 'awardonepointmanyusers'; break;
                    case ($feedback->points<>1 && $feedback->usercount==1): $feedback->stringname = 'awardmanypointsoneuser'; break;
                    case ($feedback->points<>1 && $feedback->usercount<>1): $feedback->stringname = 'awardmanypointsmanyusers'; break;
                    default: $feedback->stringname = 'awardnopoints'; // shouldn't happen !!
                }
                $feedback = get_string($feedback->stringname, $plugin, $feedback);
                if ($undo==0 && count($undoparams['pointsid'])) {
                    if (count($undoparams['pointsid'])==1) {
                        $undoparams['pointsid'] = reset($undoparams['pointsid']);
                    } else {
                        foreach ($undoparams['pointsid'] as $i => $id) {
                            $undoparams['pointsid['.$i.']'] = $id;
                        }
                        unset($undoparams['pointsid']);
                    }
                    $link = new moodle_url('/mod/assign/view.php', $undoparams);
                    $link = html_writer::link($link, get_string('undo', $plugin), array('id' => 'undolink'));
                    $feedback .= " $link";
                }
            } else {
                $feedback = '';
            }

            // get latest groupid (it may have changed)
            $groupid = groups_get_activity_group($cm, true);
            if ($groupid===false) {
                $groupid = 0;
            }

            if ($groupid != $map->groupid) {
                $userlist = $this->assignment->list_participants($groupid, false);
                $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
                // it is necessary to adjust $_POST so that old map
                // coordinates are not used for new user maps in
                // _process_submission() in "lib/formslib.php"
                unset($_POST['awardtox']);
                unset($_POST['awardtoy']);
                unset($_POST['groupid']);
                unset($_POST['mapid']);
                unset($_POST['mapwidth']);
                unset($_POST['mapheight']);
                unset($_POST['userwidth']);
                unset($_POST['userheight']);
                unset($_POST['mapprivacy']);
            }
        } else {
            $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
        }

        if ($feedback===null) {
            $feedback = '';
        } else {
            $feedback = html_writer::tag('span', $feedback, array('id' => 'feedback'));
        }

        return array($multipleusers, $groupid, $map, $feedback, $userlist);
    }

    /**
     * get_grade_data
     *
     * @param  object  $gradeassign
     * @param  decimal $grade
     * @param  boolean $sendnotifications
     * @param  object  $gradingdata required by "rubric" and "guide" grading methods
     * @return object
     */
    protected function get_grade_data($gradeassign, $grade, $sendnotifications, $gradingdata) {

        $gradedata = (object)array(
            'id'              => $gradeassign->id,
            'grade'           => $grade,
            'applytoall'      => 0,
            'attemptnumber'   => $gradeassign->attemptnumber,
            'sendstudentnotifications' => $sendnotifications
        );

        foreach (get_object_vars($gradingdata) as $name => $value) {
            $gradedata->$name = $value;
        }

        // the "assignment->save_grade()" method
        // will call the "save()" method of each feedback plugin,
        // so we must ensure that the $gradedata object includes
        // the expected properties for each activated feedback plugin
        $plugins = $this->assignment->get_feedback_plugins();
        foreach ($plugins as $plugin) {
            if (! $plugin->is_enabled()) {
                continue;
            }
            if (! $plugin->is_visible()) {
                continue;
            }
            switch ($plugin->get_type()) {

                case 'comments':
                    $gradedata->assignfeedbackcomments_editor = array(
                        'text' => '',
                        'format' => FORMAT_HTML
                    );
                    break;

                case 'editpdf':
                    $gradedata->editpdf_source_userid = 0;
                    break;

                case 'file':
                    $gradedata->files_0_filemanager = null;
                    break;
            }
        }

        return $gradedata;
    }

    /**
     * compact_coords
     *
     * @param string  $direction "x" or "y"
     * @param integer $size of tile in this $direction
     * @param array   $coords
     * @return void
     */
    public function compact_coords($direction, $size, $coords) {
        usort($coords, array($this, 'usort_coords_'.$direction));
        $gap = 0;
        $previous = 0;
        foreach ($coords as $c => $coord) {
            if ($coord->$direction > $previous) {
                $gap += ($coord->$direction - $previous);
            }
            $previous = ($coord->$direction + $size);
            if ($gap) {
                $coords[$c]->$direction -= $gap;
            }
        }
        return ($gap > 0);
    }

    /**
     * usort_coords_x
     *
     * @param object  $a
     * @param object  $b
     * @return integer -1 : $a < $b, 0 : $a==$b, 1 : $a > $b
     */
    public function usort_coords_x($a, $b) {
        if ($a->x < $b->x) {
            return -1;
        }
        if ($a->x > $b->x) {
            return 1;
        }
        if ($a->y < $b->y) {
            return -1;
        }
        if ($a->y > $b->y) {
            return 1;
        }
        return 0; // shouldn't happen !!
    }

    /**
     * usort_coords_y
     *
     * @param object  $a
     * @param object  $b
     * @return integer -1 : $a < $b, 0 : $a==$b, 1 : $a > $b
     */
    public function usort_coords_y($a, $b) {
        if ($a->y < $b->y) {
            return -1;
        }
        if ($a->y > $b->y) {
            return 1;
        }
        if ($a->x < $b->x) {
            return -1;
        }
        if ($a->x > $b->x) {
            return 1;
        }
        return 0; // shouldn't happen !!
    }

    /**
     * This plugin does not save through the normal interface so this returns false.
     *
     * @param stdClass $grade The grade.
     * @param stdClass $data  Form data from the feedback form.
     * @return boolean - False
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        return false;
    }

    /**
     * return a string so it can be consumed by webservices.
     *
     * @param stdClass The assign_grade data
     * @param bool $showviewlink Modifed to return whether or not to show a link to the full submission/feedback
     * @return string - return a string representation of the submission in full
     */
    public function view_summary(stdClass $grade, &$showviewlink) {
        return $this->text_for_gradebook($grade);
    }

    /**
     * If this plugin adds to the gradebook comments field,
     * it must specify the format of the text of the comment
     *
     * Sadly, only one feedback plugin can push comments to the gradebook
     * and that is a site-wide setting on the assignment settings page.
     *
     * @param stdClass $grade record from assign_grades table
     * @return int
     */
    public function format_for_gradebook(stdClass $grade) {
        return FORMAT_MOODLE;
    }

    /**
     * If this plugin adds to the gradebook comments field,
     * it must format the text of the comment
     *
     * Only one feedback plugin can push comments to the gradebook
     * and that is chosen via the settings page for the assignment module.
     * Site admin -> Plugins ->︎ Activity modules ->︎ Assignment ->︎ Assignment settings
     * Feedback plugin (assign | feedback_plugin_for_gradebook)
     *
     * @param stdClass $grade record from assign_grades table
     * @return string
     */
    public function text_for_gradebook(stdClass $grade) {
        global $DB, $PAGE;
        static $firsttime = true;
        $plugin = 'assignfeedback_points';

        $count = 0;
        $total = 0;
        $list = array();

        if ($this->assignment->has_instance()) {
            $pointstype = $this->get_config('pointstype');
            $params = array('assignid'   => $this->assignment->get_instance()->id,
                            'awardto'    => $grade->userid,
                            'pointstype' => $pointstype,
                            'timecancelled' => 0);
            if ($awards = $DB->get_records('assignfeedback_points', $params, 'timeawarded ASC')) {
                if ($pointstype==self::POINTSTYPE_NEWEST) {
                    // total points - use most recent award only
                    $awards = array_slice($awards, -1, 1, true);
                }
                $maxcommentlength = 16;
                $dateformat = get_string('strftimerecent', 'langconfig');
                foreach ($awards as $award) {

                    $count++;
                    $total += $award->points;

                    // format each component
                    $award->timeawarded = userdate($award->timeawarded, $dateformat);
                    $award->points      = number_format($award->points);
                    $award->title       = format_text($award->commenttext, $award->commentformat);
                    $award->title       = strip_tags($award->title); // neutralize title text

                    // truncate long comments, if necessary
                    // (the full comment is used as the title)
                    if ($this->textlib('strlen', $award->title) <= $maxcommentlength) {
                        $award->comment = $award->title;
                    } else {
                        $award->comment = $this->textlib('substr', $award->title, 0, $maxcommentlength).' ...';
                    }

                    // wrap each component in a span with an appropriate CSS class
                    $award->timeawarded = html_writer::tag('span', $award->timeawarded, array('class' => 'timeawarded'));
                    $award->points      = html_writer::tag('span', $award->points,      array('class' => 'points'));
                    $award->comment     = html_writer::tag('span', $award->comment,     array('class' => 'comment'));

                    $feedback = get_string('textforgradebook', $plugin, $award);
                    $list[] = html_writer::tag('li', $feedback, array('class' => 'feedback', 'title' => $award->title));
                }
            }
        }
        if ($count==0) {
            return '';
        } else {

            $js = '';
            if ($firsttime) {
                $firsttime = false;
                $js .= '<script type="text/javascript">'."\n";
                $js .= "//<![CDATA[\n";
                $js .= "function toggleawardlist(img, listid) {\n";
                $js .= "    var obj = document.getElementById(listid);\n";
                $js .= "    if (obj) {\n";
                $js .= "        if (obj.style.display=='none') {\n";
                $js .= "            obj.style.display = '';\n";
                $js .= "            img.src = img.src.replace('plus','minus');\n";
                $js .= "        } else {\n";
                $js .= "            obj.style.display = 'none';\n";
                $js .= "            img.src = img.src.replace('minus','plus');;\n";
                $js .= "        }\n";
                $js .= "    }\n";
                $js .= "    return false;\n";
                $js .= "}\n";
                $js .= "//]]>\n";
                $js .= "</script>\n";
            }

            $listid = 'awards_'.$grade->userid;

            // format count and average
            $average = round($total / $count, 1);
            $average = array('count' => $count, 'average' => $average);
            $average = get_string('averagepoints', $plugin, (object)$average);

            // append icon to expand list
            $img = $PAGE->theme->pix_url('t/switch_plus', 'core')->out();
            $img = array('src' => $img, 'onclick' => 'toggleawardlist(this, "'.$listid.'")');
            $img = ' '.html_writer::empty_tag('img', $img);
            $average = html_writer::tag('p', "$average $img", array('class' => 'averagepoints'));

            // convert list of awards to HTML
            $list = html_writer::tag('ol', implode($list), array('id'    => $listid,
                                                                 'class' => 'awards',
                                                                 'style' => 'display:none;'));

            // return formatted $average and $list
            return html_writer::tag('div', $js.$average.$list, array('class' => 'assignfeedback_points'));
        }
    }

    /**
     * The assignment has been deleted - remove the plugin specific data
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        if ($this->assignment->has_instance()) {
            $assign = $this->assignment->get_instance();
            $params = array('assignid' => $assign->id);
            if ($mapids = $DB->get_records('assignfeedback_points_maps', $params, 'id', 'id,assignid')) {
                $mapids = array_keys($mapids);
                $DB->delete_records_list('assignfeedback_points_coords', 'mapid', $mapids);
                $DB->delete_records_list('assignfeedback_points_maps',   'id',    $mapids);
            }
            $DB->delete_records('assignfeedback_points', $params);
        }
        return true;
    }

    /**
     * get_usermap
     *
     * @param object  $cm
     * @param integer $userid
     * @param integer $groupid
     * @param integer $instanceid id of assign(ment) record
     * @param boolean $update (optional, default=FALSE)
     * @return object $map record from "assignfeedback_points_maps" table
     */
    protected function get_usermap($cm, $userid, $groupid, $instanceid, $update=false) {
        global $DB;

        $table = 'assignfeedback_points_maps';
        if ($mapid = optional_param('mapid', 0, PARAM_INT)) {
            $params = array('id' => $mapid,
                            'userid' => $userid,
                            'groupid' => $groupid,
                            'assignid' => $instanceid);
            $map = $DB->get_record($table, $params);
        } else {
            $map = false;
        }

        if ($map==false) {
            $params = array('userid' => $userid,
                            'groupid' => $groupid,
                            'assignid' => $instanceid);
            if ($map = $DB->get_records($table, $params, 'context DESC, privacy ASC', '*')) {
                $map = reset($map); // i.e. the most private user-map
            }
        }
        if ($map==false) {
            $mapname = get_string('default');
            if ($groupid) {
                $mapname .= ': '.groups_get_group_name($groupid);
            }
            $map = (object)array(
                'name'       => $mapname,
                'userid'     => $userid,
                'groupid'    => $groupid,
                'assignid'   => $instanceid,
                'context'    => CONTEXT_MODULE,
                'mapwidth'   => 0,
                'mapheight'  => 0,
                'userwidth'  => 0,
                'userheight' => 0,
                'privacy'    => 0
            );
        }

        // update map details
        if ($update) {
            $map->name       = optional_param('mapname',    $map->name,      PARAM_TEXT);
            $map->context    = optional_param('mapcontext', $map->context,    PARAM_INT);
            $map->mapwidth   = optional_param('mapwidth',   $map->mapwidth,   PARAM_INT);
            $map->mapheight  = optional_param('mapheight',  $map->mapheight,  PARAM_INT);
            $map->userwidth  = optional_param('userwidth',  $map->userwidth,  PARAM_INT);
            $map->userheight = optional_param('userheight', $map->userheight, PARAM_INT);
            $map->privacy    = optional_param('mapprivacy', $map->privacy,    PARAM_INT);
        }

        if (isset($map->id)) {
            $DB->update_record($table, $map);
        } else {
            $map->id = $DB->insert_record($table, $map);
        }

        return $map;
    }

    /**
     * update_dimensions
     *
     * @param object  $map
     * @param array   $x coordinates
     * @param array   $y coordinates
     * @param integer $mapwidth
     * @param integer $mapheight
     * @return void but may update DB table: assignfeedback_points_coords
     */
    protected function update_dimensions($plugin, $map, $mapwidth, $mapheight) {
        global $DB;
        $table = $plugin.'_maps';
        $map->mapwidth = $mapwidth;
        $map->mapheight = $mapheight;
        $DB->update_record($table, $map);
    }

    /**
     * update_coordinates
     *
     * @param object  $map
     * @param array   $x coordinates
     * @param array   $y coordinates
     * @return void but may update DB table: assignfeedback_points_coords
     */
    protected function update_coordinates($plugin, $map, $x, $y) {
        global $DB;
        $table = $plugin.'_coords';
        foreach (array_keys($x) as $userid) {
            if (isset($y[$userid])) {
                $params = array('mapid' => $map->id, 'userid' => $userid);
                if ($coords = $DB->get_records($table, $params)) {
                    $coords = reset($coords);
                } else {
                    $coords = (object)$params;
                }
                $coords->x = $x[$userid];
                $coords->y = $y[$userid];
                if (isset($coords->id)) {
                    $coords->id = $DB->update_record($table, $coords);
                } else {
                    $coords->id = $DB->insert_record($table, $coords);
                }
            }
        }
    }

    /**
     * fix_romanization
     *
     * @return string
     */
    static public function fix_romanization($name, $is_firstname, $force_standard=true, $use_macrons=true) {
        $name= self::textlib('strtolower', $name);

         // check that this looks like a romanized Japanese name
        if (preg_match(self::JAPANESE_NAME_STRING, $name)) {

            if ($force_standard) {
                // fix "si", "ti", "tu", "sy(a|u|o)", "jy(a|u|o)" and "nanba"
                $replace = array(
                    'si' => 'shi', 'ti' => 'chi', 'tu' => 'tsu', 'sy' => 'sh', 'jy' =>'j', 'nb' => 'mb'
                );
                $name = strtr($name, $replace);

                // fix "hu" (but not "chu" or "shu") e.g. hujimura
                $name = preg_replace('/(?<![cs])hu/', 'fu', $name);

                if ($is_firstname) {
                    // kiyou(hei)
                    // shiyou(go|hei|ta|tarou)
                    // shiyun(suke|ya), shiyuu(ji|ta|tarou|ya)
                    // riyou(ga|ki|suke|ta|tarou|ya)
                    // riyuu(ichi|ki|ta|ma|saku|sei|shi|zou)
                    $replace = array(
                        'kiyou'  => 'kyou',
                        'shiyou' => 'shou', 'jiyou' => 'jou',
                        'shiyuu' => 'shuu', 'jiyuu' => 'juu',
                        'shiyun' => 'shun', 'jiyun' => 'jun',
                        'riyou'  => 'ryou', 'riyuu' => 'ryuu',
                    );
                } else {
                    // gasshiyou (GASSHŌ)
                    // mukaijiyou (MUKAIJŌ)
                    // chiya(da|ta)ani (not UCHIYAMA or TSUCHIYA)
                    $replace = array(
                        'shiyou'    => 'shou',
                        'jiyou'     => 'jou',
                        'chiyatani' => 'chatani',
                        'chiyadani' => 'chadani'
                    );
                }
                $name = strtr($name, $replace);
            }

            if ($use_macrons) {
                $replace = array(
                    'noue' => 'noue', 'kaaki' => 'kaaki',
                    'aa' => 'ā', 'ii' => 'ī', 'uu' => 'ū', 'ee' => 'ē', 'oo' => 'ō', 'ou' => 'ō'
                );
            } else {
                $replace = array(
                    'ooa' => "oh'a", 'ooi' => "oh'i", 'oou' => "oh'u", 'ooe' => "oh'e", 'ooo' => "oh'o",
                    'too' => 'to', 'oo' => 'oh', 'ou' => 'o', 'uu' => 'u'
                );
            }
            $name = strtr($name, $replace);
        }
        return $name;

    }

    /**
     * get_defaultvalues
     *
     * @return array(name => default)
     */
    static public function get_defaultvalues($plugin) {
        return array('pointstype'      => 0,
                     'minpoints'       => 1,
                     'increment'       => 1,
                     'maxpoints'       => 2,
                     'pointsperrow'    => 0,
                     'sendimmediately' => 1,
                     'multipleusers'   => 0,
                     'showelement'     => 0,
                     'showpicture'     => 0,
                     'nameformat'      => '',
                     'newlinetoken'     => get_string('newlinetokendefault', $plugin),
                     'nametokens'      => array(), // base64_encode(serialize(array()))
                     'showpointstoday' => 1,
                     'showpointstotal' => 1,
                     'showscorerubric' => 0,
                     'showscoreguide'  => 0,
                     'showgradeassign' => 0,
                     'showgradecourse' => 0,
                     'showcomments'    => 1,
                     'showfeedback'    => 0,
                     'showlink'        => 1,
                     'allowselectable' => 1);
    }

    /**
     * get_nametoken_setting_types
     *
     * @return array($name => $paramtype)
     */
    static function get_nametoken_setting_types() {
        return array('token'    => PARAM_TEXT,
                     'field'    => PARAM_ALPHANUM,
                     'split'    => PARAM_TEXT,
                     'start'    => PARAM_INT,
                     'count'    => PARAM_INT,
                     'length'   => PARAM_INT,
                     'head'     => PARAM_INT,
                     'join'     => PARAM_TEXT,
                     'tail'     => PARAM_INT,
                     'style'    => PARAM_ALPHA,
                     'case'     => PARAM_INT,
                     'romanize' => PARAM_INT);
    }

    /**
     * get_nametoken_setting_types
     *
     * @return array($name => $paramtype)
     */
    static function get_nametoken_setting_defaults($strman, $plugin) {
        $defaults = self::get_nametoken_setting_types();
        foreach ($defaults as $name => $type) {
            $defaults[$name] = self::get_formfield_default($strman, $plugin, 'nametoken'.$name, $type);
        }
        return $defaults;
    }

    /**
     * get_formfield_default
     *
     * @param object  $strman
     * @param string  $plugin
     * @param string  $name
     * @param integer $type PARAM_xxx
     * @return mixed the default value for a form field
     */
    static function get_formfield_default($strman, $plugin, $name, $type) {
        if ($type==PARAM_ALPHA || $type==PARAM_ALPHANUM || $type==PARAM_TEXT) {
            if ($strman->string_exists($name.'default', $plugin)) {
                return get_string($name.'default', $plugin);
            }
            return '';
        }
        if ($type==PARAM_INT || $type==PARAM_BOOL) {
            return 0;
        }
        return null; // shouldn't happen !!
    }

    /**
     * get_text_options
     *
     * return an array of options for string text boxes
     * suitable for use in a Moodle form
     *
     * @return array of form element options
     */
    static public function get_text_options($size=4) {
        return array('size' => $size, 'maxsize' => $size);
    }

    /**
     * get_pointstype_options
     *
     * return an array of formatted pointstype options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_pointstype_options() {
        $plugin = 'assignfeedback_points';
        return array(self::POINTSTYPE_SUM     => get_string('pointstypesum',     $plugin),
                     self::POINTSTYPE_NEWEST  => get_string('pointstypenewest',  $plugin),
                     self::POINTSTYPE_MAXIMUM => get_string('pointstypemaximum', $plugin),
                     self::POINTSTYPE_AVERAGE => get_string('pointstypeaverage', $plugin),
                     self::POINTSTYPE_MEDIAN  => get_string('pointstypemedian',  $plugin),
                     self::POINTSTYPE_MODE    => get_string('pointstypemode',    $plugin),
                     self::POINTSTYPE_MINIMUM => get_string('pointstypeminimum', $plugin),
                     self::POINTSTYPE_OLDEST  => get_string('pointstypeoldest',  $plugin));
    }

    /**
     * get_nametoken_options
     *
     * return an array of formatted name field options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_nametoken_field_options() {
        $fields = array('' => '', 'default' => '');
        $fields += self::get_all_user_name_fields();
        $fields['username'] = '';
        foreach (array_keys($fields) as $field) {
            if ($field) {
                $fields[$field] = get_string($field);
            }
        }
        $defaultnames = (object)$fields;
        $defaultnames = fullname($defaultnames);
        $fields['default'] .= " ($defaultnames)";
        return $fields;
    }

    /**
     * get_nametoken_style_options
     *
     * return an array of formatted style options
     * suitable for use in a Moodle form
     *
     * @return array of name case options
     */
    static public function get_nametoken_style_options() {
        $plugin = 'assignfeedback_points';
        return array('' => get_string('none'),
                     'b' => 'b',
                     'i' => 'i',
                     'u' => 'u',
                     'em' => 'em',
                     'strong' => 'strong',
                     'small' => 'small',
                     'big' => 'big',
                     'sup' => 'sup',
                     'sub' => 'sub',
                     'tt'  => 'tt',
                     'var' => 'var');
    }

    /**
     * get_namecase_options
     *
     * return an array of name case options
     * suitable for use in a Moodle form
     *
     * @return array of name case options
     */
    static public function get_nametoken_case_options() {
        $plugin = 'assignfeedback_points';
        return array(self::CASE_ORIGINAL => get_string('originalcase', $plugin),
                     self::CASE_PROPER   => get_string('propercase',   $plugin),
                     self::CASE_LOWER    => get_string('lowercase',    $plugin),
                     self::CASE_UPPER    => get_string('uppercase',    $plugin));
    }

    /**
     * get_nameromanize_options
     *
     * return an array of formatted romanization options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_nametoken_romanize_options() {
        $plugin = 'assignfeedback_points';
        return array(self::ROMANIZE_NO  => get_string('no'),
                     self::ROMANIZE_YES => get_string('yes'),
                     self::ROMANIZE_FIX => get_string('fix', $plugin));
    }

    /**
     * get_showfeedback_options
     *
     * return an array of formatted showfeedback options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_showfeedback_options() {
        $plugin = 'assignfeedback_points';
        return array(0 => get_string('no'),
                     1 => get_string('yes'),
                     2 => get_string('automatically', $plugin));
    }

    /**
     * requires_jquery
     *
     * add standard jquery base to this page
     *
     * @param array extra JS $scripts to be added to this $PAGE
     * @param string name of the this $plugin e.g. assignfeedback_points
     * @return void, but will add several JS files to this $PAGE
     */
    static public function requires_jquery($scripts, $plugin) {
        global $PAGE;
        if (method_exists($PAGE->requires, 'jquery')) {
            // Moodle >= 2.5
            $PAGE->requires->jquery();
            $PAGE->requires->jquery_plugin('ui');
            $PAGE->requires->jquery_plugin('ui-css');
            $PAGE->requires->jquery_plugin('ui.touch-punch', $plugin);
        } else {
            // Moodle <= 2.4
            $jquery = '/mod/assign/feedback/points/jquery';
            $PAGE->requires->css($jquery.'/jquery-ui.css');
            $PAGE->requires->js($jquery.'/jquery.js', true);
            $PAGE->requires->js($jquery.'/jquery-ui.js', true);
            $PAGE->requires->js($jquery.'/jquery-ui.touch-punch.js', true);
        }
        if (is_scalar($scripts)) {
            $scripts = array($scripts);
        }
        foreach ($scripts as $script) {
            $PAGE->requires->js($script);
        }
    }

    /**
     * get_all_user_name_fields
     *
     * return an array of user field names
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_all_user_name_fields() {
       $fields = array('firstname' => 'firstname',
                       'lastname'  => 'lastname');
       if (function_exists('get_all_user_name_fields')) {
           // Moodle >= 2.6
           $fields += get_all_user_name_fields();
       }
       return $fields;
    }

    /**
     * trim_text
     *
     * @param   string   $text
     * @param   integer  $textlength (optional, default=28)
     * @param   integer  $headlength (optional, default=10)
     * @param   integer  $taillength (optional, default=10)
     * @return  string
     */
    static public function shorten_text($text, $textlength=28, $headlength=10, $taillength=10, $join=' ... ') {
        $strlen = self::textlib('strlen', $text);
        if ($strlen > $textlength) {
            $head = self::textlib('substr', $text, 0, $headlength);
            $tail = self::textlib('substr', $text, $strlen - $taillength, $taillength);
            $text = $head.$join.$tail;
        }
        return $text;
    }

    /**
     * textlib
     *
     * a wrapper method to offer consistent API for textlib class
     * in Moodle 2.0 - 2.1, $textlib is first initiated, then called
     * in Moodle 2.2 - 2.5, we use only static methods of the "textlib" class
     * in Moodle >= 2.6, we use only static methods of the "core_text" class
     *
     * @param string $method
     * @param mixed any extra params that are required by the textlib $method
     * @return result from the textlib $method
     * @todo Finish documenting this function
     */
    static public function textlib() {
        if (class_exists('core_text')) {
            // Moodle >= 2.6
            $textlib = 'core_text';
        } else if (method_exists('textlib', 'textlib')) {
            // Moodle 2.0 - 2.1
            $textlib = textlib_get_instance();
        } else {
            // Moodle 2.2 - 2.5
            $textlib = 'textlib';
        }
        $args = func_get_args();
        $method = array_shift($args);
        $callback = array($textlib, $method);
        return call_user_func_array($callback, $args);
    }

    /**
     * optional_param_array
     *
     * a wrapper method to offer consistent API for getting array parameters
     *
     * @param string $name the name of the parameter
     * @param mixed $default
     * @param mixed $type one of the PARAM_xxx constants
     * @param mixed $recursive (optional, default = true)
     * @return either an array of form values or the $default value
     */
    static public function optional_param_array($name, $default, $type, $recursive=true) {

        switch (true) {
            case isset($_POST[$name]): $param = $_POST[$name]; break;
            case isset($_GET[$name]) : $param = $_GET[$name]; break;
            default: return $default; // param not found
        }

        if (is_array($param) && function_exists('clean_param_array')) {
            return clean_param_array($param, $type, $recursive);
        }

        // not an array (or Moodle <= 2.1)
        return clean_param($param, $type);
    }
}
