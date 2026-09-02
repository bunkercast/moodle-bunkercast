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
 * Tests for the playback-URL cache definition.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast;

/**
 * Tests for the playback-URL cache definition.
 *
 * Two things about this cache are load-bearing, and both have already gone
 * wrong once, so they are pinned here.
 *
 * @coversNothing
 */
final class cache_test extends \advanced_testcase {
    /** @var string A well-formed Bunkercast file id. */
    const FILEID = '36bb40db-1a39-480f-8148-4346a76388fd';

    /**
     * The key must have the uuid's hyphens stripped.
     *
     * The definition sets simplekeys, which Moodle restricts to [a-zA-Z0-9_].
     * Passing a raw uuid throws a coding_exception from inside cache::get() —
     * which surfaces to the viewer as "this video is currently unavailable" and
     * points nowhere near the cache. Stripping the hyphens is lossless: 32 hex
     * characters are still unique.
     */
    public function test_raw_uuid_is_not_a_valid_simple_key(): void {
        $this->resetAfterTest();

        $cache = \cache::make('filter_bunkercast', 'playbackurl');

        $this->expectException(\coding_exception::class);

        $cache->set('7_' . self::FILEID, ['url' => 'https://example.invalid/', 'expires' => time() + 60]);
    }

    /**
     * The stripped key round-trips.
     */
    public function test_stripped_key_round_trips(): void {
        $this->resetAfterTest();

        $cache = \cache::make('filter_bunkercast', 'playbackurl');
        $key = '7_' . str_replace('-', '', self::FILEID);
        $value = ['url' => 'https://example.invalid/embed', 'expires' => time() + 60];

        $this->assertTrue($cache->set($key, $value));
        $this->assertSame($value, $cache->get($key));
    }

    /**
     * The cache is shared across users, so the user id must be part of the key.
     *
     * MODE_APPLICATION is not per-user. A token belongs to one viewer and must
     * never be handed to another, so two users asking for the same video have to
     * land on different keys.
     */
    public function test_two_users_do_not_share_an_entry(): void {
        $this->resetAfterTest();

        $cache = \cache::make('filter_bunkercast', 'playbackurl');
        $stripped = str_replace('-', '', self::FILEID);

        $cache->set('7_' . $stripped, ['url' => 'user-7-url', 'expires' => time() + 60]);
        $cache->set('8_' . $stripped, ['url' => 'user-8-url', 'expires' => time() + 60]);

        $this->assertSame('user-7-url', $cache->get('7_' . $stripped)['url']);
        $this->assertSame('user-8-url', $cache->get('8_' . $stripped)['url']);
    }
}
