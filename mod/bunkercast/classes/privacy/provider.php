<?php
// Privacy declaration for mod_bunkercast.

namespace mod_bunkercast\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * The activity stores which video it shows — course content, not personal data.
 *
 * The playback side, which sends an opaque viewer reference to Bunkercast, is
 * filter_bunkercast's and declared there.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
