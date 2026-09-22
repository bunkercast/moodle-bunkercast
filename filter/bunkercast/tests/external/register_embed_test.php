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
 * Tests for the authorise web service.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast\external;

use filter_bunkercast\embed;

/**
 * Tests for register_embed — the only web service that writes.
 *
 * The editor picker calls this as it inserts a video, so it is the route most
 * authorisations are created by. Everything that decides whether a call is allowed
 * lives in embed::register(); these tests check that this class actually reaches
 * it, in the right order, and that the service is declared so Moodle will accept
 * the call at all.
 *
 * @covers \filter_bunkercast\external\register_embed
 */
final class register_embed_test extends \advanced_testcase {
    /** @var string A well-formed Bunkercast file id. */
    const FILEID = '36bb40db-1a39-480f-8148-4346a76388fd';

    /**
     * A teacher authorises a video, and doing it twice changes nothing.
     */
    public function test_a_teacher_may_authorise(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $this->assertSame(['status' => true], register_embed::execute(self::FILEID, $context->id));
        $this->assertTrue(embed::is_authorised(self::FILEID, $context));

        register_embed::execute(self::FILEID, $context->id);
        $this->assertSame(1, $DB->count_records(embed::TABLE));
    }

    /**
     * A student may not, even in a course they are enrolled in.
     *
     * The capability is the whole gate: without it, anyone who can reach a context
     * could authorise any video in the account and spend the site's balance.
     */
    public function test_a_student_may_not_authorise(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        try {
            register_embed::execute(self::FILEID, \context_course::instance($course->id)->id);
            $this->fail('A student must not be able to authorise a video');
        } catch (\required_capability_exception $e) {
            $this->assertSame(0, $DB->count_records(embed::TABLE));
        }
    }

    /**
     * Someone who cannot reach the context is stopped before anything else.
     *
     * validate_context() runs first, so an outsider never reaches the capability
     * check — and never learns whether the video was authorisable there.
     */
    public function test_an_outsider_is_refused(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $outsider = $this->getDataGenerator()->create_user();
        $this->setUser($outsider);

        try {
            register_embed::execute(self::FILEID, \context_course::instance($course->id)->id);
            $this->fail('A user who cannot reach the context must be refused');
        } catch (\moodle_exception $e) {
            // Refusal comes from require_login(), so match on the class name rather
            // than an errorcode — core has moved these between namespaces.
            $this->assertStringContainsString('require_login', get_class($e));
        }
    }

    /**
     * Nothing above a course may hold an authorisation, not even for an admin.
     *
     * Run as a site admin deliberately. Moodle does not enforce a capability's
     * declared contextlevel — has_capability() never reads it — so without the
     * explicit rule an admin could authorise at the system context and make the
     * video playable everywhere on the site.
     */
    public function test_nothing_above_a_course_may_be_authorised(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();
        $user = $this->getDataGenerator()->create_user();

        $refused = [
            'system'    => \context_system::instance(),
            'category'  => \context_coursecat::instance($category->id),
            'user'      => \context_user::instance($user->id),
            'frontpage' => \context_course::instance(SITEID),
        ];

        foreach ($refused as $name => $context) {
            try {
                register_embed::execute(self::FILEID, $context->id);
                $this->fail("A $name context must not hold an authorisation, even for an admin");
            } catch (\moodle_exception $e) {
                $this->assertSame('cannotembedhere', $e->errorcode);
            }
        }

        $this->assertSame(0, $DB->count_records(embed::TABLE));
    }

    /**
     * An activity context is accepted, and stays scoped to that activity.
     *
     * This is what the picker does when a video is inserted while editing an
     * existing activity, so it is the common case rather than an edge one.
     */
    public function test_an_activity_context_is_accepted_and_stays_scoped(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $modulecontext = \context_module::instance($activity->cmid);

        register_embed::execute(self::FILEID, $modulecontext->id);

        $this->assertTrue(embed::is_authorised(self::FILEID, $modulecontext));
        $this->assertFalse(embed::is_authorised(self::FILEID, \context_course::instance($course->id)));
    }

    /**
     * A file id that is not a uuid is rejected.
     *
     * PARAM_ALPHANUMEXT permits hyphens but knows nothing of uuid shape, so the
     * explicit check is what stops a malformed id being stored.
     */
    public function test_a_malformed_fileid_is_rejected(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $contextid = \context_course::instance($course->id)->id;

        $this->expectException(\invalid_parameter_exception::class);
        register_embed::execute('not-a-uuid', $contextid);
    }

    /**
     * The service is declared so Moodle will accept the call and demand a sesskey.
     *
     * Cheap, and it catches two silent misconfigurations that no other test would:
     * without ajax the picker's call dies as servicenotavailable, and turning off
     * loginrequired would stop external_api requiring a sesskey, removing the CSRF
     * protection this write relies on.
     */
    public function test_the_service_is_declared_for_ajax_and_requires_login(): void {
        $this->resetAfterTest();

        $info = \core_external\external_api::external_function_info('filter_bunkercast_register_embed');

        $this->assertTrue((bool) $info->allowed_from_ajax);
        $this->assertTrue((bool) $info->loginrequired);
    }
}
