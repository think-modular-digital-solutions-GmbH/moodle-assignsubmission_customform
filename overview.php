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
 * Table of submissions for custom form submission plugin
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_customform\customform;
use assignsubmission_customform\helper;

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

defined('MOODLE_INTERNAL') || die();

// Get id.
$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'assign');

// Permission check.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/assign:viewgrades', $context);

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url('/mod/assign/submission/customform/overview.php'),
    ['id' => $id]
);
$title = get_string('overview', 'assignsubmission_customform');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Download handling.
$download = optional_param('download', '', PARAM_ALPHA);

// Setup table.
$table = new flexible_table('customform-table');
$table->is_downloading($download, $cm->name . '_customform_submissions', $title);
$table->define_baseurl($PAGE->url);

// Get plugin config.
$assign = new assign($context, $cm, $course);
$plugin = $assign->get_submission_plugin_by_type('customform');
$config = $plugin->get_config();
$customform = new customform($config);

// Get headers and columns.
$columns = ['userid'];
$headers = [get_string('defaultcoursestudent')];

// Add columns from custom user data.
if ($config->userdata) {
    $userfields = explode(",", trim($config->userdata));
    $validuserfields = helper::get_userfields();
    foreach ($userfields as $key => $userfield) {
        $userfield = trim($userfield);
        $userfields[$key] = $userfield;
        if (in_array($userfield, array_keys($validuserfields))) {
            $columns[] = $userfield;
            $headers[] = $validuserfields[$userfield];
        } else {
            \core\notification::add(
                get_string('invaliduserdata', 'assignsubmission_customform', $userfield),
                \core\output\notification::NOTIFY_WARNING
            );
            unset($userfields[$key]);
        }
    }
}

// Add columns from form data.
$columns = array_merge($headers, array_keys($customform->labels));
$headers = array_merge($headers, $customform->labels);

// Add column for grades.
$columns[] = 'grade';
$headers[] = get_string('gradenoun');
$table->column_class('grade', 'rightalign');

// Truncate headers.
foreach ($headers as $key => $header) {
    $headers[$key] = shorten_text($header, 50);
}

// Add columns and headers.
$table->define_columns($columns);
$table->define_headers($headers);
$table->setup();

// Get submission data.
$customformsubmissions = $DB->get_records('assignsubmission_customform', ['assignment' => $cm->instance]);

// Get grades.
$gradeinfo = grade_get_grades(
    $course->id,
    'mod',
    'assign',
    $cm->instance,
    array_keys($customformsubmissions)
);
$gradeitem  = $gradeinfo->items[0];
$usergrades = $gradeitem->grades;

// Output header if not downloading.
if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);
}

// Add data.
foreach ($customformsubmissions as $customformsubmission) {
    // Get submission.
    $submission = $DB->get_record(
        'assign_submission',
        ['id' => $customformsubmission->submission],
        'userid',
        MUST_EXIST,
    );

    // Add user link.
    $userid = $submission->userid;
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $userurl = new moodle_url('/user/profile.php', ['id' => $userid]);
    $userlink = html_writer::link($userurl, fullname($user));
    $row = [$userlink];

    // Add custom user data.
    profile_load_custom_fields($user);
    foreach ($userfields as $userfield) {
        if (property_exists($user, $userfield)) {
            $row[] = $user->{$userfield};
        } else if (array_key_exists($userfield, $user->profile)) {
            $row[] = $user->profile[$userfield];
        } else {
            $row[] = '';
        }
    }

    // Add custom form data.
    $row = array_merge($row, $customform->decode_data($customformsubmission->data));

    // Add grade.
    if (isset($usergrades[$userid]) && $usergrades[$userid]->grade !== null) {
        // Preformatted grade string (respects course / item display settings).
        $row[] = $usergrades[$userid]->str_grade;
    } else {
        $row[] = '-';
    }

    $table->add_data(array_values($row));
}
$table->finish_output();

// Back to assignment link.
if (!$table->is_downloading()) {
    $url = new moodle_url('/mod/assign/view.php', ['id' => $cm->id]);
    $html = html_writer::link(
        $url,
        get_string('back'),
        ['class' => 'btn btn-secondary mb-3']
    );
    echo $html;
    echo $OUTPUT->footer();
}
