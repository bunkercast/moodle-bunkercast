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
 * Web service: mint a playback URL for the calling user.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use filter_bunkercast\api;

/**
 * The authorisation boundary.
 *
 * Moodle decides whether this user may see this context; only then do we ask
 * Bunkercast for a credential. Enrolment, group restrictions, availability
 * conditions and completion prerequisites are all already reflected in that
 * decision — so unenrolling a student stops the next mint with no further work.
 */
class get_playback_url extends external_api {
    /**
     * Describes the parameters accepted by execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fileid'    => new external_value(PARAM_ALPHANUMEXT, 'Bunkercast file id (uuid)'),
            'contextid' => new external_value(PARAM_INT, 'Context the video is embedded in'),
        ]);
    }

    /**
     * Mints a short-lived playback URL for the calling user, or refuses.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param int $contextid Context the placeholder was rendered in.
     * @return array{url: string}
     * @throws \invalid_parameter_exception If the file id is not a uuid.
     * @throws \required_capability_exception If the user cannot access the context.
     * @throws \moodle_exception If Bunkercast refuses to mint.
     */
    public static function execute(string $fileid, int $contextid): array {
        global $USER;

        [
            'fileid'    => $fileid,
            'contextid' => $contextid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'fileid'    => $fileid,
            'contextid' => $contextid,
        ]);

        // PARAM_ALPHANUMEXT permits hyphens but not a uuid shape, so check it.
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', strtolower($fileid))) {
            throw new \invalid_parameter_exception('fileid is not a uuid');
        }
        $fileid = strtolower($fileid);

        // This is the check that matters: it throws unless the user can access
        // the context the placeholder was rendered in.
        $context = \context::instance_by_id($contextid);
        self::validate_context($context);

        $cache = \cache::make('filter_bunkercast', 'playbackurl');

        // The definition uses simplekeys, which Moodle restricts to
        // [a-zA-Z0-9_] — so the uuid's hyphens have to go or cache::get()
        // throws a coding_exception. Stripping them is lossless: 32 hex
        // characters are still unique, and simple keys avoid the hashing
        // overhead of the general case.
        $key = $USER->id . '_' . str_replace('-', '', $fileid);

        $hit = $cache->get($key);
        if (is_array($hit) && !empty($hit['url']) && ($hit['expires'] ?? 0) > time()) {
            return ['url' => $hit['url']];
        }

        // The viewerRef is the Moodle user id: opaque, stable, and within the
        // character set Bunkercast accepts. Never send an email address.
        $minted = api::mint($fileid, (string)$USER->id);

        $cache->set($key, $minted);

        return ['url' => $minted['url']];
    }

    /**
     * Describes the value returned by execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'url' => new external_value(PARAM_URL, 'Player URL to load in an iframe'),
        ]);
    }
}
