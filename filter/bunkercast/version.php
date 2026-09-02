<?php
// Bunkercast DRM video filter for Moodle.
//
// Turns a [bunkercast:<file-id>] placeholder in any Moodle content into a
// DRM-protected player, with the playback credential minted per viewer at
// request time rather than stored in the content.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'filter_bunkercast';
$plugin->version   = 2026090200;

// Moodle 4.5. That is where filter classes moved to classes/text_filter.php;
// on 4.1-4.4 the class must live in filter.php instead. Supporting those needs
// a class_alias shim in the old location — see README.
$plugin->requires  = 2024100700;

$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
