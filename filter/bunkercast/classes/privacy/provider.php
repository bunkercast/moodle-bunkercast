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
 * Privacy declaration for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * This plugin stores no personal data of its own, but it does send an opaque
 * reference to the viewing user to a third party, so it cannot be a
 * null_provider — the external transmission has to be declared.
 *
 * Declaring metadata alone is not enough: Moodle's compliance rule is that a
 * component implementing metadata\provider must also implement a request
 * provider, so the request methods below are present and deliberately empty.
 * Nothing is stored locally — minted URLs live only in a volatile cache keyed by
 * user id, which Moodle purges with the rest of the application cache — so there
 * is genuinely nothing to locate, export or delete. This mirrors how core's own
 * third-party integrations declare themselves (see aiprovider_openai).
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Declares the viewer reference and file id sent to Bunkercast on playback.
     *
     * @param collection $collection The metadata collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'bunkercast',
            [
                'viewerref' => 'privacy:metadata:bunkercast:viewerref',
                'fileid'    => 'privacy:metadata:bunkercast:fileid',
            ],
            'privacy:metadata:bunkercast'
        );

        return $collection;
    }

    /**
     * Returns an empty list: this plugin holds no data in any context.
     *
     * @param int $userid The user to search for.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Adds nobody: this plugin holds no data in any context.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist) {
    }

    /**
     * Exports nothing: this plugin holds no data to export.
     *
     * @param approved_contextlist $contextlist The approved contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
    }

    /**
     * Deletes nothing: this plugin holds no data to delete.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
    }

    /**
     * Deletes nothing: this plugin holds no data to delete.
     *
     * @param approved_contextlist $contextlist The approved contexts and user to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }

    /**
     * Deletes nothing: this plugin holds no data to delete.
     *
     * @param approved_userlist $userlist The approved context and users to delete for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
    }
}
