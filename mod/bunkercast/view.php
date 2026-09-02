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
 * Shows one Bunkercast video.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT);          // Course module id.
$b  = optional_param('b', 0, PARAM_INT);           // Instance id.

if ($id) {
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'bunkercast');
    $instance = $DB->get_record('bunkercast', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $instance = $DB->get_record('bunkercast', ['id' => $b], '*', MUST_EXIST);
    [$course, $cm] = get_course_and_cm_from_instance($instance, 'bunkercast');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/bunkercast:view', $context);

// Completion is view-based: that is the honest limit of what a third-party
// embed can report without the player sending heartbeats back.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$event = \mod_bunkercast\event\course_module_viewed::create([
    'objectid' => $instance->id,
    'context'  => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('bunkercast', $instance);
$event->trigger();

$PAGE->set_url('/mod/bunkercast/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_activity_record($instance);

// The same container the filter emits, and the same AMD module. No credential
// is placed in this HTML: player.js fetches a per-viewer URL through
// filter_bunkercast's web service, which authorises against THIS module's
// context — so group restrictions and availability conditions apply too.
$PAGE->requires->js_call_amd('filter_bunkercast/player', 'init');

echo $OUTPUT->header();

if (trim(strip_tags($instance->intro ?? '')) !== '') {
    echo $OUTPUT->box(format_module_intro('bunkercast', $instance, $cm->id), 'generalbox', 'intro');
}

echo html_writer::div(
    html_writer::tag('div', get_string('loading', 'filter_bunkercast'), [
        'class' => 'bunkercast-video-placeholder',
    ]),
    'bunkercast-video',
    [
        'data-fileid'    => strtolower($instance->fileid),
        'data-contextid' => $context->id,
    ]
);

echo $OUTPUT->footer();
