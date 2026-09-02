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
 * Tests for the Bunkercast text filter.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast;

/**
 * Tests for the Bunkercast text filter.
 *
 * @covers \filter_bunkercast\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    /** @var string A well-formed Bunkercast file id. */
    const FILEID = '36bb40db-1a39-480f-8148-4346a76388fd';

    /**
     * Builds a filter bound to the system context.
     *
     * @return text_filter
     */
    protected function make_filter(): text_filter {
        return new text_filter(\context_system::instance(), []);
    }

    /**
     * A placeholder becomes a container carrying the file id and context id.
     */
    public function test_placeholder_becomes_container(): void {
        $this->resetAfterTest();

        $context = \context_system::instance();
        $out = (new text_filter($context, []))->filter('[bunkercast:' . self::FILEID . ']');

        $this->assertStringContainsString('data-fileid="' . self::FILEID . '"', $out);
        $this->assertStringContainsString('data-contextid="' . $context->id . '"', $out);
        $this->assertStringContainsString('bunkercast-video', $out);
        $this->assertStringNotContainsString('[bunkercast:', $out);
    }

    /**
     * No playback credential may appear in filtered HTML.
     *
     * This is the property the whole design rests on: every active filter runs
     * over every output string and Moodle caches formatted text in several
     * places, so a token emitted here could be served from cache to a different
     * student. The credential is fetched per user over AJAX instead.
     */
    public function test_output_carries_no_credential(): void {
        $this->resetAfterTest();

        $out = $this->make_filter()->filter('[bunkercast:' . self::FILEID . ']');

        $this->assertStringNotContainsString('iframe', $out);
        $this->assertStringNotContainsString('http', $out);
        $this->assertStringNotContainsString('token', $out);
        $this->assertStringNotContainsString('pt=', $out);
    }

    /**
     * Text with no placeholder is returned byte-identical.
     */
    public function test_plain_text_untouched(): void {
        $this->resetAfterTest();

        $filter = $this->make_filter();

        foreach (['', 'plain text', '<p>Some <b>HTML</b></p>', 'bunkercast without brackets'] as $text) {
            $this->assertSame($text, $filter->filter($text));
        }
    }

    /**
     * Text around a placeholder survives untouched.
     */
    public function test_surrounding_text_preserved(): void {
        $this->resetAfterTest();

        $out = $this->make_filter()->filter('<p>Before</p>[bunkercast:' . self::FILEID . ']<p>After</p>');

        $this->assertStringContainsString('<p>Before</p>', $out);
        $this->assertStringContainsString('<p>After</p>', $out);
    }

    /**
     * Every placeholder in a string is replaced, not just the first.
     */
    public function test_multiple_placeholders(): void {
        $this->resetAfterTest();

        $second = 'e59f22b5-8993-49db-aa7d-c0fa09621bd9';
        $out = $this->make_filter()->filter(
            '[bunkercast:' . self::FILEID . '] and [bunkercast:' . $second . ']'
        );

        $this->assertStringContainsString('data-fileid="' . self::FILEID . '"', $out);
        $this->assertStringContainsString('data-fileid="' . $second . '"', $out);
        $this->assertSame(2, substr_count($out, 'bunkercast-video"'));
    }

    /**
     * A file id typed in capitals still resolves; Bunkercast ids are lowercase.
     */
    public function test_uppercase_fileid_normalised(): void {
        $this->resetAfterTest();

        $out = $this->make_filter()->filter('[bunkercast:' . strtoupper(self::FILEID) . ']');

        $this->assertStringContainsString('data-fileid="' . self::FILEID . '"', $out);
    }

    /**
     * Anything that is not a uuid is left exactly as the author typed it.
     *
     * Leaving the raw text visible is deliberate: a teacher who mistypes an id
     * sees their own text rather than a silently empty player.
     *
     * @dataProvider malformed_placeholder_provider
     * @param string $text Text containing something that resembles a placeholder.
     */
    public function test_malformed_placeholders_left_alone(string $text): void {
        $this->resetAfterTest();

        $this->assertSame($text, $this->make_filter()->filter($text));
    }

    /**
     * Placeholders that must not match.
     *
     * @return array<string, array{string}>
     */
    public static function malformed_placeholder_provider(): array {
        return [
            'too short'        => ['[bunkercast:36bb40db]'],
            'not hex'          => ['[bunkercast:zzzzzzzz-1a39-480f-8148-4346a76388fd]'],
            'no hyphens'       => ['[bunkercast:36bb40db1a39480f81484346a76388fd]'],
            'empty'            => ['[bunkercast:]'],
            'unclosed'         => ['[bunkercast:36bb40db-1a39-480f-8148-4346a76388fd'],
            'wrong keyword'    => ['[bunkercst:36bb40db-1a39-480f-8148-4346a76388fd]'],
            'trailing garbage' => ['[bunkercast:36bb40db-1a39-480f-8148-4346a76388fdX]'],
        ];
    }

    /**
     * The container reports the context it was rendered in, which is what the
     * web service later authorises against.
     */
    public function test_contextid_is_the_rendering_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $out = (new text_filter($coursecontext, []))->filter('[bunkercast:' . self::FILEID . ']');

        $this->assertStringContainsString('data-contextid="' . $coursecontext->id . '"', $out);
    }
}
