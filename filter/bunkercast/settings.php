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
 * Admin settings for filter_bunkercast.
 *
 * Moodle builds the settings page for a filter automatically when this file
 * exists, so $settings is already available here.
 *
 * @package    filter_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configpasswordunmask(
        'filter_bunkercast/apikey',
        get_string('apikey', 'filter_bunkercast'),
        get_string('apikey_desc', 'filter_bunkercast'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'filter_bunkercast/playerbase',
        get_string('playerbase', 'filter_bunkercast'),
        get_string('playerbase_desc', 'filter_bunkercast'),
        'https://player.bunkercast.com',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'filter_bunkercast/ttlsec',
        get_string('ttlsec', 'filter_bunkercast'),
        get_string('ttlsec_desc', 'filter_bunkercast'),
        3600,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'filter_bunkercast/hostlock',
        get_string('hostlock', 'filter_bunkercast'),
        get_string('hostlock_desc', 'filter_bunkercast'),
        0
    ));

    // Not a setting — guidance, because the commonest failure is an account
    // that runs out of minutes mid-lesson, which this plugin cannot detect.
    $settings->add(new admin_setting_description(
        'filter_bunkercast/planhint',
        get_string('planhint', 'filter_bunkercast'),
        get_string('planhint_desc', 'filter_bunkercast')
    ));
}
