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
 * TinyMCE picker for the Bunkercast DRM video filter.
 *
 * Authoring convenience only: it inserts the same [bunkercast:<uuid>]
 * placeholder a teacher could type by hand. All playback and access-control
 * logic lives in filter_bunkercast, which this plugin depends on.
 *
 * @package    tiny_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'tiny_bunkercast';
$plugin->version   = 2026090200;
$plugin->requires  = 2024100700;   // Moodle 4.5.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';

// The picker calls filter_bunkercast's web service and inserts its placeholder,
// so it is useless on its own.
$plugin->dependencies = [
    'filter_bunkercast' => 2026090200,
];
