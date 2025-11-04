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
 * Contains the class for helper functions for the custom form submission plugin.
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
 * Helper functions.
 *
 * @package    assignsubmission_customform
 * @author     Stefan Weber <stefan.weber@think-modular.com>
 * @copyright  2025 think modular
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Returns all possible user fields that can be used in the userdata setting.
     *
     * @return array
     */
    public static function get_userfields(): array {
        global $DB;
        $fields = [];

        // Get core fields.
        $columns = $DB->get_columns('user');
        $corefields = array_keys($columns);
        foreach ($corefields as $corefield) {
            if (get_string_manager()->string_exists($corefield, 'core')) {
                $label = get_string($corefield);
            } else {
                $label = $corefield;
            }
            $fields[$corefield] = $label;
        }

        // Get custom profile fields.
        $customfields = $DB->get_records_menu('user_info_field', null, '', 'shortname, name');
        return array_merge($fields, $customfields);
    }
}
