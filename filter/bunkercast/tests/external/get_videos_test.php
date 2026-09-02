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
 * Tests for the library-listing web service.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast\external;

/**
 * Tests for the library-listing web service.
 *
 * The library belongs to the whole Bunkercast account, so this service is
 * capability-gated: without the gate any logged-in user could enumerate every
 * video title the institution holds, including material from other courses and
 * departments.
 *
 * @covers \filter_bunkercast\external\get_videos
 */
final class get_videos_test extends \advanced_testcase {
    /**
     * A student may not enumerate the institution's video library.
     */
    public function test_student_cannot_browse_library(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $this->expectException(\required_capability_exception::class);

        get_videos::execute(\context_course::instance($course->id)->id);
    }

    /**
     * An editing teacher holds the capability.
     *
     * As with minting, this is proven by how far execution gets: past
     * require_capability() and into api::list_videos(), which then fails on the
     * unconfigured API key.
     */
    public function test_editing_teacher_holds_the_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        try {
            get_videos::execute(\context_course::instance($course->id)->id);
            $this->fail('Expected the missing API key to stop this, not the capability check');
        } catch (\moodle_exception $e) {
            $this->assertSame('notconfigured', $e->errorcode);
        }
    }

    /**
     * An unenrolled user is refused before the capability is even considered.
     */
    public function test_unenrolled_user_is_refused(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_user());

        try {
            get_videos::execute(\context_course::instance($course->id)->id);
            $this->fail('An unenrolled user must not list the library');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('require_login', get_class($e));
        }
    }

    /**
     * Each listed video exposes an id, a name and a duration — and nothing more.
     */
    public function test_returns_id_name_and_duration(): void {
        $returns = get_videos::execute_returns();

        $this->assertInstanceOf(\core_external\external_single_structure::class, $returns);
        $this->assertSame(['videos'], array_keys($returns->keys));
        $this->assertSame(
            ['fileid', 'name', 'durationminutes'],
            array_keys($returns->keys['videos']->content->keys)
        );
    }
}
