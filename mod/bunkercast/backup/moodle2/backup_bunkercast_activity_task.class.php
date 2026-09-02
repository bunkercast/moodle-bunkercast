<?php
// Backup task for mod_bunkercast.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/bunkercast/backup/moodle2/backup_bunkercast_stepslib.php');

class backup_bunkercast_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // None.
    }

    protected function define_my_steps() {
        $this->add_step(new backup_bunkercast_activity_structure_step('bunkercast_structure', 'bunkercast.xml'));
    }

    /**
     * Encode links to this activity's scripts so they survive a restore into a
     * different course or site.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = "/(" . $base . "\/mod\/bunkercast\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BUNKERCASTINDEX*$2@$', $content);

        $search = "/(" . $base . "\/mod\/bunkercast\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@BUNKERCASTVIEWBYID*$2@$', $content);

        return $content;
    }
}
