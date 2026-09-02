<?php
// Capabilities for filter_bunkercast.

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Required to list the Bunkercast library.
    //
    // This gates the picker, and it is not cosmetic: the library is the whole
    // account's, so without a capability check any logged-in user could
    // enumerate every video title the institution holds — including material
    // from other courses and departments.
    'filter/bunkercast:browselibrary' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
];
