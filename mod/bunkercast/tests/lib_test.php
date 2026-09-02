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
 * Tests for the mod_bunkercast core callbacks.
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_bunkercast;

/**
 * Tests for the mod_bunkercast core callbacks.
 *
 * @coversNothing
 */
final class lib_test extends \advanced_testcase {
    /**
     * Loads lib.php, which is not autoloaded.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/mod/bunkercast/lib.php');
    }

    /**
     * The supported-features answers.
     *
     * Backup matters because it was off at first and a course backup silently
     * dropped the activity. Grading is deliberately absent: nothing here can be
     * graded honestly.
     *
     * @dataProvider supports_provider
     * @param string $feature The FEATURE_* constant name.
     * @param mixed $expected What bunkercast_supports() must answer.
     */
    public function test_supports(string $feature, $expected): void {
        $this->assertSame($expected, bunkercast_supports(constant($feature)));
    }

    /**
     * Feature expectations.
     *
     * @return array<string, array{string, mixed}>
     */
    public static function supports_provider(): array {
        return [
            'intro'            => ['FEATURE_MOD_INTRO', true],
            'show description' => ['FEATURE_SHOW_DESCRIPTION', true],
            'backup'           => ['FEATURE_BACKUP_MOODLE2', true],
            'completion views' => ['FEATURE_COMPLETION_TRACKS_VIEWS', true],
            'groups'           => ['FEATURE_GROUPS', true],
            'groupings'        => ['FEATURE_GROUPINGS', true],
            'no grading'       => ['FEATURE_GRADE_HAS_GRADE', false],
            'purpose'          => ['FEATURE_MOD_PURPOSE', MOD_PURPOSE_CONTENT],
        ];
    }

    /**
     * An unknown feature returns null rather than false.
     *
     * Moodle distinguishes the two: false means "supported and off", null means
     * "this module knows nothing about that feature".
     */
    public function test_unknown_feature_is_null(): void {
        $this->assertNull(bunkercast_supports('no_such_feature'));
    }

    /**
     * Creating an instance stores the file id and stamps both timestamps.
     */
    public function test_add_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $fileid = 'e59f22b5-8993-49db-aa7d-c0fa09621bd9';

        $instance = $this->getDataGenerator()->create_module('bunkercast', [
            'course' => $course->id,
            'name'   => 'Lecture 1',
            'fileid' => $fileid,
        ]);

        $row = $DB->get_record('bunkercast', ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertSame($fileid, $row->fileid);
        $this->assertSame('Lecture 1', $row->name);
        $this->assertGreaterThan(0, (int)$row->timecreated);
        $this->assertSame((int)$row->timecreated, (int)$row->timemodified);
    }

    /**
     * Updating an instance can swap the video and moves timemodified only.
     */
    public function test_update_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('bunkercast', ['course' => $course->id]);
        $before = $DB->get_record('bunkercast', ['id' => $instance->id], '*', MUST_EXIST);

        $replacement = 'e59f22b5-8993-49db-aa7d-c0fa09621bd9';
        $data = (object)[
            'instance' => $instance->id,
            'course'   => $course->id,
            'name'     => 'Lecture 1 (revised)',
            'fileid'   => $replacement,
        ];
        $this->assertTrue(bunkercast_update_instance($data));

        $after = $DB->get_record('bunkercast', ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertSame($replacement, $after->fileid);
        $this->assertSame('Lecture 1 (revised)', $after->name);
        $this->assertSame((int)$before->timecreated, (int)$after->timecreated);
        $this->assertGreaterThanOrEqual((int)$before->timemodified, (int)$after->timemodified);
    }

    /**
     * Deleting an instance removes the row.
     */
    public function test_delete_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('bunkercast', ['course' => $course->id]);

        $this->assertTrue(bunkercast_delete_instance($instance->id));
        $this->assertFalse($DB->record_exists('bunkercast', ['id' => $instance->id]));
    }

    /**
     * Deleting an instance that is already gone reports failure, not a crash.
     */
    public function test_delete_missing_instance(): void {
        $this->resetAfterTest();

        $this->assertFalse(bunkercast_delete_instance(999999999));
    }

    /**
     * Deleting one activity must not touch another that shows the same video.
     *
     * The video belongs to the Bunkercast account, not to the activity — the
     * same file is expected to be reused across activities and courses.
     */
    public function test_delete_leaves_a_sibling_alone(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $shared = '36bb40db-1a39-480f-8148-4346a76388fd';
        $first = $this->getDataGenerator()->create_module('bunkercast', [
            'course' => $course->id, 'fileid' => $shared,
        ]);
        $second = $this->getDataGenerator()->create_module('bunkercast', [
            'course' => $course->id, 'fileid' => $shared,
        ]);

        bunkercast_delete_instance($first->id);

        $survivor = $DB->get_record('bunkercast', ['id' => $second->id], '*', MUST_EXIST);
        $this->assertSame($shared, $survivor->fileid);
    }
}
