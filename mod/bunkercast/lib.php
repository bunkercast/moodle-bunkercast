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
 * Core callbacks for mod_bunkercast.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Reports which optional module features this activity supports.
 *
 * @param string $feature One of the FEATURE_* constants.
 * @return mixed True/false for supported features, a value for FEATURE_MOD_PURPOSE, null if unknown.
 */
function bunkercast_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            // Offers "student must view this activity to complete it" — the
            // honest limit of what we can report. Real watch-time would need
            // the player to send heartbeats, which a third-party embed cannot.
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Stores a new activity instance.
 *
 * @param stdClass $data Submitted values from mod_form.
 * @param mixed $mform The form itself, unused here.
 * @return int New instance id.
 */
function bunkercast_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // Before the insert, and before the update in the sibling below, so that a
    // refusal leaves nothing half-created. It can refuse: the front-page course
    // is not somewhere a Bunkercast video can be authorised, so adding this
    // activity there fails with cannotembedhere rather than saving an activity
    // that could never play.
    bunkercast_authorise_video($data);

    return $DB->insert_record('bunkercast', $data);
}

/**
 * Authorises the chosen video for the course this activity belongs to.
 *
 * filter_bunkercast refuses to mint a playback link unless the video has been
 * authorised where it is being shown, so without this the activity would save
 * cleanly and then fail to play.
 *
 * Authorises at the COURSE context, not this activity's own: the course grant
 * already covers every activity inside it, it is the same grant the filter's
 * authorise page makes, and it avoids depending on when the module context comes
 * into existence relative to this function. Access control is unaffected — the
 * viewer is still checked against the activity's own context at playback, so
 * group restrictions and availability conditions apply exactly as before.
 *
 * Uses grant() rather than register() deliberately: mod/bunkercast:addinstance
 * has already been enforced, and a filter capability failure here would block
 * saving an activity the user was entitled to create.
 *
 * @param stdClass $data Submitted values from mod_form; needs fileid and course.
 * @return void
 */
function bunkercast_authorise_video($data) {
    if (empty($data->fileid) || empty($data->course)) {
        return;
    }

    \filter_bunkercast\embed::grant($data->fileid, context_course::instance($data->course));
}

/**
 * Saves changes to an existing activity instance.
 *
 * @param stdClass $data Submitted values from mod_form.
 * @param mixed $mform The form itself, unused here.
 * @return bool
 */
function bunkercast_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    bunkercast_authorise_video($data);

    return $DB->update_record('bunkercast', $data);
}

/**
 * Removes an activity instance, leaving the video in Bunkercast untouched.
 *
 * @param int $id Instance id.
 * @return bool
 */
function bunkercast_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('bunkercast', ['id' => $id])) {
        return false;
    }

    // Note what is NOT done here: nothing is deleted in Bunkercast. The video
    // belongs to the account, may be used by other activities or courses, and
    // removing an activity must never destroy content.
    $cm = get_coursemodule_from_instance('bunkercast', $id);
    if ($cm) {
        \core_completion\api::update_completion_date_event($cm->id, 'bunkercast', $id, null);
    }

    return $DB->delete_records('bunkercast', ['id' => $id]);
}
