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
 * Web service: list the Bunkercast library for the authoring picker.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use filter_bunkercast\api;

/**
 * Proxies GET /api/videos so the API key stays on the server.
 *
 * Capability-gated: the library belongs to the whole account, so an ungated
 * version would let any logged-in user enumerate every video title the
 * institution holds.
 */
class get_videos extends external_api {
    /**
     * Describes the parameters accepted by execute().
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context the picker was opened in'),
        ]);
    }

    /**
     * Returns the account's ready videos for a user allowed to browse the library.
     *
     * @param int $contextid Context the picker was opened in.
     * @return array{videos: array}
     * @throws \required_capability_exception If the user may not browse the library.
     * @throws \moodle_exception If Bunkercast cannot be reached.
     */
    public static function execute(int $contextid): array {
        ['contextid' => $contextid] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
        ]);

        $context = \context::instance_by_id($contextid);
        self::validate_context($context);
        require_capability('filter/bunkercast:browselibrary', $context);

        return ['videos' => api::list_videos()];
    }

    /**
     * Describes the value returned by execute().
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'videos' => new external_multiple_structure(
                new external_single_structure([
                    'fileid'          => new external_value(PARAM_ALPHANUMEXT, 'Bunkercast file id'),
                    'name'            => new external_value(PARAM_TEXT, 'Original filename'),
                    'durationminutes' => new external_value(PARAM_FLOAT, 'Length in minutes'),
                ])
            ),
        ]);
    }
}
