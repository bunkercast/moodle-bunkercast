<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Cache definitions for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
