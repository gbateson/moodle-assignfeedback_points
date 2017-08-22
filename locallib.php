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

    const NAME_COUNT_MAX = 8;
    const NAME_COUNT_ADD = 1;

    // see http://unicode.org/charts/PDF/Uxxxx.pdf
    // where "xxxx" is the start number of a unicode group

    // 0000 - 001F Control characters e.g. tab
    // 0020 - 007F ASCII basic e.g. abc
    // 0080 - 009F Control characters
    // 00A0 - 00FF ASCII extended (1) e.g. àáâãäå
    // 0100 - 017F ASCII extended (2) e.g. āăą

    // 3000 - 303F CJK punctuation
    // FF61 - FF64 CJK punctuation (half width)

    // 3040 - 309F hiragana [Japanese]
    // 30A0 - 30FF katakana [Japanese] (full width)
    // 31F0 - 31FF katakana [Japanese] (Ainu extensions)
    // 3300 - 33FF katakana [Japanese] (square words) e.g. ㌔
    // FF65 - FF9F katakana [Japanese] (half width)

    // 3130 - 318F hangul [Korean]
    // FFA0 - FFDF hangul [Korean] (half-width)

    // 3100 - 312F bopomofo [Chinese]
    // 31A0 - 31BF bopomofo [Chinese] extended

    // FF00 - FF60 Latin letters and punctuation (full width)

    // 3190 - 319F ideographic annotaion marks e.g. ㆖
    // 31C0 - 31EF CJK Strokes
    // 3200 - 32FF Enclosed CJK Letters and Months e.g. ㋐
    // 3400 - 4DFF CJK unified ideographs i.e. kanji

    const ASCII_BASIC_STRING = '/^[\x{0000}-\x{007F}]+$/u';
    const ASCII_EXTENDED_STRING = '/^[\x{0000}-\x{017F}]+$/u';

    const HIRAGANA_STRING = '/^[ \x{3000}-\x{303F}\x{3040}-\x{309F}]+$/u';
    const KATAKANA_FULL_STRING = '/^[ \x{3000}-\x{303F}\x{30A0}-\x{30FF}]+$/u';
    const KATAKANA_HALF_STRING = '/^[ \x{3000}-\x{303F}\x{31F0}-\x{31FF}\x{FF61}-\x{FF9F}]+$/u';
    const ROMAJI_STRING = '/^( |(t?chi|s?shi|t?tsu)|((b?by|t?ch|hy|jy|k?ky|p?py|ry|s?sh|s?sy|w|y)[auo])|((b?b|d|f|g|h|j|k?k|m|n|p?p|r|s?s|t?t|z)[aiueo])|[aiueo]|[mn])+$/i';

    //const HANGUL_FULL_STRING = '/^[ \x{3000}-\x{303F}\x{3130}-\x{318F}]+$/u';
    //const HANGUL_HALF_STRING = '/^[ \x{3000}-\x{303F}\x{FF61}-\x{FF64}\x{FFA0}-\x{FFDF}]+$/u';

    const ROMANIZE_NO = 0;
    const ROMANIZE_ROMAJI = 1;
    const ROMANIZE_HIRAGANA = 2;
    const ROMANIZE_KATAKANA_FULL = 3;
    const ROMANIZE_KATAKANA_HALF = 4;

    const FIXVOWELS_NO = 0;
    const FIXVOWELS_MACRONS = 1;
    const FIXVOWELS_SHORTEN = 2;

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

    const ALIGN_NONE = '';
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_CENTER = 'center';
    const ALIGN_JUSTIFY = 'justify';

    const SHOWGRADE_NONE = 0;
    const SHOWGRADE_GRADE = 1;
    const SHOWGRADE_PERCENT = 2;
    const SHOWGRADE_FRACTION = 3;
    const SHOWGRADE_GRADEBOOK = 4;

    /** cache of current user's capabilities **/
    protected $can = null;

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

        // add defaults for missing fields
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

        // force value of development settings on non-developer sites
        if (self::is_developer()==false) {
            $config->showelement     = 0;
            $config->multipleusers   = 0;
            $config->sendimmediately = 1;
            $config->allowselectable = 1;
            $config->showlink        = 1;
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

        // on the main Assign(ment) settings form,
        // we add config settings as hidden fields
        // because there are quite a few of them
        foreach ($config as $name => $value) {
            if ($name=='version') {
                continue;
            }
            if (substr($name, -4)=='_adv') {
                continue;
            }
            if (substr($name, -7)=='_locked') {
                continue;
            }
            if (is_scalar($value)) {
                self::get_setting($mform, $name, $value);
            } else if (is_array($value)) {
                foreach ($value as $i => $settings) {
                    foreach (array_keys($settings) as $setting) {
                        self::get_setting($mform, $name."[$i][$setting]", $settings[$setting]);
                    }
                }
            }
        }
   }

    /**
     * get_setting
     *
     * @param object $mform
     * @param string $name
     * @param mixed  $value
     * @todo Finish documenting this function
     */
    static public function get_setting($mform, $name, $value) {
        $mform->addElement('hidden', $name, $value);
        if (is_numeric($value)) {
            $mform->setType($name, PARAM_INT);
        } else if (preg_match('/^\w+$/', $value)) {
            $mform->setType($name, PARAM_ALPHANUM);
        } else {
            $mform->setType($name, PARAM_TEXT);
        }
    }

    /**
     * add_settings
     *
     * @param object $mform
     * @param string $plugin
     * @param object $custom
     * @todo Finish documenting this function
     */
    static public function add_settings($mform, $plugin, $custom) {
        global $OUTPUT;


        // cache reference to string manager
        $strman = get_string_manager();

        // cache reference to config settings
        $config = $custom->config;

        // names of hidden fields
        $hiddenfields = array();

        if ($custom->grading->method=='') {
            self::add_heading($mform, 'points', $plugin, false);
        }

        $name = 'minpoints';
        if ($custom->grading->method=='') {
            $options = self::get_text_options();
            self::add_setting($mform, $config, $name, 'text', $options);
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'maxpoints';
        if ($custom->grading->method=='') {
            $options = self::get_text_options();
            self::add_setting($mform, $config, $name, 'text', $options);
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'increment';
        if ($custom->grading->method=='') {
            $options = self::get_text_options();
            self::add_setting($mform, $config, $name, 'text', $options);
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'pointsperrow';
        if ($custom->grading->method=='') {
            $options = self::get_text_options();
            self::add_setting($mform, $config, $name, 'text', $options);
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'pointstype';
        if ($custom->grading->method=='') {
            $options = self::get_pointstype_options($plugin);
            self::add_setting($mform, $config, $name, 'select', $options);
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showcomments';
        if ($custom->grading->method=='') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        // points, scores and grades
        self::add_heading($mform, 'totals', $plugin , false);

        $name = 'text';
        if ($custom->grading->method) {
            $elements = array();
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, null, 'length', 'text',   0);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, null, 'head',   'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, null, 'join',   'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, null, 'tail',   'text',   1, 2);

            $groupname = $name.'settings';
            $label = get_string($groupname, $plugin);
            $mform->addElement('group', $groupname, $label, $elements, '', false);
            $mform->addHelpButton($groupname, $groupname, $plugin);

            $setting = $name.'length';
            $mform->setType($setting, PARAM_INT);
            $mform->setDefault($setting, $config->$setting);

            $setting = $name.'head';
            $mform->setType($setting, PARAM_INT);
            $mform->setDefault($setting, $config->$setting);
            $mform->disabledIf($setting, $name.'length', 'eq', '');
            $mform->disabledIf($setting, $name.'length', 'eq', '0');

            $setting = $name.'join';
            $mform->setType($setting, PARAM_TEXT);
            $mform->setDefault($setting, $config->$setting);
            $mform->disabledIf($setting, $name.'length', 'eq', '');
            $mform->disabledIf($setting, $name.'length', 'eq', '0');

            $setting = $name.'tail';
            $mform->setType($setting, PARAM_INT);
            $mform->setDefault($setting, $config->$setting);
            $mform->disabledIf($setting, $name.'length', 'eq', '');
            $mform->disabledIf($setting, $name.'length', 'eq', '0');
        } else {
            $hiddenfields[$name.'length'] = PARAM_INT;
            $hiddenfields[$name.'head'] = PARAM_INT;
            $hiddenfields[$name.'join'] = PARAM_TEXT;
            $hiddenfields[$name.'tail'] = PARAM_INT;
        }

        $name = 'alignscoresgrades';
        $options = self::get_alignscoresgrades_options($plugin);
        self::add_setting($mform, $config, $name, 'select', $options, PARAM_ALPHA);

        $name = 'showresetbuttons';
        if ($custom->grading->method) {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showpointstoday';
        if ($custom->grading->method=='') {
            self::add_setting($mform, $config, $name, 'checkbox');
            $mform->disabledIf($name, 'pointstype', 'ne', self::POINTSTYPE_SUM);
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showpointstotal';
        if ($custom->grading->method=='') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showrubrictotal';
        if ($custom->grading->method=='rubric') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showrubricscores';
        if ($custom->grading->method=='rubric') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showrubricremarks';
        if ($custom->grading->method=='rubric') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showguidetotal';
        if ($custom->grading->method=='guide') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showguidescores';
        if ($custom->grading->method=='guide') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showguideremarks';
        if ($custom->grading->method=='guide') {
            self::add_setting($mform, $config, $name, 'checkbox');
        } else {
            $hiddenfields[$name] = PARAM_INT;
        }

        $name = 'showassigngrade';
        $options = self::get_showgrade_options($plugin, false); // without "gradebook"
        self::add_setting($mform, $config, $name, 'select', $options, PARAM_INT);

        $name = 'showmodulegrade';
        $options = self::get_showgrade_options($plugin);
        self::add_setting($mform, $config, $name, 'select', $options, PARAM_INT);

        $name = 'showcoursegrade';
        $options = self::get_showgrade_options($plugin);
        self::add_setting($mform, $config, $name, 'select', $options, PARAM_INT);

        $name = 'gradeprecision';
        $options = self::get_gradeprecision_options($plugin);
        self::add_setting($mform, $config, $name, 'select', $options, PARAM_INT);

        // add hidden fields, if any
        foreach ($hiddenfields as $name => $type) {
            $mform->addElement('hidden', $name, $config->$name);
            $mform->setType($name, $type);
        }

        self::add_heading($mform, 'names', $plugin, false);

        self::add_setting($mform, $config, 'showpicture', 'checkbox');

        $name = 'nameformat';
        $options = self::get_text_options(20);
        self::add_setting($mform, $config, $name, 'text', $options, PARAM_TEXT);

        $name = 'newlinetoken';
        $options = self::get_text_options();
        self::add_setting($mform, $config, $name, 'text', $options, PARAM_TEXT);

        // nametokens
        $name = 'nametokens';

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
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'token',     'text',   0, 3);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'field',     'select', 0, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'split',     'text',   2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'start',     'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'count',     'text',   1, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'romanize',  'select', 2, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'fixvowels', 'select', 2, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'length',    'text',   2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'head',      'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'join',      'text',   1);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'tail',      'text',   1, 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'style',     'select', 2);
            self::add_nametokens_setting($mform, $elements, $strman, $plugin, $custom, $name, $i, 'case',      'select', 1);

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

            // if "romanize" is zero, disable "fixvowels"
            $mform->disabledIf("nametokens[$i][fixvowels]", "nametokens[$i][romanize]", 'eq', '0');
        }

        // button to add more "nametokens"
        if ($count < self::NAME_COUNT_MAX) {
            $label = get_string($name.'add', $plugin);
            if (self::NAME_COUNT_ADD > 1) {
                $label = str_ireplace('{no}', self::NAME_COUNT_ADD, $label);
            }
            $mform->addElement('submit', $name.'add', $label);
            $mform->registerNoSubmitButton($name.'add');
        }

        // development settings (one day, these may be hidden completely)
        self::add_heading($mform, 'development', 'admin', false);

        $options = self::get_showfeedback_options($plugin);
        self::add_setting($mform, $config, 'showfeedback', 'select', $options);

        if (self::is_developer()) {
            $options = array();
        } else {
            // disable these settings on non-developer sites
            $options = array('disabled' => 'disabled');
        }
        self::add_setting($mform, $config, 'showelement',     'checkbox', $options);
        self::add_setting($mform, $config, 'multipleusers',   'checkbox', $options);
        self::add_setting($mform, $config, 'sendimmediately', 'checkbox', $options);
        self::add_setting($mform, $config, 'allowselectable', 'checkbox', $options);
        self::add_setting($mform, $config, 'showlink',        'checkbox', $options);

        // ========================
        // jQuery (javascript)
        // ========================
        //
        self::add_field_jquery($mform, $plugin, $custom);
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
    static public function add_setting($mform, $config, $name, $type, $options=null, $paramtype=PARAM_INT) {

        $plugin = 'assignfeedback_points';
        $label = get_string($name, $plugin);
        if ($type=='checkbox') {
            $mform->addElement($type, $name, $label, ' ', $options);
        } else {
            $mform->addElement($type, $name, $label, $options);
        }
        $mform->addHelpButton($name, $name, $plugin);
        $mform->setType($name, $paramtype);

        if (isset($config->$name)) {
            $mform->setDefault($name, $config->$name);
        }

        $name_adv = $name.'_adv';
        if (isset($config->$name_adv)) {
            $mform->setAdvanced($name, $config->$name_adv);
        }
    }

    /**
     * add_nametokens_setting
     *
     * @param object  $mform
     * @param array   $elements (baed by reference)
     * @param object  $strman
     * @param string  name of $plugin
     * @param object  $custom settings for the form
     * @param string  $name of form element (should be "nametokens")
     * @param integer $i(ndex) of form element
     * @param string  name of $setting
     * @param string  $elementtype
     * @param $labeltype (optional, default=0) 0=none, 1=thin, 2=wide
     * @param $spacetype (optional, default=0) 0=none, 1=space, 2=newline, 3=get_string("nameseparator", $plugin)
     * @todo Finish documenting this function
     */
    static public function add_nametokens_setting($mform, &$elements, $strman, $plugin, $custom, $name, $i, $setting, $elementtype, $labeltype=0, $spacetype=0) {
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
            $options = call_user_func(array($class, $method), $plugin, $custom);
        } else if ($elementtype=='text') {
            $options = self::get_text_options();
        } else {
            $options = null;
        }
        if ($i===null) {
            $elementname = "$name$setting";
        } else {
            $elementname = $name."[$i][$setting]";
        }
        $elements[] = $mform->createElement($elementtype, $elementname, '', $options);

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
    static public function add_field_jquery($mform, $plugin, $custom) {
        global $CFG, $OUTPUT;

        // add jQuery script to this page
        self::requires_jquery('/mod/assign/feedback/points/awardpoints.js', $plugin);

        $js = '';
        $js .= '<script type="text/javascript">'."\n";
        $js .= '//<![CDATA['."\n";

        $js .= '    if (typeof(window.PTS)=="undefined") {'."\n";
        $js .= '        window.PTS = {};'."\n";
        $js .= '    }'."\n";

        $js .= '    PTS.moodletheme           = "'.self::js_safe($CFG->theme).'";'."\n";

        $js .= '    PTS.str = {};'."\n";
        $js .= '    PTS.str.reset            = "'.self::js_safe(get_string('reset')).'";'."\n";
        $js .= '    PTS.str.showless         = "'.self::js_safe(get_string('showless', 'form')).'";'."\n";
        $js .= '    PTS.str.showmore         = "'.self::js_safe(get_string('showmore', 'form')).'";'."\n";
        $js .= '    PTS.str.newlineempty     = "'.self::js_safe(get_string('newlineempty', $plugin)).'";'."\n";
        $js .= '    PTS.str.newlinespace     = "'.self::js_safe(get_string('newlinespace', $plugin)).'";'."\n";

        $js .= '    PTS.str.contactingserver = "'.self::js_safe(get_string('contactingserver', $plugin)).'";'."\n";

        // determine html tag used to enclose group elements
        // templatable forms on Moodle >= 2.9 use "LABEL" tags
        // non-templatable forms use "SPAN" tags

        $group_element_tag = 'span';
        if (method_exists($OUTPUT, 'mform_element')) {
            // Moodle >= 2.9
            $element = $mform->getElement('mapmodeelements'); // group of radio elements
            $element = $element->getElements();               // array of radio elements
            $element = $element[0];                           // the first radio element
            if ($element = $OUTPUT->mform_element($element, false, false, '', true)) {
                $group_element_tag = preg_replace('/^.*?<(\w+)[^>]*>.*$/s', '$1', $element);
            }
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

        $showpointstoday = 0;
        $showpointstotal = 0;

        $showguidescores = 0;
        $showguideremarks = 0;
        $showguidetotal = 0;

        $showrubricscores = 0;
        $showrubricremarks = 0;
        $showrubrictotal = 0;

        $usercriteriascores = array();
        $criteriascores = array();
        switch ($custom->grading->method) {

            case '':
                $showpointstoday = intval($custom->config->showpointstoday);
                $showpointstotal = intval($custom->config->showpointstotal);
                break;

            case 'guide':
                $showguidescores = intval($custom->config->showguidescores);
                $showguideremarks = intval($custom->config->showguideremarks);
                $showguidetotal = intval($custom->config->showguidetotal);
                $criteria = $custom->grading->method.'_criteria';
                $criteria = $custom->grading->definition->$criteria;
                foreach ($criteria as $criterionid => $criterion) {
                    $criteriascores[] = $criterionid.':'.$criterion['maxscore'];
                }
                break;

            case 'rubric':
                $showrubricscores = intval($custom->config->showrubricscores);
                $showrubricremarks = intval($custom->config->showrubricremarks);
                $showrubrictotal = intval($custom->config->showrubrictotal);
                $criteria = $custom->grading->method.'_criteria';
                $criteria = $custom->grading->definition->$criteria;
                foreach ($criteria as $criterionid => $criterion) {
                    $scores = array();
                    foreach ($criterion['levels'] as $levelid => $level) {
                        $scores[] = $levelid.':'.$level['score'];
                    }
                    $criteriascores[] = $criterionid.':{'.
                        '"min":'.$criterion['minscore'].','.
                        '"max":'.$criterion['maxscore'].','.
                        '"levels":{'.implode(',', $scores).'}'.
                    '}';
                }
                break;
        }

        $criteriascores = '{'.implode(',', $criteriascores).'}';
        $usercriteriascores = '{'.implode(',', $usercriteriascores).'}';

        $js .= '    PTS.gradingmethod         = "'.$custom->grading->method.'";'."\n";
        $js .= '    PTS.gradingcontainer      = "#fitem_id_advancedgrading";'."\n";

        $js .= '    PTS.elementtype           = "'.($custom->config->multipleusers ? 'checkbox' : 'radio').'";'."\n";
        $js .= '    PTS.elementdisplay        = "'.($custom->config->showelement  ? ' ' : 'none').'";'."\n";

        $js .= '    PTS.pointstype            = '.intval($custom->config->pointstype).";\n";
        $js .= '    PTS.pointsperrow          = '.intval($custom->config->pointsperrow).";\n";
        $js .= '    PTS.mapactionsperrow      = 0;'.";\n";
        $js .= '    PTS.sendimmediately       = '.intval($custom->config->sendimmediately).";\n";
        $js .= '    PTS.showfeedback          = '.intval($custom->config->showfeedback).";\n";
        $js .= '    PTS.showresetbuttons      = '.intval($custom->config->showresetbuttons).";\n";

        $js .= '    PTS.showpointstoday       = '.$showpointstoday.";\n";
        $js .= '    PTS.showpointstotal       = '.$showpointstotal.";\n";

        $js .= '    PTS.showrubricscores      = '.$showrubricscores.";\n";
        $js .= '    PTS.showrubricremarks     = '.$showrubricremarks.";\n";
        $js .= '    PTS.showrubrictotal       = '.$showrubrictotal.";\n";

        $js .= '    PTS.showguidescores       = '.$showguidescores.";\n";
        $js .= '    PTS.showguideremarks      = '.$showguideremarks.";\n";
        $js .= '    PTS.showguidetotal        = '.$showguidetotal.";\n";

        $js .= '    PTS.usercriteriascores    = '.$usercriteriascores.";\n";
        $js .= '    PTS.criteriascores        = '.$criteriascores.";\n";

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

        $js .= '    PTS.sortby                = '.json_encode($custom->sortby).';'."\n";

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
        $value = null;
        $plugin = 'assignfeedback_points';

        if (isset($data->$name)) {
            $class = get_class();
            $method = 'get_'.$name.'_options';
            if (method_exists($class, $method)) {
                $options = call_user_func(array($class, $method), $plugin);
                if (array_key_exists($data->$name, $options)) {
                    $value = $data->$name;
                }
            } else {
                $value = $data->$name;
            }
        }
        if ($value===null) {
            if ($allowmissing) {
                $value = $default;
            } else {
                $value = (is_numeric($default) ? 0 : '');
            }
        }
        if (is_array($value)) {
            $value = base64_encode(serialize($value));
        }
        if (isset($value)) {
            $this->set_config($name, $value);
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
        if (true || $this->get_config('showlink')) {

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
        // $multipleusers, $groupid, $map, $feedback, $userlist, $grading
        list($multipleusers, $groupid, $map, $feedback, $userlist, $grading) = $this->process_formdata();

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
            'feedback'   => $feedback,
            'grading'    => $grading,
            'namefields' => self::get_activenamefields($userlist),
            'exportfilename' => self::get_default_filename($plugin, $instance, $grading)
        );
        $custom->sortby = self::get_sortby($userlist, $custom);
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
        $context  = $this->assignment->get_context();
        $instance = $this->assignment->get_instance();
        $cm       = $this->assignment->get_course_module();

        $ajax = optional_param('ajax', 0, PARAM_INT);
        $undo = optional_param('undo', 0, PARAM_INT);

        switch (true) {
            case optional_param('submitbutton', null, PARAM_RAW): $action = 'submit'; break;
            case optional_param('importbutton', null, PARAM_RAW): $action = 'import'; break;
            case optional_param('exportbutton', null, PARAM_RAW): $action = 'export'; break;
            default: $action = '';
        }
        if ($action=='' && count($_POST)) {
            $action = 'submit'; // auto submit
        }

        $config = $this->get_all_config($plugin);

        if ($action=='import') {
            $config = self::import_settings($plugin, $config);
            foreach ($config as $name => $value) {
                if (is_array($value)) {
                    $value = base64_encode(serialize($value));
                }
                if (isset($value)) {
                    $this->set_config($name, $value);
                }
            }
        }

        $grading = self::get_grading_instance($config, $context);

        // cache the current time
        $time = time();

        // initialize "feedback" details
        $feedback = (object)array('text'       => '',
                                  'stringname' => '',
                                  'points'     => 0,
                                  'usercount'  => 0,
                                  'userlist'   => array(),
                                  'values'     => array(),
                                  'undo'       => array(),
                                  'type'       => $grading->method);
        if ($grading->method) {
            $feedback->type = get_string('pluginname', 'gradingform_'.$grading->method);
        }

        // get multipleusers setting that was used to create incoming form data
        if ($ajax) {
            $multipleusers = 0; // i.e. one user at a time
        } else {
            $multipleusers = $this->get_config('multipleusers');
        }

        // get original groupid
        $groupid = optional_param('groupid', false, PARAM_INT);
        if ($groupid===false) {
            $groupid = groups_get_activity_group($cm, false);
            if ($groupid===false) {
                $groupid = 0;
            }
        }

        // get/update user map (do not update if processing an "undo" request)
        $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id, ($undo ? false : true));
        $mapid = $map->id;

        // get userlist for original $groupid
        $userlist = $this->assignment->list_participants($groupid, false);
        $this->format_userlist_names($userlist, $config);

        if ($action=='export') {
            self::export_settings($plugin, $config, $instance, $this->get_version());
        }

        if ($undo) {
            // Handle an "undo" request - i.e. cancel previously awarded points.
            // Note that "undo" is available only for simple grading using points.
            $this->process_undo($feedback, $userlist, $instance, $plugin, $time);
        } else if ($action=='import') {
            // do nothing
        } else if ($action=='export') {
            // do nothing
        } else if ($action=='submit') {

            if ($ajax) {
                // don't save settings
            } else {
                $this->save_settings_allowmissing(data_submitted(), false);
            }

            // get (x, y) coordinates
            $x = self::optional_param_array('awardtox', array(), PARAM_INT);
            $y = self::optional_param_array('awardtoy', array(), PARAM_INT);

            // register incoming points in assignfeedback_points table
            $this->process_layouts($feedback, $userlist, $instance, $plugin, $x, $y, $map, $mapid, $ajax);

            // initialize parameters for "undo" link
            $feedback->undo = array('undo'          => 1,
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
                                    'commenttext'   => get_string('undo', $plugin));

            // award the points to selected users
            $this->process_awardto($feedback, $userlist, $cm, $instance, $time, $grading);

            // format feedback text, if necessary
            if ($feedback->usercount = count($feedback->userlist)) {

                $feedback->userlist = implode(', ', $feedback->userlist);
                switch (true) {
                    case ($grading->method && $feedback->usercount==1): $feedback->stringname = 'updatescoresoneuser'; break;
                    case ($grading->method && $feedback->usercount >1): $feedback->stringname = 'updatescoresmanyusers'; break;
                    case ($feedback->points==1 && $feedback->usercount==1): $feedback->stringname = 'awardonepointoneuser'; break;
                    case ($feedback->points==1 && $feedback->usercount >1): $feedback->stringname = 'awardonepointmanyusers'; break;
                    case ($feedback->points >1 && $feedback->usercount==1): $feedback->stringname = 'awardmanypointsoneuser'; break;
                    case ($feedback->points >1 && $feedback->usercount >1): $feedback->stringname = 'awardmanypointsmanyusers'; break;
                    default: $feedback->stringname = 'awardnopoints'; // shouldn't happen !!
                }
                $feedback->text = get_string($feedback->stringname, $plugin, $feedback);

                // add "Undo" link, if required
                if ($count = count($feedback->undo['pointsid'])) {

                    // prepare "pointsid" array for Undo link
                    $params = array();
                    foreach ($feedback->undo['pointsid'] as $i => $id) {
                        $params['pointsid['.$i.']'] = $id;
                    }
                    if ($count==1) {
                        $feedback->undo['pointsid'] = reset($params);
                    } else {
                        $feedback->undo['pointsid'] = $params;
                    }

                    // create Undo link
                    $link = new moodle_url('/mod/assign/view.php', $feedback->undo);
                    $text = get_string('undo', $plugin);
                    $params = array('id' => 'undolink');
                    $link = html_writer::link($link, $text, $params);

                    // append Undo link to $feedback->text
                    $feedback->text .= ' '.$link;
                }
            }

            // get latest groupid (it may have changed)
            $groupid = groups_get_activity_group($cm, true);
            if ($groupid===false) {
                $groupid = 0;
            }

            if ($groupid != $map->groupid) {
                $map = $this->get_usermap($cm, $USER->id, $groupid, $instance->id);
                $userlist = $this->assignment->list_participants($groupid, false);
                $this->format_userlist_names($userlist, $config);
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
        }

        if ($feedback->text) {
            $feedback->text = html_writer::tag('span', $feedback->text, array('id' => 'feedback'));
        }
        if (empty($feedback->values) || $ajax==0) {
            $feedback = $feedback->text;
        } else {
            unset($feedback->stringname);
            unset($feedback->points);
            unset($feedback->usercount);
            unset($feedback->userlist);
            unset($feedback->undo);
            unset($feedback->type);
        }

        if ($action=='export') {
            self::export_settings($plugin, $config, $instance, $this->get_version());
        }

        return array($multipleusers, $groupid, $map, $feedback, $userlist, $grading);
    }

    /**
     * format_userlist_names
     *
     * @param array  $userlist (passed by reference)
     * @param object $config
     * @return void, but may update $userlist
     */
    protected function format_userlist_names(&$userlist, $config) {

        // cache the <br /> tag
        $br = html_writer::empty_tag('br');

        // valid fields in user object
        $namefields = self::get_all_user_fields();

        // check to see if the nameformat string contains any $namefields
        if (empty($config->nameformat)) {
            $usernamefields = array();
        } else {
            $usernamefields = $namefields;
            foreach (array_keys($usernamefields) as $field) {
                if (strpos($config->nameformat, $field)===false) {
                    unset($usernamefields[$field]);
                }
            }
        }

        // format the display names for these users
        foreach ($userlist as $id => $user) {

            // setup cache of name tokens for this user
            // this will be required later to setup "sortby"
            $userlist[$id]->nametokens = array();

            // $defaultname will be fetched only if needed
            $defaultname = null;

            // $displayname starts as nameformat string
            $displayname = $config->nameformat;

            foreach ($config->nametokens as $i => $nametoken) {
                $nametoken = (object)$nametoken;

                if (empty($nametoken->field)) {
                    continue;
                }
                $field = $nametoken->field;

                if (array_key_exists($field, $namefields) && property_exists($user, $field)) {
                    if (property_exists($user, $field.'format')) {
                        $text = self::format_text($user, $field); // e.g. description
                    } else {
                        $text = $user->$field;
                    }
                } else if ($field=='default') {
                    if ($defaultname===null) {
                        $defaultname = fullname($user);
                    }
                    $text = $defaultname;
                } else {
                    continue; // shouldn't happen !!
                }

                if ($nametoken->split) {
                    $text = explode($nametoken->split, $text);
                    $start = ($nametoken->start - ($nametoken->start > 0 ? 1 : 0));
                    $count = ($nametoken->count ? $nametoken->count : count($text));
                    $text = implode('', array_splice($text, $start, $count));
                }

                if ($nametoken->romanize) {
                    switch ($nametoken->romanize) {
                        case self::ROMANIZE_ROMAJI:
                            if (preg_match(self::ROMAJI_STRING, $text)) {
                                $text = self::romanize_romaji($text, $field);
                            }
                            break;
                        case self::ROMANIZE_HIRAGANA:
                            if (preg_match(self::HIRAGANA_STRING, $text)) {
                                $text = self::romanize_hiragana($text);
                            }
                            break;
                        case self::ROMANIZE_KATAKANA_FULL:
                            if (preg_match(self::KATAKANA_FULL_STRING, $text)) {
                                $text = self::romanize_katakana_full($text);
                            }
                            break;
                        case self::ROMANIZE_KATAKANA_HALF:
                            if (preg_match(self::KATAKANA_HALF_STRING, $text)) {
                                $text = self::romanize_katakana_half($text);
                            }
                            break;
                    }

                    // what to do with these names:
                    // ooizumi, ooie, ooba, oohama, tooru, iita (井板), fujii (藤井)
                    // takaaki, maako, kousuke, koura, inoue, matsuura, yuuki
                    // nanba, junpei, junichirou, shinya, shinnosuke, gonnokami, shinnou

                    switch ($nametoken->fixvowels) {
                        case self::FIXVOWELS_MACRONS:
                            $text = strtr($text, array(
                                'noue' => 'noue', 'kaaki' => 'kaaki',
                                'aa' => 'ā', 'ii' => 'ī', 'uu' => 'ū',
                                'ee' => 'ē', 'oo' => 'ō', 'ou' => 'ō'
                            ));
                            break;
                        case self::FIXVOWELS_SHORTEN:
                            $text = strtr($text, array(
                                'ooa' => "oh'a", 'ooi' => "oh'i", 'oou' => "oh'u",
                                'ooe' => "oh'e", 'ooo' => "oh'o", 'too' => 'to',
                                'oo'  => 'oh',   'ou'  => 'o',    'uu'  => 'u'
                            ));
                            break;
                        default:
                    }
                }

                if ($nametoken->case) {
                    switch ($nametoken->case) {
                        case self::CASE_UPPER:  $text = self::textlib('strtoupper', $text); break;
                        case self::CASE_PROPER: $text = self::textlib('strtotitle', $text); break;
                        case self::CASE_LOWER:  $text = self::textlib('strtolower', $text); break;
                    }
                }
                // cache the plain text nametoken value for this user
                $userlist[$id]->nametokens[$i] = $text;

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

            // insert standard user name fields, if any
            if (count($usernamefields)) {
                foreach (array_keys($usernamefields) as $field) {
                    $usernamefields[$field] = $user->$field;
                }
                $displayname = strtr($displayname, $usernamefields);
            }

            // use plain text $displayname as $feedbackname
            $feedbackname = strip_tags($displayname);
            if ($feedbackname=='') {
                $feedbackname = get_string('fullnamedisplay', 'moodle', $user);
            }

            if ($displayname=='') {
                $displayname = $feedbackname;
            }

            if ($config->newlinetoken) {
                // https://pureform.wordpress.com/2008/01/04/matching-a-word-characters-outside-of-html-tags/
                $search = '/('.preg_quote($config->newlinetoken, '/').')+(?!([^<]+)?>)/u';
                $displayname = preg_replace($search, $br, $displayname);
                $feedbackname = preg_replace($search, ' ', $feedbackname);
            }

            $userlist[$id]->displayname = $displayname;
            $userlist[$id]->feedbackname = $feedbackname;
        }

        return $userlist;
    }

    /**
     * process_undo
     *
     * @param  object  $feedback (passed by reference)
     * @param  array   $userlist (passed by reference)
     * @param  object  $instance assignment instance record
     * @param  string  $plugin
     * @param  integer $time
     * @return void, but may update $userlist and $feedback
     */
    protected function process_undo(&$feedback, &$userlist, $instance, $plugin, $time) {
        global $DB, $USER;

        // get ids from incoming data
        $name = 'pointsid';
        $ids = self::optional_param_array($name, 0, PARAM_INT);
        if (is_scalar($ids)) {
            $ids = array($ids);
        }
        $ids = array_filter($ids);

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
                    $feedback->userlist[] = $userlist[$points->awardto]->feedbackname;
                }
            }
        }

        // set up feedback
        if ($feedback->usercount = count($feedback->userlist)) {
            $feedback->userlist = implode(', ', $feedback->userlist);
            switch (true) {
                case ($feedback->points==1 && $feedback->usercount==1): $feedback->stringname = 'undoonepointoneuser'; break;
                case ($feedback->points==1 && $feedback->usercount >1): $feedback->stringname = 'undoonepointmanyusers'; break;
                case ($feedback->points >1 && $feedback->usercount==1): $feedback->stringname = 'undomanypointsoneuser'; break;
                case ($feedback->points >1 && $feedback->usercount >1): $feedback->stringname = 'undomanypointsmanyusers'; break;
                default: $feedback->stringname = 'awardnopoints'; // shouldn't happen !!
            }
            $feedback->text = get_string($feedback->stringname, $plugin, $feedback);
        }
    }

    /**
     * process_layouts
     *
     * @param  object  $feedback (passed by reference)
     * @param  array   $userlist
     * @param  object  $instance assign(ment) record from DB
     * @param  string  $plugin
     * @param  array   $x
     * @param  array   $y
     * @param  object  $map
     * @param  integer $mapid
     * @param  integer $ajax
     * @return void
     */
    protected function process_layouts(&$feedback, &$userlist, $instance, $plugin, $x, $y, $map, $mapid, $ajax) {
        global $DB, $USER;

        // set up layouts, if required
        $update_form_values = false;
        $update_dimensions  = false;
        $update_coordinates = false;

        $name = 'layouts';
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
    }

    /**
     * process_awardto
     *
     * @param  object  $feedback (passed by reference)
     * @param  array   $userlist (passed by reference)
     * @param  object  $instance assign(ment) record from DB
     * @param  integer $time
     * @param  array   $grading (passed by reference)
     * @return void, but may update $feedback
     */
    protected function process_awardto(&$feedback, &$userlist, $cm, $instance, $time, &$grading) {
        global $DB, $USER;

        // cache certain config settings
        $gradeprecision = $this->get_config('gradeprecision');
        $showpointstotal = $this->get_config('showpointstotal');
        $showassigngrade = $this->get_config('showassigngrade');
        $showmodulegrade = $this->get_config('showmodulegrade');
        $showcoursegrade = $this->get_config('showcoursegrade');
        $sendnotifications = $this->get_config('sendnotifications');

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

        $name = 'awardto';
        $userids = self::optional_param_array($name, array(), PARAM_INT);
        if (is_scalar($userids)) {
            $userids = array($userids => 1);
        }

        // prepare incoming parameters
        if ($grading->method=='') {
            // Simple grading: Points

            $pointstype = $this->get_config('pointstype');
            $points = optional_param('points', 0, PARAM_INT);
            $feedback->points = $points;

            $commenttext   = optional_param('commenttextmenu', '',  PARAM_TEXT);
            $commentformat = optional_param('commentformat',    0,   PARAM_INT);

            // if commenttext was not selected from the drop down menu
            // try to get it from the text input element
            if ($commenttext=='') {
                $commenttext = optional_param('commenttext',   '', PARAM_TEXT);
            }

            // append to $undparams comment, if necessary
            if ($commenttext) {
                $feedback->undo['commenttext'] .= ": $commenttext";
            }

        } else {
            // Advanced grading: Rubric or Marking guide

            // shortcut to rubric/guide criteria details
            $criteria = $grading->method.'_criteria';
            $criteria =& $grading->definition->$criteria;

            // shortcut to incoming form data
            $formdata =& $grading->data->advancedgrading;
        }

        // add points for each user
        foreach ($userids as $userid => $checked) {

            if ($checked==0) {
                continue; // shouldn't happen !!
            }

            // Initialize $feedback->values for this user.
            // These are values that are displayed in the browser
            // but which can only be calculated here, on the server.
            $feedback->values[$userid] = array();

            // get associated assign_grades record id (create if necessary)
            $assigngrade = $this->get_assign_grade($userid, true);

            if ($grading->method=='') {

                // add new assignfeedback_points record
                $assignfeedbackpoints = (object)array(
                    'assignid'      => $instance->id,
                    'gradeid'       => $assigngrade->id,
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
                $feedback->undo['pointsid'][] = $assignfeedbackpoints->id;

                // set SQL parameters for "points" records to calculate grade
                $aggregate = '';
                $limitfrom = 0;
                $limitnum  = 0;
                $orderby   = '';
                $groupby   = '';
                $grade     = null;

                switch ($pointstype) {
                    case self::POINTSTYPE_SUM:     $aggregate = 'SUM'; break;
                    case self::POINTSTYPE_AVERAGE: $aggregate = 'AVG'; break;
                    case self::POINTSTYPE_MAXIMUM: $aggregate = 'MAX'; break;
                    case self::POINTSTYPE_MINIMUM: $aggregate = 'MIN'; break;
                    case self::POINTSTYPE_OLDEST:  $orderby = 'timeawarded ASC'; break;
                    case self::POINTSTYPE_MEDIAN:  $orderby = 'points ASC'; break;
                    case self::POINTSTYPE_MODE:    $groupby = 'points'; break;
                    // any thing else, including POINTSTYPE_NEWEST,
                    // will default to $grade = $points
                }

                $from = '{assignfeedback_points}';
                $where = 'assignid = :assignid '.
                         'AND awardto = :awardto '.
                         'AND cancelby = :cancelby '.
                         'AND pointstype = :pointstype';
                $params = array('assignid'   => $instance->id,
                                'awardto'    => $userid,
                                'cancelby'   => 0,
                                'pointstype' => $pointstype);

                if ($aggregate) {
                    $grade = "SELECT $aggregate(points) FROM $from WHERE $where";
                    $grade = $DB->get_field_sql($grade, $params);
                } else if ($orderby) {
                    $grade = "SELECT id, points FROM $from WHERE $where ORDER BY $orderby";
                    $grade = $DB->get_records_sql_menu($grade, $params, $limitfrom, $limitnum);
                    if ($grade) {
                        if ($limitnum==1) {
                            // get first (and only) value
                            $grade = reset($grade);
                        } else if ($pointstype==self::POINTSTYPE_MEDIAN) {
                            // get halfway value
                            $grade = array_slice($grade, floor(count($grade) / 2), 1);
                        } else {
                            $grade = null; // shoudln't happen !!
                        }
                    }
                } else if ($groupby) {
                    $grade = "SELECT $groupby, count(*) as countvalues ".
                             "FROM $from WHERE $where ".
                             "GROUP BY $groupby ".
                             "ORDER BY countvalues DESC, $groupby ASC";
                    $grade = $DB->get_records_sql_menu($grade, $params, $limitfrom, $limitnum);
                    if ($grade) {
                        $grade = reset($grade);
                        $grade = $grade->$groupby;
                    }
                } else {
                    $grade = $points;
                }
                if ($grade===null || $grade===false) {
                    $grade = 0;
                }

                if ($showpointstotal) {
                    $feedback->values[$userid]['pointstotal'] = $grade;
                }

                $gradingdata = $grading->data;

            } else {

                $name = 'advancedgrading';
                $gradingdata = (object)array(
                    $name => array('criteria' => array()),
                    $name.'instanceid' => $grading->instance->get_id()
                );

                $newdata =& $gradingdata->$name;
                $olddata = false;
                $defaults = array();

                // get most recent grading instance id for this user + assignment
                $select = 'gi.id, gi.status, gi.timemodified';
                $from   = '{grading_instances} gi '.
                          'JOIN {assign_grades} ag ON ag.id = gi.itemid '.
                          'JOIN {grading_definitions} gd ON gd.id = gi.definitionid '.
                          'JOIN {grading_areas} ga ON ga.id = gd.areaid AND ga.activemethod = gd.method';
                $where  = 'ga.contextid = ? AND ag.assignment = ? AND ag.userid = ? AND gi.status IN (?, ?, ?)';
                $order  = 'gi.status ASC, gi.timemodified DESC';
                $params = array($this->assignment->get_context()->id, $instance->id, $userid,
                                    gradingform_instance::INSTANCE_STATUS_ACTIVE,
                                    gradingform_instance::INSTANCE_STATUS_NEEDUPDATE,
                                    gradingform_instance::INSTANCE_STATUS_ARCHIVE);

                if ($gradinginstanceid = $DB->get_records_sql("SELECT $select FROM $from WHERE $where ORDER BY $order", $params)) {
                    $gradinginstanceid = reset($gradinginstanceid);
                    $gradinginstanceid = $gradinginstanceid->id;
                }

                if ($grading->method=='rubric') {
                    // specify $defaults for new data
                    $defaults = array('levelid' => 0,
                                      'remark'  => '',
                                      'remarkformat' => 0);
                    // fetch $olddata about this user from $DB
                    if ($gradinginstanceid) {
                        $params = array('instanceid' => $gradinginstanceid);
                        $select = 'criterionid, levelid, remark, remarkformat';
                        $olddata = $DB->get_records('gradingform_rubric_fillings', $params, null, $select);
                    }
                }

                if ($grading->method=='guide') {
                    // specify $defaults for new data
                    $defaults = array('score' => 0,
                                      'remark' => '',
                                      'remarkformat' => 0);
                    // fetch $olddata about this user from $DB
                    if ($gradinginstanceid) {
                        $params = array('instanceid' => $gradinginstanceid);
                        $select = 'criterionid, score, remark, remarkformat';
                        $olddata = $DB->get_records('gradingform_guide_fillings', $params, null, $select);
                    }
                    $gradingdata->showmarkerdesc = $grading->data->showmarkerdesc;
                    $gradingdata->showstudentdesc = $grading->data->showstudentdesc;
                }

                $grade = 0;
                foreach ($criteria as $criterionid => $criterion) {

                    // cache flag showing if $formdata exists for this criterionid
                    if (array_key_exists('criteria', $formdata)) {
                        $exists = array_key_exists($criterionid, $formdata['criteria']);
                    } else {
                        $exists = false;
                    }

                    $new = array();
                    foreach ($defaults as $name => $default) {
                        if ($exists && array_key_exists($name, $formdata['criteria'][$criterionid])) {
                            $value = $formdata['criteria'][$criterionid][$name];
                        } else if ($olddata) {
                            $value = $olddata[$criterionid]->$name;
                        } else {
                            $value = $default;
                        }
                        if ($value==0 && $name=='levelid') {
                            $value = reset($criterion['levels']);
                            $value = $value['id'];
                        }
                        $new[$name] = $value;
                        if ($name=='score') {
                            $grade += $value;
                        } else if ($name=='levelid') {
                            if (array_key_exists($value, $criterion['levels'])) {
                                $grade += $criterion['levels'][$value]['score'];
                            }
                        }
                    }
                    $newdata['criteria'][$criterionid] = $new;
                }

                // unset references to new/old data
                unset($newdata);
                unset($olddata);
            }

            // append this user to "feedback" details
            $feedback->userlist[] = $userlist[$userid]->feedbackname;

            $gradedata = $this->get_grade_data($assigngrade, $grade, $sendnotifications, $gradingdata);
            $this->assignment->save_grade($userid, $gradedata);

            if ($showassigngrade) {
                $grade = array('id' => $assigngrade->id);
                if ($grade = $DB->get_record('assign_grades', $grade)) {
                    $grade = round($grade->grade, $gradeprecision);
                    $feedback->values[$userid]['assigngrade'] = $grade;
                }
            }

            if ($showmodulegrade) {
                $grade = array('courseid'=>$cm->course,
                               'itemtype'=>'mod',
                               'itemmodule' => 'assign',
                               'iteminstance' => $cm->instance);
                if ($grade = grade_item::fetch($grade)) {
                    $grade = $grade->get_grade($userid)->finalgrade;
                    $grade = round($grade, $gradeprecision);
                    $feedback->values[$userid]['modulegrade'] = $grade;
                }
            }

            if ($showcoursegrade) {
                if ($grade = grade_item::fetch_course_item($cm->course)) {
                    $grade = $grade->get_final($userid)->finalgrade;
                    $grade = round($grade, $gradeprecision);
                    $feedback->values[$userid]['coursegrade'] = $grade;
                }
            }

            // remove $feedback values (i.e. scores and grades), if it is not required
            if (empty($feedback->values[$userid])) {
                unset($feedback->values[$userid]);
            }
        }
        if (isset($criteria)) {
            unset($criteria);
        }
        if (isset($formdata)) {
            unset($formdata);
        }
    }


    /**
     * Retrieve the assign(ment) id
     *
     * @return integer The id of an assign record in the DB
     */
    public function get_assign_id() {
        return $this->assignment->get_instance()->id;
    }

    /**
     * Retrieve the course module id
     *
     * @return integer The id of a course_module record in the DB
     */
    public function get_course_module_id() {
        return $this->assignment->get_course_module()->id;
    }

    /**
     * Retrieve an assign_grade object from the DB, optionally creating it if required.
     *
     * @param integer $userid
     * @param boolean $create If true the grade will be created if it does not exist
     * @param integer $attemptnumber The attempt number to retrieve the grade for. -1 means the latest submission.
     * @return stdClass The grade record
     */
    public function get_assign_grade($userid, $create, $attemptnumber=-1) {
        return $this->assignment->get_user_grade($userid, $create, $attemptnumber);
    }

    /**
     * get_grade_data
     *
     * @param  object  $assigngrade
     * @param  decimal $grade
     * @param  boolean $sendnotifications
     * @param  object  grading $data required by "rubric" and "guide" grading methods
     * @return object
     */
    protected function get_grade_data($assigngrade, $grade, $sendnotifications, $gradingdata) {

        $gradedata = (object)array(
            'id'              => $assigngrade->id,
            'grade'           => $grade,
            'applytoall'      => 0,
            'attemptnumber'   => $assigngrade->attemptnumber,
            'sendstudentnotifications' => $sendnotifications
        );

        if ($gradingdata) {
            foreach (get_object_vars($gradingdata) as $name => $value) {
                $gradedata->$name = $value;
            }
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
                    $award->title       = html_to_text($award->title, 0, false); // neutralize title text

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
            if (method_exists($PAGE->theme, 'image_url')) {
                $image_url = 'image_url'; // Moodle >= 3.3
            } else {
                $image_url = 'pix_url'; // Moodle <= 3.2
            }
            $img = $PAGE->theme->$image_url('t/switch_plus', 'core')->out();
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
     * format_grade
     *
     * @param object $grade
     * @param string $showgrade
     * @return string
     */
    static public function format_grade($showgrade, $grade, $maxgrade, $gradeprecision, $gradeitem=null) {
        if ($showgrade==self::SHOWGRADE_GRADE) {
            return round($grade, $gradeprecision);
        }
        if ($showgrade==self::SHOWGRADE_PERCENT) {
            return round(100 * ($grade / $maxgrade), $gradeprecision).'%';
        }
        if ($showgrade==self::SHOWGRADE_FRACTION) {
            return round($grade, $gradeprecision).'/'.round($maxgrade, $gradeprecision);
        }
        if ($showgrade==self::SHOWGRADE_GRADEBOOK) {
            return str_replace(' ', '', grade_format_gradevalue($grade, $gradeitem));
        }
        return $grade; // shouldn't happen !!
    }

    /**
     * romanize_romaji
     *
     * @return string
     */
    static public function romanize_romaji($name, $field) {

        // convert to lowercase
        $name= self::textlib('strtolower', $name);

        // fix "si", "ti", "tu", "sy(a|u|o)", "jy(a|u|o)" and "nanba"
        $name = strtr($name, array(
            'si' => 'shi', 'ti' => 'chi', 'tu' => 'tsu',
            'sy' => 'sh',  'jy' =>'j',    'nb' => 'mb'
        ));

        // fix "hu" (but not "chu" or "shu") e.g. hujimura
        $name = preg_replace('/(?<![cs])hu/', 'fu', $name);

        if (is_numeric(strpos($field, 'firstname'))) {
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
                'riyou'  => 'ryou', 'riyuu' => 'ryuu'
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

        return self::romanize($name, '', $replace);
    }

    /**
     * romanize_hiragana
     *
     * @param string $name
     * @return string $name
     */
    static public function romanize_hiragana($name) {
        return self::romanize($name, 'っ', array(
            // space
            '　' => ' ',

            // two-char (double-byte hiragana)
            'きゃ' => 'kya', 'ぎゃ' => 'gya', 'しゃ' => 'sha', 'じゃ' => 'ja',
            'ちゃ' => 'cha', 'にゃ' => 'nya', 'ひゃ' => 'hya', 'りゃ' => 'rya',

            'きゅ' => 'kyu', 'ぎゅ' => 'gyu', 'しゅ' => 'shu', 'じゅ' => 'ju',
            'ちゅ' => 'chu', 'にゅ' => 'nyu', 'ひゅ' => 'hyu', 'りゅ' => 'ryu',

            'きょ' => 'kyo', 'ぎょ' => 'gyo', 'しょ' => 'sho', 'じょ' => 'jo',
            'ちょ' => 'cho', 'にょ' => 'nyo', 'ひょ' => 'hyo', 'りょ' => 'ryo',

            'んあ' => "n'a", 'んい' => "n'i", 'んう' => "n'u", 'んえ' => "n'e", 'んお' => "n'o",
            'んや' => "n'ya", 'んゆ' => "n'yu", 'んよ' => "n'yo",

            // one-char (double-byte hiragana)
            'あ' => 'a', 'い' => 'i', 'う' => 'u', 'え' => 'e', 'お' => 'o',
            'か' => 'ka', 'き' => 'ki', 'く' => 'ku', 'け' => 'ke', 'こ' => 'ko',
            'が' => 'ga', 'ぎ' => 'gi', 'ぐ' => 'gu', 'げ' => 'ge', 'ご' => 'go',
            'さ' => 'sa', 'し' => 'shi', 'す' => 'su', 'せ' => 'se', 'そ' => 'so',
            'ざ' => 'za', 'じ' => 'ji', 'ず' => 'zu', 'ぜ' => 'ze', 'ぞ' => 'zo',
            'た' => 'ta', 'ち' => 'chi', 'つ' => 'tsu', 'て' => 'te', 'と' => 'to',
            'だ' => 'da', 'ぢ' => 'ji', 'づ' => 'zu', 'で' => 'de', 'ど' => 'do',
            'な' => 'na', 'に' => 'ni', 'ぬ' => 'nu', 'ね' => 'ne', 'の' => 'no',
            'は' => 'ha', 'ひ' => 'hi', 'ふ' => 'fu', 'へ' => 'he', 'ほ' => 'ho',
            'ば' => 'ba', 'び' => 'bi', 'ぶ' => 'bu', 'べ' => 'be', 'ぼ' => 'bo',
            'ぱ' => 'pa', 'ぴ' => 'pi', 'ぷ' => 'pu', 'ぺ' => 'pe', 'ぽ' => 'po',
            'ま' => 'ma', 'み' => 'mi', 'む' => 'mu', 'め' => 'me', 'も' => 'mo',
            'や' => 'ya', 'ゆ' => 'yu', 'よ' => 'yo',
            'ら' => 'ra', 'り' => 'ri', 'る' => 'ru', 'れ' => 're', 'ろ' => 'ro',
            'わ' => 'wa', 'を' => 'o', 'ん' => 'n'
        ));
    }

    /**
     * romanize_katakana_full
     *
     * @param string $name
     * @return string $name
     */
    static public function romanize_katakana_full($name) {
        return self::romanize($name, 'ッ', array(
            // space
            '　' => ' ',

            // two-char (full-width katakana)
            'キャ' => 'kya', 'ギャ' => 'gya', 'シャ' => 'sha', 'ジャ' => 'ja',
            'チャ' => 'cha', 'ニャ' => 'nya', 'ヒャ' => 'hya', 'リャ' => 'rya',

            'キュ' => 'kyu', 'ギュ' => 'gyu', 'シュ' => 'shu', 'ジュ' => 'ju',
            'チュ' => 'chu', 'ニュ' => 'nyu', 'ヒュ' => 'hyu', 'リュ' => 'ryu',

            'キョ' => 'kyo', 'ギョ' => 'gyo', 'ショ' => 'sho', 'ジョ' => 'jo',
            'チョ' => 'cho', 'ニョ' => 'nyo', 'ヒョ' => 'hyo', 'リョ' => 'ryo',

            'ンア' => "n'a", 'ンイ' => "n'i", 'ンウ' => "n'u", 'ンエ' => "n'e", 'ンオ' => "n'o",
            'ンヤ' => "n'ya", 'ンユ' => "n'yu", 'ンヨ' => "n'yo",

            // one-char (full-width katakana)
            'ア' => 'a', 'イ' => 'i', 'ウ' => 'u', 'エ' => 'e', 'オ' => 'o',
            'カ' => 'ka', 'キ' => 'ki', 'ク' => 'ku', 'ケ' => 'ke', 'コ' => 'ko',
            'ガ' => 'ga', 'ギ' => 'gi', 'グ' => 'gu', 'ゲ' => 'ge', 'ゴ' => 'go',
            'サ' => 'sa', 'シ' => 'shi', 'ス' => 'su', 'セ' => 'se', 'ソ' => 'so',
            'ザ' => 'za', 'ジ' => 'ji', 'ズ' => 'zu', 'ゼ' => 'ze', 'ゾ' => 'zo',
            'タ' => 'ta', 'チ' => 'chi', 'ツ' => 'tsu', 'テ' => 'te', 'ト' => 'to',
            'ダ' => 'da', 'ヂ' => 'ji', 'ヅ' => 'zu', 'デ' => 'de', 'ド' => 'do',
            'ナ' => 'na', 'ニ' => 'ni', 'ヌ' => 'nu', 'ネ' => 'ne', 'ノ' => 'no',
            'ハ' => 'ha', 'ヒ' => 'hi', 'フ' => 'fu', 'ヘ' => 'he', 'ホ' => 'ho',
            'バ' => 'ba', 'ビ' => 'bi', 'ブ' => 'bu', 'ベ' => 'be', 'ボ' => 'bo',
            'パ' => 'pa', 'ピ' => 'pi', 'プ' => 'pu', 'ペ' => 'pe', 'ポ' => 'po',
            'マ' => 'ma', 'ミ' => 'mi', 'ム' => 'mu', 'メ' => 'me', 'モ' => 'mo',
            'ヤ' => 'ya', 'ユ' => 'yu', 'ヨ' => 'yo',
            'ラ' => 'ra', 'リ' => 'ri', 'ル' => 'ru', 'レ' => 're', 'ロ' => 'ro',
            'ワ' => 'wa', 'ヲ' => 'o', 'ン' => 'n'
        ));
    }

    /**
     * romanize_katakana_full
     *
     * @param string $name
     * @return string $name
     */
    static public function romanize_katakana_half($name) {
        return self::romanize($name, 'ｯ', array(
            // space
            '　' => ' ',

            // two-char (half-width katakana)
            'ｷｬ' => 'kya', 'ｷﾞｬ' => 'gya', 'ｼｬ' => 'sha', 'ｼﾞｬ' => 'ja',
            'ﾁｬ' => 'cha', 'ﾆｬ' => 'nya', 'ﾋｬ' => 'hya', 'ﾘｬ' => 'rya',

            'ｷｭ' => 'kyu', 'ｷﾞｭ' => 'gyu', 'ｼｭ' => 'shu', 'ｼﾞｭ' => 'ju',
            'ﾁｭ' => 'chu', 'ﾆｭ' => 'nyu', 'ﾋｭ' => 'hyu', 'ﾘｭ' => 'ryu',

            'ｷｮ' => 'kyo', 'ｷﾞｮ' => 'gyo', 'ｼｮ' => 'sho', 'ｼﾞｮ' => 'jo',
            'ﾁｮ' => 'cho', 'ﾆｮ' => 'nyo', 'ﾋｮ' => 'hyo', 'ﾘｮ' => 'ryo',

            'ｶﾞ' => 'ga', 'ｷﾞ' => 'gi', 'ｸﾞ' => 'gu', 'ｹﾞ' => 'ge', 'ｺﾞ' => 'go',
            'ｻﾞ' => 'za', 'ｼﾞ' => 'ji', 'ｽﾞ' => 'zu', 'ｾﾞ' => 'ze', 'ｿﾞ' => 'zo',
            'ﾀﾞ' => 'da', 'ﾁﾞ' => 'ji', 'ﾂﾞ' => 'zu', 'ﾃﾞ' => 'de', 'ﾄﾞ' => 'do',
            'ﾊﾞ' => 'ba', 'ﾋﾞ' => 'bi', 'ﾌﾞ' => 'bu', 'ﾍﾞ' => 'be', 'ﾎﾞ' => 'bo',
            'ﾊﾟ' => 'pa', 'ﾋﾟ' => 'pi', 'ﾌﾟ' => 'pu', 'ﾍﾟ' => 'pe', 'ﾎﾟ' => 'po',

            'ﾝｱ' => "n'a", 'ﾝｲ' => "n'i", 'ﾝｳ' => "n'u", 'ﾝｴ' => "n'e", 'ﾝｵ' => "n'o",
            'ﾝﾔ' => "n'ya", 'ﾝﾕ' => "n'yu", 'ﾝﾖ' => "n'yo",

            // one-char (half-width katakana)
            'ｱ' => 'a', 'ｲ' => 'i', 'ｳ' => 'u', 'ｴ' => 'e', 'ｵ' => 'o',
            'ｶ' => 'ka', 'ｷ' => 'ki', 'ｸ' => 'ku', 'ｹ' => 'ke', 'ｺ' => 'ko',
            'ｻ' => 'sa', 'ｼ' => 'shi', 'ｽ' => 'su', 'ｾ' => 'se', 'ｿ' => 'so',
            'ﾀ' => 'ta', 'ﾁ' => 'chi', 'ﾂ' => 'tsu', 'ﾃ' => 'te', 'ﾄ' => 'to',
            'ﾅ' => 'na', 'ﾆ' => 'ni', 'ﾇ' => 'nu', 'ﾈ' => 'ne', 'ﾉ' => 'no',
            'ﾊ' => 'ha', 'ﾋ' => 'hi', 'ﾌ' => 'fu', 'ﾍ' => 'he', 'ﾎ' => 'ho',
            'ﾏ' => 'ma', 'ﾐ' => 'mi', 'ﾑ' => 'mu', 'ﾒ' => 'me', 'ﾓ' => 'mo',
            'ﾔ' => 'ya', 'ﾕ' => 'yu', 'ﾖ' => 'yo',
            'ﾗ' => 'ra', 'ﾘ' => 'ri', 'ﾙ' => 'ru', 'ﾚ' => 're', 'ﾛ' => 'ro',
            'ﾜ' => 'wa', 'ｦ' => 'o', 'ﾝ' => 'n'
        ));
    }

    /**
     * romanize
     */
    static public function romanize($name, $tsu='', $replace=null) {
        if ($replace) {
            $name = strtr($name, $replace);
        }
        if ($tsu) {
            $name = preg_replace('/'.$tsu.'(.)/u', '$1$1', $name);
        }
        return str_replace('nb', 'mb', $name);
    }

    /**
     * get_activenamefields
     *
     * @param  object $custom
     * @param  object $can
     * @return array of name fields that are active in this $userlist
     */
    static public function get_activenamefields($userlist) {
        $activenamefields = array();
        $namefields = self::get_all_user_fields();
        foreach ($namefields as $namefield) {
            $active = false;
            foreach ($userlist as $userid => $user) {
                if ($user->$namefield===null || $user->$namefield==='') {
                    continue;
                }
                $active = true;
                break;
            }
            if ($active) {
                $activenamefields[] = $namefield;
            }
        }
        return $activenamefields;
    }

    /**
     * get_sortby
     *
     * @param  arrat  $userlist
     * @param  object $custom
     * @return string array of sortby sequences
     */
    static public function get_sortby($userlist, $custom) {
        if (empty($custom->config->nametokens)) {
            $nametokens = false;
            $sortfields = $custom->namefields;
        } else {
            $nametokens = true;
            $sortfields = $custom->config->nametokens;
        }
        $sortby = array();
        foreach ($sortfields as $i => $sortfield) {
            $count = 0;
            $ids = array();
            $sortids = array();
            if ($nametokens) {
                $sortfield = $sortfield['token'];
            }
            foreach ($userlist as $id => $user) {
                if ($nametokens) {
                    $value = $user->nametokens[$i];
                } else {
                    $value = $user->$sortfield;
                }
                if ($value===null || $value==='') {
                    $ids[$id] = '';
                } else {
                    if (preg_match(self::ASCII_EXTENDED_STRING, $value)) {
                        if (preg_match(self::ASCII_BASIC_STRING, $value)==false) {
                            // convert accented chars to basic ascii equivalents
                            // e.g. "àáâãäå" and "āăą" are all converted to "a"
                            $value = self::textlib('convert', $value, 'utf-8', 'ascii');
                        }
                    }
                    $ids[$id] = $value;
                    $count++;
                }
            }
            if ($count) {
                // ToDo: add a sortlocale setting to name tokens
                // e.g. ja_JP.UTF-8, ko_KR.UTF-8, zh_CN.UTF-8
                // https://docs.moodle.org/dev/Table_of_locales
                if ($nametokens && isset($custom->config->nametokens[$i]->sortlocale)) {
                    $locale = $custom->config->nametokens[$i]->sortlocale;
                } else {
                    $locale = null;
                }
                if (class_exists('core_collator')) {
                    // Moodle >= 2.6
                    core_collator::asort($ids, core_collator::SORT_STRING);
                } else if (class_exists('Collator')) {
                    // PHP >= 5.3
                    Collator::create($locale)->asort($ids);
                } else {
                    // this is unlikely to work with Asian locales
                    // and may well have problems on Windows :-(
                    setlocale(LC_COLLATE, $locale);
                    asort($ids, SORT_LOCALE_STRING);
                }
                $sortby[$sortfield] = array_keys($ids);
            }
        }
        return $sortby;
    }

    /**
     * get_defaultvalues
     *
     * @return array(name => default)
     */
    static public function get_defaultvalues($plugin) {
        return array('pointstype'         => 0,
                     'minpoints'          => 1,
                     'increment'          => 1,
                     'maxpoints'          => 2,
                     'pointsperrow'       => 0,
                     'showcomments'       => 1,
                     'nameformat'         => '',
                     'newlinetoken'       => get_string('newlinetokendefault', $plugin),
                     'nametokens'         => array(), // base64_encode(serialize(array()))
                     'showpicture'        => 0,
                     'textlength'         => 0,
                     'texthead'           => 0,
                     'textjoin'           => '...',
                     'texttail'           => 0,
                     'alignscoresgrades'  => self::ALIGN_NONE,
                     'gradeprecision'     => 0,
                     'showresetbuttons'   => 0,
                     'showpointstoday'    => 1,
                     'showpointstotal'    => 1,
                     'showrubricscores'   => 0,
                     'showrubricremarks'  => 0,
                     'showrubrictotal'    => 1,
                     'showguidescores'    => 0,
                     'showguideremarks'   => 0,
                     'showguidetotal'     => 1,
                     'showassigngrade'    => 0,
                     'showmodulegrade'    => 0,
                     'showcoursegrade'    => 0,
                     'showfeedback'       => 0,
                     'showelement'        => 0,
                     'multipleusers'      => 0,
                     'sendimmediately'    => 1,
                     'allowselectable'    => 1,
                     'showlink'           => 1);
    }

    /**
     * get_nametoken_setting_types
     *
     * @return array($name => $paramtype)
     */
    static function get_nametoken_setting_types() {
        return array('token'     => PARAM_TEXT,
                     'field'     => PARAM_ALPHANUM,
                     'split'     => PARAM_TEXT,
                     'start'     => PARAM_INT,
                     'count'     => PARAM_INT,
                     'romanize'  => PARAM_INT,
                     'fixvowels' => PARAM_INT,
                     'length'    => PARAM_INT,
                     'head'      => PARAM_INT,
                     'join'      => PARAM_TEXT,
                     'tail'      => PARAM_INT,
                     'style'     => PARAM_ALPHA,
                     'case'      => PARAM_INT);
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
        return array('size' => $size, 'maxsize' => $size, 'style' => 'width: auto;');
    }

    /**
     * get_pointstype_options
     *
     * return an array of formatted pointstype options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @return array of field names
     */
    static public function get_pointstype_options($plugin) {
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
     * get_sortby_options
     *
     * return an array of formatted sortby options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @param  object $custom settings for the form
     * @return array of sortby options
     */
    static public function get_sortby_options($plugin, $custom) {

        // if there are no name tokens, return a lits of ALL name fields
        if (empty($custom->config->nametokens)) {
            return self::get_nametoken_field_options($plugin, $custom, false);
        }

        // otherwise, cache formatted fields names
        $fields = self::get_nametoken_field_options($plugin, $custom, true);

        $options = array();
        foreach ($custom->config->nametokens as $i => $nametoken) {
            // $string['tokenbasedonfield'] = '{$a->token} (based on {$a->field})';
            //$a = (object)array(
            //    'nametoken' => get_string('nametoken', $plugin, $i),
            //    'token' => $nametoken['token'],
            //    'field' => $fields[$nametoken['field']]
            //);
            //$text = get_string('tokenbasedonfield', $plugin, $a);
            $options[$nametoken['token']] = $nametoken['token'];
        }
        return $options;
    }

    /**
     * get_nametoken_field_options
     *
     * return an array of formatted name field options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @param  object $custom settings for the form
     * @param boolean $includedefault (optional, default=TRUE)
     * @return array of field names
     */
    static public function get_nametoken_field_options($plugin, $custom, $includedefault=true) {

        if ($includedefault) {
            $fields = array('' => '', 'default' => '');
        } else {
            $fields = array();
        }
        $fields += self::get_all_user_fields();

        $default = array_filter(array_keys($fields));
        $default = array_combine($default, $default);
        $default = fullname((object)$default);

        $space = '/[[:space:]]/u';
        $punct = '/[[:punct:]]/u';
        $ascii = '/^[\x00-\xff]*$/u';

        $has_space = preg_match($space, $default);
        $is_ascii = ($has_space ? true : false);

        $char = ''; // a punctuation char
        foreach (array_keys($fields) as $field) {
            if ($field) {
                switch ($field) {
                    case 'aim': $string = 'aimid'; break;
                    case 'msn': $string = 'msnid'; break;
                    case 'icq': $string = 'icqnumber'; break;
                    case 'skype': $string = 'skypeid'; break;
                    case 'yahoo': $string = 'yahooid'; break;
                    default: $string = $field;
                }
                $string = get_string($string);
                if ($is_ascii) {
                    $is_ascii = preg_match($ascii, $string);
                }
                if ($is_ascii && $char=='' && preg_match($punct, $string, $chars)) {
                    $char = $chars[0];
                }
                $fields[$field] = $string;
            }
        }

        if ($has_space) {
            $search = '/[[:punct:][:space:]]+/u';
            $replace = ($is_ascii ? $char : '');
            foreach (array_keys($fields) as $field) {
                if ($field) {
                    $fields[$field] = preg_replace($search, $replace, $fields[$field]);
                }
            }
        }

        if ($includedefault) {
            $default = fullname((object)$fields);
            $fields['default'] .= ": $default";
        }

        // remove $fields that are not used by this group of users
        foreach (array_keys($fields) as $field) {
            if ($field=='' || $field=='default' || in_array($field, $custom->namefields)) {
                // do nothing
            } else {
                unset($fields[$field]);
            }
        }

        return $fields;
    }

    /**
     * get_nametoken_style_options
     *
     * return an array of formatted style options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @param  object $custom settings for the form
     * @return array of name case options
     */
    static public function get_nametoken_style_options($plugin, $custom) {
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
     * get_nametoken_case_options
     *
     * return an array of name case options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @param  object $custom settings for the form
     * @return array of name case options
     */
    static public function get_nametoken_case_options($plugin, $custom) {
        return array(self::CASE_ORIGINAL => get_string('originalcase', $plugin),
                     self::CASE_PROPER   => get_string('propercase',   $plugin),
                     self::CASE_LOWER    => get_string('lowercase',    $plugin),
                     self::CASE_UPPER    => get_string('uppercase',    $plugin));
    }

    /**
     * get_nametoken_romanize_options
     *
     * return an array of formatted romanization options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @param  object $custom settings for the form
     * @return array of field names
     */
    static public function get_nametoken_romanize_options($plugin, $custom) {
        return array(self::ROMANIZE_NO  => get_string('no'),
                     self::ROMANIZE_ROMAJI => get_string('romanizeromaji', $plugin),
                     self::ROMANIZE_HIRAGANA => get_string('romanizehiragana', $plugin),
                     self::ROMANIZE_KATAKANA_FULL => get_string('romanizekatakanafull', $plugin),
                     self::ROMANIZE_KATAKANA_HALF => get_string('romanizekatakanahalf', $plugin));
    }

    /**
     * get_nametoken_fixvowels_options
     *
     * return an array of formatted romanization options
     * suitable for use in a Moodle form
     *
     * @param  string $plugin name
     * @param  object $custom settings for the form
     * @return array of field names
     */
    static public function get_nametoken_fixvowels_options($plugin, $custom) {
        return array(self::FIXVOWELS_NO  => get_string('no'),
                     self::FIXVOWELS_MACRONS => get_string('fixvowelsmacrons', $plugin),
                     self::FIXVOWELS_SHORTEN => get_string('fixvowelsshorten', $plugin));
    }

    /**
     * get_showgrade_options
     *
     * return an array of formatted alignscoresgrades options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_showgrade_options($plugin, $includegradebook=true) {
        $yes = get_string('yes');
        $grade = get_string('grade', 'grades');
        $total = get_string('total', 'grades');
        $percent = get_string('percent', 'grades');
        $options = array(self::SHOWGRADE_NONE => get_string('no'),
                         self::SHOWGRADE_GRADE => "$yes - $grade",
                         self::SHOWGRADE_PERCENT => "$yes - $percent %",
                         self::SHOWGRADE_FRACTION => "$yes - $grade / $total");
        if ($includegradebook) {
             $options[self::SHOWGRADE_GRADEBOOK] = "$yes - ".get_string('gradebook', 'grades');
        }
        return $options;
    }

    /**
     * get_alignscoresgrades_options
     *
     * return an array of formatted alignscoresgrades options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_alignscoresgrades_options($plugin) {
        return array(self::ALIGN_NONE    => get_string('default'),
                     self::ALIGN_LEFT    => get_string('alignleft', $plugin),
                     self::ALIGN_RIGHT   => get_string('alignright', $plugin),
                     self::ALIGN_CENTER  => get_string('aligncenter', $plugin),
                     self::ALIGN_JUSTIFY => get_string('alignjustify', $plugin));
    }

    /**
     * get_gradeprecision_options
     *
     * return an array of formatted gradeprecision options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_gradeprecision_options($plugin) {
        return range(0, 3);
    }

    /**
     * get_showfeedback_options
     *
     * return an array of formatted showfeedback options
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_showfeedback_options($plugin) {
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
     * get_grading_instance
     *
     * @param object  $mform
     */
    static public function get_grading_instance($config, $context) {
        global $USER;

        $grading = (object)array(
            'manager'    => null,
            'method'     => '',
            'controller' => null,
            'instance'   => null,
            'definition' => null,
            'data'       => null
        );

        $component = 'mod_assign';
        $area      = 'submissions';
        $name      = 'advancedgrading';

        $grading->manager = get_grading_manager($context, $component, $area);
        $grading->method = $grading->manager->get_active_method();
        if ($grading->method) {
            $grading->controller = $grading->manager->get_controller($grading->method);
            if ($grading->controller->is_form_available()) {
                $instanceid = optional_param($name.'instanceid', 0, PARAM_INT);
                $grading->instance = $grading->controller->get_or_create_instance($instanceid, $USER->id, null);
            }

            $grading->data = new stdClass();
            $grading->data->$name = self::optional_param_array($name, array(), PARAM_TEXT);

            $param = $name.'instanceid';
            $grading->data->$param = optional_param($param, 0, PARAM_INT);

            if ($grading->method=='guide') {
                $param = 'showmarkerdesc';
                $grading->data->$param = optional_param($param, true, PARAM_BOOL);
                $param = 'showstudentdesc';
                $grading->data->$param = optional_param($param, true, PARAM_BOOL);
            }

            $grading->definition = $grading->controller->get_definition();

            $name = 'description';
            $text = self::format_text($grading->definition, $name);
            $grading->definition->{$name.'text'} = $text;

            $grading->definition->minscore = 0;
            $grading->definition->maxscore = 0;

            // shortcuts to text length settings
            $length = $config->textlength;
            $head   = $config->texthead;
            $join   = $config->textjoin;
            $tail   = $config->texttail;

            $criteria = $grading->method.'_criteria';
            $criteria =& $grading->definition->$criteria;
            foreach ($criteria as $criterionid => $criterion) {

                $minscore = null;
                $maxscore = null;

                switch ($grading->method) {

                    case 'rubric':

                        $name = 'description';
                        $text = self::format_text($criterion, $name);
                        $text = self::shorten_text($text, $length, $head, $tail, $join, true);
                        $criteria[$criterionid][$name.'text'] = $text;

                        $levels =& $criteria[$criterionid]['levels'];
                        foreach ($levels as $levelid => $level) {

                            $name = 'definition';
                            $text = self::format_text($level, $name);
                            $text = self::shorten_text($text, $length, $head, $tail, $join, true);
                            $levels[$levelid][$name.'text'] = $text;

                            if ($minscore===null || $minscore > $level['score']) {
                                $minscore = $level['score'];
                            }
                            if ($maxscore===null || $maxscore < $level['score']) {
                                $maxscore = $level['score'];
                            }
                        }
                        unset($levels);
                        break;

                    case 'guide':
                        $name = 'shortname';
                        $text = $criterion[$name];
                        $text = self::shorten_text($text, $length, $head, $tail, $join, true);
                        $criteria[$criterionid][$name.'text'] = $text;
                        $maxscore = $criterion['maxscore'];
                        $minscore = 0;
                        break;
                }

                $criteria[$criterionid]['minscore'] = ($minscore===null ? 0 : $minscore);
                $criteria[$criterionid]['maxscore'] = ($maxscore===null ? 0 : $maxscore);
                $grading->definition->minscore += $criteria[$criterionid]['minscore'];
                $grading->definition->maxscore += $criteria[$criterionid]['maxscore'];
            }
            unset($criteria);
        }
        return $grading;
    }

    /**
     * format_text
     *
     * return an array of user field names
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function format_text($values, $name, $defaultvalue=null, $defaultformat=0) {
        if (is_object($values) && property_exists($values, $name)) {
            $value = $values->$name;
            $format = $values->{$name.'format'};
        } else if (is_array($values) && array_key_exists($name, $values)) {
            $value = $values[$name];
            $format = $values[$name.'format'];
        } else {
            $value = $defaultvalue;
            $format = $defaultformat;
        }
        if ($value===null || $value==='') {
            return '';
        }
        if (is_numeric($value)) {
            return $value;
        }
        $value = strip_tags($value); // remove @@PLUGINFILE@@
        return html_to_text(format_text($value, $format), 0, false);
    }

    /**
     * get_all_user_fields
     *
     * return an array of user field names
     * suitable for use in a Moodle form
     *
     * @return array of field names
     */
    static public function get_all_user_fields() {
        global $PAGE;
        static $fields = null;

        if ($fields===null) {
            $visiblefields = array();

            // get capabilities (see "user/lib.php")
            $can = (object)array(
                'viewalldetails'    => has_capability('moodle/user:viewalldetails',    $PAGE->context),
                'viewhiddendetails' => has_capability('moodle/user:viewhiddendetails', $PAGE->context),
                'viewfullnames'     => has_capability('moodle/site:viewfullnames',     $PAGE->context),
                'viewuseridentity'  => has_capability('moodle/site:viewuseridentity',  $PAGE->context),
            );

            if ($can->viewalldetails) {
                $visiblefields += array('username' => 'username');
            }

            if ($can->viewfullnames) {
                $visiblefields += array('firstname' => 'firstname',
                                        'lastname'  => 'lastname');
            }

            // we need these fields to get fullname($user)
            if (function_exists('get_all_user_name_fields')) {
                // Moodle >= 2.6
                $visiblefields += get_all_user_name_fields();
            }

            // these fields are sometimes hidden
            $fields = array('description', 'city', 'country',
                            'icq', 'skype', 'yahoo', 'aim', 'msn');
            $fields = array_combine($fields, $fields);

            if ($can->viewhiddendetails==false && isset($CFG->hiddenuserfields)) {
                foreach (array_explode(',', $CFG->hiddenuserfields) as $field) {
                    switch ($field) {
                        case 'icqnumber': $field = 'icq';   break;
                        case 'skypeid':   $field = 'skype'; break;
                        case 'yahooid':   $field = 'yahoo'; break;
                        case 'aimid':     $field = 'aim';   break;
                        case 'msnid':     $field = 'msn';   break;
                    }
                    unset($fields[$field]);
                }
            }
            $visiblefields += $fields;

            // these fields are also considered "hidden",
            // but do not appear in $CFG->hiddenuserfields
            if ($can->viewhiddendetails) {
                $fields = array('address', 'phone1', 'phone2');
                $visiblefields += array_combine($fields, $fields);
            }

            // add identity fields from $visiblefields
            if ($can->viewalldetails || $can->viewuseridentity) {
                $fields = array('idnumber', 'email', 'phone1', 'phone2', 'department', 'institution');
            } else if (isset($CFG->showuseridentity)) {
                $fields = array_explode(',', $CFG->showuseridentity);
            }
            $visiblefields += array_combine($fields, $fields);

            // we want the fields in this order
            $fields = array('username', 'idnumber', 'firstname', 'firstnamephonetic',
                            'lastname', 'lastnamephonetic', 'middlename', 'alternatename',
                            'email', 'phone1', 'phone2', 'icq', 'skype', 'yahoo', 'aim', 'msn',
                            'department', 'institution', 'address', 'city', 'country', 'description');
            $fields = array_combine($fields, $fields);

            $fields = array_intersect_assoc($fields, $visiblefields);
        }

        return $fields;
    }

    /**
     * shorten_text
     *
     * @param   string   $text
     * @param   integer  $textlength (optional, default=28)
     * @param   integer  $headlength (optional, default=10)
     * @param   integer  $taillength (optional, default=10)
     * @param   boolean  $singleline (optional, default=false)
     * @return  string
     */
    static public function shorten_text($text, $textlength=28, $headlength=10, $taillength=10, $join=' ... ', $singleline=false) {
        if ($singleline) {
            $text = preg_replace("/(?: |\t|\r|\n|(?:<br\b[^>]*>))+/", ' ', $text);
        }
        if ($textlength && ($headlength || $taillength)) {
            $strlen = self::textlib('strlen', $text);
            if ($strlen > $textlength) {
                $head = self::textlib('substr', $text, 0, $headlength);
                $tail = self::textlib('substr', $text, $strlen - $taillength, $taillength);
                $text = $head.$join.$tail;
            }
        }
        return $text;
    }

    /**
     * is_developer
     *
     * @return boolean TRUE if current user is developer on localhost; otherwise, FALSE
     */
    static public function is_developer() {
        global $CFG, $USER;
        if (strpos($CFG->wwwroot, '//localhost/')===false) {
            return false;
        }
        if (substr($USER->username, -7)=='bateson') {
            return true;
        }
        if (substr($USER->username, 0, 6)=='gordon') {
            return true;
        }
        return false;
    }

    /**
     * xml_tag
     *
     * @param string  $name
     * @return string UPPERCASE version of $name, with underscores removed
     */
    static public function xml_tag($name) {
        return strtr(strtoupper($name), array('_' => ''));
    }

    /**
     * get_default_filename
     *
     * @param string  $plugin
     * @param string  $name of assignment
     * @param string  $type of file (optional, default=".xml")
     * @return string, suitable for use as a filename
     */
    static public function get_default_filename($plugin, $instance, $grading, $filetype='.xml') {
        $search = '/([[:space:]]|[[:punct:]]|\x{3000})+/u';
        $replace = '-';

        if ($grading->method) {
            $method = 'gradingform_'.$grading->method;
            $method = get_string('pluginname', $method);
        } else {
            $method = get_string('points', $plugin);
        }
        $method = preg_replace($search, $replace, $method);
        //$method = assign_feedback_points::textlib('strtolower', $method);

        $settings = get_string('settings');
        $settings = preg_replace($search, $replace, $settings);
        $settings = assign_feedback_points::textlib('strtolower', $settings);

        $filename = strip_tags(format_string($instance->name, true));
        $filename = preg_replace($search, $replace, $filename);
        $filename = trim($filename, '-');

        $a = (object)array('method' => $method,
                           'settings' => $settings,
                           'filename' => $filename);
        $filename = get_string('defaultfilename', $plugin, $a);

        $filename = clean_filename($filename.$filetype);
        return $filename;
    }

    /**
     * export_settings
     *
     * @param string  $plugin
     * @param object  $config
     * @param object  $instance of assignment from DB
     * @param string  $version
     * @return void, but will send XML file to browser
     */
    static public function export_settings($plugin, $config, $instance, $version) {

        $filename = optional_param('exportfilename', '', PARAM_RAW);
        if ($filename=='') {
            self::get_default_filename($plugin, $instance);
        } else if (substr($filename, -4)=='.xml') {
            // do nothing
        } else {
            $filename .= '.xml';
        }

        // set main XML tag name for this plugin
        $PLUGIN = self::xml_tag($plugin);

        $content = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $content .= "<$PLUGIN>\n";
        $content .= '  <VERSION>'.$version."</VERSION>\n";

        $defaults = self::get_defaultvalues($plugin);
        foreach (array_keys($defaults) as $name) {

            $content .= "  <SETTING>\n";
            $content .= '    <NAME>'.self::xml_tag_safe_content($name)."</NAME>\n";

            if (is_scalar($config->$name)) {
                $content .= '    <VALUE>'.self::xml_tag_safe_content($config->$name)."</VALUE>\n";
            } else if ($name=='nametokens') {
                foreach ($config->$name as $i => $token) {
                    $content .= "    <TOKEN>\n";
                    foreach ($token as $n => $v) {
                        $content .= "      <TOKENSETTING>\n";
                        $content .= '        <NAME>'.self::xml_tag_safe_content($n)."</NAME>\n";
                        $content .= '        <VALUE>'.self::xml_tag_safe_content($v)."</VALUE>\n";
                        $content .= "      </TOKENSETTING>\n";
                    }
                    $content .= "    </TOKEN>\n";
                }
            }

            $content .= "  </SETTING>\n";
        }
        $content .= "</$PLUGIN>\n";

        send_file($content, $filename, 0, 0, true, true);
    }

    /**
     * import_settings
     *
     * @param string  $plugin
     * @param object  $config
     * @return object config settings (old overwritten by new)
     */
    static public function import_settings($plugin, $config) {
        global $CFG, $USER;
        require_once($CFG->dirroot.'/lib/xmlize.php');

        $fs = get_file_storage();
        $component = 'user';
        $filearea = 'draft';
        $context = context_user::instance($USER->id);

        $xml = '';

        // get content of uploaded file
        $paramname = 'importfile';
        if ($importfile = optional_param($paramname, 0, PARAM_INT)) {
            if ($xml = $fs->get_area_files($context->id, $component, $filearea, $importfile, 'id DESC', false)) {
                if ($xml = reset($xml)) {
                    $xml = $xml->get_content();
                }
            }
        } else if (isset($_FILES[$paramname])) {
            $xml = file_get_contents($_FILES[$paramname]['tmp_name']);
        }

        // remove uploaded file from server
        if ($importfile) {
            $fs->delete_area_files($context->id, $component, $filearea, $importfile);
        } else if (isset($_FILES[$paramname])) {
            unlink($_FILES[$paramname]['tmp_name']);
        }

        if (empty($xml)) {
            return $config;
        }

        if (! $xml = xmlize($xml, 0)) {
            return $config;
        }

        // set main XML tag name for this plugin
        $PLUGIN = self::xml_tag($plugin);

        if (empty($xml[$PLUGIN]['#']['SETTING'])) {
            return $config;
        }

        // shortcut to $SETTING array
        $SETTING = &$xml[$PLUGIN]['#']['SETTING'];

        $i = 0;
        while (isset($SETTING[$i]['#'])) {
            if ($name = $SETTING[$i]['#']['NAME'][0]['#']) {
                if (isset($SETTING[$i]['#']['VALUE'][0]['#'])) {
                    $config->$name = $SETTING[$i]['#']['VALUE'][0]['#'];
                } else if ($name=='nametokens') {
                    $config->$name = array();
                    if (isset($SETTING[$i]['#']['TOKEN'])) {
                        $ii = 0;
                        while (isset($SETTING[$i]['#']['TOKEN'][$ii]['#'])) {
                            $token = array();
                            $iii = 0;
                            while (isset($SETTING[$i]['#']['TOKEN'][$ii]['#']['TOKENSETTING'][$iii]['#'])) {
                                $n = $SETTING[$i]['#']['TOKEN'][$ii]['#']['TOKENSETTING'][$iii]['#']['NAME'][0]['#'];
                                $v = $SETTING[$i]['#']['TOKEN'][$ii]['#']['TOKENSETTING'][$iii]['#']['VALUE'][0]['#'];
                                if ($n) {
                                    $token[$n] = $v;
                                }
                                $iii++;
                            }
                            if (count($token)) {
                                array_push($config->$name, $token);
                            }
                            $ii++;
                        }
                    }
                }
                // remove from incoming form data so that
                // the outgoing form uses the new config setting
                unset($_POST[$name]);
            }
            $i++;
        }
        unset($SETTING);
        return $config;
    }

    /**
     * xml_tag_safe_content
     *
     * copied from Moodle 1.9 backup/backuplib.php
     */
    static public function xml_tag_safe_content($content) {
        global $CFG;
        //If enabled, we strip all the control chars (\x0-\x1f) from the text but tabs (\x9),
        //newlines (\xa) and returns (\xd). The delete control char (\x7f) is also included.
        //because they are forbiden in XML 1.0 specs. The expression below seems to be
        //UTF-8 safe too because it simply ignores the rest of characters.
        $content = preg_replace('/[\x0-\x8\xb-\xc\xe-\x1f\x7f]/s','',$content);
        $content = preg_replace('/\r\n|\r/', "\n", htmlspecialchars($content));
        return $content;
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
