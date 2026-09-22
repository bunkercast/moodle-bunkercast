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
 * Tests for the authorisation record.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast;

/**
 * Tests for embed — which videos may play in which places.
 *
 * Two of these exist because a design that looked right failed review. Test 1
 * pins the rule that a placeholder a user can author must not authorise itself;
 * test 3 pins the direction of the course/activity relationship, which is silent
 * and total if inverted.
 *
 * @covers \filter_bunkercast\embed
 */
final class embed_test extends \advanced_testcase {
    /** @var string A well-formed Bunkercast file id. */
    const FILEID = '36bb40db-1a39-480f-8148-4346a76388fd';

    /**
     * Authoring a placeholder must not authorise it.
     *
     * THE test. An earlier design recorded the authorisation when the filter
     * rendered a placeholder, which any student defeats: filters run over forum
     * posts, profile descriptions and blog entries, and a blog entry renders at
     * the SYSTEM context, so the binding wrote itself at the most privileged
     * context on the site with no enrolment anywhere.
     *
     * Rendering is not asserted here — nothing renders. That is the point: no
     * path a student can reach creates a row, so there is nothing for the filter
     * to write and nothing for them to match against.
     */
    public function test_a_student_cannot_authorise_by_authoring(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $coursecontext = \context_course::instance($course->id);

        // The context a blog entry is filtered in, and a course they are in.
        $this->assertFalse(embed::is_authorised(self::FILEID, \context_system::instance()));
        $this->assertFalse(embed::is_authorised(self::FILEID, $coursecontext));
        $this->assertFalse(embed::is_authorised(self::FILEID, \context_user::instance($student->id)));

        // And they cannot create one either. Assert on the exception type and on
        // the table, not on the message — Moodle renders the capability's lang
        // string there, not its identifier.
        global $DB;
        try {
            embed::register(self::FILEID, $coursecontext);
            $this->fail('A student must not be able to authorise a video');
        } catch (\required_capability_exception $e) {
            $this->assertSame(0, $DB->count_records(embed::TABLE));
        }
    }

    /**
     * Nothing above a course may hold an authorisation.
     *
     * Run as a site admin on purpose. Moodle does NOT enforce a capability's
     * declared contextlevel — has_capability() never reads it, and an admin
     * short-circuits every check — so without an explicit rule an admin could
     * create a system-context authorisation, which would authorise the file id
     * for the entire site.
     */
    public function test_nothing_above_a_course_may_be_authorised(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();
        $user = $this->getDataGenerator()->create_user();

        $refused = [
            'system'   => \context_system::instance(),
            'category' => \context_coursecat::instance($category->id),
            'user'     => \context_user::instance($user->id),
            'frontpage' => \context_course::instance(SITEID),
        ];

        foreach ($refused as $name => $context) {
            try {
                embed::register(self::FILEID, $context);
                $this->fail("A $name context must not hold an authorisation, even for an admin");
            } catch (\moodle_exception $e) {
                $this->assertSame('cannotembedhere', $e->errorcode);
            }
            $this->assertFalse(embed::may_host($context), "may_host should refuse a $name context");
        }
    }

    /**
     * A course authorisation reaches down, an activity authorisation does not reach up.
     *
     * The direction is the whole point and it is silent when wrong: walking the
     * context path from the BINDING rather than from the REQUEST would let a
     * single forum's authorisation cover the entire course and every sibling
     * activity, and every other test here would still pass.
     */
    public function test_a_course_authorisation_reaches_down_but_not_up(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $one = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $two = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $onecontext = \context_module::instance($one->cmid);
        $twocontext = \context_module::instance($two->cmid);

        // Authorised for the course: plays in the course and in every activity.
        embed::grant(self::FILEID, $coursecontext);
        $this->assertTrue(embed::is_authorised(self::FILEID, $coursecontext));
        $this->assertTrue(embed::is_authorised(self::FILEID, $onecontext));
        $this->assertTrue(embed::is_authorised(self::FILEID, $twocontext));

        // Authorised for ONE activity: plays there and nowhere else.
        $other = $this->getDataGenerator()->create_course();
        $othercourse = \context_course::instance($other->id);
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $other->id]);
        $activitycontext = \context_module::instance($activity->cmid);

        embed::grant(self::FILEID, $activitycontext);
        $this->assertTrue(embed::is_authorised(self::FILEID, $activitycontext));
        $this->assertFalse(
            embed::is_authorised(self::FILEID, $othercourse),
            'An activity authorisation must not reach up to its course'
        );
    }

    /**
     * An authorisation is scoped to one course.
     */
    public function test_one_course_does_not_authorise_another(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $a = $this->getDataGenerator()->create_course();
        $b = $this->getDataGenerator()->create_course();

        embed::grant(self::FILEID, \context_course::instance($a->id));

        $this->assertTrue(embed::is_authorised(self::FILEID, \context_course::instance($a->id)));
        $this->assertFalse(embed::is_authorised(self::FILEID, \context_course::instance($b->id)));
    }

    /**
     * A teacher may authorise, and doing it twice is a no-op.
     *
     * Idempotency is what the unique index is for: update_instance() and the
     * picker both re-authorise routinely, and duplicates would grow the table
     * without bound.
     */
    public function test_a_teacher_may_authorise_and_it_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $coursecontext = \context_course::instance($course->id);

        embed::register(self::FILEID, $coursecontext);
        embed::register(self::FILEID, $coursecontext);
        embed::register(strtoupper(self::FILEID), $coursecontext);

        $this->assertSame(1, $DB->count_records(embed::TABLE, ['contextid' => $coursecontext->id]));
        $this->assertTrue(embed::is_authorised(self::FILEID, $coursecontext));
    }

    /**
     * Creating an activity authorises its video for THAT ACTIVITY, not the course.
     *
     * This pins a real hole. A playback request names the context it is asking
     * from, and Moodle checks only as deeply as that name reaches: name an
     * activity and it verifies the activity is visible to the viewer — hidden,
     * availability dates, groups — but name a course and it verifies enrolment and
     * nothing more, because no activity was named. So a course-level row would be
     * matched by a request naming the course, and every restriction on the activity
     * holding the video would go unchecked. An earlier version did exactly that
     * while its comments claimed otherwise.
     */
    public function test_creating_an_activity_authorises_only_that_activity(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $activity = $this->getDataGenerator()->create_module('bunkercast', [
            'course' => $course->id,
            'fileid' => self::FILEID,
        ]);

        // It plays where it was put.
        $this->assertTrue(embed::is_authorised(self::FILEID, \context_module::instance($activity->cmid)));

        // A request naming the COURSE gets nothing — the bypass this closes.
        $this->assertFalse(embed::is_authorised(self::FILEID, $coursecontext));

        // And it does not leak sideways to another activity in the same course.
        $other = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->assertFalse(embed::is_authorised(self::FILEID, \context_module::instance($other->cmid)));
    }

    /**
     * grant() skips the filter capability on purpose, and that is worth pinning.
     *
     * Its callers — creating an activity, and restore — have already had Moodle
     * enforce mod/bunkercast:addinstance or moodle/restore:*, and failing there on
     * a filter capability would block a save or abort a restore. The consequence is
     * that the effective right to authorise a video is browselibrary OR addinstance
     * OR the restore capabilities, not browselibrary alone. If that ever stops being
     * deliberate, this test should fail and force the decision to be retaken.
     */
    public function test_grant_deliberately_skips_the_filter_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modulecontext = \context_module::instance($activity->cmid);

        // A plain student, who cannot browse the library anywhere.
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $this->assertFalse(has_capability('filter/bunkercast:browselibrary', $modulecontext));

        // Refused by register(), which does check the capability...
        try {
            embed::register(self::FILEID, $modulecontext);
            $this->fail('register() must require the capability');
        } catch (\required_capability_exception $e) {
            $this->assertFalse(embed::is_authorised(self::FILEID, $modulecontext));
        }

        // ...while grant() does not check it. Nothing a student can reach calls
        // grant() directly; this documents the asymmetry rather than blessing it.
        embed::grant(self::FILEID, $modulecontext);
        $this->assertTrue(embed::is_authorised(self::FILEID, $modulecontext));
    }

    /**
     * An authorisation can be withdrawn, and withdrawing needs the capability.
     *
     * Granting without a way to withdraw is half a control: the authorisation is
     * what permits playback, so there has to be a way to take it back.
     */
    public function test_an_authorisation_can_be_withdrawn(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $this->setUser($teacher);
        embed::register(self::FILEID, $coursecontext);
        $this->assertTrue(embed::is_authorised(self::FILEID, $coursecontext));

        // A student may not undo it.
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        try {
            embed::revoke(self::FILEID, $coursecontext);
            $this->fail('A student must not be able to withdraw an authorisation');
        } catch (\required_capability_exception $e) {
            $this->assertTrue(embed::is_authorised(self::FILEID, $coursecontext));
        }

        // The teacher may, and it stops playing everywhere in the course.
        $this->setUser($teacher);
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        embed::revoke(self::FILEID, $coursecontext);

        $this->assertFalse(embed::is_authorised(self::FILEID, $coursecontext));
        $this->assertFalse(embed::is_authorised(self::FILEID, \context_module::instance($activity->cmid)));

        // Withdrawing something that was never authorised is not an error.
        embed::revoke(self::FILEID, $coursecontext);
    }

    /**
     * Deleting an activity or a course takes its authorisations with it.
     *
     * Two different mechanisms, because Moodle deletes them differently: an
     * activity deleted on its own fires course_module_deleted, while a course
     * deletion removes each module's context directly without firing it, so the
     * course observer has to sweep by absence instead.
     */
    public function test_authorisations_do_not_outlive_their_context(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modulecontext = \context_module::instance($activity->cmid);

        embed::grant(self::FILEID, $coursecontext);
        embed::grant(self::FILEID, $modulecontext);
        $this->assertSame(2, $DB->count_records(embed::TABLE));

        // Deleting the activity takes its row, and leaves the course's alone.
        course_delete_module($activity->cmid);
        $this->assertSame(1, $DB->count_records(embed::TABLE));
        $this->assertSame(1, $DB->count_records(embed::TABLE, ['contextid' => $coursecontext->id]));

        // Deleting the course takes the rest.
        delete_course($course, false);
        $this->assertSame(0, $DB->count_records(embed::TABLE));
    }

    /**
     * A malformed file id is refused wherever it arrives.
     */
    public function test_a_malformed_fileid_is_refused(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        foreach (['', 'not-a-uuid', '36bb40db1a39480f81484346a76388fd', '../../etc/passwd'] as $bad) {
            try {
                embed::grant($bad, $coursecontext);
                $this->fail("'$bad' must not be accepted as a file id");
            } catch (\invalid_parameter_exception $e) {
                $this->assertStringContainsString('uuid', $e->getMessage());
            }
        }
    }
}
