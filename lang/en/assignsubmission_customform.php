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
 * Language strings for the customform submission plugin.
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowcustomformsubmissions'] = 'Enabled';
$string['enabled'] = 'Custom form';
$string['enabled_help'] = 'If enabled, you will be able to define a custom form, which students can fill out in their submission.';
$string['eventassessableuploaded'] = 'An custom form submission has been uploaded.';
$string['formdata'] = 'Form data';
$string['howto'] = '<h4>How to create a custom form:</h4>
    <ul>
        <li>Define form elements in this format:
            <pre>elementtype|label|required|options</pre>
        </li>
        <li>The "required" field is either "true" or "false", and assumed to be "false" if not specified</li>
        <li>One form element per line</li>
        <li>Supported element types:<ul>
            <li>html</li>
            <li>text</li>
            <li>textarea</li>
            <li>select</li>
            <li>multiselect</li>
            <li>checkbox</li>
            <li>date_selector</li>
            <li>date_time_selector</li>
        </ul></li>
        <li>For select elements, provide options separated by commas inside square brackets</li>
        <li>Example:
            <pre>
                html|&lt;h3&gt;Personal Information&lt;/h3&gt;
                text|Your Name|true
                textarea|Your Address|true
                select|Your Country|true|[Austria,Switzerland,Germany]
                multiselect|Languages Spoken|false|[English,French,Spanish,German,Italian]
                checkbox|Subscribe to Newsletter
                date_selector|Date of Birth|true
                date_time_selector|Appointment Time
            </pre>
        </li>
    </ul>';
$string['invalidformdata'] = 'Skipping invalid form element definition: {$a}';
$string['invalidformelement'] = 'Skipping invalid form element: {$a}';
$string['nosubmission'] = 'Nothing has been submitted for this assignment';
$string['pluginname'] = 'Custom form submission';
$string['privacy:metadata:assignmentid'] = 'Assignment ID';
$string['privacy:metadata:filepurpose'] = 'Files that are embedded in the text submission.';
$string['privacy:metadata:submissionpurpose'] = 'The submission ID that links to submissions for the user.';
$string['privacy:metadata:tablepurpose'] = 'Stores the text submission for each attempt.';
$string['privacy:metadata:textpurpose'] = 'The actual text submitted for this attempt of the assignment.';
$string['privacy:path'] = 'Submission Text';
$string['title'] = 'Custom form title';
