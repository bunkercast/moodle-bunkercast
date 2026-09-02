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
 * Entry point. Must resolve with [pluginName, Configuration].
 *
 * @module     tiny_bunkercast/plugin
 * @copyright  2026 Bunkercast
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getTinyMCE} from 'editor_tiny/loader';
import {getPluginMetadata} from 'editor_tiny/utils';
import common from './common';
import {getSetup as getCommandSetup} from './commands';
import * as Configuration from './configuration';

const {component, pluginName} = common;

// Deliberately not `new Promise(async (resolve) => ...)`, which is what the
// Moodle developer docs show: its own ESLint config rejects that with
// no-async-promise-executor, and the rule is right — a rejection inside an async
// executor is swallowed rather than propagated. Promise.all().then() satisfies
// the same editor_tiny contract and surfaces failures.
export default Promise.all([
    getTinyMCE(),
    getPluginMetadata(component, pluginName),
    getCommandSetup(),
]).then(([tinyMCE, pluginMetadata, setupCommands]) => {
    tinyMCE.PluginManager.add(pluginName, (editor) => {
        setupCommands(editor);
        return pluginMetadata;
    });

    return [pluginName, Configuration];
});
