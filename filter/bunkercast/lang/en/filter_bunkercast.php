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
 * Language strings for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bunkercast DRM video';
$string['filtername'] = 'Bunkercast DRM video';

// Settings.
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Created in Bunkercast under Account → Settings. It is shown only once, so store it here immediately. This key stays on the Moodle server and is never sent to a browser — anyone holding it can create playback links for your whole library.';

$string['playerbase'] = 'Player base URL';
$string['playerbase_desc'] = 'Leave as the default unless Bunkercast have given you a different endpoint.';

$string['ttlsec'] = 'Playback link lifetime (seconds)';
$string['ttlsec_desc'] = 'How long each minted playback link stays valid, between 60 and 14400 (4 hours). A link is minted per viewer per video and cached for its lifetime, so a shorter value means more requests to Bunkercast and a longer one means a copied link keeps working for longer. One hour suits a typical lesson.';

$string['hostlock'] = 'Restrict playback to this site';
$string['hostlock_desc'] = 'Ask Bunkercast to refuse playback unless the player is loaded from this Moodle site. This is stronger, but it relies on the browser sending a referrer — <strong>the Moodle mobile app usually does not</strong>, so enabling this can stop app users watching. Leave it off unless you have tested the app.';

$string['planhint'] = 'Choosing a plan';
$string['planhint_desc'] = 'Pay-as-you-go is billed per minute watched, which is the cheapest way to evaluate this plugin. For real classroom use the Professional plan is normally the right fit: a lecture watched by a whole class consumes minutes quickly (30 students × 45 minutes is 1,350 minutes), and Professional replaces per-minute billing with a flat monthly fee. Check your balance and plan in Bunkercast before a lesson — this plugin cannot top up your account.';

// Cache.
$string['cachedef_playbackurl'] = 'Minted playback URLs per viewer';

// Player.
$string['loading'] = 'Loading video…';
$string['unavailable'] = 'This video is currently unavailable.';
$string['notconfigured'] = 'Bunkercast is not configured. A site administrator needs to add an API key.';

// Capability + picker.
$string['bunkercast:browselibrary'] = 'Browse the Bunkercast video library';
$string['listfailed'] = 'Could not reach Bunkercast to list your videos. Check the API key in the filter settings.';

// Privacy.
$string['privacy:metadata'] = 'The Bunkercast DRM video filter sends an opaque reference to the viewing user to Bunkercast so that a playback link can be issued for that person alone. No name, email address or other personal detail is transmitted.';
$string['privacy:metadata:bunkercast'] = 'Data sent to Bunkercast in order to issue a playback link.';
$string['privacy:metadata:bunkercast:viewerref'] = 'An opaque identifier for the viewing user, used to attribute the playback and to identify a leaked link. It is not a name or an email address.';
$string['privacy:metadata:bunkercast:fileid'] = 'The identifier of the video being requested.';
