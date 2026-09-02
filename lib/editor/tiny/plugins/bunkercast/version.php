<?php
// TinyMCE picker for the Bunkercast DRM video filter.
//
// Authoring convenience only: it inserts the same [bunkercast:<uuid>]
// placeholder a teacher could type by hand. All playback and access-control
// logic lives in filter_bunkercast, which this plugin depends on.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'tiny_bunkercast';
$plugin->version   = 2026090200;
$plugin->requires  = 2024100700;   // Moodle 4.5.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';

// The picker calls filter_bunkercast's web service and inserts its placeholder,
// so it is useless on its own.
$plugin->dependencies = [
    'filter_bunkercast' => 2026090200,
];
