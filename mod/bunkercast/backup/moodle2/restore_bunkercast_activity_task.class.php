<?php
// Restore task for mod_bunkercast.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bunkercast/backup/moodle2/restore_bunkercast_stepslib.php');

class restore_bunkercast_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // None.
    }

    protected function define_my_steps() {
        $this->add_step(new restore_bunkercast_activity_structure_step('bunkercast_structure', 'bunkercast.xml'));
    }

    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('bunkercast', ['intro'], 'bunkercast');

        return $contents;
    }

    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('BUNKERCASTVIEWBYID', '/mod/bunkercast/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('BUNKERCASTINDEX', '/mod/bunkercast/index.php?id=$1', 'course');

        return $rules;
    }

    public static function define_restore_log_rules() {
        return [];
    }
}
