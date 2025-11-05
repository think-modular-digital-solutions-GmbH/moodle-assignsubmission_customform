<?php
// This file is part of Moodle - http://moodle.org/
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
use assign;

/**
 * This class builds custom forms from formdata.
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customform {
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

    /** @var stdClass Plugin config. */
    public stdClass $config;
    /** @var array List of element types. */
    public array $types;
    /** @var array List of element labels. */
    public array $labels;
    /** @var array List of element options. */
    public array $options;

    /**
     * Constructor.
     *
     * @param stdClass $config Plugin config.
     */
    public function __construct(stdClass $config) {
        $this->config = $config;
        $this->types = [];
        $this->labels = [];
        $this->options = [];

        $formdata = [];
        $formdata['types'] = [];
        $formdata['labels'] = [];
        $formdata['options'] = [];
        $elements = explode("\n", trim($this->config->formdata));
        $i = 0;
        foreach ($elements as $element) {
            // Get parts.
            if (!self::validate($element)) {
                continue;
            }
            $elementparts = explode('|', trim($element));

            // Collect types.
            $type = $elementparts[0];
            $this->types[$i] = $type;

            // Skip html elements.
            if ($type == 'html') {
                $i++;
                continue;
            }

            // Collect labels.
            $this->labels[$i] = $elementparts[1];

            // Collect options.
            $optionsdata = $elementparts[3] ?? '';
            if ($optionsdata) {
                $optionsdata = trim($optionsdata, '[]');
                $this->options[$i] = array_map('trim', explode(',', $optionsdata));
            }

            $i++;
        }
    }

    /**
     * Builds a custom form from formdata.
     *
     * @param MoodleQuickForm $mform The Moodle form to add elements to.
     * @param string $formdata The formdata.
     */
    public function build(MoodleQuickForm $mform, string $formdata) {

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
     * @return string HTML formatted result.
     */
    public function result_html(string $data): string {
        $result = $this->decode_data($data);
        $html = '';
        foreach ($result as $key => $value) {
            $label = $this->labels[$key];
            $line = html_writer::div(htmlspecialchars($label), 'font-weight-bold col-md-3');
            $line .= html_writer::div($value, 'value col-md-9');
            $html .= html_writer::div($line, 'row py-2');
        }
        return html_writer::div($html, 'container');
    }

    /**
     * Returns the submission data as an array.
     *
     * @param string $data The submission data as a JSON string.
     * @return array submission data as an array.
     */
    public function decode_data(string $data): array {
        $formdata = [];
        $submissiondata = json_decode($data, true);
        for ($i = 0; $i < (count($this->types)); $i++) {
            // Get value.
            $key = "assignsubmission_customform_element_$i";
            if (!array_key_exists($key, $submissiondata)) {
                $value = '';
            } else {
                $value = $submissiondata[$key];
            }

            if ($this->types[$i] == 'html') {
                continue;
            } else if (in_array($this->types[$i], ['date_selector', 'date_time_selector'])) {
                // For date selectors, format the timestamp.
                $timestamp = (int)$value;
                if ($timestamp > 0) {
                    $formvalue = userdate($timestamp);
                } else {
                    $formvalue = '';
                }
            } else if ($this->types[$i] == 'checkbox') {
                // For checkboxes, show yes/no.
                $formvalue = ($value) ? get_string('yes') : get_string('no');
            } else {
                if (array_key_exists($i, $this->options) && !empty($this->options[$i])) {
                    // Map option values to their labels.
                    if (is_array($value)) {
                        $mappedvalues = [];
                        foreach ($value as $val) {
                            $mappedvalues[] = $this->options[$i][$val] ?? $val;
                        }
                        $value = implode(', ', $mappedvalues);
                    } else {
                        $value = $this->options[$i][$value] ?? $value;
                    }
                } else {
                    // No options, just return the raw value.
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                };
                $formvalue = htmlspecialchars(is_array($value) ? implode(', ', $value) : $value);
            }

            // Add to data.
            $formdata[$i] = $formvalue;
        }

        return $formdata;
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
