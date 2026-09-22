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
 * Event observers for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Deleting an activity takes its context with it, orphaning any authorisation
    // stored against that activity.
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => '\filter_bunkercast\observer::course_module_deleted',
    ],

    // Deleting a course orphans its own row and every activity row beneath it.
    [
        'eventname' => '\core\event\course_deleted',
        'callback'  => '\filter_bunkercast\observer::course_deleted',
    ],
];
