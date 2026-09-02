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
 * Plugin info for tiny_bunkercast.
 *
 * @package    tiny_bunkercast
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tiny_bunkercast;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_menuitems;

/**
 * Registers the toolbar button and menu item.
 *
 * The context id is deliberately not supplied here: editor_tiny/options already
 * exports getContextId(editor) to every plugin, so a plugin_with_configuration
 * implementation returning it was redundant.
 */
class plugininfo extends plugin implements
    plugin_with_buttons,
    plugin_with_menuitems {
    /**
     * Names the toolbar button this plugin provides.
     *
     * @return string[]
     */
    public static function get_available_buttons(): array {
        return ['tiny_bunkercast/bunkercast'];
    }

    /**
     * Names the menu item this plugin provides.
     *
     * @return string[]
     */
    public static function get_available_menuitems(): array {
        return ['tiny_bunkercast/bunkercast'];
    }

    /**
     * Hide the button entirely unless it would work.
     *
     * Two reasons it might not: the site has no API key configured, or this
     * user cannot browse the library. Showing a button that always errors is
     * worse than showing none.
     */
    public static function is_enabled(
        context $context,
        array $options,
        array $fpoptions,
        ?\editor_tiny\editor $editor = null
    ): bool {
        if (trim((string)get_config('filter_bunkercast', 'apikey')) === '') {
            return false;
        }

        return has_capability('filter/bunkercast:browselibrary', $context);
    }
}
