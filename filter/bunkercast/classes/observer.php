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
 * Keeps video authorisations from outliving the places they were made for.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast;

/**
 * Removes authorisations whose context has gone.
 *
 * An orphaned row cannot currently authorise anything — is_authorised() only
 * considers a context may_host() accepts, and a deleted context resolves to
 * nothing — so this is hygiene rather than a patch over a hole. It is worth doing
 * anyway: the rows would otherwise accumulate for the life of the site, and the
 * authorise page's listing would carry a growing tail of entries naming courses
 * and activities that no longer exist.
 */
class observer {
    /**
     * Deleting an activity removes the authorisations made against it.
     *
     * @param \core\event\course_module_deleted $event
     * @return void
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        global $DB;

        $DB->delete_records(embed::TABLE, ['contextid' => $event->contextid]);
    }

    /**
     * Deleting a course removes its own authorisations and every activity's.
     *
     * Swept by absence rather than by context path, because by the time this
     * fires there is no path left to walk: remove_course_contents() deletes each
     * module's context directly (lib/moodlelib.php, the per-module loop) WITHOUT
     * firing course_module_deleted, so the sibling observer above never sees them,
     * and the course context is gone too.
     *
     * The subquery is the reason this hangs off course deletion rather than
     * running on every module deletion: {context} is a large table on a real site,
     * and deleting a course is both rare and already expensive.
     *
     * @param \core\event\course_deleted $event
     * @return void
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $DB;

        $DB->delete_records_select(embed::TABLE, 'contextid NOT IN (SELECT id FROM {context})');
    }
}
