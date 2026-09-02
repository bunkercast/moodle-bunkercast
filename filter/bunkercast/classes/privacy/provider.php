<?php
// Privacy declaration for filter_bunkercast.

namespace filter_bunkercast\privacy;

use core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * This plugin stores no personal data of its own, but it does send an opaque
 * reference to the viewing user to a third party, so it cannot be a
 * null_provider — the external transmission has to be declared.
 *
 * Nothing is stored locally: minted URLs live only in a volatile cache keyed by
 * user id, which Moodle purges with the rest of the application cache, so there
 * is nothing to export or delete on request.
 */
class provider implements \core_privacy\local\metadata\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'bunkercast',
            [
                'viewerref' => 'privacy:metadata:bunkercast:viewerref',
                'fileid'    => 'privacy:metadata:bunkercast:fileid',
            ],
            'privacy:metadata:bunkercast'
        );

        return $collection;
    }
}
