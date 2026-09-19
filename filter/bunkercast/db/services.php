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
 * Web service declarations for filter_bunkercast.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'filter_bunkercast_get_playback_url' => [
        'classname'     => 'filter_bunkercast\external\get_playback_url',
        'description'   => 'Mint a short-lived Bunkercast playback URL for the current user and one video.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'filter_bunkercast_get_videos' => [
        'classname'     => 'filter_bunkercast\external\get_videos',
        'description'   => 'List the Bunkercast library for the authoring picker.',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'filter/bunkercast:browselibrary',
    ],

    // Leaving loginrequired at its default true is what makes
    // external_api::call_external_function() require a sesskey, so this write is
    // CSRF-protected without doing anything here. Do not set it to false.
    'filter_bunkercast_register_embed' => [
        'classname'     => 'filter_bunkercast\external\register_embed',
        'description'   => 'Authorise a Bunkercast video for use in a course or activity.',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
        'capabilities'  => 'filter/bunkercast:browselibrary',
    ],
];
