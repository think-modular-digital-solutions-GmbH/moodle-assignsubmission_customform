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
 * Library file for custom form submission plugin
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignsubmission_customform\customform;
use assignsubmission_customform\helper;

/**
 * Library class for custom form submission plugin
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_customform extends assign_submission_plugin {
    /**
     * Get the name of the submission plugin
     * @return string
     */
    public function get_name(): string {
        return get_string('pluginname', 'assignsubmission_customform');
    }

    /**
     * Get submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_customform_submission($submissionid): mixed {
        global $DB;
        return $DB->get_record('assignsubmission_customform', ['submission' => $submissionid]);
    }

    /**
     * Remove a submission.
     *
     * @param stdClass $submission The submission
     * @return boolean
     */
    public function remove(stdClass $submission) {
        global $DB;

        $submissionid = $submission ? $submission->id : 0;
        if ($submissionid) {
            $DB->delete_records('assignsubmission_customform', ['submission' => $submissionid]);
        }
        return true;
    }

    /**
     * Get the default setting for exernal server submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform): void {
        global $DB, $OUTPUT;

        // Title.
        $mform->addElement(
            'text',
            'assignsubmission_customform_title',
            get_string('title', 'assignsubmission_customform'),
        );
        $mform->setType('assignsubmission_customform_title', PARAM_TEXT);
        $mform->hideIf('assignsubmission_customform_title', 'assignsubmission_customform_enabled', 'notchecked');
        if (!$default = $this->get_config('title')) {
            $default = '';
        }
        $mform->setDefault('assignsubmission_customform_title', $default);

        // Check if submissions exist to prevent changing form data.
        $formoptions = ['rows' => 10, 'cols' => 70];
        $submissionsexist = $DB->record_exists(
            'assignsubmission_customform',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        if ($submissionsexist) {
            $mform->addElement(
                'static',
                'assignsubmission_customform_formdata_warning',
                '',
                $OUTPUT->notification(get_string('formdata_warning', 'assignsubmission_customform'), 'warning')
            );
            $formoptions['disabled'] = 'disabled';
        }

        // Form elements.
        $mform->addElement(
            'textarea',
            'assignsubmission_customform_formdata',
            get_string('formdata', 'assignsubmission_customform'),
            $formoptions
        );
        $mform->hideIf('assignsubmission_customform_formdata', 'assignsubmission_customform_enabled', 'notchecked');
        if (!$default = $this->get_config('formdata')) {
            $default = '';
        }
        $mform->setDefault('assignsubmission_customform_formdata', $default);
        $mform->addHelpButton(
            'assignsubmission_customform_formdata',
            'formdata',
            'assignsubmission_customform'
        );

        // Form elements example.
        $mform->addElement(
            'static',
            'assignsubmission_customform_settings_example',
            get_string('formdata_example', 'assignsubmission_customform'),
            $OUTPUT->notification(get_string('formdata_example_text', 'assignsubmission_customform'), 'info')
        );
        $mform->hideIf('assignsubmission_customform_settings_howto', 'assignsubmission_customform_enabled', 'notchecked');

        // Additional user data.
        $mform->addElement(
            'textarea',
            'assignsubmission_customform_userdata',
            get_string('userdata', 'assignsubmission_customform'),
            ['rows' => 5, 'cols' => 70]
        );
        $mform->hideIf('assignsubmission_customform_userdata', 'assignsubmission_customform_enabled', 'notchecked');
        if (!$default = $this->get_config('userdata')) {
            $default = '';
        }
        $mform->setDefault('assignsubmission_customform_userdata', $default);
        $mform->addHelpButton(
            'assignsubmission_customform_userdata',
            'userdata',
            'assignsubmission_customform'
        );

        // Userdata how to.
        $mform->addElement(
            'static',
            'assignsubmission_customform_settings_howto',
            get_string('userdata_allowed_fields', 'assignsubmission_customform'),
            $OUTPUT->notification(implode(', ', array_keys(helper::get_userfields())), 'info')
        );
        $mform->hideIf('assignsubmission_customform_settings_howto', 'assignsubmission_customform_enabled', 'notchecked');
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data): bool {
        $this->set_config('title', $data->assignsubmission_customform_title);
        $this->set_config('formdata', $data->assignsubmission_customform_formdata);
        $this->set_config('userdata', $data->assignsubmission_customform_userdata);
        return true;
    }

    /**
     * Get form data from submitted data
     *
     * @param stdClass $data
     */
    public function get_customformdata(stdClass $data): array {
        $customformdata = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'assignsubmission_customform_') === 0) {
                $customformdata[$key] = $value;
            }
        }
        return $customformdata;
    }

    /**
     * Add elements to submission form.
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data): bool {
        // Get config.
        $config = $this->get_config();
        $title = $config->title ?? get_string('pluginname', 'assignsubmission_customform');
        $formdata = $config->formdata;

        // Title.
        $mform->addElement(
            'header',
            'assignsubmission_customform_header',
            $title
        );

        // Create custom form.
        $customform = new customform($config);
        $customform->build($mform, $formdata);

        // Set submitted data.
        if ($submission) {
            $customformsubmission = $this->get_customform_submission($submission->id);
            if ($customformsubmission) {
                $submitteddata = json_decode($customformsubmission->data, true);
                foreach ($submitteddata as $key => $value) {
                    if (is_array($value)) {
                        $mform->setDefault($key, $value);
                    } else {
                        $mform->setDefault($key, htmlspecialchars_decode($value));
                    }
                }
            }
        }

        return true;
    }

    /**
     * Save form data.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data): bool|int {
        global $USER, $DB;

        $customformsubmission = $this->get_customform_submission($submission->id);
        $customformdata = json_encode($this->get_customformdata($data));

        $params = [
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => [
                'pathnamehashes' => [],
                'content' => $customformdata,
            ],
        ];
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        }
        if ($this->assignment->is_blind_marking()) {
            $params['anonymous'] = 1;
        }
        $event = \assignsubmission_customform\event\assessable_uploaded::create($params);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', ['id' => $submission->groupid], MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = [
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'groupid' => $groupid,
            'groupname' => $groupname,
        ];

        // Create or update custom form submission.
        if ($customformsubmission) {
            $customformsubmission->data = $customformdata;
            $params['objectid'] = $customformsubmission->id;
            $updatestatus = $DB->update_record('assignsubmission_customform', $customformsubmission);
            $event = \assignsubmission_customform\event\submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $updatestatus;
        } else {
            $customformsubmission = new stdClass();
            $customformsubmission->data = $customformdata;
            $customformsubmission->submission = $submission->id;
            $customformsubmission->assignment = $this->assignment->get_instance()->id;
            $customformsubmission->id = $DB->insert_record('assignsubmission_customform', $customformsubmission);
            $params['objectid'] = $customformsubmission->id;
            $event = \assignsubmission_customform\event\submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $customformsubmission->id > 0;
        }
    }

    /**
     * Display only the showviewlink if there is a submission
     *
     * @param stdClass $submission
     * @param bool $showviewlink - If the summary has been truncated set this to true
     * @return string
     */
    public function view_summary(stdClass $submission, &$showviewlink): string {
        $customformsubmission = $this->get_customform_submission($submission->id);
        $showviewlink = ($customformsubmission);
        return '';
    }

    /**
     * Full submission view
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission): string {
        if (!$customformsubmission = $this->get_customform_submission($submission->id)) {
            return get_string('nosubmission', 'assignsubmission_customform');
        }
        $config = $this->get_config();
        $showviewlink = true;
        $customform = new customform($config);
        return $customform->result_html($customformsubmission->data);
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance(): bool {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records(
            'assignsubmission_customform',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        return true;
    }

    /**
     * Submission can never be empty - cannot be submitted if required fields are empty anyways.
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        return false;
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission): bool {
        global $DB;

        // Copy the assignsubmission_customform record.
        $customformsubmission = $this->get_customform_submission($sourcesubmission->id);
        if ($customformsubmission) {
            unset($customformsubmission->id);
            $customformsubmission->submission = $destsubmission->id;
            $DB->insert_record('assignsubmission_customform', $customformsubmission);
        }
        return true;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */
    public function get_config_for_external(): array {
        global $CFG;

        $configs = $this->get_config();

        // Get a size in bytes.
        if ($configs->maxsubmissionsizebytes == 0) {
            $configs->maxsubmissionsizebytes = get_max_upload_file_size(
                $CFG->maxbytes,
                $this->assignment->get_course()->maxbytes,
                get_config('assignsubmission_customform', 'maxbytes')
            );
        }
        return (array) $configs;
    }

    /**
     * This allows a plugin to render an introductory section which is displayed
     * right below the activity's "intro" section on the main assignment page.
     *
     * @return string
     */
    public function view_header(): string {
        global $OUTPUT;

        // Render button to view all submissions.
        $url = new moodle_url(
            '/mod/assign/submission/customform/overview.php',
            ['id' => $this->assignment->get_course_module()->id]
        );
        $html = html_writer::link(
            $url,
            get_string('viewall', 'assignsubmission_customform'),
            ['class' => 'btn btn-secondary mb-3']
        );
        return $html;
    }
}
