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
 * Structure step for backing up a Bunkercast video activity.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backs up the instance row.
 *
 * Note what travels: the Bunkercast `fileid` only. There is deliberately no
 * playback credential to back up — credentials are minted per viewer at request
 * time and never stored — so a backup file can never leak access to a video.
 */
class backup_bunkercast_activity_structure_step extends backup_activity_structure_step {
    /**
     * Declares the instance row and its embedded intro files.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // No user data in this activity: nothing is recorded per student beyond
        // the standard completion/log tables, which core handles.
        $bunkercast = new backup_nested_element('bunkercast', ['id'], [
            'name', 'intro', 'introformat', 'fileid', 'timecreated', 'timemodified',
        ]);

        $bunkercast->set_source_table('bunkercast', ['id' => backup::VAR_ACTIVITYID]);

        // The description can embed images.
        $bunkercast->annotate_files('mod_bunkercast', 'intro', null);

        return $this->prepare_activity_structure($bunkercast);
    }
}
