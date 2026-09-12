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
 * Bunkercast DRM video filter for Moodle.
 *
 * Turns a [bunkercast:<file-id>] placeholder in any Moodle content into a
 * DRM-protected player, with the playback credential minted per viewer at
 * request time rather than stored in the content.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'filter_bunkercast';
$plugin->version   = 2026090200;

// Moodle 4.5. That is where filter classes moved to classes/text_filter.php;
// on 4.1-4.4 the class must live in filter.php instead. Supporting those needs
// a class_alias shim in the old location — see README.
$plugin->requires  = 2024100700;

$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '0.1.0';
