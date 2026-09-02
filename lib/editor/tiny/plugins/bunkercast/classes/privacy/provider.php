<?php
// Privacy declaration for tiny_bunkercast.

namespace tiny_bunkercast\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * The picker stores nothing and transmits nothing about the user. It lists the
 * site's own video library and inserts a placeholder into content being edited.
 *
 * The playback side — which does send an opaque viewer reference to Bunkercast
 * — belongs to filter_bunkercast, and is declared there.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
