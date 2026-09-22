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
 * Authorise a Bunkercast video for use in a course.
 *
 * The editor picker does this as it inserts a video, so this page exists for the
 * references the picker cannot reach: typed or pasted by hand, or arrived with
 * imported content. filter_bunkercast is usable without the editor plugin, so
 * this is the only authoring path guaranteed to be present.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use filter_bunkercast\api;
use filter_bunkercast\embed;

$contextid = required_param('contextid', PARAM_INT);

$context = context::instance_by_id($contextid);
$coursecontext = embed::course_context_for($context);
if (!$coursecontext) {
    throw new moodle_exception('cannotembedhere', 'filter_bunkercast');
}
$course = get_course($coursecontext->instanceid);

require_login($course);
require_capability('filter/bunkercast:browselibrary', $coursecontext);

$pageurl = new moodle_url('/filter/bunkercast/authorise.php', ['contextid' => $coursecontext->id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('authorisevideos', 'filter_bunkercast'));
$PAGE->set_heading(format_string($course->fullname));

// The library is fetched once and used for three things: the select, the name in
// the confirmation message, and the titles in the table below. A failure here is
// an unconfigured or unreachable API, which the notice at the bottom explains.
$videos = [];
$listfailed = false;
try {
    $videos = api::list_videos();
} catch (moodle_exception $e) {
    $listfailed = true;
}

$names = [];
foreach ($videos as $video) {
    $names[$video['fileid']] = $video['name'];
}

$fileid = optional_param('fileid', '', PARAM_ALPHANUMEXT);
if ($fileid !== '') {
    // Note require_sesskey() rather than confirm_sesskey(): the latter returns
    // false for a wrong key, which would silently render the page again as though
    // the button had not been pressed.
    require_sesskey();
    // Every check that decides whether this is allowed lives in embed::register().
    embed::register($fileid, $coursecontext);
    redirect(
        $pageurl,
        get_string('authorised', 'filter_bunkercast', s($names[strtolower($fileid)] ?? $fileid)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$remove = optional_param('remove', '', PARAM_ALPHANUMEXT);
if ($remove !== '') {
    require_sesskey();
    $removename = s($names[strtolower($remove)] ?? $remove);

    // Confirm first. Unlike authorising, this takes something away: every
    // reference to the video in this course stops playing the moment it is gone.
    if (!optional_param('confirm', 0, PARAM_BOOL)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('authorisevideos', 'filter_bunkercast'));
        echo $OUTPUT->confirm(
            get_string('removeconfirm', 'filter_bunkercast', $removename),
            new moodle_url($pageurl, ['remove' => $remove, 'confirm' => 1, 'sesskey' => sesskey()]),
            $pageurl
        );
        echo $OUTPUT->footer();
        exit;
    }

    embed::revoke($remove, $coursecontext);
    redirect(
        $pageurl,
        get_string('removed', 'filter_bunkercast', $removename),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Course-context authorisations only: this page manages the course-wide grant,
// which already covers every activity inside it. get_records() keys by id, so
// build the file-id set separately.
$authorised = $DB->get_records(embed::TABLE, ['contextid' => $coursecontext->id], 'timecreated DESC');
$authorisedids = array_flip(array_column($authorised, 'fileid'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('authorisevideos', 'filter_bunkercast'));
echo html_writer::tag('p', get_string('authoriseintro', 'filter_bunkercast'));

if ($listfailed) {
    echo $OUTPUT->notification(get_string('listfailed', 'filter_bunkercast'), 'notifyproblem');
} else {
    // Already-authorised videos are dropped from the select: re-authorising is a
    // harmless no-op, but offering it invites the reader to think it does something.
    // Escape here: html_writer::select() passes option labels to html_writer::tag()
    // unescaped, and a video name is whatever the file was called when it was
    // uploaded to Bunkercast.
    $options = [];
    foreach ($videos as $video) {
        if (!isset($authorisedids[$video['fileid']])) {
            $options[$video['fileid']] = s($video['name']);
        }
    }

    if ($options) {
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::div(
            html_writer::label(get_string('choosevideo', 'filter_bunkercast'), 'bunkercast-fileid') . ' ' .
            html_writer::select($options, 'fileid', '', ['' => 'choosedots'], ['id' => 'bunkercast-fileid']) . ' ' .
            html_writer::empty_tag('input', [
                'type'  => 'submit',
                'class' => 'btn btn-primary',
                'value' => get_string('authorise', 'filter_bunkercast'),
            ]),
            'form-inline mb-3'
        );
        echo html_writer::end_tag('form');
    }
}

echo $OUTPUT->heading(get_string('authorisedvideos', 'filter_bunkercast'), 3);

if (!$authorised) {
    echo html_writer::tag('p', get_string('noneauthorised', 'filter_bunkercast'));
} else {
    $table = new html_table();
    $table->head = [
        get_string('colvideo', 'filter_bunkercast'),
        get_string('colwhen', 'filter_bunkercast'),
        get_string('colaction', 'filter_bunkercast'),
    ];
    foreach ($authorised as $row) {
        // A video deleted from Bunkercast, or one the API could not be asked
        // about, still has a row here. Show the id rather than an empty cell —
        // and it is precisely the row most worth being able to remove.
        $table->data[] = [
            s($names[$row->fileid] ?? $row->fileid),
            userdate($row->timecreated, get_string('strftimedatetimeshort')),
            html_writer::link(
                new moodle_url($pageurl, ['remove' => $row->fileid, 'sesskey' => sesskey()]),
                get_string('remove', 'filter_bunkercast')
            ),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
