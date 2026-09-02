<?php
// Web service: mint a playback URL for the calling user.

namespace filter_bunkercast\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use filter_bunkercast\api;

defined('MOODLE_INTERNAL') || die();

/**
 * The authorisation boundary.
 *
 * Moodle decides whether this user may see this context; only then do we ask
 * Bunkercast for a credential. Enrolment, group restrictions, availability
 * conditions and completion prerequisites are all already reflected in that
 * decision — so unenrolling a student stops the next mint with no further work.
 */
class get_playback_url extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'fileid'    => new external_value(PARAM_ALPHANUMEXT, 'Bunkercast file id (uuid)'),
            'contextid' => new external_value(PARAM_INT, 'Context the video is embedded in'),
        ]);
    }

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
        $key = $USER->id . '_' . $fileid;

        $hit = $cache->get($key);
        if (is_array($hit) && !empty($hit['url']) && ($hit['expires'] ?? 0) > time()) {
            return ['url' => $hit['url']];
        }

        // viewerRef is the Moodle user id: opaque, stable, and within the
        // character set Bunkercast accepts. Never send an email address.
        $minted = api::mint($fileid, (string)$USER->id);

        $cache->set($key, $minted);

        return ['url' => $minted['url']];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'url' => new external_value(PARAM_URL, 'Player URL to load in an iframe'),
        ]);
    }
}
