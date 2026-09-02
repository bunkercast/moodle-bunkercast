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
 * Test data generator for mod_bunkercast.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates Bunkercast video activities for tests.
 */
class mod_bunkercast_generator extends testing_module_generator {
    /**
     * Creates one activity instance, defaulting the video to a valid uuid.
     *
     * @param array|stdClass|null $record Instance fields.
     * @param array|null $options Generator options.
     * @return stdClass The created instance.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;

        if (!isset($record->fileid)) {
            $record->fileid = '36bb40db-1a39-480f-8148-4346a76388fd';
        }
        if (!isset($record->intro)) {
            $record->intro = 'Test video';
        }
        if (!isset($record->introformat)) {
            $record->introformat = FORMAT_HTML;
        }

        return parent::create_instance($record, (array)$options);
    }
}
