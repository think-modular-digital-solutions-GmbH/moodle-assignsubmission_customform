<?php
// This file is part of mod_extserver for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains the class building custom forms from formdata.
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_customform;

use html_writer;
use MoodleQuickForm;
use stdClass;

/**
 * This class builds custom forms from formdata.
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class form {
    /** @var array List of allowed form elements. */
    const ALLOWED_ELEMENTS = [
        'html',
        'text',
        'textarea',
        'select',
        'multiselect',
        'checkbox',
        'date_selector',
        'date_time_selector',
    ];

    /**
     * Builds a custom form from formdata.
     *
     * @param MoodleQuickForm $mform The Moodle form to add elements to.
     * @param string $formdata The formdata.
     */
    public static function build(MoodleQuickForm $mform, string $formdata) {

        $elements = explode("\n", trim($formdata));
        $i = 0;
        foreach ($elements as $element) {
            $name = "assignsubmission_customform_element_$i";

            // Get parts.
            if (!self::validate($element)) {
                continue;
            }
            $elementparts = explode('|', trim($element));

            // Get the rest of the element parameters.
            $type = $elementparts[0];
            $label = $elementparts[1];
            $required = $elementparts[2] ?? 'false';
            $options = $elementparts[3] ?? '';
            if ($options) {
                $options = trim($options, '[]');
                $options = array_map('trim', explode(',', $options));
            }
            $multiple = false;
            if ($type == 'multiselect') {
                $type = 'select';
                $multiple = true;
            }

            // Add element to form.
            if ($type == 'html') {
                $mform->addElement('html', $label);
            } else {
                $mform->addElement($type, $name, $label, is_array($options) ? $options : null);
                $mform->setType($name, PARAM_RAW);
                if (strtolower($required) === 'true') {
                    $mform->addRule($name, null, 'required', null, 'client');
                }
                if ($multiple) {
                    $mform->getElement($name)->setMultiple($name, true);
                }
            }
            $i++;
        }
    }

    /**
     * Formats the submission data for display.
     *
     * @param string $data The submission data.
     * @param stdClass $config The plugin config.
     * @return string The formatted submission data.
     */
    public static function result(string $data, stdClass $config): string {
        $result = '';
        $types = [];
        $labels = [];
        $options = [];
        $elements = explode("\n", trim($config->formdata));
        $i = 0;
        foreach ($elements as $element) {
            // Get parts.
            if (!self::validate($element)) {
                continue;
            }
            $elementparts = explode('|', trim($element));

            // Skip html elements.
            $type = $elementparts[0];
            if ($type == 'html') {
                $result .= $elementparts[1];
                continue;
            }

            // Collect types.
            $types[] = $type;

            // Collect labels.
            $label = $elementparts[1];
            $labels[] = $label;

            // Collect options.
            $optionsdata = $elementparts[3] ?? '';
            if ($optionsdata) {
                $optionsdata = trim($optionsdata, '[]');
                $options[$i] = array_map('trim', explode(',', $optionsdata));
            } else {
                $options[$i] = [];
            }
            $i++;
        }

        $submissiondata = json_decode($data, true);
        $i = 0;
        foreach ($submissiondata as $key => $value) {
            $label = $labels[$i];
            $line = html_writer::div(htmlspecialchars($label), 'font-weight-bold col-md-3');
            if (array_key_exists($i, $options) && !empty($options[$i])) {
                // Map option values to their labels.
                if (is_array($value)) {
                    $mappedvalues = [];
                    foreach ($value as $val) {
                        $mappedvalues[] = $options[$i][$val] ?? $val;
                    }
                    $value = implode(', ', $mappedvalues);
                } else {
                    $value = $options[$i][$value] ?? $value;
                }
            } else {
                // No options, just return the raw value.
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
            };
            $formvalue = htmlspecialchars(is_array($value) ? implode(', ', $value) : $value);

            // For date selectors, format the timestamp.
            if (in_array($types[$i], ['date_selector', 'date_time_selector'])) {
                $timestamp = (int)$value;
                if ($timestamp > 0) {
                    $formvalue = userdate($timestamp);
                } else {
                    $formvalue = '';
                }
            }

            // For checkboxes, show yes/no.
            if ($types[$i] == 'checkbox') {
                $formvalue = ($value) ? get_string('yes') : get_string('no');
            }

            $line .= html_writer::div($formvalue, 'value col-md-9');
            $result .= html_writer::div($line, 'row py-2');
            $i++;
        }
        return $result;
    }

    /**
     * Validates element parts.
     *
     * @param string $element The element string.
     * @return bool True if valid, false otherwise.
     */
    private static function validate(string $element): bool {
        $elementparts = explode('|', trim($element));
        if (count($elementparts) === 0) {
            return false;
        }
        if (count($elementparts) < 2) {
            \core\notification::add(
                get_string('invalidformdata', 'assignsubmission_customform', $element),
                \core\output\notification::NOTIFY_WARNING
            );
            return false;
        }
        $type = $elementparts[0];
        if (!in_array($type, self::ALLOWED_ELEMENTS)) {
            \core\notification::add(
                get_string('invalidformelement', 'assignsubmission_customform', $element),
                \core\output\notification::NOTIFY_WARNING
            );
            return false;
        }
        return true;
    }
}
