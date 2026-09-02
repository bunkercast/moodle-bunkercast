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
 * Tests for the playback-URL web service.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast\external;

/**
 * Tests for the playback-URL web service — the plugin's authorisation boundary.
 *
 * None of these tests reach the network. They stop either at Moodle's access
 * check or at the missing-API-key check inside api::mint(), and which of the two
 * is reached is exactly what is being asserted: refusal for a user who may not
 * see the context, and "got as far as needing a key" for one who may.
 *
 * @covers \filter_bunkercast\external\get_playback_url
 */
final class get_playback_url_test extends \advanced_testcase {
    /** @var string A well-formed Bunkercast file id. */
    const FILEID = '36bb40db-1a39-480f-8148-4346a76388fd';

    /**
     * A user who is not enrolled must be refused.
     *
     * This is the security property the plugin exists to provide: access is
     * Moodle's decision, so unenrolling a student stops the next mint with no
     * further work anywhere.
     */
    public function test_unenrolled_user_is_refused(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $outsider = $this->getDataGenerator()->create_user();
        $this->setUser($outsider);

        try {
            get_playback_url::execute(self::FILEID, \context_course::instance($course->id)->id);
            $this->fail('An unenrolled user must not obtain a playback URL');
        } catch (\moodle_exception $e) {
            // Refusal comes from require_login(), so the exception is a
            // require_login_exception rather than anything of ours. Matching on
            // the class name keeps this robust across Moodle's move of core
            // exceptions into the core\exception namespace.
            $this->assertStringContainsString('require_login', get_class($e));
        }
    }

    /**
     * An enrolled student passes the access check.
     *
     * Proven by how far execution gets: past validate_context() and into
     * api::mint(), which then fails on the unconfigured API key. Reaching
     * 'notconfigured' means authorisation succeeded.
     */
    public function test_enrolled_student_passes_the_access_check(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        try {
            get_playback_url::execute(self::FILEID, \context_course::instance($course->id)->id);
            $this->fail('Expected the missing API key to stop this, not the access check');
        } catch (\moodle_exception $e) {
            $this->assertSame('notconfigured', $e->errorcode);
        }
    }

    /**
     * A file id that is not a uuid is rejected before anything else happens.
     *
     * PARAM_ALPHANUMEXT permits hyphens but knows nothing of uuid shape, so the
     * explicit check is what stops a malformed id reaching Bunkercast.
     *
     * @dataProvider bad_fileid_provider
     * @param string $fileid A file id that must be rejected.
     */
    public function test_malformed_fileid_rejected(string $fileid): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $this->expectException(\invalid_parameter_exception::class);

        get_playback_url::execute($fileid, \context_course::instance($course->id)->id);
    }

    /**
     * File ids that must be rejected.
     *
     * @return array<string, array{string}>
     */
    public static function bad_fileid_provider(): array {
        return [
            'too short'  => ['36bb40db'],
            'no hyphens' => ['36bb40db1a39480f81484346a76388fd'],
            'not hex'    => ['zzzzzzzz-1a39-480f-8148-4346a76388fd'],
            'empty'      => [''],
        ];
    }

    /**
     * A context id that does not exist is rejected rather than ignored.
     */
    public function test_nonexistent_context_rejected(): void {
        $this->resetAfterTest();

        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\moodle_exception::class);

        get_playback_url::execute(self::FILEID, 999999999);
    }

    /**
     * The declared return structure is a single URL and nothing else.
     *
     * Worth pinning: anything added here would end up in filtered pages and in
     * Moodle's web-service documentation.
     */
    public function test_returns_only_a_url(): void {
        $returns = get_playback_url::execute_returns();

        $this->assertInstanceOf(\core_external\external_single_structure::class, $returns);
        $this->assertSame(['url'], array_keys($returns->keys));
    }
}
