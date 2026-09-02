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
 * Backup task for mod_bunkercast.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bunkercast/backup/moodle2/backup_bunkercast_stepslib.php');

/**
 * Wires the backup step for a Bunkercast video activity.
 */
class backup_bunkercast_activity_task extends backup_activity_task {
    /**
     * Defines activity-specific backup settings.
     *
     * @return void
     */
    protected function define_my_settings() {
        // None.
    }

    /**
     * Adds the single structure step that writes bunkercast.xml.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new backup_bunkercast_activity_structure_step('bunkercast_structure', 'bunkercast.xml'));
    }

    /**
     * Encode links to this activity's scripts so they survive a restore into a
     * different course or site.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = "/(" . $base . "\/mod\/bunkercast\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BUNKERCASTINDEX*$2@$', $content);

        $search = "/(" . $base . "\/mod\/bunkercast\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BUNKERCASTVIEWBYID*$2@$', $content);

        return $content;
    }
}
