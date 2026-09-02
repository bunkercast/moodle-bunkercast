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
 * The filter itself.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast;

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
    /** @var string Matches [bunkercast:36bb40db-1a39-480f-8148-4346a76388fd]. */
    const PATTERN = '/\[bunkercast:([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})\]/';

    /** @var bool Guard against requiring the module more than once per request. */
    protected static bool $jsrequired = false;

    /**
     * Load the player module.
     *
     * This must happen here rather than in filter(). By the time filter() runs
     * the page's JS requirements have usually already been written to the head,
     * so a js_call_amd() there silently never loads — the container renders and
     * nothing ever replaces it. Every core filter that needs JS uses this hook
     * (see filter_mathjaxloader and filter_glossary).
     *
     * The cost is that the module loads on any page where the filter is active,
     * including pages with no video. init() is a no-op when it finds no
     * containers, which is the same trade core makes.
     *
     * @param \moodle_page $page
     * @param \context $context
     */
    public function setup($page, $context) {
        if (self::$jsrequired) {
            return;
        }
        self::$jsrequired = true;

        $page->requires->js_call_amd('filter_bunkercast/player', 'init');
    }

    /**
     * Replaces each placeholder in the text with an empty player container.
     *
     * The signature must match the parent exactly: core_filters\text_filter
     * declares `abstract public function filter($text, array $options = [])`
     * with no type on $text and no return type. PHP forbids narrowing an
     * untyped parameter to `string`, so the typed signature shown in the Moodle
     * developer documentation is a fatal error at load time.
     *
     * @param string $text Text to filter.
     * @param array $options Filter options, unused here.
     * @return string
     */
    public function filter($text, array $options = []) {
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

        return $result;
    }
}
