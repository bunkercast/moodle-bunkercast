<?php
// Plugin info for tiny_bunkercast.

namespace tiny_bunkercast;

use context;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_menuitems;

defined('MOODLE_INTERNAL') || die();

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

    public static function get_available_buttons(): array {
        return ['tiny_bunkercast/bunkercast'];
    }

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
