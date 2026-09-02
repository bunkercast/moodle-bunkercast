<?php
// Structure step for backing up a Bunkercast video activity.

defined('MOODLE_INTERNAL') || die();

/**
 * Backs up the instance row.
 *
 * Note what travels: the Bunkercast `fileid` only. There is deliberately no
 * playback credential to back up — credentials are minted per viewer at request
 * time and never stored — so a backup file can never leak access to a video.
 */
class backup_bunkercast_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // No user data in this activity: nothing is recorded per student beyond
        // the standard completion/log tables, which core handles.
        $bunkercast = new backup_nested_element('bunkercast', ['id'], [
            'name', 'intro', 'introformat', 'fileid', 'timecreated', 'timemodified',
        ]);

        $bunkercast->set_source_table('bunkercast', ['id' => backup::VAR_ACTIVITYID]);

        // The description can embed images.
        $bunkercast->annotate_files('mod_bunkercast', 'intro', null);

        return $this->prepare_activity_structure($bunkercast);
    }
}
