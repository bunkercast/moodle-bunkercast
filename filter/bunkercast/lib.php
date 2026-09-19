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
 * Callbacks for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds "Authorise Bunkercast videos" to a course's administration menu.
 *
 * The editor picker authorises a video as it inserts it, so most courses never
 * need this page. It exists for the two cases the picker cannot reach: a
 * reference typed or pasted by hand, and one that arrived with imported content.
 * That matters because filter_bunkercast is usable on its own — the activity
 * module and the editor button are both optional companions.
 *
 * Lands under Course administration rather than Reports: that container accepts
 * only report_* plugins.
 *
 * @param navigation_node $navigation The course administration node.
 * @param stdClass $course
 * @param context_course $context
 * @return void
 */
function filter_bunkercast_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('filter/bunkercast:browselibrary', $context)) {
        return;
    }

    // Same rule the web service and the picker use, so the menu entry never
    // appears anywhere that would refuse on submit — the front-page course in
    // particular.
    if (!\filter_bunkercast\embed::may_host($context)) {
        return;
    }

    $navigation->add(
        get_string('authorisevideos', 'filter_bunkercast'),
        new moodle_url('/filter/bunkercast/authorise.php', ['contextid' => $context->id]),
        navigation_node::TYPE_SETTING,
        null,
        'filter_bunkercast_authorise',
        new pix_icon('i/settings', '')
    );
}
