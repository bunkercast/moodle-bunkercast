<?php
// The filter itself.

namespace filter_bunkercast;

defined('MOODLE_INTERNAL') || die();

/**
 * Replaces [bunkercast:<uuid>] with a player container.
 *
 * The container carries only the file id. The playback credential is fetched
 * per user over AJAX (see amd/src/player.js) and never appears in filtered
 * HTML — because every active filter runs over every output string and Moodle
 * caches formatted text in several places, so a token embedded here could be
 * served from cache to a different student.
 */
class text_filter extends \core_filters\text_filter {

    /** Matches [bunkercast:36bb40db-1a39-480f-8148-4346a76388fd] */
    const PATTERN = '/\[bunkercast:([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})\]/';

    public function filter(string $text, array $options = []): string {
        global $PAGE;

        // Cheap guard first. This runs on every string Moodle outputs, so the
        // regex must not be reached unless a placeholder is actually present.
        if (strpos($text, '[bunkercast:') === false) {
            return $text;
        }

        $contextid = $this->context->id;
        $found = 0;

        $result = preg_replace_callback(self::PATTERN, function ($m) use ($contextid, &$found) {
            $found++;
            return \html_writer::div(
                \html_writer::tag('div', get_string('loading', 'filter_bunkercast'), [
                    'class' => 'bunkercast-video-placeholder',
                ]),
                'bunkercast-video',
                [
                    'data-fileid'    => strtolower($m[1]),
                    'data-contextid' => $contextid,
                ]
            );
        }, $text);

        if ($found === 0) {
            return $text;
        }

        // Requiring the module here rather than on every page keeps the JS off
        // pages that contain no video.
        $PAGE->requires->js_call_amd('filter_bunkercast/player', 'init');

        return $result;
    }
}
