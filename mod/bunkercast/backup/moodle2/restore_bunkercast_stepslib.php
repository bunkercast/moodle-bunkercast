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
 * Structure step for restoring a Bunkercast video activity.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Recreates the instance row from bunkercast.xml.
 */
class restore_bunkercast_activity_structure_step extends restore_activity_structure_step {
    /**
     * Declares the paths this step handles.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('bunkercast', '/activity/bunkercast');

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Inserts one restored activity instance.
     *
     * @param array|stdClass $data The parsed /activity/bunkercast element.
     * @return void
     */
    protected function process_bunkercast($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // The fileid is carried across verbatim, which is right when the
        // destination Moodle points at the SAME Bunkercast account.
        //
        // Restoring into a Moodle configured with a DIFFERENT account leaves an
        // activity referring to a video that account does not own; playback then
        // fails with a "video unavailable" message, because Bunkercast refuses
        // to mint for a file the key's owner does not hold. That is the correct
        // outcome — it must not be possible to inherit someone else's video by
        // restoring a backup — but it is worth knowing before moving courses
        // between sites.
        $newitemid = $DB->insert_record('bunkercast', $data);

        $this->apply_activity_instance($newitemid);
    }

    /**
     * Reattaches embedded files, and authorises the video for the restored activity.
     *
     * @return void
     */
    protected function after_execute() {
        global $DB;

        $this->add_related_files('mod_bunkercast', 'intro', null);

        // Restore and course import both bypass bunkercast_add_instance(), so
        // without this the video arrives unauthorised and refuses to play.
        //
        // It happens here rather than in process_bunkercast() because the module
        // context does not exist until apply_activity_instance() has run, and the
        // authorisation belongs against the activity: stored against the course it
        // would be matched by any request naming the course, and the restrictions
        // on this activity would never be checked.
        //
        // grant() skips the filter capability check deliberately — the restoring
        // user holds moodle/restore:*, not necessarily
        // filter/bunkercast:browselibrary.
        $fileid = $DB->get_field('bunkercast', 'fileid', ['id' => $this->task->get_activityid()]);
        if (!$fileid) {
            return;
        }

        try {
            \filter_bunkercast\embed::grant($fileid, \context::instance_by_id($this->task->get_contextid()));
        } catch (\moodle_exception $e) {
            // Never abort a restore over this. A malformed file id in an old or
            // hand-crafted backup, or a target where a video cannot be authorised,
            // leaves the activity restored and the video refusing to play — which
            // is the correct way for it to fail, and repairable from the course's
            // authorise page.
            debugging('filter_bunkercast: could not authorise restored video: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
