<?php
// Core callbacks for mod_bunkercast.

defined('MOODLE_INTERNAL') || die();

/**
 * @param string $feature
 * @return mixed
 */
function bunkercast_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            // Deliberately false until backup/ is written. Claiming support
            // without it would let a course duplicate silently drop the video.
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            // "Student must view this activity to complete it" — the honest
            // limit of what we can report. Real watch-time would need the
            // player to send heartbeats, which a third-party embed cannot.
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
 * @param stdClass $data from mod_form
 * @return int new instance id
 */
function bunkercast_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    return $DB->insert_record('bunkercast', $data);
}

/**
 * @param stdClass $data from mod_form
 * @return bool
 */
function bunkercast_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('bunkercast', $data);
}

/**
 * @param int $id instance id
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
