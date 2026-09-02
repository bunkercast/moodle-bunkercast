<?php
// Bunkercast video activity.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_bunkercast';
$plugin->version   = 2026090200;
$plugin->requires  = 2024100700;   // Moodle 4.5.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';

// Everything that governs access lives in the filter: the API client, the
// authorising web service, the per-user cache and the player JS. This activity
// is a course-level wrapper around it, so it cannot work alone.
$plugin->dependencies = [
    'filter_bunkercast' => 2026090200,
];
