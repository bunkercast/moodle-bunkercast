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
 * Upgrade steps for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade from an earlier version of the plugin.
 *
 * install.xml applies at INSTALL time only, so without this an existing 0.1.0
 * site bumps its version, reports "Success", and is left with no table — every
 * playback then fails on a database error. Measured: the upgrade genuinely does
 * report success while leaving the table absent, which is the worst shape of
 * failure because nothing looks wrong.
 *
 * v0.1.0 was never approved for the plugins directory, but its ZIPs are attached
 * to a public GitHub release, so an installed base is possible however small.
 *
 * @param int $oldversion Version the site is upgrading from.
 * @return bool
 */
function xmldb_filter_bunkercast_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026091900) {
        $table = new xmldb_table('filter_bunkercast_embed');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);
        $table->add_key('contextidfileid', XMLDB_KEY_UNIQUE, ['contextid', 'fileid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026091900, 'filter', 'bunkercast');
    }

    return true;
}
