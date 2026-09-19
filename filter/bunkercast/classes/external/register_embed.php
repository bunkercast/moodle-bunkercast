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
 * Web service: authorise a video for use in a course or activity.
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
use filter_bunkercast\embed;

/**
 * Called by the editor picker when a video is inserted.
 *
 * Everything that decides whether this is allowed lives in embed::register() —
 * the context-level rule and the capability check — so this class is only the
 * transport. The authorise page calls the same method directly.
 */
class register_embed extends external_api {
    /**
     * Describes the parameters accepted by execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fileid'    => new external_value(PARAM_ALPHANUMEXT, 'Bunkercast file id (uuid)'),
            'contextid' => new external_value(PARAM_INT, 'Course or activity the video is being used in'),
        ]);
    }

    /**
     * Authorises the video, or refuses.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param int $contextid Course or activity context.
     * @return array{status: bool}
     * @throws \invalid_parameter_exception If the file id is not a uuid.
     * @throws \moodle_exception If the context cannot hold an authorisation.
     * @throws \required_capability_exception If the user may not publish here.
     */
    public static function execute(string $fileid, int $contextid): array {
        [
            'fileid'    => $fileid,
            'contextid' => $contextid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'fileid'    => $fileid,
            'contextid' => $contextid,
        ]);

        $context = \context::instance_by_id($contextid);
        self::validate_context($context);

        embed::register($fileid, $context);

        return ['status' => true];
    }

    /**
     * Describes the value returned by execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'True when the video is authorised here'),
        ]);
    }
}
