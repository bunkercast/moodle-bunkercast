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
 * Language strings for mod_bunkercast.
 *
 * NOTE the filename: activity modules use lang/en/<modname>.php WITHOUT the
 * mod_ prefix, unlike every other plugin type (filter_bunkercast.php,
 * tiny_bunkercast.php). Core does the same — mod/quiz/lang/en/quiz.php. Getting
 * it wrong makes Moodle refuse to install the plugin as "defective or
 * outdated: Missing mandatory en language pack", which does not hint at the
 * filename. Strings are still fetched with get_string('x', 'mod_bunkercast').
 *
 * @package    mod_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bunkercast video';
$string['modulename'] = 'Bunkercast video';
$string['modulenameplural'] = 'Bunkercast videos';
$string['modulename_help'] = 'Adds a DRM-protected video from your Bunkercast library. Access is granted per viewing, by this Moodle site — a student who loses access to the course cannot watch, and there is no link to revoke.';
$string['pluginadministration'] = 'Bunkercast video settings';

$string['bunkercastname'] = 'Name';
$string['video'] = 'Video';
$string['video_help'] = 'Only encoded videos appear here. Upload and encode in Bunkercast first, then reload this form.';
$string['novideos'] = 'No encoded videos were found in your Bunkercast account. Upload and encode one, then reload this form.';
$string['listfailed'] = 'Could not reach Bunkercast to list your videos. A site administrator should check the API key in the Bunkercast filter settings.';
$string['nobunkercasts'] = 'There are no Bunkercast videos in this course.';

$string['bunkercast:addinstance'] = 'Add a new Bunkercast video';
$string['bunkercast:view'] = 'View a Bunkercast video';

$string['privacy:metadata'] = 'The Bunkercast video activity stores which video an activity shows. Playback credentials are issued by the Bunkercast filter, which declares its own data transmission.';
