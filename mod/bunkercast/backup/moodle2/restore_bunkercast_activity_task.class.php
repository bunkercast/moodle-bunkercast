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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Restore task for mod_bunkercast.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bunkercast/backup/moodle2/restore_bunkercast_stepslib.php');

/**
 * Wires the restore step and the link-decoding rules for a Bunkercast activity.
 */
class restore_bunkercast_activity_task extends restore_activity_task {
    /**
     * Defines activity-specific restore settings.
     *
     * @return void
     */
    protected function define_my_settings() {
        // None.
    }

    /**
     * Adds the single structure step that reads bunkercast.xml.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_bunkercast_activity_structure_step('bunkercast_structure', 'bunkercast.xml'));
    }

    /**
     * Names the fields whose content may contain encoded links.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('bunkercast', ['intro'], 'bunkercast');

        return $contents;
    }

    /**
     * Turns the placeholders written at backup time back into real URLs.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('BUNKERCASTVIEWBYID', '/mod/bunkercast/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('BUNKERCASTINDEX', '/mod/bunkercast/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Declares log-record rules; this activity writes none of its own.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }
}
