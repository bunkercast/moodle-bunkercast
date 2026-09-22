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
 * Where a Bunkercast video is authorised to play.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_bunkercast;

/**
 * The authorisation record: which videos may play in which places.
 *
 * A playback request carries a file id AND a context, both chosen by the browser.
 * Checking only that the caller may reach the context proves nothing about the
 * video — for the system context it reduces to require_login(), so any logged-in
 * user could mint a link for any video in the account. This class is the other
 * half of that check: the video must also have been authorised for that place by
 * somebody who could publish there.
 *
 * Rows are written by a deliberate act only — the authorise page, the editor
 * picker, or creating a Bunkercast activity. NEVER as a side effect of rendering.
 * Filters run over text that ordinary users author (forum posts, blog entries,
 * profile descriptions), so a render-time write would let any student authorise
 * any video simply by typing a placeholder and looking at it.
 */
class embed {
    /** @var string Table holding the authorisations. */
    const TABLE = 'filter_bunkercast_embed';

    /**
     * The course a context belongs to, or null if it cannot hold an authorisation.
     *
     * This is the ONE place the rule lives, and every level check calls it.
     * `get_course_context(false)` returns the course context for course, module and
     * block contexts, and false for system, category and user contexts — so those
     * can never match an authorisation, which is what stops the blog-entry and
     * profile-description routes. The front-page course is excluded too: it is
     * reachable by every authenticated user with no enrolment, so an authorisation
     * there would be site-wide in all but name.
     *
     * @param \context $context
     * @return \context|null
     */
    public static function course_context_for(\context $context): ?\context {
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext || $coursecontext->instanceid == SITEID) {
            return null;
        }
        return $coursecontext;
    }

    /**
     * Whether an authorisation may be stored against this context at all.
     *
     * Course and activity contexts only. A block sitting in a course can PLAY a
     * video authorised for that course (see is_authorised) but is not somewhere an
     * authorisation is stored, which keeps the stored set small and predictable.
     *
     * @param \context $context
     * @return bool
     */
    public static function may_host(\context $context): bool {
        if ($context->contextlevel !== CONTEXT_COURSE && $context->contextlevel !== CONTEXT_MODULE) {
            return false;
        }
        return self::course_context_for($context) !== null;
    }

    /**
     * Authorise a video for use in a course or activity.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param \context $context Course or activity context.
     * @return void
     * @throws \invalid_parameter_exception If the file id is not a uuid.
     * @throws \moodle_exception If this context cannot hold an authorisation.
     * @throws \required_capability_exception If the user may not publish here.
     */
    public static function register(string $fileid, \context $context): void {
        if (!self::may_host($context)) {
            throw new \moodle_exception('cannotembedhere', 'filter_bunkercast');
        }
        require_capability('filter/bunkercast:browselibrary', $context);

        self::grant($fileid, $context);
    }

    /**
     * Store an authorisation WITHOUT checking a capability.
     *
     * For callers where Moodle has already enforced its own, stricter permission
     * and a capability failure here would be the wrong failure: creating a
     * Bunkercast activity (mod/bunkercast:addinstance) and restoring or importing
     * a course (moodle/restore:*). A restoring user legitimately may not hold
     * filter/bunkercast:browselibrary, and throwing half way through a restore is
     * far worse than authorising a video whose activity is being restored anyway.
     *
     * The consequence is worth stating plainly: the effective set of people who
     * can authorise a video is browselibrary OR addinstance OR the restore
     * capabilities, not browselibrary alone.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param \context $context Course or activity context.
     * @return void
     * @throws \invalid_parameter_exception If the file id is not a uuid.
     * @throws \moodle_exception If this context cannot hold an authorisation.
     */
    public static function grant(string $fileid, \context $context): void {
        global $DB;

        $fileid = self::clean_fileid($fileid);

        if (!self::may_host($context)) {
            throw new \moodle_exception('cannotembedhere', 'filter_bunkercast');
        }

        $key = ['contextid' => $context->id, 'fileid' => $fileid];
        if ($DB->record_exists(self::TABLE, $key)) {
            return;
        }

        try {
            $DB->insert_record(self::TABLE, (object) ($key + ['timecreated' => time()]));
        } catch (\dml_exception $e) {
            // A concurrent request may have won the unique index between the check
            // and the insert. Re-check rather than swallow: if the row still is not
            // there the failure was something else (disk full, lost connection) and
            // must surface.
            if (!$DB->record_exists(self::TABLE, $key)) {
                throw $e;
            }
        }
    }

    /**
     * Withdraw an authorisation.
     *
     * Destructive in a way granting is not: anything in the course already using
     * the video stops playing, and says so. Deliberately does NOT check may_host()
     * — a row that should not be there is exactly the one worth being able to
     * delete — but does require the same capability as granting, so the ability to
     * authorise and the ability to withdraw stay together.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param \context $context The context the authorisation is stored against.
     * @return void
     * @throws \invalid_parameter_exception If the file id is not a uuid.
     * @throws \required_capability_exception If the user may not publish here.
     */
    public static function revoke(string $fileid, \context $context): void {
        global $DB;

        $fileid = self::clean_fileid($fileid);
        require_capability('filter/bunkercast:browselibrary', $context);

        $DB->delete_records(self::TABLE, ['contextid' => $context->id, 'fileid' => $fileid]);
    }

    /**
     * Whether a video may play in this context.
     *
     * Matches an authorisation stored against the context itself, or against the
     * course it belongs to — so authorising once for a course covers every
     * activity and block inside it, which is what makes the editor picker usable
     * when a new activity is authored in the course context but rendered in the
     * module context.
     *
     * The converse deliberately does NOT hold: an authorisation stored against one
     * activity does not reach the course or a sibling activity.
     *
     * @param string $fileid Bunkercast file id (uuid).
     * @param \context $context Context the placeholder was rendered in.
     * @return bool
     */
    public static function is_authorised(string $fileid, \context $context): bool {
        global $DB;

        $fileid = self::clean_fileid($fileid);

        $candidates = [$context->id];
        if ($coursecontext = self::course_context_for($context)) {
            $candidates[] = $coursecontext->id;
        }

        [$insql, $params] = $DB->get_in_or_equal(array_unique($candidates), SQL_PARAMS_NAMED, 'ctx');
        $params['fileid'] = $fileid;

        return $DB->record_exists_select(self::TABLE, "fileid = :fileid AND contextid $insql", $params);
    }

    /**
     * Lower-case a file id and reject anything that is not a uuid.
     *
     * PARAM_ALPHANUMEXT permits hyphens but not a uuid shape, so the external
     * parameter type alone is not enough.
     *
     * @param string $fileid
     * @return string
     * @throws \invalid_parameter_exception
     */
    public static function clean_fileid(string $fileid): string {
        $fileid = strtolower($fileid);
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $fileid)) {
            throw new \invalid_parameter_exception('fileid is not a uuid');
        }
        return $fileid;
    }
}
