<?php
// Cache definitions for filter_bunkercast.

defined('MOODLE_INTERNAL') || die();

$definitions = [

    // Minted playback URLs, keyed "<userid>_<fileid>".
    //
    // Why this cache is not optional: a course page with ten videos opened by a
    // class of thirty is three hundred mint requests, and Bunkercast rate limits
    // per API key. Caching for the token's own lifetime turns that into roughly
    // one request per student per video per lesson.
    //
    // MODE_APPLICATION is shared across users, so the user id is part of the key
    // deliberately and explicitly — a token belongs to one viewer and must never
    // be handed to another. Expiry is checked against the stored 'expires' value
    // rather than a definition-level ttl, because the lifetime is configurable.
    'playbackurl' => [
        'mode'       => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
    ],
];
