<?php
// Web service declarations for filter_bunkercast.

defined('MOODLE_INTERNAL') || die();

$functions = [
    'filter_bunkercast_get_playback_url' => [
        'classname'     => 'filter_bunkercast\external\get_playback_url',
        'description'   => 'Mint a short-lived Bunkercast playback URL for the current user and one video.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'filter_bunkercast_get_videos' => [
        'classname'     => 'filter_bunkercast\external\get_videos',
        'description'   => 'List the Bunkercast library for the authoring picker.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'filter/bunkercast:browselibrary',
    ],
];
