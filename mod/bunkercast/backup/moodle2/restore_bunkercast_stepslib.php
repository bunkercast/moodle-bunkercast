<?php
// Structure step for restoring a Bunkercast video activity.

defined('MOODLE_INTERNAL') || die();

class restore_bunkercast_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('bunkercast', '/activity/bunkercast');

        return $this->prepare_activity_structure($paths);
    }

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

    protected function after_execute() {
        $this->add_related_files('mod_bunkercast', 'intro', null);
    }
}
