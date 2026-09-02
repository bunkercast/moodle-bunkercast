<?php
// Web service: list the Bunkercast library for the authoring picker.

namespace filter_bunkercast\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use filter_bunkercast\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Proxies GET /api/videos so the API key stays on the server.
 *
 * Capability-gated: the library belongs to the whole account, so an ungated
 * version would let any logged-in user enumerate every video title the
 * institution holds.
 */
class get_videos extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context the picker was opened in'),
        ]);
    }

    public static function execute(int $contextid): array {
        ['contextid' => $contextid] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
        ]);

        $context = \context::instance_by_id($contextid);
        self::validate_context($context);
        require_capability('filter/bunkercast:browselibrary', $context);

        return ['videos' => api::list_videos()];
    }

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
